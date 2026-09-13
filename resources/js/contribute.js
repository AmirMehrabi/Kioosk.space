import { initCalendar } from './persian-date.js';

export function hasMeaningfulDraft(draft) {
    const p = draft.payload || {};
    return !!(draft.photos?.length || p.body?.trim() || p.rating || (!p.business_id && (['name', 'city', 'address', 'description', 'category_id'].some(key => String(p[key] || '').trim()) || p.phones?.length || p.websites?.length || Object.values(p.weekly_hours || {}).some(day => !day.closed || day.shifts?.length))));
}

export function contributionSteps(payload) {
    if (payload.edit_review_id) return [{id: 3, label: 'ویرایش تجربه'}, {id: 4, label: 'بررسی و ثبت'}];
    return [
        {id: 1, label: 'پیدا کردن مکان'},
        ...(!payload.business_id ? [{id: 2, label: 'اطلاعات مکان'}] : []),
        {id: 3, label: payload.with_review ? 'تجربه و عکس‌ها' : 'عکس‌ها (اختیاری)'},
        {id: 4, label: 'بررسی و ثبت'},
    ];
}

export function contributionErrors(payload, step) {
    const errors = {};
    if ((step === 2 || step === 4) && !payload.business_id) {
        for (const [key, message] of Object.entries({name: 'نام مکان را وارد کن.', category_id: 'دسته‌بندی مکان را انتخاب کن.', city: 'شهر را از فهرست انتخاب کن.', address: 'آدرس مکان را وارد کن.'})) {
            if (!String(payload[key] || '').trim()) errors[key] = [message];
        }
        for (const [type, valueKey] of [['phones', 'value'], ['websites', 'url']]) {
            (payload[type] || []).forEach((item, index) => {
                if (!(item.label || '').trim()) errors[`${type}.${index}.label`] = ['عنوان را وارد کن؛ مثلاً «اصلی».'];
                const value = (item[valueKey] || '').trim();
                if (type === 'phones' && (!value || !/^[+۰-۹٠-٩0-9()\s-]+$/.test(value))) errors[`${type}.${index}.value`] = ['شماره تلفن معتبر را همراه با پیش‌شماره وارد کن.'];
                if (type === 'websites') {
                    try { const url = new URL(value); if (!['http:', 'https:'].includes(url.protocol)) throw new Error(); }
                    catch { errors[`${type}.${index}.url`] = ['آدرس کامل وب‌سایت را وارد کن؛ مثلاً https://example.com']; }
                }
            });
        }
        for (const [day, hours] of Object.entries(payload.weekly_hours || {})) {
            if (hours.closed) continue;
            if (!hours.shifts?.length) errors[`weekly_hours.${day}.shifts`] = ['یک نوبت کاری اضافه کن یا این روز را تعطیل مشخص کن.'];
            (hours.shifts || []).forEach((shift, index) => {
                for (const key of ['opens', 'closes']) if (!/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(shift[key] || '')) errors[`weekly_hours.${day}.shifts.${index}.${key}`] = ['ساعت معتبر را وارد کن.'];
                if (shift.opens && shift.closes && !shift.next_day && shift.closes <= shift.opens) errors[`weekly_hours.${day}.shifts.${index}.closes`] = ['ساعت پایان باید بعد از شروع باشد؛ اگر بعد از نیمه‌شب است، «روز بعد» را انتخاب کن.'];
            });
        }
        if (!payload.confirm_distinct) errors.confirm_distinct = ['تأیید کن که این مکان یا شعبه در نتایج جست‌وجو نبود.'];
    }
    if ((step === 3 || step === 4) && payload.with_review) {
        if (![1, 2, 3, 4, 5].includes(Number(payload.rating))) errors.rating = ['از ۱ تا ۵ ستاره به این مکان امتیاز بده.'];
        const length = [...(payload.body || '').trim()].length;
        if (length < 10 || length > 2000) errors.body = ['تجربه‌ات را بین ۱۰ تا ۲۰۰۰ حرف بنویس.'];
    }
    return errors;
}

const app = typeof document === 'undefined' ? null : document.getElementById('contribution-app');
if (app) startContribution().catch(error => { document.getElementById('contribution-error').hidden = false; document.getElementById('error-title').textContent = 'راه‌اندازی فرم انجام نشد. صفحه را دوباره باز کنید.'; });

