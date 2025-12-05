document.addEventListener('DOMContentLoaded', () => {
    const themeSwitcher = document.createElement('button');
    themeSwitcher.innerText = '🌙';
    themeSwitcher.style.position = 'fixed';
    themeSwitcher.style.top = '1rem';
    themeSwitcher.style.right = '1rem';
    themeSwitcher.style.fontSize = '1.5rem';
    themeSwitcher.style.background = 'none';
    themeSwitcher.style.border = 'none';
    themeSwitcher.style.cursor = 'pointer';
    document.body.appendChild(themeSwitcher);

    const currentTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
    themeSwitcher.innerText = currentTheme === 'light' ? '🌙' : '☀️';

    themeSwitcher.addEventListener('click', () => {
        let theme = document.documentElement.getAttribute('data-theme');
        if (theme === 'light') {
            theme = 'dark';
            themeSwitcher.innerText = '☀️';
        } else {
            theme = 'light';
            themeSwitcher.innerText = '🌙';
        }
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
    });
});
