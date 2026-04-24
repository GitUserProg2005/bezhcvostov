<script setup>
import { computed } from 'vue';

const props = defineProps({
    emotion: {
        type: String,
        default: 'hello',
    },
});

const allowed = new Set([
    'hello',
    'jump',
    'maskot_with_pen',
    'with_notebook',
    'with_panel',
]);

const imageSrc = computed(() => {
    const value = typeof props.emotion === 'string' ? props.emotion.trim() : '';
    const emotion = allowed.has(value) ? value : 'hello';

    return `/img/maskot/${emotion}.png`;
});
</script>

<template>
    <img
        :src="imageSrc"
        alt="Mascot emotion"
        class="w-6 rounded-full object-contain shrink-0"
    >
</template>
