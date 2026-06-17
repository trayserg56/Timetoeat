<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Inertia\Inertia;
use Inertia\Response;

class ContactsController extends Controller
{
    public function __invoke(): Response
    {
        $siteSettings = SiteSetting::current();

        return Inertia::render('Contacts', [
            'contacts' => [
                'phone' => $siteSettings->contact_phone,
                'email' => $siteSettings->contact_email,
                'telegram' => $siteSettings->contact_telegram,
                'address' => $siteSettings->contact_address,
                'schedule' => $siteSettings->contact_schedule,
            ],
        ]);
    }
}
