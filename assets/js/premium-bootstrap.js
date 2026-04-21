(function () {
    try {
        const storedTheme = localStorage.getItem('premium-theme');
        const theme = storedTheme === 'light' ? 'light' : 'dark';
        document.documentElement.dataset.theme = theme;
        document.documentElement.style.colorScheme = theme;
    } catch (error) {
        document.documentElement.dataset.theme = 'dark';
        document.documentElement.style.colorScheme = 'dark';
    }
}());