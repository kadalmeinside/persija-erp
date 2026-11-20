import { ref, onMounted } from 'vue';

// Shared state across all component instances
const isDark = ref(false);
const isInitialized = ref(false);

const applyThemeClass = (dark) => {
    isDark.value = dark;
    if (typeof document !== 'undefined') {
        const root = document.documentElement;
        if (dark) {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
    }
};

export function useThemeMode() {
    const initTheme = () => {
        if (typeof window === 'undefined') return;

        const stored = localStorage.getItem('theme-mode') || localStorage.getItem('theme');
        let shouldBeDark = false;

        if (stored) {
            shouldBeDark = stored === 'dark';
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            shouldBeDark = true;
        }

        applyThemeClass(shouldBeDark);
        isInitialized.value = true;
    };

    const toggleTheme = () => {
        const next = !isDark.value;
        applyThemeClass(next);
        const mode = next ? 'dark' : 'light';
        try {
            localStorage.setItem('theme-mode', mode);
            localStorage.setItem('theme', mode);
        } catch (e) {
            console.error('Failed to save theme to localStorage:', e);
        }
    };

    const setTheme = (mode) => {
        const dark = mode === 'dark';
        applyThemeClass(dark);
        try {
            localStorage.setItem('theme-mode', mode);
            localStorage.setItem('theme', mode);
        } catch (e) {
            console.error('Failed to save theme to localStorage:', e);
        }
    };

    onMounted(() => {
        if (!isInitialized.value) {
            initTheme();
        } else {
            // Keep reactive ref in sync with actual DOM state
            if (typeof document !== 'undefined') {
                isDark.value = document.documentElement.classList.contains('dark');
            }
        }
    });

    return {
        isDark,
        toggleTheme,
        setTheme,
        initTheme,
    };
}

