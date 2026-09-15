<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('blog.index', ['posts' => [$this->contributionGuide()]]);
    }

    public function show(string $slug): View
    {
        abort_unless($slug === 'contribution-guide', 404);

        $post = $this->contributionGuide();

        return view('blog.show', [
            'post' => $post,
            'content' => Str::markdown(File::get(base_path('contribution_guide.md')), [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ]);
    }

    /**
     * @return array{slug: string, title: string, eyebrow: string, excerpt: string, published_at: string, reading_time: string}
     */
    private function contributionGuide(): array
    {
        return [
            'slug' => 'contribution-guide',
            'title' => 'چطور چیزی به کیوسک اضافه کنیم که واقعاً به درد بخورد؟',
            'eyebrow' => 'راهنمای مشارکت',
            'excerpt' => 'قاعده‌ها پیچیده نیستند: چیزی را ثبت کنید که خودتان دوست داشتید پیش از رفتن به یک مکان بدانید؛ دقیق، واقعی و بی‌اغراق.',
            'published_at' => '۲۴ شهریور ۱۴۰۵',
            'reading_time' => '۶ دقیقه',
        ];
    }
}
