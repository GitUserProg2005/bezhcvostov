<script setup>
import { computed } from 'vue';

const props = defineProps({
    difficulty: {
        type: String,
        required: true,
    },
});

const difficultyLabel = computed(() => {
    const map = {
        easy: 'Легкая',
        medium: 'Средняя',
        hard: 'Сложная',
    };

    return map[props.difficulty] ?? props.difficulty;
});
</script>

<template>
    <span 
    :class="{ 'badge badge-completed': difficulty === 'easy', 
        'badge badge-in-progress': difficulty === 'medium', 
        'badge badge-pending': difficulty === 'hard' }
    "
    >
        {{ difficultyLabel }}
    </span>
</template>