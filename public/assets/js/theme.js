(() => {
    const storageKey = 'fioreee-theme';
    const root = document.documentElement;

    const readTheme = () => {
        try {
            return localStorage.getItem(storageKey) === 'dark' ? 'dark' : 'light';
        } catch (error) {
            return root.dataset.theme === 'dark' ? 'dark' : 'light';
        }
    };

    const saveTheme = theme => {
        try {
            localStorage.setItem(storageKey, theme);
        } catch (error) {
            // El cambio sigue activo durante la visita aunque no pueda guardarse.
        }
    };

    const updateButtons = theme => {
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            const dark = theme === 'dark';
            const icon = button.querySelector('[data-theme-icon]');
            const label = button.querySelector('[data-theme-label]');
            if (icon) icon.textContent = dark ? '☀' : '☾';
            if (label) label.textContent = dark ? 'Claro' : 'Oscuro';
            button.setAttribute('aria-label', dark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro');
            button.setAttribute('title', dark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro');
        });
    };

    const applyTheme = theme => {
        if (theme === 'dark') {
            root.dataset.theme = 'dark';
        } else {
            delete root.dataset.theme;
        }

        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.setAttribute('content', theme === 'dark' ? '#0b0f17' : '#111827');
        updateButtons(theme);
    };

    const buildButton = () => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-light btn-sm theme-toggle';
        button.dataset.themeToggle = '';
        button.innerHTML = '<span class="theme-toggle-icon" data-theme-icon>☾</span><span class="theme-toggle-label" data-theme-label>Oscuro</span>';
        button.addEventListener('click', () => {
            const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            saveTheme(nextTheme);
            applyTheme(nextTheme);
        });
        return button;
    };

    document.addEventListener('DOMContentLoaded', () => {
        const button = buildButton();
        const publicActions = document.querySelector('.header-actions');
        const adminTopbar = document.querySelector('.admin-topbar');
        const loginBox = document.querySelector('.login-box');

        if (publicActions) {
            publicActions.prepend(button);
        } else if (adminTopbar) {
            const lastAction = adminTopbar.lastElementChild;
            adminTopbar.insertBefore(button, lastAction || null);
        } else if (loginBox) {
            loginBox.append(button);
        }

        applyTheme(readTheme());
    });
})();
