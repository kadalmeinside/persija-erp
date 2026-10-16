<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import {
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    message: {
        type: String,
        default: null,
    },
    title: {
        type: String,
        default: null,
    },
    type: {
        type: String,
        default: 'info',
    },
    duration: {
        type: Number,
        default: 4000,
    },
});

const { toasts, removeToast, show } = useToast();

// If props are provided directly to component (backward compatibility)
watch(
    () => props.message,
    (newVal) => {
        if (newVal) {
            show({
                title: props.title,
                message: newVal,
                type: props.type,
                duration: props.duration,
            });
        }
    },
    { immediate: true }
);

// Manage active timers with pause/resume support
const timers = new Map();

const startTimer = (toast) => {
    if (!toast || toast.duration <= 0) return;
    if (timers.has(toast.id)) return;

    const startTime = Date.now();
    const remainingTime = toast.remainingTime ?? toast.duration;

    const timeoutId = setTimeout(() => {
        timers.delete(toast.id);
        removeToast(toast.id);
    }, remainingTime);

    timers.set(toast.id, {
        timeoutId,
        startTime,
        remainingTime,
        paused: false,
    });
};

const pauseTimer = (toast) => {
    const timerData = timers.get(toast.id);
    if (!timerData || timerData.paused) return;

    clearTimeout(timerData.timeoutId);
    const elapsed = Date.now() - timerData.startTime;
    timerData.remainingTime = Math.max(0, timerData.remainingTime - elapsed);
    timerData.paused = true;
};

const resumeTimer = (toast) => {
    const timerData = timers.get(toast.id);
    if (!timerData || !timerData.paused) return;

    timerData.startTime = Date.now();
    timerData.paused = false;
    timerData.timeoutId = setTimeout(() => {
        timers.delete(toast.id);
        removeToast(toast.id);
    }, timerData.remainingTime);
};

// Start timers for incoming toasts
watch(
    () => [...toasts.value],
    (currentToasts, oldToasts) => {
        currentToasts.forEach((t) => {
            if (!timers.has(t.id)) {
                startTimer(t);
            }
        });

        // Clean up removed toasts
        const currentIds = new Set(currentToasts.map((t) => t.id));
        for (const [id, timerData] of timers.entries()) {
            if (!currentIds.has(id)) {
                clearTimeout(timerData.timeoutId);
                timers.delete(id);
            }
        }
    },
    { deep: true, immediate: true }
);

onUnmounted(() => {
    for (const timerData of timers.values()) {
        clearTimeout(timerData.timeoutId);
    }
    timers.clear();
});

const isPaused = (id) => timers.get(id)?.paused ?? false;

const typeConfig = {
    success: {
        containerClass:
            'bg-white/95 dark:bg-slate-900/95 border-emerald-500/30 dark:border-emerald-500/40 shadow-emerald-500/10 text-slate-800 dark:text-slate-100',
        iconBgClass:
            'bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/50',
        progressBarClass: 'bg-emerald-500 dark:bg-emerald-400',
        icon: CheckCircleIcon,
    },
    error: {
        containerClass:
            'bg-white/95 dark:bg-slate-900/95 border-rose-500/30 dark:border-rose-500/40 shadow-rose-500/10 text-slate-800 dark:text-slate-100',
        iconBgClass:
            'bg-rose-50 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400 border border-rose-200/60 dark:border-rose-800/50',
        progressBarClass: 'bg-rose-500 dark:bg-rose-400',
        icon: XCircleIcon,
    },
    warning: {
        containerClass:
            'bg-white/95 dark:bg-slate-900/95 border-amber-500/30 dark:border-amber-500/40 shadow-amber-500/10 text-slate-800 dark:text-slate-100',
        iconBgClass:
            'bg-amber-50 dark:bg-amber-950/70 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/50',
        progressBarClass: 'bg-amber-500 dark:bg-amber-400',
        icon: ExclamationTriangleIcon,
    },
    info: {
        containerClass:
            'bg-white/95 dark:bg-slate-900/95 border-sky-500/30 dark:border-sky-500/40 shadow-sky-500/10 text-slate-800 dark:text-slate-100',
        iconBgClass:
            'bg-sky-50 dark:bg-sky-950/70 text-sky-600 dark:text-sky-400 border border-sky-200/60 dark:border-sky-800/50',
        progressBarClass: 'bg-sky-500 dark:bg-sky-400',
        icon: InformationCircleIcon,
    },
};
</script>

<template>
    <div
        aria-live="assertive"
        class="fixed top-4 right-4 z-[9999] flex flex-col gap-2.5 max-w-sm sm:max-w-md w-full pointer-events-none px-3 sm:px-0"
    >
        <TransitionGroup
            enter-active-class="transition-all duration-300 ease-out transform"
            enter-from-class="opacity-0 -translate-y-4 sm:translate-y-0 sm:translate-x-8 scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
            leave-active-class="transition-all duration-250 ease-in transform"
            leave-from-class="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
            leave-to-class="opacity-0 -translate-y-4 sm:translate-y-0 sm:translate-x-8 scale-95"
            move-class="transition-all duration-300 ease-out"
        >
            <div
                v-for="t in toasts"
                :key="t.id"
                @mouseenter="pauseTimer(t)"
                @mouseleave="resumeTimer(t)"
                :class="[
                    typeConfig[t.type]?.containerClass || typeConfig.info.containerClass,
                    'pointer-events-auto relative overflow-hidden rounded-2xl border shadow-xl backdrop-blur-md p-4 transition-shadow hover:shadow-2xl'
                ]"
                role="status"
            >
                <div class="flex items-start gap-3">
                    <!-- Icon Badge -->
                    <div
                        :class="[
                            typeConfig[t.type]?.iconBgClass || typeConfig.info.iconBgClass,
                            'flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center shadow-sm'
                        ]"
                    >
                        <component
                            :is="typeConfig[t.type]?.icon || InformationCircleIcon"
                            class="w-5 h-5 stroke-[2.2]"
                        />
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0 pt-0.5">
                        <h4
                            v-if="t.title"
                            class="text-sm font-semibold text-slate-900 dark:text-white leading-snug"
                        >
                            {{ t.title }}
                        </h4>
                        <p
                            v-if="t.message"
                            class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mt-0.5 break-words"
                        >
                            {{ t.message }}
                        </p>
                    </div>

                    <!-- Close Button -->
                    <button
                        type="button"
                        @click="removeToast(t.id)"
                        class="flex-shrink-0 -mr-1 -mt-1 p-1 text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-400"
                        title="Tutup Notifikasi"
                    >
                        <span class="sr-only">Tutup</span>
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <!-- Progress Timer Bar -->
                <div
                    v-if="t.duration > 0"
                    class="absolute bottom-0 left-0 right-0 h-1 bg-slate-100/70 dark:bg-slate-800/70 overflow-hidden"
                >
                    <div
                        :class="[
                            typeConfig[t.type]?.progressBarClass || typeConfig.info.progressBarClass,
                            'h-full origin-left will-change-transform toast-progress-bar'
                        ]"
                        :style="{
                            animationDuration: `${t.duration}ms`,
                            animationPlayState: isPaused(t.id) ? 'paused' : 'running',
                        }"
                    ></div>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
@keyframes toast-progress {
    from {
        transform: scaleX(1);
    }
    to {
        transform: scaleX(0);
    }
}

.toast-progress-bar {
    animation-name: toast-progress;
    animation-timing-function: linear;
    animation-fill-mode: forwards;
}
</style>