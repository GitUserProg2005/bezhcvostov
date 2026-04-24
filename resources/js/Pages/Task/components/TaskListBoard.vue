<script setup>
import { computed } from 'vue';
import TaskCard from './TaskCard.vue';

const props = defineProps({
    tasks: { type: Array, required: true },
});

const emit = defineEmits(['toggle-task', 'toggle-subtask', 'edit-task']);

const sortedTasks = computed(() => {
    const list = [...props.tasks];
    list.sort((a, b) => {
        const ta = a.deadline ? new Date(a.deadline).getTime() : null;
        const tb = b.deadline ? new Date(b.deadline).getTime() : null;
        if (ta === null && tb === null) {
            return (b.id ?? 0) - (a.id ?? 0);
        }
        if (ta === null) {
            return 1;
        }
        if (tb === null) {
            return -1;
        }
        return ta - tb;
    });
    return list;
});
</script>

<template>
    <div class="task-scroll-hide min-h-0 flex-1 space-y-3 overflow-y-auto pr-1 md:pr-2">
        <div v-if="!sortedTasks.length" class="content-outline flex min-h-[18rem] flex-col items-center justify-center gap-2 p-6 text-center">
            <img src="/img/maskot/maskot_with_pen.png" class="object-contain w-32" alt="">
            <p class="context">Нет задач</p>
        </div>
        <div class="overflow-y-auto h-[100vh]">
            <TaskCard
                v-for="task in sortedTasks"
                :key="task.id"
                :task="task"
                title-color="var(--content-primary)"
                variant="desktop"
                @edit="(t) => emit('edit-task', t)"
                @toggle-task="(t, c) => emit('toggle-task', t, c)"
                @toggle-subtask="(s, c) => emit('toggle-subtask', s, c)"
            />
        </div>
    </div>
</template>
