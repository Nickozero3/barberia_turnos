document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.querySelector('.sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }

    document.querySelectorAll('[data-confirm]').forEach(element => {
        element.addEventListener('click', event => {
            if (!window.confirm(element.dataset.confirm || '¿Confirmar esta acción?')) {
                event.preventDefault();
            }
        });
    });
});
