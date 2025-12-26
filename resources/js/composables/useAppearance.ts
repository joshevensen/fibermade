export function updateTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Always remove dark class to ensure light mode
    document.documentElement.classList.remove('dark');
}

export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Always force light mode
    updateTheme();
}
