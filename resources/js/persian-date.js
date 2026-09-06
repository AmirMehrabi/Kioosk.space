const digits = value => String(value).replace(/[۰-۹٠-٩]/g, digit => '۰۱۲۳۴۵۶۷۸۹'.includes(digit) ? '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit) : '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
const persian = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { timeZone: 'Asia/Tehran', year: 'numeric', month: '2-digit', day: '2-digit' });
const civil = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Tehran', year: 'numeric', month: '2-digit', day: '2-digit' });
export function civilDate(date = new Date()) {
    const p = Object.fromEntries(civil.formatToParts(date).map(p => [p.type, p.value]));
    return `${p.year}-${p.month}-${p.day}`;
}
export function jalaliParts(date = new Date()) {
    const p = Object.fromEntries(persian.formatToParts(date).map(p => [p.type, Number(digits(p.value))]));
    return { year: p.year, month: p.month, day: p.day };
}
export function formatVisit(date) { return persian.format(new Date(`${date}T12:00:00+03:30`)); }
export function initCalendar(input) {
    const dialog = document.getElementById('jalali-calendar');
    const year = document.getElementById('calendar-year'), month = document.getElementById('calendar-month'), days = document.getElementById('calendar-days');
    const now = jalaliParts();
    const months = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    for (let y = now.year; y >= 1300; y--) year.add(new Option(y.toLocaleString('fa-IR', {useGrouping: false}), y));
    months.forEach((m, i) => month.add(new Option(m, i + 1)));
    function render() {
        days.replaceChildren();
        const start = Date.UTC(Number(year.value) + 621, 2, 15, 12);
        for (let i = 0; i < 380; i++) {
            const date = new Date(start + i * 86400000), p = jalaliParts(date);
            if (p.year !== Number(year.value) || p.month !== Number(month.value)) continue;
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'button-secondary px-1'; button.textContent = p.day.toLocaleString('fa-IR');
            button.disabled = civilDate(date) > civilDate();
            button.addEventListener('click', () => { input.value = `${p.year}/${String(p.month).padStart(2, '0')}/${String(p.day).padStart(2, '0')}`.replace(/\d/g, digit => '۰۱۲۳۴۵۶۷۸۹'[digit]); input.dispatchEvent(new Event('input', { bubbles: true })); dialog.close(); });
            days.append(button);
        }
    }
    document.getElementById('open-calendar').addEventListener('click', () => {
        const parts = digits(input.value).split(/[/-]/).map(Number);
        year.value = parts[0] || now.year; month.value = parts[1] || now.month; render(); dialog.showModal();
    });
    year.addEventListener('change', render); month.addEventListener('change', render);
    document.getElementById('close-calendar').addEventListener('click', () => dialog.close());
}
