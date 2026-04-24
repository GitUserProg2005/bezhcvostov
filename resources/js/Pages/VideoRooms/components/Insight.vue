<script setup>
import OutputParser from './OutputParser.vue';

defineProps({
    insight: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="content-glass p-4">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="badge badge-neutral">ID: {{ insight.id }}</span>
            <span class="badge badge-neutral">{{ insight.created_at }}</span>
            <span class="badge badge-neutral">{{ insight.room?.title }}</span>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-4">
            <OutputParser :output="insight.output" />
        </div>
    </div>
</template>

