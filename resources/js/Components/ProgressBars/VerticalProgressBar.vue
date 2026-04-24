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
    width: {
        type: [Number, String],
        default: 34,
    },
    height: {
        type: [Number, String],
        default: 120,
    },
    rounded: {
        type: [Number, String],
        default: 9999,
    },
    variant: {
        type: String,
        default: 'solid', // 'solid' | 'hatch'
        validator: (v) => ['solid', 'hatch'].includes(v),
    },
    hatchOpacity: {
        type: Number,
        default: 0.55,
    },
    hatchSize: {
        type: Number,
        default: 10,
    },
});

const clamped = computed(() => Math.min(100, Math.max(0, Number(props.value) || 0)));
const wPx = computed(() => (typeof props.width === 'number' ? `${props.width}px` : props.width));
const hPx = computed(() => (typeof props.height === 'number' ? `${props.height}px` : props.height));
const radiusPx = computed(() => (typeof props.rounded === 'number' ? `${props.rounded}px` : props.rounded));

const trackStyle = computed(() => ({
    width: wPx.value,
    height: hPx.value,
    borderRadius: radiusPx.value,
    background: props.trackColor,
}));

const fillStyle = computed(() => ({
    height: `${clamped.value}%`,
    background: props.color,
    borderRadius: radiusPx.value,
}));

const hatchStyle = computed(() => {
    if (props.variant !== 'hatch') return {};

    const o = Math.min(1, Math.max(0, Number(props.hatchOpacity) || 0));
    const step = Math.max(4, Number(props.hatchSize) || 10);

    return {
        backgroundImage: `repeating-linear-gradient(135deg,
            rgba(255,255,255,${o}) 0px,
            rgba(255,255,255,${o}) ${Math.round(step * 0.45)}px,
            rgba(255,255,255,0) ${Math.round(step * 0.45)}px,
            rgba(255,255,255,0) ${step}px
        )`,
        mixBlendMode: 'overlay',
        opacity: 0.9,
    };
});
</script>

<template>
    <div class="vp-track" :style="trackStyle" role="progressbar" :aria-valuenow="clamped" aria-valuemin="0" aria-valuemax="100">
        <div class="vp-fill" :style="fillStyle">
            <div v-if="variant === 'hatch'" class="vp-hatch" :style="hatchStyle" />
        </div>
    </div>
</template>

<style scoped>
.vp-track {
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: flex-end;
}

.vp-fill {
    width: 100%;
    transition: height 240ms ease;
    position: relative;
    overflow: hidden;
}

.vp-hatch {
    position: absolute;
    inset: 0;
}
</style>
