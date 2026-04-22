(function () {
    const storageKey = 'premium-theme';
    const systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');
    let theme = systemThemeQuery.matches ? 'dark' : 'light';
    let themeSource = 'system';

    try {
        const storedTheme = localStorage.getItem(storageKey);
        if (storedTheme === 'light' || storedTheme === 'dark') {
            theme = storedTheme;
            themeSource = 'stored';
        }
    } catch (error) {
        // Keep the system theme when storage is unavailable.
    }

    document.documentElement.dataset.theme = theme;
    document.documentElement.dataset.themeSource = themeSource;
    document.documentElement.style.colorScheme = theme;
}());
