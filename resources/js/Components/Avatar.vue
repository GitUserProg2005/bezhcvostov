<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    name: String,
    src: String,
    userId: Number,
    noLink: { type: Boolean, default: false },
    /** sm = 8, md = 10, lg = 24 (как в TikTok) */
    size: { type: String, default: 'md' },
})

const sizeClass = computed(() => {
    const map = { sm: 'w-8 h-8', md: 'w-10 h-10', lg: 'w-24 h-24' }
    return map[props.size] || map.md
})

const initial = computed(() => props.name ? props.name[0] : '')
const imageError = ref(false)

function handleImageError() {
    imageError.value = true;
}
</script>

<template>
    <Link
        v-if="!noLink && userId != null"
        :href="route('user.profile', userId)"
        class="inline-block shrink-0 overflow-hidden rounded-full"
        :class="sizeClass"
    >
        <span
            v-if="!props.src || imageError"
            class="bg-gray-200 rounded-full text-gray-500 flex items-center justify-center w-full h-full"
            :class="sizeClass"
        >
            <i class="fa-solid fa-user text-sm" />
        </span>
        <img
            v-else
            :src="props.src"
            :alt="props.name"
            class="block w-full h-full object-cover object-center"
            @error="handleImageError"
            @load="imageError = false"
        />
    </Link>
    <span
        v-else
        class="inline-block shrink-0 overflow-hidden rounded-full"
        :class="sizeClass"
    >
        <span
            v-if="!props.src || imageError"
            class="bg-gray-200 rounded-full text-gray-500 flex items-center justify-center w-full h-full"
            :class="sizeClass"
        >
            <i class="fa-solid fa-user text-sm" />
        </span>
        <img
            v-else
            :src="props.src"
            :alt="props.name"
            class="block w-full h-full object-cover object-center"
            @error="handleImageError"
            @load="imageError = false"
        />
    </span>
</template>
