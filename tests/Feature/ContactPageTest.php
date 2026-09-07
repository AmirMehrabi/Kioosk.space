<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_contact_page_renders_with_the_business_details_and_footer_link(): void
    {
        $this->get('/')->assertOk()->assertSee(route('contact'), false)->assertSee('تماس با ما');

        $this->get('/contact')->assertOk()
            ->assertSee('info@sabz.co.ir')
            ->assertSee('۰۳۴-۹۱۰۹-۷۹۵۳')
            ->assertSee('کرمان، میدان قرنی، ساختمان پدر، واحد ۳۰۲')
            ->assertSee('ارسال پیام');
    }

    public function test_contact_form_sends_an_email_and_redirects_back_with_status(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => 'علی رضایی',
            'email' => 'ali@example.com',
            'subject' => 'مشکل در ورود',
            'message' => 'سلام، هنگام ورود به حساب کاربری با خطا مواجه می‌شوم و نیاز به راهنمایی دارم.',
        ])->assertRedirect(route('contact'))->assertSessionHas('status', 'پیام شما دریافت شد. به‌زودی پاسخ می‌دهیم.');

        Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail): bool {
            return $mail->hasTo('info@sabz.co.ir');
        });
    }

    public function test_contact_form_requires_core_fields(): void
    {
        $this->from('/contact')->post('/contact', [
            'name' => 'علی رضایی',
            'email' => 'not-an-email',
            'message' => 'کوتاه',
        ])->assertRedirect('/contact')->assertSessionHasErrors(['email', 'message']);
    }
}
