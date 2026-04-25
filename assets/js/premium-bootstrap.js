(function () {
    const storageKey = 'premium-theme';
    let theme = 'dark';
    let themeSource = 'default';

    try {
        const storedTheme = localStorage.getItem(storageKey);
        if (storedTheme === 'light' || storedTheme === 'dark') {
            theme = storedTheme;
            themeSource = 'stored';
        }
    } catch (error) {
        // Keep the default dark theme when storage is unavailable.
    }

    document.documentElement.dataset.theme = theme;
    document.documentElement.dataset.themeSource = themeSource;
    document.documentElement.style.colorScheme = theme;
}());
