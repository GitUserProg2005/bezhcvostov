<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
});

function formatIsoDate(value) {
    if (!value) {
        return 'Без дедлайна';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toISOString().slice(0, 10);
}
</script>

<template>
    <DashboardLayout>
        <div class="p-4 space-y-4">
            <a :href="route('tasks.index')" class="context inline-flex items-center gap-2">
                <i class="fa-solid fa-arrow-left" />
                Назад к задачам
            </a>

            <div class="content-outline space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h1 class="title">{{ props.task.title }}</h1>
                    <span class="badge badge-neutral">{{ props.task.status }}</span>
                </div>

                <p class="context">{{ props.task.description || 'Без описания' }}</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="content-p-0 p-3">
                        <p class="t-mini">Сложность</p>
                        <p class="t-body">{{ props.task.difficulty || 'medium' }}</p>
                    </div>
                    <div class="content-p-0 p-3">
                        <p class="t-mini">Дедлайн</p>
                        <p class="t-body">{{ formatIsoDate(props.task.deadline) }}</p>
                    </div>
                    <div class="content-p-0 p-3">
                        <p class="t-mini">Оценка времени</p>
                        <p class="t-body">{{ props.task.estimated_minutes ?? '—' }}</p>
                    </div>
                </div>

                <div>
                    <h2 class="title-2 mb-2">Подзадачи</h2>
                    <div v-if="props.task.subtasks?.length" class="space-y-2">
                        <div v-for="subtask in props.task.subtasks" :key="subtask.id" class="content-p-0 p-3">
                            <p class="t-body">{{ subtask.title }}</p>
                            <p class="context">{{ subtask.description || 'Без описания' }}</p>
                        </div>
                    </div>
                    <p v-else class="context">Подзадач нет</p>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
