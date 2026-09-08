import './auth';
import './navigation';
import './city-select';
import './contribute';
import './business-management';
import './home-feed';
import './business-gallery';

if (document.querySelector('#discovery, [data-location-picker]')) {
    import('./maps').catch(() => {
        document.querySelectorAll('[data-map-status], [data-location-status]').forEach(status => {
            status.textContent = 'نقشه بارگذاری نشد. صفحه را تازه‌سازی کنید؛ فهرست و ورود دستی مختصات در دسترس است.';
        });
    });
}

document.querySelectorAll('[data-discard-draft]').forEach(button => button.addEventListener('click', async () => {
    button.disabled = true;
    try {
        const response = await fetch(`/contribution-drafts/${button.dataset.discardDraft}`, {method:'DELETE', headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}});
        if (!response.ok) throw new Error();
        button.parentElement.remove();
    } catch { button.disabled=false; button.textContent='حذف انجام نشد؛ تلاش دوباره'; }
}));

// Staff select a destination by its public name and address.
document.querySelectorAll('[data-merge-search]').forEach(container => {
    const button = container.querySelector('[data-merge-find]');
    button.addEventListener('click', async () => {
        button.disabled = true;
        const status = container.querySelector('[data-merge-status]');
        const results = container.querySelector('[data-merge-results]');
        status.textContent = 'در حال جست‌وجو…';
        try {
            const query = container.querySelector('[data-merge-query]').value.trim();
            const response = await fetch(`/businesses/search?${new URLSearchParams({query})}`, {headers:{Accept:'application/json'}});
            if (!response.ok) throw new Error();
            const data = await response.json();
            results.replaceChildren(new Option('مکان اصلی را انتخاب کنید', ''));
            data.data.forEach(business => results.add(new Option(`${business.name} · ${business.city}، ${business.address}`, business.id)));
            status.textContent = data.next_page_url ? 'برای نتایج دقیق‌تر نام کامل‌تری بنویسید.' : data.data.length ? 'آدرس و شعبه را پیش از ادغام بررسی کنید.' : 'مکان تأییدشده‌ای پیدا نشد.';
        } catch { status.textContent = 'جست‌وجو انجام نشد؛ دوباره تلاش کنید.'; }
        finally { button.disabled = false; }
    });
});
