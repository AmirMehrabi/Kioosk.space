import { initCalendar } from './persian-date.js';

export function hasMeaningfulDraft(draft) {
    const p = draft.payload || {};
    return !!(draft.photos?.length || p.body?.trim() || p.rating || (!p.business_id && (['name', 'city', 'address', 'description', 'category_id'].some(key => String(p[key] || '').trim()) || p.phones?.length || p.websites?.length || Object.values(p.weekly_hours || {}).some(day => !day.closed || day.shifts?.length))));
}

const app = typeof document === 'undefined' ? null : document.getElementById('contribution-app');
if (app) startContribution().catch(error => { document.getElementById('contribution-error').hidden = false; document.getElementById('contribution-error').textContent = 'راه‌اندازی فرم انجام نشد. صفحه را دوباره باز کنید.'; });

async function startContribution() {
    const el = id => document.getElementById(id), form = el('contribution-form');
    const initial = JSON.parse(el('contribution-initial').textContent);
    initial.phones ??= initial.phone ? [{label:'اصلی', value:initial.phone}] : [];
    initial.websites ??= initial.website ? [{label:'وب‌سایت اصلی', url:initial.website}] : [];
    initial.weekly_hours ??= Object.fromEntries(['saturday','sunday','monday','tuesday','wednesday','thursday','friday'].map(day => [day,{closed:true,shifts:[]}]));
    initial.featured_photo_ids ??= [];
    let user = app.dataset.user || null, generic = app.dataset.generic === '1';
    let db, storageAvailable = true, localPersistenceEnabled = app.dataset.persistDraft === '1', saveTimer, serverTimer, version = 1, adopted = false, busy = false, uploadBusy = false, pendingSave = Promise.resolve();
    let state = { serverVersion: null, id: crypto.randomUUID(), payload: initial, photos: [], step: initial.business_id ? 3 : 1, owner: user, expires: Date.now() + 7 * 86400000 };
    let recovered = false, searchTimer, searchSequence = 0, searchController, geographySequence = 0;
    const params = new URLSearchParams(location.search);
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    function status(text) { el('draft-status').textContent = text; }
    function fail(error) {
        el('contribution-error').hidden = false; el('contribution-error').textContent = error.message || 'ارتباط برقرار نشد؛ پیش‌نویس محفوظ است. دوباره تلاش کنید.';
        let focusTarget;
        if (error.errors) Object.entries(error.errors).forEach(([key, messages]) => {
            const target = form.querySelector(`[data-error="${key.split('.')[0].replace(/[^a-z_]/g, '')}"]`);
            if (target) target.textContent = messages[0];
            const root = key.split('.')[0].replace(/[^a-z_]/g, '');
            const input = form.querySelector(`[name="${root}"]`) || target?.parentElement.querySelector('input, textarea, select, button');
            if (!focusTarget && input) {
                const section = input.closest('[data-step]');
                if (section) showStep(Number(section.dataset.step));
                for (let parent = input.parentElement; parent; parent = parent.parentElement) if (parent.tagName === 'DETAILS') parent.open = true;
                focusTarget = input;
            }
            if (root === 'mobile' || root === 'code') { showStep(3); el('otp-panel').hidden = false; focusTarget = el(root === 'mobile' ? 'otp-mobile' : 'otp-code'); }

        });
        if (error.status === 409 && user) { el('draft-conflict').hidden = false; el('reload-draft').href = `/contribute?draft=${encodeURIComponent(state.id)}`; }
        if (error.edit_url) { el('conflict').hidden = false; el('edit-existing').href = error.edit_url; }
        queueMicrotask(() => (focusTarget || el('contribution-error')).focus());
    }
    function clearErrors() { el('contribution-error').hidden = true; form.querySelectorAll('[data-error]').forEach(node => node.textContent = ''); }
    async function api(url, options = {}) {
        const response = await fetch(url, { credentials: 'same-origin', ...options, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), ...(options.body instanceof FormData ? {} : {'Content-Type': 'application/json'}), ...options.headers } });
        const text = await response.text(); let data;
        try { data = JSON.parse(text); } catch { data = text; }
        if (!response.ok) { const error = Object.assign(new Error(data.message || (response.status === 419 ? 'نشست منقضی شده؛ پیش‌نویس محفوظ است. صفحه را تازه کنید.' : 'درخواست انجام نشد؛ دوباره تلاش کنید.')), typeof data === 'object' ? data : {}); error.status = response.status; throw error; }
        return data;
    }
    try {
        db = await new Promise((resolve, reject) => {
            const request = indexedDB.open('kioosk-contributions', 1);
            request.onupgradeneeded = () => request.result.createObjectStore('drafts', { keyPath: 'id' });
            request.onsuccess = () => resolve(request.result); request.onerror = () => reject(request.error);
        });
        const records = await new Promise((resolve, reject) => { const r = db.transaction('drafts').objectStore('drafts').getAll(); r.onsuccess = () => resolve(r.result); r.onerror = () => reject(r.error); });
        for (const record of records.filter(r => r.expires < Date.now() || r.payload?.edit_review_id)) db.transaction('drafts', 'readwrite').objectStore('drafts').delete(record.id);
        const recover = records.filter(r => r.expires > Date.now() && !r.payload?.edit_review_id && (!r.owner || String(r.owner) === String(user)) && !r.submitted && hasMeaningfulDraft(r)).sort((a,b) => b.savedAt - a.savedAt);
        if (!params.has('new') && !params.has('business') && !params.has('review') && !params.has('draft') && recover[0]) { state = recover[0]; recovered = true; }
        el('clear-device-drafts').hidden = false;
        if (el('draft-tools')) el('draft-tools').hidden = !records.some(r => r.expires > Date.now() && !r.payload?.edit_review_id && hasMeaningfulDraft(r));
    } catch { storageAvailable = false; }
    function collect() {
        for (const key of ['name', 'city', 'address', 'description', 'body', 'visit_date', 'display_name']) {
            if (state.payload.business_id && ['name', 'city'].includes(key)) continue;
            state.payload[key] = form.elements[key].value || null;
        }
        state.payload.phones = [...form.querySelectorAll('[data-contribution-phone]')].map(row => ({label:row.querySelector('[data-label]').value, value:row.querySelector('[data-value]').value})).filter(item => item.label || item.value);
        state.payload.websites = [...form.querySelectorAll('[data-contribution-website]')].map(row => ({label:row.querySelector('[data-label]').value, url:row.querySelector('[data-value]').value})).filter(item => item.label || item.url);
        state.payload.weekly_hours = Object.fromEntries([...form.querySelectorAll('[data-contribution-day]')].map(day => [day.dataset.contributionDay, {closed:day.querySelector('[data-contribution-closed]').checked, shifts:[...day.querySelectorAll('[data-contribution-shift]')].map(shift => ({opens:shift.querySelector('[data-opens]').value,closes:shift.querySelector('[data-closes]').value,next_day:shift.querySelector('[data-next-day]').checked}))}]));
        state.payload.category_id = Number(form.elements.category_id.value) || null;
        state.payload.rating = Number(form.elements.rating.value) || null;
        state.payload.with_review = state.payload.business_id ? true : form.elements.with_review.checked;
        state.payload.confirm_distinct = form.elements.confirm_distinct.checked;
        state.payload.photo_ids = state.photos.map(p => p.client_id);
    }
    async function saveLocal() {
        if (state.submitted) return;
        if (!hasMeaningfulDraft(state)) { await deleteLocalDraft(state.id); status(''); return; }
        state.savedAt = Date.now(); state.expires = Date.now() + 7 * 86400000;
        if (!storageAvailable) { status('ذخیره روی دستگاه در دسترس نیست؛ این صفحه را تا ثبت نهایی باز نگه دارید.'); return; }
        if (!localPersistenceEnabled || state.payload.edit_review_id) {
            try {
                await deleteLocalDraft(state.id);
                status(state.payload.edit_review_id ? 'ویرایش تجربه روی این دستگاه ذخیره نمی‌شود.' : 'پیش‌نویس‌های این دستگاه پاک شدند.');
            } catch {
                storageAvailable = false; status('ذخیره روی دستگاه در دسترس نیست؛ این صفحه را تا ثبت نهایی باز نگه دارید.');
            }
            return;
        }
        try {
            await new Promise((resolve, reject) => { const tx = db.transaction('drafts', 'readwrite'); tx.objectStore('drafts').put(state); tx.oncomplete = resolve; tx.onerror = () => reject(tx.error); tx.onabort = () => reject(tx.error); });
            status('ذخیره شد');
            if (el('draft-tools')) el('draft-tools').hidden = false;
        } catch { storageAvailable = false; status('فضای ذخیره‌سازی کافی نیست؛ این صفحه را تا ثبت نهایی باز نگه دارید.'); }
    }
    async function deleteLocalDraft(id) {
        if (!storageAvailable) return;
        await new Promise((resolve, reject) => { const tx = db.transaction('drafts', 'readwrite'); tx.objectStore('drafts').delete(id); tx.oncomplete = resolve; tx.onerror = () => reject(tx.error); tx.onabort = () => reject(tx.error); });
    }
    el('clear-device-drafts').addEventListener('click', async () => {
        if (!confirm('همه پیش‌نویس‌ها و عکس‌های ذخیره‌شده روی این دستگاه پاک شوند؟ پیش‌نویس‌های حساب شما حذف نمی‌شوند.')) return;
        clearTimeout(saveTimer); clearTimeout(serverTimer); localPersistenceEnabled = false;
        const button = el('clear-device-drafts'); button.disabled = true;
        try {
            await new Promise((resolve, reject) => { const tx = db.transaction('drafts', 'readwrite'); tx.objectStore('drafts').clear(); tx.oncomplete = resolve; tx.onerror = () => reject(tx.error); tx.onabort = () => reject(tx.error); });
            status('پیش‌نویس‌های دستگاه پاک شدند.');
            if (el('resume-draft')) el('resume-draft').hidden = true;
            if (el('draft-tools')) el('draft-tools').hidden = true;
        } catch {
            status('پاک‌کردن پیش‌نویس‌های دستگاه انجام نشد؛ دوباره تلاش کنید.');
        } finally {
            button.disabled = false;
        }
    });
    function populate() {
        for (const key of ['name', 'city', 'category_id', 'address', 'description', 'body', 'visit_date', 'display_name']) form.elements[key].value = state.payload[key] ?? '';
        form.elements.with_review.checked = state.payload.with_review;
        form.elements.confirm_distinct.checked = !!state.payload.confirm_distinct;
        form.querySelectorAll('[name="rating"]').forEach(input => input.checked = Number(input.value) === Number(state.payload.rating));
        el('search-name').value = state.payload.name || ''; if (state.payload.city) el('search-city').value = state.payload.city;
        renderStructuredProfile(); showStep(state.step); renderPhotos();
    }
    function contactRow(type, item = {}) {
        const row = document.createElement('div'); row.dataset[type === 'phones' ? 'contributionPhone' : 'contributionWebsite'] = ''; row.className='grid gap-2 sm:grid-cols-[9rem_1fr_auto]';
        row.innerHTML=`<input data-label class="field !mt-0" maxlength="40" placeholder="عنوان"><input data-value class="field !mt-0" dir="ltr" maxlength="500" placeholder="${type === 'phones' ? 'شماره' : 'https://'}"><button type="button" data-contribution-remove class="button-secondary">حذف</button>`;
        row.querySelector('[data-label]').value=item.label||''; row.querySelector('[data-value]').value=type === 'phones' ? item.value||'' : item.url||''; return row;
    }
    function shiftRow(shift = {opens:'09:00',closes:'17:00',next_day:false}) {
        const row=document.createElement('div'); row.dataset.contributionShift=''; row.className='flex flex-wrap items-end gap-2';
        row.innerHTML='<label>از<input data-opens type="time" class="field !mt-1"></label><label>تا<input data-closes type="time" class="field !mt-1"></label><label class="mb-3 flex gap-2"><input data-next-day type="checkbox"> روز بعد</label><button type="button" data-contribution-remove-shift class="button-secondary">حذف</button>';
        row.querySelector('[data-opens]').value=shift.opens; row.querySelector('[data-closes]').value=shift.closes; row.querySelector('[data-next-day]').checked=!!shift.next_day; return row;
    }
    function renderStructuredProfile() {
        for(const type of ['phones','websites']) { const list=form.querySelector(`[data-contribution-list="${type}"]`); list.replaceChildren(...(state.payload[type]||[]).map(item=>contactRow(type,item))); }
        form.querySelectorAll('[data-contribution-day]').forEach(day=>{const data=state.payload.weekly_hours?.[day.dataset.contributionDay]||{closed:true,shifts:[]};day.querySelector('[data-contribution-closed]').checked=!!data.closed;day.querySelector('[data-contribution-shifts]').replaceChildren(...(data.shifts||[]).map(shiftRow));});
    }
    form.addEventListener('click', event => {
        const add=event.target.closest('[data-contribution-add]'); if(add){const type=add.dataset.contributionAdd,list=form.querySelector(`[data-contribution-list="${type}"]`);if(list.children.length<5)list.append(contactRow(type));}
        const remove=event.target.closest('[data-contribution-remove]'); if(remove)remove.parentElement.remove();
        const addShift=event.target.closest('[data-contribution-add-shift]'); if(addShift){const day=addShift.closest('[data-contribution-day]'),list=day.querySelector('[data-contribution-shifts]');if(list.children.length<4){day.querySelector('[data-contribution-closed]').checked=false;list.append(shiftRow());}}
        const removeShift=event.target.closest('[data-contribution-remove-shift]'); if(removeShift)removeShift.parentElement.remove();
        if (add || remove || addShift || removeShift) changed();
    });
    async function adopt() {
        if (!user || adopted || !hasMeaningfulDraft(state)) return;
        const draft = await api('/contribution-drafts', {method: 'POST', body: JSON.stringify({id: state.id})});
        if (draft.status === 'submitted') { success(draft.result); return; }
        if (state.serverVersion && state.serverVersion !== draft.version) {
            const error = new Error('نسخه جدیدتری در حساب ذخیره شده است. متن این دستگاه محفوظ است؛ نسخه حساب را در پنجره تازه بررسی کنید.'); error.status = 409; throw error;
        }
        version = draft.version; state.serverVersion = version; adopted = true; state.owner = user;
        draft.photos.forEach(photo => {
            const local = state.photos.find(p => p.client_id === photo.client_id);
            if (local) { local.server_id = photo.id; local.status = 'uploaded'; }
        });
    }
    async function saveServer() {
        if (!user || state.submitted || !hasMeaningfulDraft(state)) return;
        await adopt();
        if (state.submitted) return;
        const draft = await api(`/contribution-drafts/${state.id}`, {method:'PUT', body:JSON.stringify({...state.payload, version})});
        version = draft.version; state.serverVersion = version; await saveLocal(); status('در حساب ذخیره شد');
    }
    function queueSave() {
        pendingSave = pendingSave.catch(() => {}).then(saveServer);
        pendingSave.catch(error => { status('ذخیره در حساب انجام نشد؛ نسخه دستگاه محفوظ است.'); fail(error); });
        return pendingSave;
    }
    function changed(event) {
        if (event && ['search-name', 'search-city', 'otp-mobile', 'otp-code'].includes(event.target.id)) return;
        collect(); status('تغییرات هنوز ذخیره نشده‌اند…'); clearTimeout(saveTimer); clearTimeout(serverTimer);
        saveTimer = setTimeout(() => saveLocal(), 300);
        if (user) serverTimer = setTimeout(queueSave, 1000);
        showStep(state.step);
        if (!hasMeaningfulDraft(state)) status('');
    }
    form.addEventListener('input', changed); form.addEventListener('change', changed);
    form.addEventListener('submit', event => event.preventDefault());
    function showStep(step) {
        step = step === 4 ? 3 : step;
        state.step = step;
        form.querySelectorAll('[data-step]').forEach(section => section.hidden = Number(section.dataset.step) !== step);
        el('stepper').querySelectorAll('li').forEach(node => {
            const active = Number(node.dataset.stepLabel) === (step === 2 ? 1 : step);
            node.classList.toggle('text-pomegranate', active); node.setAttribute('aria-current', active ? 'step' : 'false');
            node.textContent = Number(node.dataset.stepLabel) === 1 ? (step === 2 ? '۱. اطلاعات مکان' : '۱. پیدا کردن مکان') : (state.payload.with_review ? '۲. نوشتن نظر و ثبت' : '۲. عکس و ثبت مکان');
        });
        el('step-actions').hidden = step === 1;
        el('previous-step').hidden = step === 1 || !!state.payload.edit_review_id;
        el('next-step').hidden = step === 1;
        el('next-step').textContent = step === 3 ? (state.payload.edit_review_id ? 'ثبت تغییرات نظر' : state.payload.business_id ? 'ثبت نظر' : state.payload.with_review ? 'ثبت مکان و نظر' : 'ثبت مکان') : 'ادامه و نوشتن نظر';
        if (el('change-business')) el('change-business').hidden = !!state.payload.edit_review_id;
        if (el('signin-hint')) el('signin-hint').hidden = !!user;
        if (el('rating-label')) el('rating-label').textContent = ['امتیاز بدهید', 'خیلی بد', 'بد', 'متوسط', 'خوب', 'عالی'][state.payload.rating || 0];
        form.querySelectorAll('[data-rating-value]').forEach(label => {
            const filled = Number(label.dataset.ratingValue) <= Number(state.payload.rating || 0);
            label.classList.toggle('text-pomegranate', filled);
            label.dataset.selected = String(filled);
        });
        if (el('body-count')) el('body-count').textContent = `${(state.payload.body || '').length.toLocaleString('fa-IR')} از ۲٬۰۰۰ نویسه`;
        el('review-optional').hidden = !!state.payload.business_id;
        el('review-fields').hidden = !state.payload.with_review;
        el('selected-business').textContent = `${state.payload.name || ''} · ${state.payload.city || ''}`;
        el('review-heading').textContent = state.payload.edit_review_id ? 'ویرایش تجربه من' : 'تجربه شما';
        el('display-name-field').hidden = !user || !generic;
        el('submission-summary').textContent = state.payload.business_id ? 'تجربه روی مکان تأییدشده منتشر می‌شود. تجربه پنهان‌شده تا بررسی مدیریت خصوصی می‌ماند.' : 'مکان جدید پس از تأیید مدیریت منتشر می‌شود. تا آن زمان تجربه و عکس‌های شما خصوصی هستند.';
        if (el('featured-photo-help')) el('featured-photo-help').hidden = true;
    }
    function invalidateSearch() {
        clearTimeout(searchTimer); searchSequence++; searchController?.abort();
        el('new-business').hidden = true; el('search-more').hidden = true;
        el('search-results').replaceChildren(); el('search-results').setAttribute('aria-busy', 'false');
    }
    async function search(page = 1) {
        clearTimeout(searchTimer); searchController?.abort();
        const sequence = ++searchSequence; searchController = new AbortController();
        el('new-business').hidden = true; el('search-more').hidden = true;
        el('search-results').setAttribute('aria-busy', 'true');
        if (el('search-status')) el('search-status').textContent = 'در حال جست‌وجو…';
        try {
            const query = new URLSearchParams({query: el('search-name').value, city: el('search-city').value, page});
            if (state.payload.latitude && state.payload.longitude) { query.set('latitude', state.payload.latitude); query.set('longitude', state.payload.longitude); }
            const result = await api(`/businesses/search?${query}`, {signal: searchController.signal});
            if (sequence !== searchSequence) return;
            if (page === 1) el('search-results').replaceChildren();
            if (!result.data.length && page === 1) { const p = document.createElement('p'); p.textContent = 'مکانی پیدا نشد. می‌توانید مکان جدید اضافه کنید.'; el('search-results').append(p); }
            for (const business of result.data) {
                const button = document.createElement('button'); button.type = 'button'; button.className = 'button-secondary w-full justify-start text-right';
                if (business.thumbnail) { const img = document.createElement('img'); img.src = business.thumbnail; img.alt = ''; img.className = 'size-16 rounded-lg object-cover'; button.append(img); }
                const text = document.createElement('span'); text.textContent = `${business.name} — ${business.category} | ${business.city}، ${business.address}`; button.append(text);
                button.addEventListener('click', async () => {
                    collect(); resetGeography(); state.payload.business_id = business.id; state.payload.name = business.name; state.payload.city = business.city; state.payload.with_review = true;
                    state.payload.featured_photo_ids = [];
                    delete state.payload.edit_review_id; delete state.payload.review_version; delete state.payload.correction_business_id;
                    // A server-rendered existing-review lookup supplies the editing identity without adopting demo data.
                    if (user) {
                        try {
                            const html = await api(`/contribute?business=${business.id}`);
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const current = JSON.parse(doc.getElementById('contribution-initial').textContent);
                            if (current.edit_review_id) { Object.assign(state.payload, current); await deleteLocalDraft(state.id).catch(() => { storageAvailable = false; }); }
                        } catch(error) { fail(error); return; }
                    }
                    state.step = 3; populate(); changed();
                });
                el('search-results').append(button);
            }
            el('search-more').hidden = !result.next_page_url; el('search-more').onclick = () => search(page + 1);
            el('new-business').hidden = false;
            if (el('search-status')) el('search-status').textContent = result.data.length ? 'مکان مورد نظر را از نتایج انتخاب کنید.' : 'مکانی پیدا نشد؛ می‌توانید مکان تازه‌ای اضافه کنید.';
        } catch (error) {
            if (sequence !== searchSequence || error.name === 'AbortError') return;
            if (el('search-status')) el('search-status').textContent = 'جست‌وجو انجام نشد؛ دوباره تلاش کنید.';
        } finally { if (sequence === searchSequence) el('search-results').setAttribute('aria-busy', 'false'); }
    }
    function resetGeography() { geographySequence++; delete state.payload.latitude; delete state.payload.longitude; el('gps-status').textContent = 'موقعیت فقط با درخواست شما دریافت می‌شود.'; }
    [el('search-name'), el('search-city')].forEach(input => input.addEventListener('input', () => {
        invalidateSearch(); resetGeography();
        if (el('search-status')) el('search-status').textContent = '';
        if (el('search-name').value.trim() || el('search-city').value.trim()) searchTimer = setTimeout(() => search(), 350);
    }));
    el('new-business').hidden = true;
    el('change-business')?.addEventListener('click', () => {
        if (busy || state.payload.edit_review_id) return;
        collect(); resetGeography(); invalidateSearch(); showStep(1); el('search-name').focus(); saveLocal();
    });
    el('search-businesses').addEventListener('click', () => search().catch(fail));
    [el('search-name'), el('search-city')].forEach(input => input.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); search().catch(fail); } }));
    el('new-business').addEventListener('click', () => {
        collect(); resetGeography(); state.payload.confirm_distinct = false; state.payload.name = el('search-name').value; state.payload.city = el('search-city').value;
        delete state.payload.business_id; delete state.payload.edit_review_id; delete state.payload.review_version; delete state.payload.correction_business_id;
        state.step = 2; populate(); changed();
    });
    el('locate').addEventListener('click', () => {
        if (!navigator.geolocation) { el('gps-status').textContent = 'موقعیت در دسترس نیست؛ نام و شهر را وارد کنید.'; return; }
        el('gps-status').textContent = 'در حال دریافت موقعیت…';
        const geographyRequest = ++geographySequence;
        navigator.geolocation.getCurrentPosition(position => {
            if (geographyRequest !== geographySequence || state.step !== 1) return;
            const {latitude, longitude} = position.coords;
            if (latitude < 24 || latitude > 41 || longitude < 43 || longitude > 64) { el('gps-status').textContent = 'موقعیت خارج از محدوده ایران است؛ شهر را دستی وارد کنید.'; return; }
            Object.assign(state.payload, {latitude, longitude}); el('gps-status').textContent = 'موقعیت دریافت شد؛ نتایج نزدیک‌تر اول نمایش داده می‌شوند.'; changed(); search().catch(fail);
        }, () => { if (geographyRequest !== geographySequence) return; el('gps-status').textContent = 'دسترسی به موقعیت ممکن نشد؛ بدون آن ادامه دهید.'; }, {timeout: 10000, maximumAge: 300000});
    });
    async function compress(file) {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) throw new Error('این قالب پشتیبانی نمی‌شود. عکس HEIC را در گالری به JPEG تبدیل و دوباره انتخاب کنید؛ پیش‌نویس محفوظ است.');
        let bitmap;
        try { bitmap = await createImageBitmap(file, {imageOrientation: 'from-image'}); } catch { throw new Error('این عکس قابل خواندن نیست. نسخه JPEG یا PNG انتخاب کنید.'); }
        if (bitmap.width < 100 || bitmap.height < 100) { bitmap.close(); throw new Error('عکس باید حداقل ۱۰۰ × ۱۰۰ پیکسل باشد.'); }
        const ratio = Math.min(1, 2000 / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas'); canvas.width = Math.round(bitmap.width * ratio); canvas.height = Math.round(bitmap.height * ratio);
        const context = canvas.getContext('2d'); context.fillStyle = '#ffffff'; context.fillRect(0,0,canvas.width,canvas.height); context.drawImage(bitmap,0,0,canvas.width,canvas.height); bitmap.close();
        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', .86));
        if (!blob || blob.size > 10 * 1024 * 1024) throw new Error('حجم عکس پس از فشرده‌سازی بیش از ۱۰ مگابایت است.');
        return blob;
    }
    async function addPhotos(files) {
        for (const file of files) {
            if (state.photos.length >= 6) { fail(new Error('حداکثر شش عکس انتخاب کنید.')); break; }
            try { const blob = await compress(file); state.photos.push({client_id: crypto.randomUUID(), blob, name: file.name, status:'selected', progress:0}); }
            catch(error) { fail(error); }
        }
        if (!state.payload.business_id && !state.payload.featured_photo_ids?.length && state.photos.length) state.payload.featured_photo_ids = [state.photos[0].client_id];
        collect(); renderPhotos(); await saveLocal();
        if (user) { await queueSave(); await uploadAll(); }
    }
    [el('gallery-input'), el('camera-input')].forEach(input => input.addEventListener('change', async () => { const files = [...input.files]; input.value = ''; try { await addPhotos(files); } catch(error) { fail(error); } }));
    const previewUrls = new Map();
    function renderPhotos() {
        el('photo-previews').replaceChildren();
        for (const photo of state.photos) {
            const card = document.createElement('div'); card.className = 'rounded-xl border border-border p-2';
            const img = document.createElement('img'); img.className = 'h-28 w-full rounded-lg object-cover'; img.alt = 'پیش‌نمایش عکس انتخاب‌شده';
            if (photo.blob) { if (!previewUrls.has(photo.client_id)) previewUrls.set(photo.client_id, URL.createObjectURL(photo.blob)); img.src = previewUrls.get(photo.client_id); }
            else if (photo.server_id) img.src = `/media/${photo.server_id}?thumbnail=1`;
            const label = document.createElement('p'); label.className = 'my-2 text-xs'; label.textContent = photo.status === 'uploaded' ? 'بارگذاری شد' : photo.status === 'failed' ? 'بارگذاری ناموفق' : photo.status === 'uploading' ? `بارگذاری ${photo.progress || 0}٪` : 'آماده بارگذاری';
            const progress = document.createElement('progress'); progress.max = 100; progress.value = photo.status === 'uploaded' ? 100 : photo.progress || 0; progress.className = 'w-full'; progress.setAttribute('aria-label','پیشرفت بارگذاری');
            const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'button-secondary w-full'; remove.textContent = 'حذف'; remove.disabled = photo.status === 'uploading' || busy;
            remove.addEventListener('click', async () => {
                try {
                    if (photo.server_id) await api(`/contribution-drafts/${state.id}/photos/${photo.server_id}`, {method:'DELETE'});
                    state.photos = state.photos.filter(p => p.client_id !== photo.client_id); URL.revokeObjectURL(previewUrls.get(photo.client_id)); previewUrls.delete(photo.client_id);
                    state.payload.featured_photo_ids = (state.payload.featured_photo_ids || []).filter(id => id !== photo.client_id);
                    if (!state.payload.business_id && !state.payload.featured_photo_ids.length && state.photos.length) state.payload.featured_photo_ids = [state.photos[0].client_id];
                    collect(); renderPhotos(); await saveLocal(); if(user) await queueSave();
                } catch(error) { fail(error); }
            });
            card.append(img,label,progress,remove);
            if (!state.payload.business_id) {
                const selected = state.payload.featured_photo_ids?.[0] === photo.client_id;
                const feature = document.createElement('button'); feature.type = 'button'; feature.className = 'button-secondary mt-2 w-full';
                feature.textContent = selected ? 'عکس اصلی مکان' : 'انتخاب به‌عنوان عکس اصلی'; feature.disabled = selected || busy;
                feature.addEventListener('click', () => {
                    state.payload.featured_photo_ids = [photo.client_id, ...(state.payload.featured_photo_ids || []).filter(id => id !== photo.client_id)].slice(0, 5);
                    renderPhotos(); changed();
                }); card.append(feature);
            }
            if (photo.status === 'failed') { const retry = document.createElement('button'); retry.type = 'button'; retry.className='button-secondary mt-2 w-full'; retry.textContent='تلاش دوباره'; retry.addEventListener('click', () => uploadPhoto(photo).catch(fail)); card.append(retry); }
            el('photo-previews').append(card);
        }
    }
    async function uploadPhoto(photo) {
        if (photo.status === 'uploaded' || photo.status === 'uploading') return;
        if (!photo.blob) throw new Error('عکس محلی در دسترس نیست؛ آن را حذف و دوباره انتخاب کنید.');
        photo.status = 'uploading'; photo.progress = 0; renderPhotos();
        try {
            const result = await new Promise((resolve,reject) => {
                const xhr = new XMLHttpRequest(); xhr.open('POST', `/contribution-drafts/${state.id}/photos`); xhr.setRequestHeader('Accept', 'application/json'); xhr.setRequestHeader('X-CSRF-TOKEN', csrf()); xhr.timeout = 120000;
                xhr.upload.onprogress = event => { if(event.lengthComputable) { photo.progress = Math.round(event.loaded / event.total * 100); renderPhotos(); } };
                xhr.onload = () => { let data = {}; try { data = JSON.parse(xhr.responseText); } catch {} if(xhr.status >= 200 && xhr.status < 300) resolve(data); else reject(Object.assign(new Error(data.message || 'بارگذاری عکس انجام نشد.'),data)); };
                xhr.onerror = xhr.ontimeout = () => reject(new Error('ارتباط بارگذاری قطع شد؛ دوباره تلاش کنید.'));
                const data = new FormData(); data.append('client_id',photo.client_id); data.append('photo',photo.blob,'photo.jpg'); xhr.send(data);
            });
            photo.server_id = result.id; photo.status='uploaded'; photo.progress=100;
        } catch(error) { photo.status='failed'; throw error; }
        finally { renderPhotos(); await saveLocal(); }
    }
    async function uploadAll() {
        if(uploadBusy) return;
        uploadBusy = true;
        try { for(const photo of state.photos) if(photo.status !== 'uploaded') await uploadPhoto(photo); }
        finally { uploadBusy=false; }
    }
    el('previous-step').addEventListener('click', () => { collect(); showStep(state.step === 3 && state.payload.business_id ? 1 : state.step - 1); saveLocal(); });
    function validateStep() {
        const missing = state.step === 2 ? ['name','category_id','city','address'].find(k => !String(state.payload[k] || '').trim()) : null;
        if(missing) { fail({message:'اطلاعات ضروری مکان را کامل کنید.',errors:{[missing]:['این قسمت را کامل کنید.']}}); return false; }
        if (state.step === 2 && !state.payload.confirm_distinct) { fail({message:'متفاوت بودن مکان را تأیید کنید.', errors:{confirm_distinct:['این تأیید لازم است.']}}); return false; }
        if (state.step === 3 && state.payload.with_review) {
            const errors = {};
            if (!state.payload.rating) errors.rating = ['امتیاز خود را انتخاب کنید.'];
            const length = (state.payload.body || '').trim().length;
            if (length < 10 || length > 2000) errors.body = ['تجربه‌ای بین ۱۰ تا ۲۰۰۰ نویسه بنویسید.'];
            if (Object.keys(errors).length) { fail({message:'نظر خود را کامل کنید.', errors}); return false; }
        }
        return true;
    }
    el('place-only')?.addEventListener('click', async () => {
        if (busy) return; clearErrors(); collect();
        if (!validateStep()) return;
        state.payload.with_review = false; form.elements.with_review.checked = false; showStep(3);
        changed(); await saveLocal(); el('review-heading').focus();
    });
    function setBusy(value) { busy=value; form.querySelectorAll('input, textarea, select').forEach(input => input.disabled = value); el('next-step').disabled=value; el('previous-step').disabled=value; el('gallery-input').disabled=value; el('camera-input').disabled=value; renderPhotos(); }
    el('next-step').addEventListener('click', async () => {
        if(busy) return; clearErrors(); collect();
        if(!validateStep()) return;
        if(state.step === 2) { state.payload.with_review = true; form.elements.with_review.checked = true; showStep(3); await saveLocal(); form.querySelector(`[data-step="${state.step}"] h2`).focus(); return; }
        if(!user) { await saveLocal(); el('otp-panel').hidden=false; el('otp-mobile').focus(); return; }
        setBusy(true);
        try {
            clearTimeout(serverTimer); await saveLocal(); await queueSave(); await uploadAll();
            if(state.photos.some(p => p.status !== 'uploaded')) throw new Error('بارگذاری عکس‌ها را کامل کنید یا عکس ناموفق را حذف کنید.');
            const result = await api(`/contribution-drafts/${state.id}/submit`,{method:'POST',body:'{}'}); success(result);
        } catch(error) { fail(error); } finally { setBusy(false); }
    });
    function success(result) {
        state.submitted=true; state.photos=[]; deleteLocalDraft(state.id).catch(() => {}); form.hidden=true; el('stepper').hidden=true; el('contribution-success').hidden=false;
        el('success-heading').textContent = result.status === 'published' ? 'تجربه شما منتشر شد' : result.status === 'pending' ? 'در انتظار تأیید مکان' : 'مشارکت نیازمند بررسی یا اصلاح است';
        el('success-copy').textContent = result.status === 'published' ? 'از به‌اشتراک‌گذاشتن تجربه‌تان سپاسگزاریم.' : 'وضعیت و پیام مدیریت را در «مشارکت‌های من» دنبال کنید.';
        el('success-link').href=result.url; status('مشارکت ثبت شد.');
    }
    let resendAt = 0;
    setInterval(() => { const seconds = Math.max(0, Math.ceil((resendAt - Date.now()) / 1000)); el('send-otp').disabled = seconds > 0; el('send-otp').textContent = seconds ? `ارسال دوباره تا ${seconds.toLocaleString('fa-IR')} ثانیه` : 'دریافت کد ورود'; }, 1000);
    async function otpAction(button, callback) {
        button.disabled=true; try { clearErrors(); await callback(); } catch(error) { fail(error); } finally { button.disabled=false; }
    }
    el('send-otp').addEventListener('click', () => otpAction(el('send-otp'), async () => {
        await saveLocal(); await api('/login?contribute=1');
        const sent = await api('/login',{method:'POST',body:JSON.stringify({mobile:el('otp-mobile').value})});
        resendAt = Date.now() + sent.resend_after * 1000;
        el('otp-code-panel').hidden=false; el('otp-status').textContent='در صورت مجاز بودن ورود، کد برای این شماره ارسال می‌شود.'; el('otp-code').focus();
    }));
    el('verify-otp').addEventListener('click', () => otpAction(el('verify-otp'), async () => {
        const authenticated = await api('/verify',{method:'POST',body:JSON.stringify({code:el('otp-code').value})});
        if(!authenticated.user_id) throw new Error('ورود کامل نشد؛ دوباره تلاش کنید.');
        document.querySelector('meta[name="csrf-token"]').content=authenticated.csrf_token;
        user=String(authenticated.user_id); generic=authenticated.needs_display_name; state.owner=user;
        el('otp-panel').hidden=true; el('otp-code').value=''; el('otp-mobile').value=''; showStep(3);
        await adopt(); await saveLocal(); await queueSave(); await uploadAll();
    }));
    if(params.has('draft') && user) {
        const draft = await api(`/contribution-drafts/${encodeURIComponent(params.get('draft'))}`);
        state = {serverVersion:draft.version,id:draft.id,payload:draft.payload,photos:draft.photos.map(p => ({client_id:p.client_id,server_id:p.id,status:'uploaded'})),step:draft.payload.business_id ? 3 : 2,owner:user,expires:Date.now()+7*86400000}; version=draft.version; adopted=true; recovered=hasMeaningfulDraft(state);
        if(draft.status==='submitted') { success(draft.result); return; }
    }
    state.photos.forEach(p => { if(p.status==='uploading') p.status='selected'; });
    if (!recovered && !state.payload.edit_review_id) state.payload.visit_date = null;
    if (el('resume-draft')) el('resume-draft').hidden = !recovered;
    status(recovered ? 'پیش‌نویس بازیابی شد' : '');
    populate(); initCalendar(form.elements.visit_date);
    window.addEventListener('beforeunload', event => { if(!state.submitted && hasMeaningfulDraft(state) && (!storageAvailable || uploadBusy || busy)) { event.preventDefault(); event.returnValue=''; } });
}
