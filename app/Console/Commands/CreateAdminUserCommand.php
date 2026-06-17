<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateAdminUserCommand extends Command
{
    protected $signature = 'admin:create
                            {email=admin@ecocample.ru : Email администратора}
                            {--name=Администратор : Имя пользователя}
                            {--password= : Пароль (если не указан — сгенерируется автоматически)}
                            {--force : Обновить существующего пользователя и выдать права администратора}';

    protected $description = 'Создать или обновить учётную запись администратора Filament';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $name = trim((string) $this->option('name'));
        $password = $this->option('password') ?: Str::password(16);
        $force = (bool) $this->option('force');

        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser && ! $force) {
            $this->error("Пользователь {$email} уже существует. Добавьте --force, чтобы обновить пароль и права.");

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_admin' => true,
            ],
        );

        $this->components->info('Администратор готов.');
        $this->line("Email: {$user->email}");
        $this->line("Пароль: {$password}");
        $this->line('Вход: /admin/login');

        return self::SUCCESS;
    }
}
