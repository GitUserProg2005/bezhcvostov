<script setup>
import { computed, onMounted, ref } from 'vue';

const STORAGE_KEY = 'stratix-theme';

const isDark = ref(false);

function readStoredTheme() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'dark' || stored === 'light') {
        return stored;
    }
    if (typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return 'dark';
    }
    return 'light';
}

function applyToDocument(theme) {
    const root = document.documentElement;
    if (theme === 'dark') {
        root.classList.add('dark');
    } else {
        root.classList.remove('dark');
    }
    isDark.value = theme === 'dark';
}

onMounted(() => {
    applyToDocument(readStoredTheme());
});

function toggle() {
    const next = isDark.value ? 'light' : 'dark';
    localStorage.setItem(STORAGE_KEY, next);
    applyToDocument(next);
}

const label = computed(() => (isDark.value ? 'Светлая тема' : 'Тёмная тема'));
</script>

<template>
    <!-- Вне #app: иначе fixed обрезается overflow/transform у предков Inertia -->
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 bottom-6 z-[9999] flex justify-center px-4"
            aria-live="polite"
        >
        <button
            type="button"
            class="pointer-events-auto inline-flex items-center gap-3 rounded-full border px-5 py-3 text-sm font-semibold shadow-lg transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
            style="
                background-color: color-mix(in srgb, var(--bg-main, #fff) 88%, transparent);
                color: var(--content-primary, #1a1a1a);
                border-color: color-mix(in srgb, var(--content-primary, #1a1a1a) 12%, transparent);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                --tw-ring-color: var(--accent, #e97358);
                --tw-ring-offset-color: var(--bg-main, #fff);
            "
            :aria-label="label"
            @click="toggle"
        >
            <span
                class="flex h-9 w-9 items-center justify-center rounded-full"
                style="background-color: color-mix(in srgb, var(--accent, #e97358) 14%, transparent); color: var(--accent, #e97358);"
            >
                <i
                    v-if="isDark"
                    class="fa-solid fa-sun text-base"
                    aria-hidden="true"
                />
                <i
                    v-else
                    class="fa-solid fa-moon text-base"
                    aria-hidden="true"
                />
            </span>
            <span class="hidden sm:inline">{{ isDark ? 'Светлая' : 'Тёмная' }}</span>
        </button>
        </div>
    </Teleport>
</template>
