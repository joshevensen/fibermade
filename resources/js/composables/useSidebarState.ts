import { onMounted, ref, watch } from 'vue';

export function useSidebarState() {
    const collapsed = ref<boolean>(false);

    const getCookie = (name: string): string | null => {
        if (typeof document === 'undefined') {
            return null;
        }

        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);

        if (parts.length === 2) {
            return parts.pop()?.split(';').shift() || null;
        }

        return null;
    };

    const setCookie = (name: string, value: string, days = 365): void => {
        if (typeof document === 'undefined') {
            return;
        }

        const maxAge = days * 24 * 60 * 60;

        document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
    };

    onMounted(() => {
        const savedState = getCookie('sidebar_state');

        if (savedState === 'collapsed') {
            collapsed.value = true;
        } else if (savedState === 'expanded') {
            collapsed.value = false;
        }
    });

    watch(collapsed, (newValue) => {
        setCookie('sidebar_state', newValue ? 'collapsed' : 'expanded');
    });

    return {
        collapsed,
    };
}
