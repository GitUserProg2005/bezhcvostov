<script setup>
import { computed } from 'vue';

const props = defineProps({
    output: {
        type: Object,
        default: () => ({}),
    },
    fallbackText: {
        type: String,
        default: '',
    },
});

const text = computed(() => {
    const value = props.output?.text;
    if (typeof value === 'string' && value.trim() !== '') {
        return value.trim();
    }

    return props.fallbackText || '';
});

const link = computed(() => {
    const value = props.output?.link;
    return typeof value === 'string' && value.trim() !== '' ? value.trim() : null;
});

const buttons = computed(() => {
    const list = Array.isArray(props.output?.buttons) ? props.output.buttons : [];

    return list
        .filter((item) => item && typeof item === 'object')
        .map((item) => ({
            title: typeof item.title === 'string' ? item.title.trim() : '',
            link: typeof item.link === 'string' ? item.link.trim() : '',
        }))
        .filter((item) => item.title !== '' && item.link !== '');
});

const resolveHref = (value) => {
    if (!value) {
        return '#';
    }

    if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) {
        return value;
    }

    if (typeof route === 'function') {
        try {
            return route(value);
        } catch (error) {
            return '#';
        }
    }

    return '#';
};
</script>

<template>
    <div class="flex flex-col gap-2">
        <p v-if="text" class="whitespace-pre-wrap break-words text-sm">
            {{ text }}
        </p>

        <a
            v-if="link"
            :href="resolveHref(link)"
            class="context underline decoration-dotted w-fit"
        >
            {{ link }}
        </a>

        <div v-if="buttons.length" class="flex flex-wrap gap-2 pt-1">
            <a
                v-for="(button, index) in buttons"
                :key="`${button.title}-${index}`"
                :href="resolveHref(button.link)"
                class="px-3 py-1 rounded-full text-xs font-semibold bg-content-outline"
            >
                {{ button.title }}
            </a>
        </div>
    </div>
</template>
