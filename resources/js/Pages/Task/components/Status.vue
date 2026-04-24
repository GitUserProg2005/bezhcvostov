<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const statusLabel = computed(() => {
    const map = {
        pending: 'Нужно сделать',
        in_progress: 'В работе',
        done: 'Сделано',
    };

    return map[props.status] ?? props.status;
});
</script>

<template>
    <span 
    :class="{ 'badge badge-completed': status === 'done', 
    'badge badge-in-progress': status === 'in_progress', 'badge badge-pending': status === 'pending' }"
    >
        {{ statusLabel }}
    </span>
</template>