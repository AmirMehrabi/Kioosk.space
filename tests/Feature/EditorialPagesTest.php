<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EditorialPagesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_blog_index_renders_the_first_post(): void
    {
        $response = $this->get(route('blog.index'));

        $response->assertOk()
            ->assertSee('یادداشت‌های کیوسک')
            ->assertSee('چطور چیزی به کیوسک اضافه کنیم که واقعاً به درد بخورد؟')
            ->assertSee(route('blog.show', 'contribution-guide'), false);
    }

    public function test_contribution_guide_renders_as_a_blog_post(): void
    {
        $response = $this->get(route('blog.show', 'contribution-guide'));

        $response->assertOk()
            ->assertSee('اصل ساده: چیزی بنویسید که به تصمیم بعدی کمک کند')
            ->assertSee('یک تجربهٔ واقعی، از ده نظر کوتاه مفیدتر است.')
            ->assertSee('BlogPosting', false);
    }

    public function test_unknown_blog_post_returns_not_found(): void
    {
        $this->get(route('blog.show', 'unknown-post'))->assertNotFound();
    }

    public function test_about_page_renders_the_project_story(): void
    {
        $response = $this->get(route('about'));

        $response->assertOk()
            ->assertSee('یک پروژهٔ شخصی برای یک سؤال روزمره')
            ->assertSee('کیوسک چیست؟')
            ->assertSee(route('blog.show', 'contribution-guide'), false);
    }

    public function test_shared_footer_links_to_editorial_pages(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee(route('blog.index'), false)
            ->assertSee(route('blog.show', 'contribution-guide'), false)
            ->assertSee(route('about'), false);
    }
}
