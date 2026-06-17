<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Support\ContactLinks;
use Inertia\Inertia;
use Inertia\Response;

class ContactsController extends Controller
{
    public function __invoke(): Response
    {
        $siteSettings = SiteSetting::current();

        return Inertia::render('Contacts', [
            'contacts' => array_merge(ContactLinks::fromSiteSetting($siteSettings), [
                'address' => $siteSettings->contact_address,
                'schedule' => $siteSettings->contact_schedule,
            ]),
        ]);
    }
}
