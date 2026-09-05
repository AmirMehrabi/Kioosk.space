import Alpine from 'alpinejs';

const text = (value, max = 2000) => typeof value === 'string' ? value.slice(0, max) : '';
const safePhoto = photo => photo && typeof photo.src === 'string' && /^data:image\/(jpeg|png|webp);base64,[A-Za-z0-9+/=]+$/.test(photo.src) && photo.src.length < 500000;
const identifier = () => globalThis.crypto?.randomUUID?.() || `local-${Date.now()}-${Math.random().toString(36).slice(2)}`;

export function businessPage(data, viewer) {
    return {
        reviews: structuredClone(data.reviews), saved: false, helpful: {}, reported: {}, expandedComments: {}, commentDrafts: {}, commentErrors: {},
        search: '', sort: 'helpful', ratingFilter: 0, photosOnly: false, visibleCount: 4, toast: '', toastTimer: null,
        activePhotos: data.gallery, photoIndex: 0, editingId: null, draft: { rating: 0, title: '', body: '', visit: 'با دوستان', photos: [] }, formError: '', uploading: false,
        action: '', actionId: null, reportReason: 'محتوای نامناسب',
        get storageKey() { return `kioosk-business-demo-v1:${data.slug}:${viewer.id}`; },
        init() {
            try {
                const stored = JSON.parse(localStorage.getItem(this.storageKey) || 'null');
                if (stored && typeof stored === 'object') {
                    this.saved = stored.saved === true;
                    this.helpful = Object.fromEntries(Object.entries(stored.helpful || {}).filter(([id, value]) => typeof id === 'string' && value === true).slice(0, 100));
                    this.reported = Object.fromEntries(Object.entries(stored.reported || {}).filter(([id, value]) => typeof id === 'string' && typeof value === 'string').slice(0, 100));
                    const own = Array.isArray(stored.own) ? stored.own.slice(0, 20).filter(review => review && /^local-/.test(review.id) && Number.isInteger(review.rating) && review.rating >= 1 && review.rating <= 5).map(review => ({
                        id: text(review.id, 80), author: viewer.name, initials: viewer.name[0], reviewerCount: 1, own: true,
                        rating: review.rating, title: text(review.title, 100), body: text(review.body), date: Number.isNaN(Date.parse(review.date)) ? new Date().toISOString() : review.date,
                        visit: text(review.visit, 30), helpful: 0, ownerReply: '', comments: [], photos: Array.isArray(review.photos) ? review.photos.filter(safePhoto).slice(0, 3).map(photo => ({ src: photo.src, caption: 'عکس همراه نظر شما' })) : [],
                    })) : [];
                    this.reviews = [...own, ...this.reviews];
                    for (const review of this.reviews) {
                        const comments = stored.comments?.[review.id];
                        if (Array.isArray(comments)) review.comments.push(...comments.slice(0, 30).filter(comment => comment && typeof comment.body === 'string').map(comment => ({ id: 'local-' + identifier(), author: viewer.name, body: text(comment.body, 500), date: text(comment.date, 40), own: true })));
                    }
                }
            } catch { this.notify('ذخیره‌سازی مرورگر در دسترس نیست؛ تغییرات فعلاً فقط در این صفحه می‌مانند.'); }
        },
        persist() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify({ saved: this.saved, helpful: this.helpful, reported: this.reported, own: this.reviews.filter(review => review.own), comments: Object.fromEntries(this.reviews.map(review => [review.id, review.comments.filter(comment => comment.own)])) }));
                return true;
            } catch { return false; }
        },
        changed(message) { this.notify(this.persist() ? message : 'تغییر در صفحه انجام شد، اما فضای ذخیره‌سازی مرورگر کافی نیست و با بازخوانی از دست می‌رود.'); },
        fa(value) { return new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 1 }).format(value); },
        date(value) { const date = new Date(value); return Number.isNaN(date.getTime()) ? '' : new Intl.DateTimeFormat('fa-IR', { day: 'numeric', month: 'long', year: 'numeric' }).format(date); },
        get average() { return this.reviews.length ? Math.round(this.reviews.reduce((sum, review) => sum + review.rating, 0) / this.reviews.length * 10) / 10 : 0; },
        ratingCount(rating) { return this.reviews.filter(review => review.rating === rating).length; },
        get filteredReviews() {
            const normalize = value => value.replace(/ي/g, 'ی').replace(/ك/g, 'ک').replace(/\u200c/g, '').trim().toLowerCase();
            const query = normalize(this.search);
            return this.reviews.filter(review => (!this.ratingFilter || review.rating === this.ratingFilter) && (!this.photosOnly || review.photos.length) && normalize(review.body + ' ' + review.title + ' ' + review.author).includes(query)).slice().sort((a, b) => {
                if (this.sort === 'highest') return b.rating - a.rating || Date.parse(b.date) - Date.parse(a.date);
                if (this.sort === 'lowest') return a.rating - b.rating || Date.parse(b.date) - Date.parse(a.date);
                if (this.sort === 'newest') return Date.parse(b.date) - Date.parse(a.date);
                return (b.helpful + Number(!!this.helpful[b.id])) - (a.helpful + Number(!!this.helpful[a.id]));
            });
        },
        setRating(rating) { this.ratingFilter = this.ratingFilter === rating ? 0 : rating; this.visibleCount = 4; },
        resetFilters() { this.search = ''; this.ratingFilter = 0; this.photosOnly = false; this.visibleCount = 4; },
        notify(message) { this.toast = message; clearTimeout(this.toastTimer); this.toastTimer = setTimeout(() => { this.toast = ''; }, 5000); },
        toggleSaved() { this.saved = !this.saved; this.changed(this.saved ? 'به مکان‌های ذخیره‌شده در این مرورگر اضافه شد.' : 'از ذخیره‌های این مرورگر حذف شد.'); },
        toggleHelpful(id) { this.helpful[id] = !this.helpful[id]; this.changed('رأی شما در نسخه نمایشی به‌روز شد.'); },
        toggleComments(id) { this.expandedComments[id] = !this.expandedComments[id]; },
        replyTo(id, author) { this.commentDrafts[id] = `${author}، `; this.$nextTick(() => document.getElementById('comment-input-' + id)?.focus()); },
        addComment(id) {
            const review = this.reviews.find(review => review.id === id);
            const body = (this.commentDrafts[id] || '').trim();
            if (!review || body.length < 3 || body.length > 500) { this.commentErrors[id] = 'پاسخ باید بین ۳ تا ۵۰۰ نویسه باشد.'; return; }
            if (review.comments.filter(comment => comment.own).length >= 30) { this.commentErrors[id] = 'حداکثر پاسخ‌های نمایشی این نظر ثبت شده است.'; return; }
            review.comments.push({ id: 'local-' + identifier(), author: viewer.name, body, date: new Date().toISOString(), own: true });
            this.commentDrafts[id] = ''; this.commentErrors[id] = ''; this.changed('پاسخ شما در همین مرورگر ذخیره شد.');
        },
        deleteComment(id, commentId) { const review = this.reviews.find(review => review.id === id); if (!review) return; review.comments = review.comments.filter(comment => comment.id !== commentId || !comment.own); this.changed('پاسخ نمایشی شما حذف شد.'); },
        openDialog(dialog) { dialog.showModal(); document.documentElement.classList.add('overflow-hidden'); },
        dialogClosed() { document.documentElement.classList.remove('overflow-hidden'); },
        openGallery(index) { this.openPhotos(data.gallery, index); },
        openPhotos(photos, index) { if (!photos.length) return; this.activePhotos = photos; this.photoIndex = index; this.openDialog(this.$refs.galleryDialog); },
        movePhoto(direction) { this.photoIndex = (this.photoIndex + direction + this.activePhotos.length) % this.activePhotos.length; },
        galleryKey(event) { if (!this.$refs.galleryDialog?.open) return; if (event.key === 'ArrowLeft') { event.preventDefault(); this.movePhoto(1); } if (event.key === 'ArrowRight') { event.preventDefault(); this.movePhoto(-1); } },
        openReview(review = null) {
            if (review && !review.own) return;
            this.editingId = review?.id || null; this.formError = '';
            this.draft = { rating: review?.rating || 0, title: review?.title || '', body: review?.body || '', visit: review?.visit || 'با دوستان', photos: review ? review.photos.map(photo => ({ ...photo })) : [] };
            this.openDialog(this.$refs.reviewDialog);
        },
        async addPhotos(event) {
            if (this.uploading) return;
            const files = [...event.target.files]; event.target.value = ''; this.formError = ''; this.uploading = true;
            try {
                for (const file of files) {
                    if (this.draft.photos.length >= 3) throw new Error('می‌توانید حداکثر ۳ عکس اضافه کنید.');
                    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) throw new Error('عکس باید JPG، PNG یا WebP و کوچک‌تر از ۵ مگابایت باشد.');
                    const url = URL.createObjectURL(file);
                    try {
                        const img = new Image(); img.src = url; await img.decode();
                        const canvas = document.createElement('canvas'); const scale = Math.min(1, 800 / Math.max(img.width, img.height));
                        canvas.width = Math.max(1, Math.round(img.width * scale)); canvas.height = Math.max(1, Math.round(img.height * scale));
                        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                        const src = canvas.toDataURL('image/jpeg', 0.7);
                        if (!safePhoto({ src })) throw new Error('حجم عکس پس از آماده‌سازی زیاد است. عکس کوچک‌تری انتخاب کنید.');
                        this.draft.photos.push({ src, caption: 'عکس همراه نظر شما' });
                    } finally { URL.revokeObjectURL(url); }
                }
            } catch (error) { this.formError = error instanceof DOMException ? 'خواندن عکس ممکن نشد. فایل تصویری دیگری انتخاب کنید.' : (error.message || 'خواندن عکس ممکن نشد.'); } finally { this.uploading = false; }
        },
        submitReview() {
            if (this.uploading) return;
            const rating = Number(this.draft.rating), title = this.draft.title.trim(), body = this.draft.body.trim();
            if (!Number.isInteger(rating) || rating < 1 || rating > 5 || !title || title.length > 100 || body.length < 20 || body.length > 2000) { this.formError = 'امتیاز، عنوان و دست‌کم ۲۰ نویسه درباره تجربه‌تان وارد کنید.'; return; }
            if (!this.editingId && this.reviews.filter(review => review.own).length >= 20) { this.formError = 'به سقف نظرهای نمایشی رسیده‌اید. می‌توانید نظرهای قبلی را ویرایش کنید.'; return; }
            const fields = { rating, title, body, visit: this.draft.visit, photos: this.draft.photos.map(photo => ({ ...photo })) };
            if (this.editingId) { const review = this.reviews.find(review => review.id === this.editingId && review.own); if (!review) return; Object.assign(review, fields); }
            else this.reviews.unshift({ ...fields, id: 'local-' + identifier(), author: viewer.name, initials: viewer.name[0], reviewerCount: 1, date: new Date().toISOString(), helpful: 0, ownerReply: '', comments: [], own: true });
            this.resetFilters(); this.sort = 'newest'; this.$refs.reviewDialog.close(); this.changed('نظر شما در همین مرورگر ذخیره شد.'); this.$nextTick(() => document.getElementById('reviews').scrollIntoView());
        },
        askDelete(id) { this.action = 'delete'; this.actionId = id; this.openDialog(this.$refs.actionDialog); },
        openReport(id) { this.action = 'report'; this.actionId = id; this.openDialog(this.$refs.actionDialog); },
        confirmAction() { if (this.action === 'delete') this.reviews = this.reviews.filter(review => review.id !== this.actionId || !review.own); else this.reported[this.actionId] = this.reportReason; this.$refs.actionDialog.close(); this.changed(this.action === 'delete' ? 'نظر نمایشی شما حذف شد.' : 'گزارش نمایشی ثبت شد و برای کسب‌وکار ارسال نمی‌شود.'); },
        async share() { try { if (navigator.share) await navigator.share({ title: data.name, url: location.href }); else { await navigator.clipboard.writeText(location.href); this.notify('پیوند صفحه کپی شد.'); } } catch (error) { if (error.name !== 'AbortError') this.notify('برای اشتراک‌گذاری، نشانی این صفحه را از نوار مرورگر کپی کنید.'); } },
    };
}

Alpine.data('businessPage', businessPage);
if (document.querySelector('[x-data^="businessPage"]')) Alpine.start();
