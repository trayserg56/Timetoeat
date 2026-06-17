<?php

namespace Tests\Feature\Auth;

use App\Mail\Auth\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset_code_by_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $this->from('/news')->post('/forgot-password', [
            'email' => $user->email,
        ])->assertRedirect('/news');

        $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();

        $this->assertNotNull($record);

        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use ($user, $record): bool {
            return $mail->hasTo($user->email)
                && Hash::check($mail->code, $record->token)
                && strlen($mail->code) === 6;
        });
    }

    public function test_user_can_reset_password_with_valid_code_and_is_logged_in(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'old-password',
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $this->from('/news')->post('/reset-password', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect('/news');

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_expired_code_cannot_be_used_to_reset_password(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'old-password',
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('123456'),
            'created_at' => now()->subMinutes(61),
        ]);

        $this->from('/news')->post('/reset-password', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect('/news')
            ->assertSessionHasErrorsIn('resetPassword', ['code']);

        $this->assertGuest();
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }
}
