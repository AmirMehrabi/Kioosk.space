const gallery = document.querySelector('[data-business-gallery]');

if (gallery) {
    const photos = [];
    const image = gallery.querySelector('[data-gallery-image]');
    const thumbnails = gallery.querySelector('[data-gallery-thumbnails]');
    const status = gallery.querySelector('[data-gallery-status]');
    const more = gallery.querySelector('[data-gallery-more]');
    const previous = gallery.querySelector('[data-gallery-prev]');
    const next = gallery.querySelector('[data-gallery-next]');
    let nextUrl = gallery.dataset.galleryUrl;
    let selected = 0;
    let busy = false;
    let loaded = false;
    let trigger;
    let previousOverflow;

    function select(index) {
        if (!photos[index]) return;
        selected = index;
        image.src = photos[index].url;
        image.alt = photos[index].alt;
        image.hidden = false;
        thumbnails.querySelectorAll('button').forEach((button, position) => button.setAttribute('aria-pressed', String(position === index)));
        gallery.querySelector('[data-gallery-position]').textContent = `${(index + 1).toLocaleString('fa-IR')} / ${photos.length.toLocaleString('fa-IR')}`;
        gallery.querySelector('[data-gallery-navigation]').hidden = photos.length < 2;
        previous.disabled = index === 0;
        next.disabled = index === photos.length - 1;
    }

    async function load() {
        if (busy || !nextUrl) return;
        busy = true;
        more.disabled = true;
        status.textContent = 'در حال دریافت تصاویر…';
        try {
            const response = await fetch(nextUrl, {headers: {Accept: 'application/json'}, signal: AbortSignal.timeout(15000)});
            if (!response.ok) throw new Error('Unable to load gallery');
            const data = await response.json();
            if (!Array.isArray(data.photos)) throw new Error('Invalid gallery');
            for (const photo of data.photos) {
                if (photos.some(existing => existing.id === photo.id)) continue;
                const index = photos.length;
                photos.push(photo);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'gallery-thumbnail';
                button.setAttribute('aria-label', `نمایش عکس ${(index + 1).toLocaleString('fa-IR')}`);
                const thumbnail = document.createElement('img');
                thumbnail.src = photo.thumbnail;
                thumbnail.alt = '';
                thumbnail.loading = 'lazy';
                thumbnail.className = 'size-full object-cover';
                button.append(thumbnail);
                button.addEventListener('click', () => select(index));
                thumbnails.append(button);
            }
            loaded = true;
            nextUrl = data.next;
            more.hidden = !nextUrl;
            more.textContent = 'تصاویر بیشتر';
            status.textContent = '';
            gallery.querySelector('[data-gallery-count]').textContent = `${Number(data.total).toLocaleString('fa-IR')} تصویر`;
            gallery.querySelector('[data-gallery-empty]').hidden = photos.length !== 0;
            select(selected);
        } catch {
            status.textContent = 'تصاویر دریافت نشد. دوباره تلاش کنید.';
            more.textContent = 'تلاش دوباره';
            more.hidden = false;
        } finally {
            busy = false;
            more.disabled = false;
        }
    }

    document.querySelectorAll('[data-open-business-gallery]').forEach(link => link.addEventListener('click', event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        trigger = link;
        previousOverflow = document.documentElement.style.overflow;
        document.documentElement.style.overflow = 'hidden';
        gallery.showModal();
        if (!loaded) load();
    }));
    gallery.querySelector('[data-gallery-close]').addEventListener('click', () => gallery.close());
    gallery.addEventListener('click', event => { if (event.target === gallery) gallery.close(); });
    gallery.addEventListener('close', () => {
        document.documentElement.style.overflow = previousOverflow;
        trigger?.focus({preventScroll: true});
    });
    previous.addEventListener('click', () => select(selected - 1));
    next.addEventListener('click', () => select(selected + 1));
    more.addEventListener('click', load);
    gallery.addEventListener('keydown', event => {
        if (event.key === 'ArrowLeft') { event.preventDefault(); select(selected + 1); }
        if (event.key === 'ArrowRight') { event.preventDefault(); select(selected - 1); }
    });
}
