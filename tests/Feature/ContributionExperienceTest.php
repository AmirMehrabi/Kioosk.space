<?php

namespace Tests\Feature;

use App\Models\Business;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ContributionExperienceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_first_visit_has_one_place_search_without_creating_a_draft(): void
    {
        $response = $this->get(route('contribute'));

        $response->assertOk()->assertSee('کجا رفتی؟')
            ->assertSee('id="search-name"', false)
            ->assertDontSee('id="navbar-query"', false)
            ->assertSee('در پایان شماره موبایلت را با یک کد پیامکی تأیید می‌کنی.')
            ->assertSee('فقط ثبت مکان؛ تجربه‌ای نمی‌نویسم')
            ->assertSee('بررسی و ثبت نهایی')
            ->assertSee('شماره موبایل عمومی نمی‌شود');
        $this->assertDatabaseCount('contribution_drafts', 0);
    }

    public function test_selected_place_form_keeps_photos_with_the_review_and_sign_in_with_final_confirmation(): void
    {
        $business = Business::factory()->create();

        $response = $this->get(route('contribute', ['business' => $business->id]));

        $response->assertOk()->assertViewHas('initial', fn (array $initial): bool => $initial['business_id'] === $business->id);
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new DOMXPath($document);
        $this->assertSame(1, $xpath->query('//section[@data-step="3"]//textarea[@name="body"]')->length);
        $this->assertSame(1, $xpath->query('//section[@data-step="3"]//input[@id="gallery-input"]')->length);
        $this->assertSame(1, $xpath->query('//section[@data-step="4"]//*[@id="otp-panel"]')->length);
        $this->assertSame(1, $xpath->query('//section[@data-step="4"]//*[@id="summary-place"]')->length);
        $this->assertSame(1, $xpath->query('//section[@data-step="4"]//button[@id="edit-review-summary"]')->length);
        $this->assertSame(5, $xpath->query('//input[@name="rating" and @aria-label]')->length);
        $this->assertSame(1, $xpath->query('//details//textarea[@name="description"]')->length);
    }

    public function test_form_provides_accessible_error_summary_and_optional_hours(): void
    {
        $response = $this->get(route('contribute'));

        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new DOMXPath($document);
        $this->assertSame(1, $xpath->query('//*[@id="contribution-error" and @role="alert" and @tabindex="-1"]')->length);
        $this->assertSame(1, $xpath->query('//button[@id="new-business" and not(@hidden)]')->length);
        $this->assertSame(1, $xpath->query('//input[@id="include-hours" and not(@checked)]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="hours-fields" and @hidden]')->length);
        $this->assertSame(1, $xpath->query('//input[@id="otp-mobile" and @aria-describedby="mobile-error"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="mobile-error" and @data-error="mobile"]')->length);
        $this->assertSame(1, $xpath->query('//input[@id="otp-code" and @aria-describedby="code-error"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="code-error" and @data-error="code"]')->length);
    }
}
