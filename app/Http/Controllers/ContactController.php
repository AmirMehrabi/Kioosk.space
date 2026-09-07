<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('contact', [
            'contactEmail' => 'info@sabz.co.ir',
            'contactPhone' => '۰۳۴-۹۱۰۹-۷۹۵۳',
            'contactAddress' => 'کرمان، میدان قرنی، ساختمان پدر، واحد ۳۰۲',
            'mapUrl' => 'https://www.google.com/maps?q='.urlencode('کرمان، میدان قرنی، ساختمان پدر، واحد ۳۰۲').'&output=embed',
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $contact = $request->validated();

        Mail::to('info@sabz.co.ir')->send(new ContactMessageMail($contact));

        return to_route('contact')->with('status', 'پیام شما دریافت شد. به‌زودی پاسخ می‌دهیم.');
    }
}
