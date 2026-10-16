import { ref } from 'vue';

const toasts = ref([]);

export function useToast() {
    const removeToast = (id) => {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    };

    const show = ({ title, message, type = 'success', duration = 4000 }) => {
        if (!message && !title) return null;

        let normalizedType = type || 'success';
        if (normalizedType === 'danger') normalizedType = 'error';
        if (!['success', 'error', 'warning', 'info'].includes(normalizedType)) {
            normalizedType = 'info';
        }

        const msgText = (message || '').toString().trim();
        const titleText = title ? title.toString().trim() : (
            normalizedType === 'success' ? 'Berhasil' :
            normalizedType === 'error' ? 'Gagal' :
            normalizedType === 'warning' ? 'Perhatian' : 'Informasi'
        );

        // Anti-duplicate guard: If an identical toast is already active, ignore or refresh timer
        const existing = toasts.value.find(
            (t) => t.message === msgText && t.type === normalizedType
        );
        if (existing) {
            existing.timestamp = Date.now();
            return existing.id;
        }

        const id = 'toast_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);

        const newToast = {
            id,
            title: titleText,
            message: msgText,
            type: normalizedType,
            duration: duration > 0 ? duration : 4000,
            timestamp: Date.now(),
        };

        toasts.value.push(newToast);
        return id;
    };

    const success = (message, title) => show({ title, message, type: 'success' });
    const error = (message, title) => show({ title, message, type: 'error' });
    const warning = (message, title) => show({ title, message, type: 'warning' });
    const info = (message, title) => show({ title, message, type: 'info' });

    const clear = () => {
        toasts.value = [];
    };

    return {
        toasts,
        show,
        success,
        error,
        warning,
        info,
        removeToast,
        clear,
    };
}
