(function(){
    const saved = localStorage.getItem('archivio_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', saved);

    function makeButton(){
        if(document.getElementById('themeToggle')) return;

        const btn = document.createElement('button');
        btn.id = 'themeToggle';
        btn.className = 'theme-toggle';
        btn.type = 'button';

        function refresh(){
            const theme = document.documentElement.getAttribute('data-theme') || 'dark';
            btn.textContent = theme === 'light' ? '🌙 Dark' : '☀️ Light';
        }

        btn.onclick = function(){
            const current = document.documentElement.getAttribute('data-theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('archivio_theme', next);
            refresh();
        };

        refresh();
        document.body.appendChild(btn);
    }

    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', makeButton);
    } else {
        makeButton();
    }
})();
