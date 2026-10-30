'use strict';

(() => {
    const menu = document.querySelector('.chi-nhanh-module #initial-branch-menu');
    if (!menu) return;
    const toggle = menu.querySelector('[data-menu-select-all]');
    const label = menu.querySelector('[data-menu-select-all-label]');
    const dishes = Array.from(menu.querySelectorAll('input[name="menu[]"]')).filter(input => !input.disabled);
    if (!toggle || !label || !dishes.length) return;

    const sync = () => {
        const selected = dishes.filter(input => input.checked).length;
        toggle.checked = selected === dishes.length;
        toggle.indeterminate = selected > 0 && selected < dishes.length;
    };
    toggle.addEventListener('change', () => {
        dishes.forEach(input => { input.checked = toggle.checked; });
        sync();
    });
    dishes.forEach(input => input.addEventListener('change', sync));
    menu.closest('form')?.addEventListener('reset', () => setTimeout(sync, 0));
    sync();
    label.hidden = false;
})();

document.querySelectorAll('.chi-nhanh-module [data-material-select]').forEach(select => {
    select.addEventListener('change', () => select.form.requestSubmit());
});

