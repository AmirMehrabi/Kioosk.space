const feed = document.querySelector('[data-review-feed]');

if (feed) {
    const grid = feed.querySelector('#review-feed');
    const load = feed.querySelector('[data-load-reviews]');
    const status = feed.querySelector('[data-feed-status]');
    let busy = false;
    load?.addEventListener('click', async event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        if (busy) return;
        busy = true;
        load.setAttribute('aria-disabled', 'true');
        grid.setAttribute('aria-busy', 'true');
        const label = load.querySelector('[data-load-label]');
        label.textContent = 'در حال دریافت…';
        status.textContent = '';
        try {
            const response = await fetch(load.href, {headers: {Accept: 'application/json'}, signal: AbortSignal.timeout(15000)});
            if (!response.ok) throw new Error('Unable to load reviews');
            const data = await response.json();
            if (typeof data.html !== 'string' || !(data.next === null || typeof data.next === 'string')) throw new Error('Invalid response');
            const template = document.createElement('template');
            template.innerHTML = data.html;
            const existing = new Set([...grid.querySelectorAll('[data-review-id]')].map(card => card.dataset.reviewId));
            const cards = [...template.content.querySelectorAll('[data-review-id]')].filter(card => !existing.has(card.dataset.reviewId));
            grid.append(...cards);
            if (cards.length) {
                cards[0].setAttribute('tabindex', '-1');
                cards[0].focus({preventScroll: true});
            }
            if (data.next) load.href = `${data.next}#recent-reviews`;
            else load.hidden = true;
            status.textContent = data.next ? `${cards.length.toLocaleString('fa-IR')} تجربهٔ دیگر اضافه شد.` : 'همهٔ تجربه‌های تازه را دیدی.';
        } catch {
            status.textContent = 'دریافت تجربه‌ها انجام نشد. دوباره تلاش کن.';
        } finally {
            busy = false;
            grid.setAttribute('aria-busy', 'false');
            load.removeAttribute('aria-disabled');
            label.textContent = 'تجربه‌های بیشتر';
        }
    });

    const dialog = document.createElement('dialog');
    dialog.className = 'review-lightbox';
    dialog.setAttribute('aria-label', 'نمایش عکس تجربه');
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'review-lightbox-close';
    close.textContent = 'بستن ×';
    const image = document.createElement('img');
    dialog.append(close, image);
    document.body.append(dialog);
    let trigger;
    feed.addEventListener('click', event => {
        const photo = event.target.closest('[data-review-photo]');
        if (!photo || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        trigger = photo;
        image.src = photo.dataset.fullImage;
        image.alt = photo.querySelector('img').alt;
        dialog.showModal();
    });
    close.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => trigger?.focus({preventScroll: true}));
}
