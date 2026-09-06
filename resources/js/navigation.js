document.querySelectorAll('[data-profile-menu]').forEach(menu => {
    document.addEventListener('click', event => {
        if (menu.open && !menu.contains(event.target)) menu.open = false;
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && menu.open) {
            menu.open = false;
            menu.querySelector('summary').focus();
        }
    });
    menu.addEventListener('focusout', () => {
        requestAnimationFrame(() => {
            if (!menu.contains(document.activeElement)) menu.open = false;
        });
    });
});
