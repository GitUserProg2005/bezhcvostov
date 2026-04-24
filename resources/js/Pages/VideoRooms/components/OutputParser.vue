<script setup>
import { computed } from 'vue';

const props = defineProps({
    output: {
        type: Object,
        default: () => ({ items: [] }),
    },
});

const items = computed(() => {
    const raw = Array.isArray(props.output?.items) ? props.output.items : [];
    return raw.filter((item) => item && typeof item === 'object');
});

function noteRotateClass(index) {
    const variants = ['rotate-[1deg]', 'rotate-[2deg]', 'rotate-[3deg]', 'rotate-[22deg]', 'rotate-[26deg]'];
    return variants[index % variants.length];
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <template v-for="(item, index) in items" :key="index">
            <p
                v-if="item.type === 'text'"
                class="context w-full text-base leading-relaxed"
            >
                {{ item.text }}
            </p>

            <div
                v-else-if="item.type === 'notes'"
                class="inline-flex"
            >
                <article
                    class="w-64 h-64 p-5 transition my-6 bg-center bg-cover bg-no-repeat overflow-hidden flex flex-col"
                    :class="noteRotateClass(index)"
                    style="background-image: url('/img/note.png');"
                >
                    <h4 class="title-2 text-base text-black">{{ item.title }}</h4>
                    <p class="context mt-2 text-sm text-black overflow-y-auto custom-scroll">{{ item.description }}</p>
                </article>
            </div>
        </template>
    </div>
</template>