async function startContribution() {
    const el = id => document.getElementById(id), form = el('contribution-form');
    const initial = JSON.parse(el('contribution-initial').textContent);
    initial.phones ??= initial.phone ? [{label:'اصلی', value:initial.phone}] : [];
    initial.websites ??= initial.website ? [{label:'وب‌سایت اصلی', url:initial.website}] : [];
    initial.weekly_hours ??= null;
    initial.featured_photo_ids ??= [];
    let user = app.dataset.user || null, generic = app.dataset.generic === '1';
    let db, storageAvailable = true, localPersistenceEnabled = app.dataset.persistDraft === '1', saveTimer, serverTimer, version = 1, adopted = false, busy = false, uploadBusy = false, pendingSave = Promise.resolve();
    let state = { serverVersion: null, id: crypto.randomUUID(), payload: initial, photos: [], step: initial.business_id ? 3 : 1, owner: user, expires: Date.now() + 7 * 86400000 };
    let searchedQuery = null;
    let recovered = false, searchTimer, searchSequence = 0, searchController, geographySequence = 0;
    const params = new URLSearchParams(location.search);
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    function status(text) { el('draft-status').textContent = text; }
    const fieldErrors = new Map();
    function errorInput(key) {
        const parts = key.split('.');
        if (parts[0] === 'mobile' || parts[0] === 'code') return el(`otp-${parts[0]}`);
        if (['phones', 'websites'].includes(parts[0]) && parts.length > 1) {
            const row = form.querySelector(`[data-contribution-list="${parts[0]}"]`)?.children[Number(parts[1])];
            return row?.querySelector(parts[2] === 'label' ? '[data-label]' : '[data-value]');
        }
        if (parts[0] === 'weekly_hours' && parts.length > 1) {
            const day = [...form.querySelectorAll('[data-contribution-day]')].find(node => node.dataset.contributionDay === parts[1]);
            const shift = day?.querySelectorAll('[data-contribution-shift]')[Number(parts[3])];
            return shift?.querySelector(parts[4] === 'opens' ? '[data-opens]' : parts[4] === 'closes' ? '[data-closes]' : '[data-next-day]') || day?.querySelector('input');
        }
        if (['photo', 'photo_ids', 'featured_photo_ids'].includes(parts[0])) return el('gallery-input');
        if (parts[0] === 'weekly_hours') return el('include-hours');
        if (['phones', 'websites'].includes(parts[0])) return form.querySelector(`[data-contribution-add="${parts[0]}"]`);
        return form.querySelector(`[name="${CSS.escape(parts[0])}"]`);
    }
    function revealInput(input) {
        const section = input.closest('[data-step]');
        if (section) showStep(Number(section.dataset.step));
        for (let parent = input.parentElement; parent; parent = parent.parentElement) if (parent.tagName === 'DETAILS') parent.open = true;
        if (input.id === 'include-hours') input.closest('details').open = true;
        if (input.closest('#hours-fields')) { el('include-hours').checked = true; el('hours-fields').hidden = false; }
        if (input.closest('#otp-panel')) { el('otp-panel').hidden = false; if (input.id === 'otp-code') el('otp-code-panel').hidden = false; }
        input.focus(); input.closest('label, fieldset, [data-city-select]')?.scrollIntoView({block: 'center', behavior: 'smooth'});
    }
    function fail(error) {
        el('contribution-error').hidden = false;
        el('error-title').textContent = error.errors ? 'چند مورد نیاز به اصلاح دارد' : (/[\u0600-\u06ff]/.test(error.message || '') ? error.message : 'درخواست انجام نشد. اتصال اینترنت را بررسی کن و دوباره تلاش کن؛ این صفحه را باز نگه دار.');
        let firstInput;
        for (const [key, messages] of Object.entries(error.errors || {})) {
            if (fieldErrors.has(key)) continue;
            const input = errorInput(key);
            let target = form.querySelector(`[data-error="${CSS.escape(key)}"]`);
            if (!target && input) { target = document.createElement('span'); target.dataset.error = key; target.className = 'field-error'; input.closest('label')?.tagName === 'LABEL' ? input.closest('label').append(target) : input.after(target); }
            const errorId = `error-${key.replaceAll('.', '-')}`;
            if (target) { target.id ||= errorId; target.textContent = messages[0]; }
            if (input) {
                input.setAttribute('aria-invalid', 'true');
                const descriptions = new Set((input.getAttribute('aria-describedby') || '').split(' ').filter(Boolean));
                if (target) descriptions.add(target.id);
                input.setAttribute('aria-describedby', [...descriptions].join(' '));
                firstInput ||= input;
            }
            const item = document.createElement('li');
            const link = document.createElement(input ? 'button' : 'span'); link.textContent = messages[0];
            if (input) { link.type = 'button'; link.className = 'text-right underline underline-offset-4'; link.addEventListener('click', () => revealInput(input)); }
            item.append(link); el('error-list').append(item); fieldErrors.set(key, {input, target, item});
        }
        if (error.status === 409 && user && !error.edit_url) { el('draft-conflict').hidden = false; el('reload-draft').href = `/contribute?draft=${encodeURIComponent(state.id)}`; }
        if (error.edit_url) { el('conflict').hidden = false; el('edit-existing').href = error.edit_url; }
        queueMicrotask(() => firstInput ? revealInput(firstInput) : el('contribution-error').focus());
    }
    function removeFieldError(key) {
        const entry = fieldErrors.get(key); if (!entry) return;
        entry.input?.removeAttribute('aria-invalid');
        if (entry.target) {
            entry.target.textContent = '';
            const described = (entry.input?.getAttribute('aria-describedby') || '').split(' ').filter(id => id !== entry.target.id).join(' ');
            if (described) entry.input?.setAttribute('aria-describedby', described); else entry.input?.removeAttribute('aria-describedby');
        }
        entry.item.remove(); fieldErrors.delete(key);
    }
    function clearErrors() {
        [...fieldErrors.keys()].forEach(removeFieldError);
        el('contribution-error').hidden = true; el('error-list').replaceChildren();
        el('conflict').hidden = true; el('draft-conflict').hidden = true;
    }
    form.addEventListener('input', event => {
        for (const [key, entry] of fieldErrors) if (entry.input === event.target || (key === 'rating' && event.target.name === 'rating')) removeFieldError(key);
        if (!fieldErrors.size && el('error-title').textContent === 'چند مورد نیاز به اصلاح دارد') el('contribution-error').hidden = true;
    });
    async function api(url, options = {}) {
        const response = await fetch(url, { credentials: 'same-origin', ...options, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), ...(options.body instanceof FormData ? {} : {'Content-Type': 'application/json'}), ...options.headers } });
        const text = await response.text(); let data;
        try { data = JSON.parse(text); } catch { data = text; }
        if (!response.ok) { const error = Object.assign(new Error(data.message || (response.status === 429 ? 'تعداد تلاش‌ها زیاد شده است. کمی صبر کن و دوباره تلاش کن.' : response.status === 419 ? 'نشست منقضی شده است. پیش از تازه‌کردن صفحه، وضعیت ذخیره پیش‌نویس را بررسی کن.' : 'درخواست انجام نشد؛ دوباره تلاش کنید.')), typeof data === 'object' ? data : {}); error.status = response.status; throw error; }
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
        state.payload.phones = [...form.querySelectorAll('[data-contribution-phone]')].map(row => ({label:row.querySelector('[data-label]').value, value:row.querySelector('[data-value]').value}));
        state.payload.websites = [...form.querySelectorAll('[data-contribution-website]')].map(row => ({label:row.querySelector('[data-label]').value, url:row.querySelector('[data-value]').value}));
        state.payload.weekly_hours = el('include-hours').checked ? Object.fromEntries([...form.querySelectorAll('[data-contribution-day]')].map(day => [day.dataset.contributionDay, {closed:day.querySelector('[data-contribution-closed]').checked, shifts:[...day.querySelectorAll('[data-contribution-shift]')].map(shift => ({opens:shift.querySelector('[data-opens]').value,closes:shift.querySelector('[data-closes]').value,next_day:shift.querySelector('[data-next-day]').checked}))}])) : null;
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
            status('پیش‌نویس روی این دستگاه ذخیره شد؛ هنوز ارسال نشده است.');
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
        el('include-hours').checked = !!state.payload.weekly_hours && Object.keys(state.payload.weekly_hours).length > 0;
        renderStructuredProfile(); showStep(state.step); renderPhotos();
    }
    function contactRow(type, item = {}) {
        const row = document.createElement('div'); row.dataset[type === 'phones' ? 'contributionPhone' : 'contributionWebsite'] = ''; row.className='grid gap-2 sm:grid-cols-[9rem_1fr_auto]';
        row.innerHTML=`<label class="block text-xs text-secondary">عنوان<input data-label class="field" maxlength="40" placeholder="مثلاً اصلی"></label><label class="block text-xs text-secondary">${type === 'phones' ? 'شماره تلفن' : 'آدرس وب‌سایت'}<input data-value class="field" dir="ltr" maxlength="${type === 'phones' ? 40 : 500}" type="${type === 'phones' ? 'tel' : 'url'}" placeholder="${type === 'phones' ? '۰۲۱۱۲۳۴۵۶۷۸' : 'https://example.com'}"></label><button type="button" data-contribution-remove class="button-secondary self-end">حذف</button>`;
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
        if (add || remove || addShift || removeShift) { clearErrors(); changed(); }
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
        version = draft.version; state.serverVersion = version; await saveLocal(); status('پیش‌نویس در حساب ذخیره شد؛ هنوز ارسال نشده است.');
    }
    function queueSave() {
        pendingSave = pendingSave.catch(() => {}).then(saveServer);
        pendingSave.catch(error => { status('ذخیره در حساب انجام نشد؛ این صفحه را باز نگه دار و دوباره تلاش کن.'); fail(error); });
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
        state.step = step;
        form.querySelectorAll('[data-step]').forEach(section => section.hidden = Number(section.dataset.step) !== step);
        const steps = contributionSteps(state.payload), current = steps.findIndex(item => item.id === step);
        const stepSignature = `${step}:${steps.map(item => item.label).join('|')}`;
        if (el('stepper').dataset.signature !== stepSignature) el('stepper').replaceChildren(...steps.map((item, index) => {
            const node = document.createElement('li');
            node.dataset.state = index < current ? 'complete' : index === current ? 'current' : 'upcoming';
            if (index === current) node.setAttribute('aria-current', 'step');
            const number = document.createElement('span'); number.className = 'contribution-step-number'; number.textContent = index < current ? '✓' : (index + 1).toLocaleString('fa-IR');
            const label = document.createElement('span'); label.textContent = item.label;
            node.append(number, label); return node;
        }));
        el('stepper').dataset.signature = stepSignature;
        const progressText = `مرحله ${(current + 1).toLocaleString('fa-IR')} از ${steps.length.toLocaleString('fa-IR')} · ${steps[current]?.label || ''}`;
        if (el('step-progress').textContent !== progressText) el('step-progress').textContent = progressText;
        el('step-actions').hidden = step === 1;
        el('previous-step').hidden = current === 0;
        el('next-step').textContent = step === 4 ? (user ? 'ثبت نهایی مشارکت' : 'تأیید شماره و ادامه') : step === 2 ? 'ادامه و نوشتن تجربه' : 'ادامه و بررسی اطلاعات';
        if (el('change-business')) el('change-business').hidden = !!state.payload.edit_review_id;
        el('edit-place-summary').hidden = !!state.payload.edit_review_id;
        el('signin-hint').hidden = !!user;
        el('rating-label').textContent = ['امتیازت را انتخاب کن', 'خیلی بد', 'بد', 'متوسط', 'خوب', 'عالی'][state.payload.rating || 0];
        form.querySelectorAll('[data-rating-value]').forEach(label => label.dataset.selected = String(Number(label.dataset.ratingValue) <= Number(state.payload.rating || 0)));
        const count = [...(state.payload.body || '')].length;
        el('body-count').textContent = `${count.toLocaleString('fa-IR')} از ۲٬۰۰۰ حرف · حداقل ۱۰ حرف`;
        el('review-optional').hidden = !!state.payload.business_id;
        el('review-fields').hidden = !state.payload.with_review;
        form.elements.body.required = !!state.payload.with_review;
        form.querySelectorAll('[name="rating"]').forEach(input => input.required = !!state.payload.with_review);
        el('selected-business').textContent = `${state.payload.name || ''} · ${state.payload.city || ''}`;
        el('review-heading').textContent = state.payload.edit_review_id ? 'ویرایش تجربه من' : state.payload.with_review ? 'تجربه‌ات چطور بود؟' : 'عکسی از این مکان داری؟';
        el('display-name-field').hidden = !user || !generic;
        el('hours-fields').hidden = !el('include-hours').checked;
        el('featured-photo-help').hidden = !!state.payload.business_id || !state.photos.length;
        el('submission-summary').textContent = state.payload.business_id ? 'تجربه روی مکان تأییدشده منتشر می‌شود. تجربه پنهان‌شده تا بررسی مدیریت خصوصی می‌ماند.' : 'مکان جدید پس از تأیید مدیریت منتشر می‌شود. تا آن زمان تجربه و عکس‌ها عمومی نیستند. وضعیت را در «مشارکت‌های من» دنبال کن.';
        if (step === 4) renderSummary();
    }
    function navigate(step) {
        if (busy || otpBusy) return;
        clearErrors(); collect(); showStep(step); saveLocal();
        form.querySelector(`[data-step="${step}"] h2`).focus();
        form.querySelector(`[data-step="${step}"]`).scrollIntoView({block: 'start', behavior: 'smooth'});
    }
    function renderSummary() {
        const p = state.payload;
        el('summary-place').textContent = `${p.name || ''} · ${p.city || ''}`;
        el('summary-address').textContent = p.business_id ? 'مکان موجود در کیوسک' : p.address || '';
        el('summary-rating').textContent = p.with_review ? `امتیاز ${Number(p.rating).toLocaleString('fa-IR')} از ۵` : 'فقط معرفی مکان؛ بدون تجربه';
        el('summary-body').textContent = p.with_review ? p.body || '' : 'نوشتن تجربه برای معرفی مکان تازه ضروری نیست.';
        el('summary-photos').textContent = state.photos.length ? `${state.photos.length.toLocaleString('fa-IR')} عکس انتخاب‌شده · ${state.photos.filter(photo => photo.status === 'uploaded').length.toLocaleString('fa-IR')} عکس بارگذاری‌شده` : 'بدون عکس؛ افزودن عکس اختیاری است.';
        const details = [];
        if (!p.business_id) {
            details.push(['دسته‌بندی', form.elements.category_id.selectedOptions[0]?.textContent || '']);
            if (p.description) details.push(['معرفی', p.description]);
            (p.phones || []).forEach(item => details.push([item.label || 'تلفن', item.value]));
            (p.websites || []).forEach(item => details.push([item.label || 'وب‌سایت', item.url]));
            if (p.weekly_hours) form.querySelectorAll('[data-contribution-day]').forEach(day => {
                const hours = p.weekly_hours[day.dataset.contributionDay];
                if (hours) details.push([day.querySelector('strong').textContent, hours.closed ? 'تعطیل' : hours.shifts.map(shift => `${shift.opens} تا ${shift.closes}${shift.next_day ? ' (روز بعد)' : ''}`).join('، ') || 'ساعت وارد نشده']);
            });
            else details.push(['ساعت کاری', 'مشخص نشده']);
        }
        if (p.with_review && p.visit_date) details.push(['تاریخ بازدید', p.visit_date]);
        el('summary-details').replaceChildren(...details.map(([label, value]) => {
            const row = document.createElement('div'), dt = document.createElement('dt'), dd = document.createElement('dd');
            dt.className = 'text-secondary'; dt.textContent = label; dd.className = 'break-words'; dd.textContent = value;
            row.append(dt, dd); return row;
        }));
    }
    el('edit-place-summary').addEventListener('click', () => navigate(state.payload.business_id ? 1 : 2));
    el('edit-review-summary').addEventListener('click', () => navigate(3));
    function invalidateSearch() {
        clearTimeout(searchTimer); searchSequence++; searchController?.abort();
        el('new-business').hidden = false; el('search-more').hidden = true;
        el('search-results').replaceChildren(); el('search-results').setAttribute('aria-busy', 'false');
    }
    async function search(page = 1) {
        clearTimeout(searchTimer); searchController?.abort();
        const sequence = ++searchSequence; searchController = new AbortController();
        el('new-business').hidden = false; el('search-more').hidden = true;
        el('search-results').setAttribute('aria-busy', 'true');
        if (el('search-status')) el('search-status').textContent = 'در حال جست‌وجو…';
        el('search-status').removeAttribute('data-failed');
        try {
            const query = new URLSearchParams({query: el('search-name').value, city: el('search-city').value, page});
            if (state.payload.latitude && state.payload.longitude) { query.set('latitude', state.payload.latitude); query.set('longitude', state.payload.longitude); }
            const result = await api(`/businesses/search?${query}`, {signal: searchController.signal});
            if (sequence !== searchSequence) return;
            searchedQuery = `${el('search-name').value.trim()}|${el('search-city').value.trim()}`;
            if (page === 1) el('search-results').replaceChildren();

            for (const business of result.data) {
                const button = document.createElement('button'); button.type = 'button'; button.className = 'contribution-search-result group flex w-full items-center gap-4 rounded-xl border border-border bg-surface p-3 text-right shadow-soft transition hover:-translate-y-0.5 hover:border-pomegranate/50 hover:bg-pomegranate/5 focus-visible:border-pomegranate';
                if (business.thumbnail) { const img = document.createElement('img'); img.src = business.thumbnail; img.alt = ''; img.className = 'size-16 rounded-lg object-cover'; button.append(img); }
                const text = document.createElement('span'); text.className = 'min-w-0 flex-1';
                const name = document.createElement('strong'); name.className = 'block text-base font-bold text-ink'; name.textContent = business.name;
                const meta = document.createElement('span'); meta.className = 'mt-1 block truncate text-sm text-secondary'; meta.textContent = `${business.category} · ${business.city}`;
                const address = document.createElement('span'); address.className = 'mt-1 block text-xs leading-6 text-muted'; address.textContent = business.address;
                text.append(name, meta, address); button.append(text);
                const arrow = document.createElement('span'); arrow.className = 'shrink-0 text-sm font-semibold text-pomegranate'; arrow.textContent = 'انتخاب ←'; button.append(arrow);
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
                    state.step = 3; populate(); changed(); navigate(3);
                });
                el('search-results').append(button);
            }
            el('search-more').hidden = !result.next_page_url; el('search-more').onclick = () => search(page + 1);
            el('new-business').hidden = false;
            if (el('search-status')) el('search-status').textContent = result.data.length ? 'مکان مورد نظر را از نتایج انتخاب کنید.' : 'مکانی پیدا نشد؛ می‌توانید مکان تازه‌ای اضافه کنید.';
        } catch (error) {
            if (sequence !== searchSequence || error.name === 'AbortError') return;
            if (el('search-status')) el('search-status').textContent = 'جست‌وجو انجام نشد. اتصال اینترنت را بررسی کن و «پیدا کردن مکان» را دوباره بزن.';
            el('search-status').dataset.failed = 'true';
        } finally { if (sequence === searchSequence) el('search-results').setAttribute('aria-busy', 'false'); }
    }
    function resetGeography() { geographySequence++; delete state.payload.latitude; delete state.payload.longitude; el('gps-status').textContent = 'موقعیت فقط با درخواست شما دریافت می‌شود.'; }
    [el('search-name'), el('search-city')].forEach(input => input.addEventListener('input', () => {
        invalidateSearch(); resetGeography();
        if (el('search-status')) el('search-status').textContent = '';
        if (el('search-name').value.trim() || el('search-city').value.trim()) searchTimer = setTimeout(() => search(), 350);
    }));
    el('new-business').hidden = false;
    el('change-business')?.addEventListener('click', () => {
        if (busy || state.payload.edit_review_id) return;
        clearErrors(); collect(); resetGeography(); invalidateSearch(); showStep(1); el('search-name').focus(); saveLocal();
    });
    el('search-businesses').addEventListener('click', () => search().catch(fail));
    [el('search-name'), el('search-city')].forEach(input => input.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); search().catch(fail); } }));
    el('new-business').addEventListener('click', async () => {
        if (!el('search-name').value.trim()) { el('search-status').textContent = 'ابتدا نام مکان تازه را بنویس تا بررسی کنیم قبلاً ثبت نشده باشد.'; el('search-name').focus(); return; }
        const query = `${el('search-name').value.trim()}|${el('search-city').value.trim()}`;
        if (searchedQuery !== query) {
            await search();
            if (searchedQuery === query) { el('search-status').textContent = 'نتایج را بررسی کن؛ اگر مکان یا شعبه‌ات نیست، «افزودن مکان جدید» را بزن.'; el('new-business').focus(); }
            return;
        }
        collect(); resetGeography(); state.payload.confirm_distinct = false; state.payload.name = el('search-name').value; state.payload.city = el('search-city').value;
        delete state.payload.business_id; delete state.payload.edit_review_id; delete state.payload.review_version; delete state.payload.correction_business_id;
        state.step = 2; populate(); changed(); navigate(2);
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
    const ratingLabels = ['امتیاز بدهید', 'خیلی بد', 'بد', 'متوسط', 'خوب', 'عالی'];
    form.addEventListener('mouseover', event => {
        const star = event.target.closest('[data-rating-value]');
        if (!star) return;
        const value = Number(star.dataset.ratingValue);
        form.querySelectorAll('[data-rating-value]').forEach(label => label.dataset.hovered = String(Number(label.dataset.ratingValue) <= value));
        el('rating-label').textContent = ratingLabels[value];
    });
    form.addEventListener('mouseout', event => {
        const star = event.target.closest('[data-rating-value]');
        if (!star || star.contains(event.relatedTarget)) return;
        form.querySelectorAll('[data-rating-value]').forEach(label => label.dataset.hovered = 'false');
        el('rating-label').textContent = ratingLabels[state.payload.rating || 0];
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
            if (state.photos.length >= 6) { fail({errors: {photo_ids: ['حداکثر شش عکس انتخاب کن. برای انتخاب عکس تازه، یکی از عکس‌ها را حذف کن.']}}); break; }
            try { const blob = await compress(file); state.photos.push({client_id: crypto.randomUUID(), blob, name: file.name, status:'selected', progress:0}); }
            catch(error) { fail({errors: {photo_ids: [error.message]}}); }
        }
        if (!state.payload.business_id && !state.payload.featured_photo_ids?.length && state.photos.length) state.payload.featured_photo_ids = [state.photos[0].client_id];
        collect(); renderPhotos(); showStep(state.step); await saveLocal();
        if (user) { await queueSave(); await uploadAll(); }
    }
    [el('gallery-input'), el('camera-input')].forEach(input => input.addEventListener('change', async () => { const files = [...input.files]; input.value = ''; try { await addPhotos(files); } catch(error) { fail(error); } }));
    const previewUrls = new Map();
    function renderPhotos() {
        el('photo-previews').replaceChildren();
        el('featured-photo-help').hidden = !!state.payload.business_id || !state.photos.length;
        for (const photo of state.photos) {
            const card = document.createElement('div'); card.className = photo.status === 'failed' ? 'rounded-xl border border-pomegranate bg-pomegranate/5 p-2' : 'rounded-xl border border-border p-2';
            const img = document.createElement('img'); img.className = 'h-28 w-full rounded-lg object-cover'; img.alt = 'پیش‌نمایش عکس انتخاب‌شده';
            if (photo.blob) { if (!previewUrls.has(photo.client_id)) previewUrls.set(photo.client_id, URL.createObjectURL(photo.blob)); img.src = previewUrls.get(photo.client_id); }
            else if (photo.server_id) img.src = `/media/${photo.server_id}?thumbnail=1`;
            const label = document.createElement('p'); label.className = photo.status === 'failed' ? 'my-2 text-xs font-semibold text-pomegranate-dark' : 'my-2 text-xs'; label.textContent = photo.status === 'uploaded' ? 'بارگذاری شد' : photo.status === 'failed' ? 'بارگذاری ناموفق' : photo.status === 'uploading' ? `بارگذاری ${photo.progress || 0}٪` : 'آماده بارگذاری';
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
            if (photo.status === 'failed') { const retry = document.createElement('button'); retry.type = 'button'; retry.className='button-secondary mt-2 w-full'; retry.textContent='تلاش دوباره'; retry.disabled = busy; retry.addEventListener('click', () => uploadPhoto(photo).catch(fail)); card.append(retry); }
            el('photo-previews').append(card);
        }
        if (state.step === 4) renderSummary();
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
    el('previous-step').addEventListener('click', () => {
        if (busy) return;
        const steps = contributionSteps(state.payload), index = steps.findIndex(item => item.id === state.step);
        if (index > 0) navigate(steps[index - 1].id);
    });
    function validateStep() {
        const errors = contributionErrors(state.payload, state.step);
        if ([2, 4].includes(state.step) && !state.payload.business_id && state.payload.city && ![...el('city-options').querySelectorAll('[data-city-option]')].some(option => option.value === state.payload.city)) errors.city = ['شهر را از گزینه‌های فهرست انتخاب کن.'];
        if (state.step === 4 && user && generic && !(state.payload.display_name || '').trim()) errors.display_name = ['نامی را وارد کن که کنار مشارکتت نمایش داده شود.'];
        if (Object.keys(errors).length) { fail({errors}); return false; }
        return true;
    }
    el('place-only')?.addEventListener('click', async () => {
        if (busy) return; clearErrors(); collect();
        if (!validateStep()) return;
        state.payload.with_review = false; form.elements.with_review.checked = false; showStep(3);
        changed(); await saveLocal(); el('review-heading').focus();
    });
    function setBusy(value) { busy = value; form.setAttribute('aria-busy', String(value)); form.querySelectorAll('input, textarea, select, button').forEach(input => input.disabled = value); renderPhotos(); if (value) el('next-step').textContent = 'در حال ثبت…'; else showStep(state.step); }
    el('next-step').addEventListener('click', async () => {
        if(busy || otpBusy) return; clearErrors(); collect();
        if(!validateStep()) return;
        if (state.step === 2) { state.payload.with_review = true; form.elements.with_review.checked = true; navigate(3); return; }
        if (state.step === 3) { navigate(4); return; }
        if(!user) { await saveLocal(); el('otp-panel').hidden=false; el('otp-mobile').focus(); return; }
        setBusy(true);
        try {
            clearTimeout(serverTimer); await saveLocal(); await queueSave(); await uploadAll();
            if(state.photos.some(p => p.status !== 'uploaded')) throw new Error('بارگذاری عکس‌ها را کامل کنید یا عکس ناموفق را حذف کنید.');
            const result = await api(`/contribution-drafts/${state.id}/submit`,{method:'POST',body:'{}'}); success(result);
        } catch(error) { fail(error); } finally { setBusy(false); }
    });
    function success(result) {
        state.submitted=true; state.photos=[]; clearTimeout(saveTimer); clearTimeout(serverTimer); clearErrors(); deleteLocalDraft(state.id).catch(() => {}); form.hidden=true; el('stepper').parentElement.hidden=true; el('resume-draft').hidden=true; el('draft-tools').hidden=true; el('contribution-success').hidden=false;
        el('success-heading').textContent = result.status === 'published' ? (state.payload.with_review ? 'تجربه‌ات منتشر شد' : 'مکان ثبت و منتشر شد') : result.status === 'pending' ? 'مشارکتت ثبت شد؛ منتظر بررسی است' : 'مشارکت نیازمند بررسی یا اصلاح است';
        el('success-copy').textContent = result.status === 'published' ? 'از به‌اشتراک‌گذاشتن تجربه‌تان سپاسگزاریم.' : 'وضعیت و پیام مدیریت را در «مشارکت‌های من» دنبال کنید.';
        el('success-link').href=result.url; el('success-link').textContent = result.status === 'published' ? 'مشاهده مکان' : 'پیگیری در مشارکت‌های من'; status(''); el('success-heading').focus();
    }
    let resendAt = 0, otpBusy = false;
    el('otp-mobile').addEventListener('input', () => { el('otp-code-panel').hidden = true; el('otp-code').value = ''; el('otp-status').textContent = ''; });
    setInterval(() => { const seconds = Math.max(0, Math.ceil((resendAt - Date.now()) / 1000)); el('send-otp').disabled = seconds > 0 || otpBusy || busy; el('send-otp').textContent = seconds ? `ارسال دوباره تا ${seconds.toLocaleString('fa-IR')} ثانیه` : 'دریافت کد ورود'; }, 1000);
    async function otpAction(button, callback) {
        if (otpBusy) return; otpBusy = true; el('otp-mobile').readOnly = true; button.disabled=true; el('otp-status').textContent = 'در حال بررسی…'; try { clearErrors(); await callback(); } catch(error) { el('otp-status').textContent = ''; fail(error); } finally { otpBusy = false; el('otp-mobile').readOnly = false; button.disabled=false; }
    }
    el('send-otp').addEventListener('click', () => { if (Date.now() < resendAt) return; return otpAction(el('send-otp'), async () => {
        await saveLocal(); await api('/login?contribute=1');
        const sent = await api('/login',{method:'POST',body:JSON.stringify({mobile:el('otp-mobile').value})});
        resendAt = Date.now() + sent.resend_after * 1000;
        el('otp-code-panel').hidden=false; el('otp-status').textContent=`در صورت مجاز بودن ورود، کد برای ${el('otp-mobile').value} ارسال می‌شود. کد پنج‌رقمی را وارد کن.`; el('otp-code').focus();
    }); });
    el('verify-otp').addEventListener('click', () => otpAction(el('verify-otp'), async () => {
        const authenticated = await api('/verify',{method:'POST',body:JSON.stringify({code:el('otp-code').value})});
        if(!authenticated.user_id) throw new Error('ورود کامل نشد؛ دوباره تلاش کنید.');
        document.querySelector('meta[name="csrf-token"]').content=authenticated.csrf_token;
        user=String(authenticated.user_id); generic=authenticated.needs_display_name; state.owner=user;
        el('otp-panel').hidden=true; el('otp-code').value=''; el('otp-mobile').value=''; showStep(4);
        el('verified-status').hidden = false;
        (generic ? form.elements.display_name : el('next-step')).focus();
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
    if (params.has('new')) {
        const currentUrl = new URL(location.href); currentUrl.searchParams.delete('new');
        history.replaceState(history.state, '', currentUrl);
    }
    populate(); initCalendar(form.elements.visit_date);
    window.addEventListener('beforeunload', event => { if(!state.submitted && hasMeaningfulDraft(state) && (!storageAvailable || uploadBusy || busy)) { event.preventDefault(); event.returnValue=''; } });
}
