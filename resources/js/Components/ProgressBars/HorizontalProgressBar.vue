<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: {
        type: [Number, String],
        default: 0,
    },
    color: {
        type: String,
        default: '#276d4c',
    },
    trackColor: {
        type: String,
        default: 'rgba(0,0,0,0.08)',
    },
    height: {
        type: [Number, String],
        default: 6,
    },
    rounded: {
        type: [Number, String],
        default: 9999,
    },
});

const clamped = computed(() => Math.min(100, Math.max(0, Number(props.value) || 0)));
const heightPx = computed(() => (typeof props.height === 'number' ? `${props.height}px` : props.height));
const radiusPx = computed(() => (typeof props.rounded === 'number' ? `${props.rounded}px` : props.rounded));

const trackStyle = computed(() => ({
    height: heightPx.value,
    borderRadius: radiusPx.value,
    background: props.trackColor,
}));

const fillStyle = computed(() => ({
    width: `${clamped.value}%`,
    background: props.color,
    borderRadius: radiusPx.value,
}));
</script>

<template>
    <div class="hp-track" :style="trackStyle" role="progressbar" :aria-valuenow="clamped" aria-valuemin="0" aria-valuemax="100">
        <div class="hp-fill" :style="fillStyle" />
    </div>
</template>

<style scoped>
.hp-track {
    position: relative;
    width: 100%;
    overflow: hidden;
}

.hp-fill {
    height: 100%;
    transition: width 240ms ease;
}
</style>
