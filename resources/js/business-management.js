const manager = document.getElementById('business-manager');

if (manager) {
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    const renumberRows = (list, type) => list.querySelectorAll('[data-repeat-row]').forEach((row, index) => row.querySelectorAll('input').forEach(input => {
        input.name = input.name.replace(new RegExp(`${type}\\[\\d+\\]`), `${type}[${index}]`);
    }));
    const renumberShifts = day => day.querySelectorAll('[data-shift]').forEach((shift, index) => shift.querySelectorAll('input').forEach(input => {
        input.name = input.name.replace(/\[shifts\]\[\d+\]/, `[shifts][${index}]`);
    }));

    document.querySelectorAll('[data-add-row]').forEach(button => button.addEventListener('click', () => {
        const type = button.dataset.addRow;
        const list = document.querySelector(`[data-repeat-list="${type}"]`);
        if (list.children.length >= 5) return;
        const row = document.createElement('div'); row.dataset.repeatRow = ''; row.className = 'grid gap-2 sm:grid-cols-[10rem_1fr_auto]';
        const label = document.createElement('input'); label.className = 'field !mt-0'; label.placeholder = 'عنوان';
        const value = document.createElement('input'); value.className = 'field !mt-0'; value.dir = 'ltr'; value.placeholder = type === 'phones' ? 'شماره' : 'https://';
        label.name = `${type}[${list.children.length}][label]`; value.name = `${type}[${list.children.length}][${type === 'phones' ? 'value' : 'url'}]`;
        const remove = document.createElement('button'); remove.type = 'button'; remove.dataset.removeRow = ''; remove.className = 'button-secondary'; remove.textContent = 'حذف';
        row.append(label, value, remove); list.append(row); label.focus();
    }));

    function addWorkingShift(day) {
        const list = day.querySelector('[data-shifts]');
        if (list.children.length >= 4) return;
        day.querySelector('[data-closed]').checked = false;
        const name = `weekly_hours[${day.dataset.day}][shifts][${list.children.length}]`;
        const row = document.createElement('div'); row.dataset.shift = ''; row.className = 'flex flex-wrap items-end gap-2';
        row.innerHTML = `<label>از<input type="time" class="field !mt-1" name="${name}[opens]" value="09:00"></label><label>تا<input type="time" class="field !mt-1" name="${name}[closes]" value="17:00"></label><label class="mb-3 flex gap-2"><input type="hidden" name="${name}[next_day]" value="0"><input type="checkbox" name="${name}[next_day]" value="1"> روز بعد</label><button type="button" data-remove-shift class="button-secondary">حذف</button>`;
        list.append(row);
    }

    function addFeatured(card) {
        const order = document.getElementById('featured-order'); const id = card.dataset.galleryId;
        if (order.children.length >= 5 || order.querySelector(`[data-featured-id="${id}"]`)) return;
        const item = document.createElement('div'); item.dataset.featuredId = id; item.className = 'w-32 rounded-xl border-2 border-pomegranate p-2';
        item.innerHTML = `<img class="h-20 w-full rounded-lg object-cover" src="${card.querySelector('img').src}" alt=""><input type="hidden" name="featured_media_ids[]" value="${id}"><div class="mt-2 flex justify-between"><button type="button" data-move="-1" aria-label="عقب">→</button><button type="button" data-unfeature>حذف</button><button type="button" data-move="1" aria-label="جلو">←</button></div>`;
        order.append(item);
    }

    document.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove-row]');
        if (remove) { const list = remove.closest('[data-repeat-list]'); remove.closest('[data-repeat-row]').remove(); renumberRows(list, list.dataset.repeatList); }
        const removeShift = event.target.closest('[data-remove-shift]');
        if (removeShift) { const day = removeShift.closest('[data-day]'); removeShift.closest('[data-shift]').remove(); renumberShifts(day); }
        const addShift = event.target.closest('[data-add-shift]'); if (addShift) addWorkingShift(addShift.closest('[data-day]'));
        const unfeature = event.target.closest('[data-unfeature]'); if (unfeature) unfeature.closest('[data-featured-id]').remove();
        const feature = event.target.closest('[data-feature]'); if (feature) addFeatured(feature.closest('[data-gallery-id]'));
        const move = event.target.closest('[data-move]');
        if (move) { const item = move.closest('[data-featured-id]'); if (Number(move.dataset.move) < 0 && item.previousElementSibling) item.parentElement.insertBefore(item, item.previousElementSibling); if (Number(move.dataset.move) > 0 && item.nextElementSibling) item.parentElement.insertBefore(item.nextElementSibling, item); }
        const deletePhoto = event.target.closest('[data-delete-photo]'); if (deletePhoto) deleteManagementPhoto(deletePhoto);
    });
    document.querySelectorAll('[data-closed]').forEach(checkbox => checkbox.addEventListener('change', () => {
        const day = checkbox.closest('[data-day]'); day.querySelector('[data-shifts]').classList.toggle('opacity-40', checkbox.checked); if (!checkbox.checked && !day.querySelector('[data-shift]')) addWorkingShift(day);
    }));

    async function deleteManagementPhoto(button) {
        if (!confirm('این تصویر مدیریتی حذف شود؟')) return;
        const response = await fetch(`${manager.dataset.deleteBase}/${button.dataset.deletePhoto}`, {method:'DELETE', headers:{Accept:'application/json','X-CSRF-TOKEN':csrf()}});
        if (response.ok) location.reload(); else alert('حذف تصویر انجام نشد.');
    }
    document.getElementById('management-photo')?.addEventListener('change', async event => {
        const file = event.target.files[0]; if (!file) return;
        const status = document.getElementById('management-photo-status'); status.textContent = 'در حال بارگذاری…';
        const data = new FormData(); data.append('photo', file);
        const response = await fetch(manager.dataset.uploadUrl, {method:'POST', body:data, headers:{Accept:'application/json','X-CSRF-TOKEN':csrf()}});
        status.textContent = response.ok ? 'تصویر بارگذاری شد.' : 'بارگذاری انجام نشد؛ نوع و اندازه فایل را بررسی کنید.';
        if (response.ok) location.reload();
    });
}
