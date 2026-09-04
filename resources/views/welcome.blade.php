<!DOCTYPE html><html lang="fa" dir="rtl" class="scroll-smooth motion-reduce:scroll-auto"><head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="کیوسک؛ راه ساده پیدا کردن مکان‌های خوب شهر بر اساس نظر مردم">
  <title>کیوسک — کشف بهترین‌های شهر</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
  
  <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
  
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
  <div class="min-h-screen bg-canvas font-sans text-ink antialiased selection:bg-pomegranate selection:text-white">
    <header class="h-[72px] border-b border-border bg-surface">
      <div class="mx-auto flex h-full max-w-[1240px] items-center justify-between px-4 md:px-6">
        <a id="header-brand-link" href="#top" class="inline-flex min-h-11 items-center rounded-xl text-[28px] font-extrabold tracking-[-0.05em] text-ink outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2" aria-label="صفحه اصلی کیوسک">کیوسک<span class="mr-1 text-pomegranate">.</span></a>
        <nav class="hidden items-center gap-8 text-[15px] font-medium text-secondary md:flex" aria-label="راهبری اصلی">
          <a id="nav-discover-link" href="#places" class="flex min-h-11 items-center transition-colors duration-200 hover:text-pomegranate focus-visible:rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2">کشف مکان‌ها</a>
          <a data-demo-action id="nav-review-link" href="#community" class="flex min-h-11 items-center transition-colors duration-200 hover:text-pomegranate focus-visible:rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2">نوشتن نظر</a>
          <a data-demo-action id="nav-business-link" href="#footer" class="flex min-h-11 items-center transition-colors duration-200 hover:text-pomegranate focus-visible:rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2">برای کسب‌وکارها</a>
        </nav>
        <div class="hidden items-center gap-3 md:flex">
          <a data-demo-action id="header-login-link" href="#footer" class="inline-flex min-h-11 items-center justify-center px-3 text-sm font-semibold text-secondary transition-colors hover:text-ink focus-visible:rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2">ورود</a>
          <a data-demo-action id="header-signup-link" href="#footer" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-border bg-surface px-5 text-sm font-bold text-ink transition-all duration-200 hover:border-muted hover:shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2">ثبت‌نام</a>
        </div>
        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-border bg-surface text-ink md:hidden" id="menu-toggle" aria-controls="mobile-menu" aria-expanded="false" aria-label="باز کردن فهرست">
          <iconify-icon icon="lucide:menu" class="text-[22px]" aria-hidden="true"></iconify-icon>
        </button>
      </div>
    </header>
    <nav id="mobile-menu" hidden aria-label="فهرست موبایل" class="border-b border-border bg-surface px-4 py-3 md:hidden">
      <a id="mobile-discover" href="#places" class="block rounded-xl p-3 hover:bg-soft">کشف مکان‌ها</a>
      <a id="mobile-reviews" href="#community" class="block rounded-xl p-3 hover:bg-soft">نظرهای مردم</a>
    </nav>

    <main id="top">
      <section class="border-b border-border bg-canvas">
        <div class="mx-auto max-w-[1240px] px-4 py-12 md:px-6 md:py-12 lg:py-12">
          <div class="max-w-[900px]">
            <p class="mb-3 text-sm font-bold text-pomegranate">پیشنهادهای خوب، نزدیک شما</p>
            <h1 class="text-[36px] font-extrabold leading-[1.35] tracking-[-0.035em] text-ink md:text-[48px]">کجا بریم؟</h1>
            <p class="mt-3 max-w-[650px] text-[16px] leading-8 text-secondary md:text-[17px]">رستوران، کافه، فروشگاه یا هر جای دیگری را با کمک تجربه واقعی آدم‌های شهر پیدا کنید.</p>
          </div>

          <form class="mt-8 grid max-w-[1080px] grid-cols-1 gap-3 rounded-2xl border border-border bg-surface p-3 shadow-soft md:grid-cols-[1.35fr_1fr_132px] md:gap-0" action="#places" method="get" role="search">
            <label class="flex min-h-[56px] items-center gap-3 rounded-xl px-4 focus-within:ring-2 focus-within:ring-pomegranate focus-within:ring-offset-2 md:rounded-l-none md:border-l md:border-border">
              <iconify-icon icon="lucide:search" class="shrink-0 text-xl text-muted" aria-hidden="true"></iconify-icon>
              <span class="sr-only">نام کسب‌وکار یا دسته‌بندی</span>
              <input name="query" type="search" class="min-w-0 w-full bg-transparent text-[15px] text-ink outline-none placeholder:text-muted" placeholder="دنبال چه می‌گردید؟ مثلاً کافه یا پزشک">
            </label>
            <label class="flex min-h-[56px] items-center gap-3 rounded-xl px-4 focus-within:ring-2 focus-within:ring-pomegranate focus-within:ring-offset-2 md:rounded-none">
              <iconify-icon icon="lucide:map-pin" class="shrink-0 text-xl text-muted" aria-hidden="true"></iconify-icon>
              <span class="sr-only">محله در تهران</span>
              <input name="location" type="search" class="min-w-0 w-full bg-transparent text-[15px] text-ink outline-none placeholder:text-muted" value="تهران" placeholder="شهر یا محله">
            </label>
            <button type="submit" class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-xl bg-pomegranate px-5 font-bold text-white transition-colors duration-200 hover:bg-pomegranate-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2 md:w-[132px] md:px-5" aria-label="جست‌وجو">
              <iconify-icon icon="lucide:search" class="text-xl" aria-hidden="true"></iconify-icon>
              <span class="inline">جست‌وجو</span>
            </button>
          </form>

          <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted">
            <span class="font-semibold text-secondary">جست‌وجوهای محبوب:</span>
            <a id="popular-breakfast-link" href="#places" class="min-h-11 py-3 transition-colors hover:text-pomegranate focus-visible:rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate">صبحانه</a>
            <a id="popular-cafe-link" href="#places" class="min-h-11 py-3 transition-colors hover:text-pomegranate focus-visible:rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate">کافه دنج</a>
            <a id="popular-iranian-link" href="#places" class="min-h-11 py-3 transition-colors hover:text-pomegranate focus-visible:rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate">غذای ایرانی</a>
            <a id="popular-dentist-link" href="#places" class="min-h-11 py-3 transition-colors hover:text-pomegranate focus-visible:rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate">دندان‌پزشک</a>
          </div>
        </div>
      </section>

      <section class="mx-auto max-w-[1240px] px-4 py-6 md:px-6 md:py-6" aria-labelledby="categories-title">
        <div class="sr-only">
          <div>
            <h2 id="categories-title" class="text-[25px] font-bold tracking-[-0.02em] md:text-[28px]">چه چیزی لازم دارید؟</h2>
            <p class="mt-2 text-[15px] text-muted">از دسته‌های پرطرفدار شروع کنید.</p>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
          <a id="category-restaurant-link" href="#places" class="group flex min-h-[76px] flex-col sm:flex-row items-center justify-center gap-3 rounded-[18px] border border-border bg-surface p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-muted hover:shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2"><span class="flex h-12 w-12 items-center justify-center rounded-xl bg-soft text-secondary group-hover:text-pomegranate"><iconify-icon icon="lucide:utensils" class="text-2xl" aria-hidden="true"></iconify-icon></span><span class="font-bold">رستوران</span></a>
          <a id="category-cafe-link" href="#places" class="group flex min-h-[76px] flex-col sm:flex-row items-center justify-center gap-3 rounded-[18px] border border-border bg-surface p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-muted hover:shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2"><span class="flex h-12 w-12 items-center justify-center rounded-xl bg-soft text-secondary group-hover:text-pomegranate"><iconify-icon icon="lucide:coffee" class="text-2xl" aria-hidden="true"></iconify-icon></span><span class="font-bold">کافه</span></a>
          <a id="category-shopping-link" href="#places" class="group flex min-h-[76px] flex-col sm:flex-row items-center justify-center gap-3 rounded-[18px] border border-border bg-surface p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-muted hover:shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2"><span class="flex h-12 w-12 items-center justify-center rounded-xl bg-soft text-secondary group-hover:text-pomegranate"><iconify-icon icon="lucide:shopping-bag" class="text-2xl" aria-hidden="true"></iconify-icon></span><span class="font-bold">خرید</span></a>
          <a id="category-doctor-link" href="#places" class="group flex min-h-[76px] flex-col sm:flex-row items-center justify-center gap-3 rounded-[18px] border border-border bg-surface p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-muted hover:shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2"><span class="flex h-12 w-12 items-center justify-center rounded-xl bg-soft text-secondary group-hover:text-pomegranate"><iconify-icon icon="lucide:stethoscope" class="text-2xl" aria-hidden="true"></iconify-icon></span><span class="font-bold">پزشک</span></a>
          <a id="category-beauty-link" href="#places" class="group flex min-h-[76px] flex-col sm:flex-row items-center justify-center gap-3 rounded-[18px] border border-border bg-surface p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-muted hover:shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2"><span class="flex h-12 w-12 items-center justify-center rounded-xl bg-soft text-secondary group-hover:text-pomegranate"><iconify-icon icon="lucide:sparkles" class="text-2xl" aria-hidden="true"></iconify-icon></span><span class="font-bold">زیبایی</span></a>
          <a id="category-home-link" href="#places" class="group flex min-h-[76px] flex-col sm:flex-row items-center justify-center gap-3 rounded-[18px] border border-border bg-surface p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-muted hover:shadow-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2"><span class="flex h-12 w-12 items-center justify-center rounded-xl bg-soft text-secondary group-hover:text-pomegranate"><iconify-icon icon="lucide:house" class="text-2xl" aria-hidden="true"></iconify-icon></span><span class="font-bold">خدمات منزل</span></a>
        </div>
      </section>

      <section id="places" class="border-y border-border bg-surface py-8 md:py-10" aria-labelledby="places-title">
        <div class="mx-auto max-w-[1240px] px-4 md:px-6">
          <div class="mb-8 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div><h2 id="places-title" class="text-[25px] font-bold tracking-[-0.02em] md:text-[28px]">این دوروبر چه خبره؟</h2><p class="mt-2 text-[15px] text-muted">چند انتخاب خوش‌امتیاز در تهران؛ اطلاعات این بخش نمونه است.</p></div>
            <a id="all-places-link" href="#places" class="inline-flex min-h-11 items-center gap-2 self-start text-sm font-bold text-pomegranate hover:text-pomegranate-dark focus-visible:rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate focus-visible:ring-offset-2">دیدن همه مکان‌ها<iconify-icon icon="lucide:arrow-left" aria-hidden="true"></iconify-icon></a>
          </div>

          <p id="no-results" hidden role="status" class="py-8 text-secondary">مکانی پیدا نشد. عبارت دیگری را امتحان کنید یا همه مکان‌ها را ببینید.</p><div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <article class="overflow-hidden rounded-2xl border border-border bg-surface transition-all duration-200 hover:-translate-y-0.5 hover:border-muted hover:shadow-soft">
              <img src="https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&amp;fit=crop&amp;w=900&amp;q=80" alt="فضای گرم و آرام یک کافه" width="900" height="600" class="aspect-[3/2] w-full object-cover">
              <div class="p-5">
                <div class="flex items-start justify-between gap-4"><div><h3 class="text-xl font-bold"><a id="venue-rira-link" href="#community" class="rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate">کافه ری‌را</a></h3><p class="mt-1 text-sm text-muted">کافه · ولیعصر · متوسط</p></div><span class="shrink-0 text-sm font-bold text-positive">باز است</span></div>
                <div class="mt-3 flex items-center gap-2" aria-label="امتیاز چهار و هشت از پنج"><strong class="text-sm">۴٫۸</strong><span class="flex gap-0.5 text-pomegranate"><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span></span><span class="text-xs text-muted">۱۲۸ نظر</span></div>
                <p class="mt-4 border-t border-border pt-4 text-sm leading-7 text-secondary">«قهوه دقیق و خوش‌طعم بود؛ حیاط کوچک کافه هم آرامش خاصی داشت.»</p>
              </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-border bg-surface transition-all duration-200 hover:-translate-y-0.5 hover:border-muted hover:shadow-soft">
              <img src="https://images.unsplash.com/photo-1569058242253-92a9c755a0ec?auto=format&amp;fit=crop&amp;w=900&amp;q=80" alt="میز غذای ایرانی در رستوران" width="900" height="600" class="aspect-[3/2] w-full object-cover">
              <div class="p-5">
                <div class="flex items-start justify-between gap-4"><div><h3 class="text-xl font-bold"><a id="venue-gilaneh-link" href="#community" class="rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate">رستوران گیلانه</a></h3><p class="mt-1 text-sm text-muted">غذای ایرانی · جردن · گران</p></div><span class="shrink-0 text-sm font-bold text-positive">باز است</span></div>
                <div class="mt-3 flex items-center gap-2" aria-label="امتیاز چهار و شش از پنج"><strong class="text-sm">۴٫۶</strong><span class="flex gap-0.5 text-pomegranate"><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-border"><span class="absolute inset-y-0 left-0 w-1/2 bg-pomegranate"></span><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span></span><span class="text-xs text-muted">۳۴۲ نظر</span></div>
                <p class="mt-4 border-t border-border pt-4 text-sm leading-7 text-secondary">«طعم‌های گیلانی اصیل و سرویس منظم؛ میرزا قاسمی واقعاً به‌یادماندنی بود.»</p>
              </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-border bg-surface transition-all duration-200 hover:-translate-y-0.5 hover:border-muted hover:shadow-soft ">
              <img src="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&amp;fit=crop&amp;w=900&amp;q=80" alt="نان‌های تازه در نانوایی" width="900" height="600" class="aspect-[3/2] w-full object-cover">
              <div class="p-5">
                <div class="flex items-start justify-between gap-4"><div><h3 class="text-xl font-bold"><a id="venue-sahar-link" href="#community" class="rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pomegranate">نانوایی سحر</a></h3><p class="mt-1 text-sm text-muted">نانوایی · یوسف‌آباد · اقتصادی</p></div><span class="shrink-0 text-sm font-bold text-positive">باز است</span></div>
                <div class="mt-3 flex items-center gap-2" aria-label="امتیاز چهار و نه از پنج"><strong class="text-sm">۴٫۹</strong><span class="flex gap-0.5 text-pomegranate"><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span></span><span class="text-xs text-muted">۸۷ نظر</span></div>
                <p class="mt-4 border-t border-border pt-4 text-sm leading-7 text-secondary">«نان‌ها همیشه تازه‌اند و برخورد کارکنان صمیمی است؛ کروسان بادام عالی بود.»</p>
              </div>
            </article>
          </div>
        </div>
      </section>

      <section id="community" class="mx-auto max-w-[1240px] px-4 py-12 md:px-6 md:py-16" aria-labelledby="community-title">
        <div class="mb-7"><h2 id="community-title" class="text-[24px] font-bold tracking-[-0.02em] md:text-[27px]">تازه از زبان مردم</h2><p class="mt-2 text-[15px] text-muted">آدم‌ها از تجربه‌هایشان می‌گویند.</p></div>
        <div class="grid grid-cols-1 overflow-hidden rounded-2xl border border-border bg-surface md:grid-cols-2 md:divide-x md:divide-x-reverse md:divide-border">
          <article class="flex gap-4 p-5 md:p-6"><div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-soft font-bold text-secondary" aria-hidden="true">م</div><div><div class="flex flex-wrap items-center gap-2"><strong class="text-sm">مریم حیدری</strong><span class="text-xs text-muted">درباره کافه ری‌را</span></div><div class="mt-1 flex gap-0.5 text-pomegranate" aria-label="پنج ستاره"><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span></div><p class="mt-2 text-sm leading-7 text-secondary">برای یک گفت‌وگوی عصرانه جای آرام و خوش‌برخوردی بود.</p></div></article>
          <article class="flex gap-4 border-t border-border p-5 md:border-t-0 md:p-6"><div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-soft font-bold text-secondary" aria-hidden="true">آ</div><div><div class="flex flex-wrap items-center gap-2"><strong class="text-sm">آرش بهنام</strong><span class="text-xs text-muted">درباره نانوایی سحر</span></div><div class="mt-1 flex gap-0.5 text-pomegranate" aria-label="پنج ستاره"><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span><span aria-hidden="true" class="relative inline-flex size-[22px] shrink-0 items-center justify-center overflow-hidden rounded-[5px] bg-pomegranate"><svg viewBox="0 0 24 24" fill="currentColor" class="relative size-[19px] text-white"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.32l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94Z"/></svg></span></div><p class="mt-2 text-sm leading-7 text-secondary">صبح زود تنوع نان‌ها بیشتر است و همه‌چیز تازه از فر بیرون می‌آید.</p></div></article>
        </div>
      </section>

      
    </main>

    <div id="demo-notice" role="status" hidden class="fixed bottom-6 inset-x-4 z-50 mx-auto max-w-md rounded-2xl border border-border bg-ink p-4 text-center text-sm leading-7 text-white shadow-soft">این نسخه نمایشی است؛ ثبت‌نام و نوشتن نظر به‌زودی فعال می‌شود.</div>
    <footer id="footer" class="border-t border-border bg-surface">
      <div class="mx-auto grid max-w-[1240px] grid-cols-1 gap-10 px-4 py-10 md:grid-cols-[1.4fr_1fr_1fr_1fr] md:px-6 md:py-12">
        <div><div class="text-[25px] font-extrabold tracking-[-0.05em]">کیوسک<span class="mr-1 text-pomegranate">.</span></div><p class="mt-3 max-w-[290px] text-sm leading-7 text-muted">راه ساده پیدا کردن جای خوب، با کمک تجربه مردم شهر.</p></div>
        <div><h3 class="text-sm font-bold">کیوسک</h3><div class="mt-3 flex flex-col text-sm text-muted"><a id="footer-about-link" href="#top" class="min-h-11 py-2 hover:text-ink">درباره ما</a><a id="footer-guidelines-link" href="#community" class="min-h-11 py-2 hover:text-ink">راهنمای نوشتن نظر</a></div></div>
        <div><h3 class="text-sm font-bold">همراهی</h3><div class="mt-3 flex flex-col text-sm text-muted"><a data-demo-action id="footer-business-link" href="#footer" class="min-h-11 py-2 hover:text-ink">برای کسب‌وکارها</a><a id="footer-contact-link" href="#footer" class="min-h-11 py-2 hover:text-ink">تماس با ما</a></div></div>
        <div><h3 class="text-sm font-bold">شهر</h3><button type="button" data-city-select class="mt-3 inline-flex min-h-11 w-full items-center justify-between rounded-xl border border-border bg-surface px-4 text-sm text-secondary"><span class="flex items-center gap-2"><iconify-icon icon="lucide:map-pin" aria-hidden="true"></iconify-icon>تهران</span><iconify-icon icon="lucide:chevron-down" aria-hidden="true"></iconify-icon></button></div>
      </div>
      <div class="border-t border-border"><div class="mx-auto flex max-w-[1240px] flex-col gap-2 px-4 py-5 text-xs text-muted sm:flex-row sm:items-center sm:justify-between md:px-6"><p>کیوسک؛ شهر از نگاه شما</p><p>اطلاعات و نظرات این صفحه نمونه هستند.</p></div></div>
    </footer>
  </div>

</body></html>