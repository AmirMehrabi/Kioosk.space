const englishDigits = (value) => value.replace(/[۰-۹]/g, digit => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))).replace(/[٠-٩]/g, digit => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)));
const persianNumber = new Intl.NumberFormat('fa-IR', { useGrouping: false });

for (const input of document.querySelectorAll('[data-mobile-input], [data-otp-input]')) {
    const normalize = value => input.hasAttribute('data-otp-input')
        ? englishDigits(value).replace(/[^0-9]/g, '').slice(0, 5)
        : englishDigits(value).replace(/[\s\u200c\u200e\u200f\u202a-\u202e]/g, '');
    input.addEventListener('input', () => { input.value = normalize(input.value); });
    input.addEventListener('paste', event => {
        event.preventDefault();
        input.value = normalize(event.clipboardData.getData('text'));
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
}

const resend = document.querySelector('[data-resend-at]');
const expiry = document.querySelector('[data-expiry]');
const refreshCountdown = () => {
    if (resend && !resend.dataset.submitting) {
        const remaining = Math.max(0, Number(resend.dataset.resendAt) - Math.floor(Date.now() / 1000));
        resend.disabled = remaining > 0;
        resend.textContent = remaining > 0 ? `ارسال دوباره تا ${persianNumber.format(remaining)} ثانیه` : 'ارسال دوباره کد';
    }
    if (expiry && Number(expiry.dataset.expiry) <= Date.now() / 1000) {
        expiry.textContent = 'مهلت این کد تمام شده است. کد جدید دریافت کنید.';
    }
};
if (resend || expiry) {
    refreshCountdown();
    setInterval(refreshCountdown, 1000);
}

for (const form of document.querySelectorAll('[data-auth-form]')) {
    form.addEventListener('submit', event => {
        if (form.dataset.submitting) { event.preventDefault(); return; }
        form.dataset.submitting = 'true';
        const button = form.querySelector('button[type="submit"]');
        button.dataset.originalLabel = button.textContent;
        button.dataset.submitting = 'true';
        button.disabled = true;
        button.textContent = button.dataset.submitLabel;
    });
}
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-auth-form]').forEach(form => {
        delete form.dataset.submitting;
        const button = form.querySelector('button[type="submit"]');
        delete button.dataset.submitting;
        button.disabled = false;
        if (button.dataset.originalLabel) button.textContent = button.dataset.originalLabel;
    });
    refreshCountdown();
});
