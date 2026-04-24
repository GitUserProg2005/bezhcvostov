<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const x = ref(0);
const y = ref(0);

const mouseX = ref(0);
const mouseY = ref(0);

const isHover = ref(false);

let animationFrame;

function handleMouseOver(e) {
    const target = e.target;

    if (target.closest('a, button')) {
        isHover.value = true;
    } else {
        isHover.value = false;
    }
}

function moveCursor(e) {
    mouseX.value = e.clientX;
    mouseY.value = e.clientY;
}

function animate() {
    x.value = x.value + (mouseX.value - x.value) * 0.1;
    y.value = y.value + (mouseY.value - y.value) * 0.1;

    animationFrame = requestAnimationFrame(animate);
}

const mainArms = 6;
const branchArms = 6;

onMounted(() => {
    window.addEventListener('mousemove', moveCursor);
    window.addEventListener('mouseover', handleMouseOver);
    animate();
});

onUnmounted(() => {
    window.removeEventListener('mousemove', moveCursor);
    window.removeEventListener('mouseover', handleMouseOver);
    cancelAnimationFrame(animationFrame);
});
</script>

<template>
    <div
        class="cursor-root fixed pointer-events-none z-[9999] transition-transform duration-100 ease-out"
        :style="{
            left: x + 'px',
            top: y + 'px',
            transform: `translate(-50%, -50%) ${isHover ? 'scale(1.12)' : 'scale(1)'}`,
        }"
    >
        <div
            class="cursor-snowflake-spin relative h-14 w-14"
            :class="
                isHover
                    ? 'opacity-90 [&_.cursor-pill]:bg-[#e97358]/50 [&_.cursor-pill]:backdrop-blur-sm'
                    : ''
            "
        >
            <!-- центр: общая точка для лучей -->
            <div class="absolute left-1/2 top-1/2 h-0 w-0">
                <!-- основные лучи снежинки -->
                <div
                    v-for="i in mainArms"
                    :key="'m-' + i"
                    class="cursor-pill absolute bottom-0 left-1/2 h-3 w-1 -translate-x-1/2 rounded-full bg-[#e97358]"
                    :style="{
                        transform: `rotate(${(i - 1) * (360 / mainArms)}deg)`,
                        transformOrigin: 'center bottom',
                    }"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.cursor-snowflake-spin {
    animation: cursor-snowflake-spin 7s linear infinite;
}

@keyframes cursor-snowflake-spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>

<style>
* {
    cursor: none !important;
}
</style>
