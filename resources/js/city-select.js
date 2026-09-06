const normalize = value => String(value).replace(/[يى]/g, 'ی').replace(/ك/g, 'ک').replace(/[\s\u200c]+/g, ' ').trim().toLocaleLowerCase('fa-IR');

document.querySelectorAll('[data-city-select]').forEach(container => {
    const input = container.querySelector('[role="combobox"]');
    const menu = container.querySelector('[data-city-options]');
    const options = [...container.querySelectorAll('[data-city-option]')];
    const empty = container.querySelector('[data-city-empty]');
    let activeIndex = -1;

    const close = () => {
        menu.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
        options.forEach(option => option.setAttribute('aria-selected', 'false'));
    };

    const visibleOptions = () => options.filter(option => !option.hidden);
    const open = () => {
        const query = normalize(input.value);
        let count = 0;
        options.forEach(option => {
            const matches = !query || normalize(option.value).includes(query);
            option.hidden = !matches;
            if (matches) count++;
        });
        empty.hidden = count !== 0;
        menu.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
    };

    const choose = option => {
        input.value = option.value;
        input.dispatchEvent(new Event('input', {bubbles: true}));
        input.dispatchEvent(new Event('change', {bubbles: true}));
        close();
    };

    const activate = index => {
        const visible = visibleOptions();
        activeIndex = Math.max(0, Math.min(index, visible.length - 1));
        options.forEach(option => option.setAttribute('aria-selected', 'false'));
        if (visible[activeIndex]) {
            visible[activeIndex].setAttribute('aria-selected', 'true');
            visible[activeIndex].scrollIntoView({block: 'nearest'});
        }
    };

    input.addEventListener('focus', open);
    input.addEventListener('input', open);
    input.addEventListener('keydown', event => {
        const visible = visibleOptions();
        if (event.key === 'ArrowDown') { event.preventDefault(); open(); activate(activeIndex + 1); }
        if (event.key === 'ArrowUp') { event.preventDefault(); open(); activate(activeIndex - 1); }
        if (event.key === 'Enter' && activeIndex >= 0 && visible[activeIndex]) { event.preventDefault(); choose(visible[activeIndex]); }
        if (event.key === 'Escape') close();
    });
    options.forEach(option => option.addEventListener('mousedown', event => { event.preventDefault(); choose(option); }));
    document.addEventListener('mousedown', event => { if (!container.contains(event.target)) close(); });
});
