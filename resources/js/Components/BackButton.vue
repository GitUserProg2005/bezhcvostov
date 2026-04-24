<script setup>
import { router } from '@inertiajs/vue3';

const emit = defineEmits(['back']);

const props = defineProps({
    /** Предыдущий URL (route) для возврата. Если не передан — переход на главную. */
    backUrl: {
        type: String,
        default: null
    }
});

function goBack() {
    if (props.backUrl) {
        router.visit(route(props.backUrl));
    } else {
        window.history.back();
    }
};
</script>

<template>
    <button type="button" @click="goBack" aria-label="Назад"
        class="rounded-full w-8 h-8  border-2 border-white"
    >
        <i class="fa-solid fa-arrow-left"></i>
    </button>
</template>
