(function () {
    const STORAGE_KEY = 'archivio_theme';
    const DEFAULT_THEME = 'dark';

    function getSavedTheme() {
        try {
            return localStorage.getItem(STORAGE_KEY) || DEFAULT_THEME;
        } catch (e) {
            return DEFAULT_THEME;
        }
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {}
    }

    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || DEFAULT_THEME;
        const next = current === 'dark' ? 'light' : 'dark';
        setTheme(next);
        refreshButton();
    }

    function refreshButton() {
        const btn = document.getElementById('themeToggle');
        if (!btn) return;

        const theme = document.documentElement.getAttribute('data-theme') || DEFAULT_THEME;
        btn.textContent = theme === 'light' ? '🌙 Dark' : '☀️ Light';
    }

    function createButton() {
        if (document.getElementById('themeToggle')) return;

        const btn = document.createElement('button');
        btn.id = 'themeToggle';
        btn.className = 'theme-toggle btn';
        btn.type = 'button';

        btn.addEventListener('click', toggleTheme);

        refreshButton();
        document.body.appendChild(btn);
    }

    function init() {
        const savedTheme = getSavedTheme();
        setTheme(savedTheme);
        createButton();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
