<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Swiper, SwiperSlide } from 'swiper/vue';
import draggable from 'vuedraggable';
import TaskCard from './TaskCard.vue';

const props = defineProps({
    tasks: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['toggle-task', 'toggle-subtask', 'change-status', 'edit-task']);

const activeMobileColumn = ref(0);
const columns = [
    { key: 'pending', title: 'Нужно сделать', badgeClass: 'badge badge-pending', titleColor: 'var(--content-primary)', items: [] },
    { key: 'in_progress', title: 'В работе', badgeClass: 'badge badge-in-progress', titleColor: 'var(--content-primary)', items: [] },
    { key: 'done', title: 'Сделано', badgeClass: 'badge badge-completed', titleColor: '#aafeb5', items: [] },
];

const boardColumns = ref(columns);
const page = usePage();
let taskChannelName = null;

function normalizeTask(task) {
    return {
        ...task,
        subtasks: Array.isArray(task?.subtasks) ? task.subtasks : [],
    };
}

function applyTasksToBoard(nextTasks) {
    const prepared = columns.map((column) => ({
        ...column,
        items: [],
    }));

    (nextTasks ?? []).forEach((task) => {
        const normalizedTask = normalizeTask(task);
        const target = prepared.find((column) => column.key === normalizedTask.status) ?? prepared[0];
        target.items.push(normalizedTask);
    });

    boardColumns.value = prepared;
}

function flattenBoardTasks() {
    return boardColumns.value.flatMap((column) => column.items).map((task) => normalizeTask(task));
}

function mapTaskRealtime(payload) {
    const type = payload?.type;
    const changedTasks = Array.isArray(payload?.data?.tasks) ? payload.data.tasks.map(normalizeTask) : [];
    const changedSubtasks = Array.isArray(payload?.data?.subtasks) ? payload.data.subtasks : [];

    if (!type) {
        return;
    }

    const taskMap = new Map(flattenBoardTasks().map((task) => [task.id, { ...task, subtasks: [...(task.subtasks ?? [])] }]));

    if (type === 'delete') {
        const removeTaskIds = new Set(payload.data.task_ids ?? []);
        const removeSubtaskIds = new Set(payload?.data?.subtask_ids ?? []);

        removeTaskIds.forEach((taskId) => taskMap.delete(taskId));

        taskMap.forEach((task, taskId) => {
            if (removeTaskIds.has(taskId)) {
                return;
            }
            
            task.subtasks = (task.subtasks ?? []).filter((subtask) => !removeSubtaskIds.has(subtask.id));
        });

        applyTasksToBoard(Array.from(taskMap.values()));
        return;
    }

    changedTasks.forEach((task) => {
        const existing = taskMap.get(task.id);
        taskMap.set(task.id, {
            ...(existing ?? {}),
            ...task,
            subtasks: existing?.subtasks ?? [],
        });
    });

    changedSubtasks.forEach((subtask) => {
        const task = taskMap.get(subtask.task_id);
        if (!task) {
            return;
        }

        const subtasks = Array.isArray(task.subtasks) ? [...task.subtasks] : [];
        const index = subtasks.findIndex((item) => item.id === subtask.id);
        if (index >= 0) {
            subtasks[index] = { ...subtasks[index], ...subtask };
        } else {
            subtasks.push(subtask);
        }
        task.subtasks = subtasks.sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
    });

    applyTasksToBoard(Array.from(taskMap.values()));
}

function subscribeTaskRealtime() {
    if (typeof window.Echo === 'undefined') {
        return;
    }

    const userId = page.props?.auth?.user?.id;
    if (!userId) {
        return;
    }

    taskChannelName = `task-manager.${userId}`;
    window.Echo.private(taskChannelName).listen('.task.change.accepted', mapTaskRealtime);
}

function leaveTaskRealtime() {
    if (typeof window.Echo === 'undefined' || !taskChannelName) {
        return;
    }

    window.Echo.leave(`private-${taskChannelName}`);
    taskChannelName = null;
}

watch(
    () => props.tasks,
    (nextTasks) => {
        applyTasksToBoard(nextTasks);
    },
    { immediate: true, deep: true },
);

onMounted(() => {
    subscribeTaskRealtime();
});

onBeforeUnmount(() => {
    leaveTaskRealtime();
});

function onMobileSlideChange(swiper) {
    activeMobileColumn.value = swiper.activeIndex;
}

function onDragChange(columnKey, event) {
    if (!event?.added?.element) {
        return;
    }

    const movedTask = event.added.element;
    if (movedTask.status === columnKey) {
        return;
    }

    emit('change-status', movedTask, columnKey);
}

function moveTaskByStep(task, currentColumnIndex, step) {
    const nextColumnIndex = currentColumnIndex + step;
    if (nextColumnIndex < 0 || nextColumnIndex >= boardColumns.value.length) {
        return;
    }

    emit('change-status', task, boardColumns.value[nextColumnIndex].key);
}
</script>

<template>
    <div class="hidden md:grid md:grid-cols-3 gap-4 md:flex-1 md:min-h-0">
        <div v-for="(column, columnIndex) in boardColumns" :key="column.key" class="content-outline p-4 space-y-3 md:h-full md:flex md:flex-col">
            <div class="flex items-center justify-between gap-2">
                <h2 class="title-2">{{ column.title }}</h2>
                <span :class="column.badgeClass">{{ column.items.length }}</span>
            </div>

            <draggable
                v-model="boardColumns[columnIndex].items"
                :group="{ name: 'tasks-kanban' }"
                item-key="id"
                class="md:flex-1 md:min-h-0 md:overflow-y-auto md:pr-1 task-scroll-hide space-y-3"
                @change="onDragChange(column.key, $event)"
            >
                <template #item="{ element: task }">
                    <TaskCard
                        :task="task"
                        :title-color="column.titleColor"
                        variant="desktop"
                        @edit="(t) => emit('edit-task', t)"
                        @toggle-task="(task, checked) => emit('toggle-task', task, checked)"
                        @toggle-subtask="(subtask, checked) => emit('toggle-subtask', subtask, checked)"
                    />
                </template>
                <template #footer>
                    <div v-if="!column.items.length" class="flex-1 h-full context flex flex-col items-center justify-center gap-2">
                        <img v-if="column.key === 'pending'" src="/img/maskot/with_notebook.png" class="object-contain w-32" alt="">
                        <img v-else-if="column.key === 'in_progress'" src="/img/maskot/with_panel.png" class="object-contain w-48" alt="">
                        <img v-else-if="column.key === 'done'" src="/img/maskot/jump.png" class="object-contain w-32" alt="">
                        <span>Пока нет задач</span>
                    </div>
                </template>
            </draggable>
        </div>
    </div>

    <div class="mt-4 md:hidden space-y-3 h-[calc(100vh-11rem)] flex flex-col min-h-0 w-full max-w-full min-w-0 overflow-x-hidden">
        <div class="flex gap-2 overflow-x-auto pb-1">
            <span
                v-for="(column, index) in boardColumns"
                :key="column.key"
                class="badge whitespace-nowrap"
                :class="index === activeMobileColumn ? column.badgeClass : 'badge-neutral'"
            >
                {{ column.title }}
            </span>
        </div>

        <Swiper
            :slides-per-view="1"
            :space-between="12"
            class="flex-1 min-h-0 w-full max-w-full min-w-0 overflow-hidden"
            @slideChange="onMobileSlideChange"
        >
            <SwiperSlide v-for="(column, columnIndex) in boardColumns" :key="column.key">
                <div class="content-outline p-4 space-y-3 h-full flex flex-col min-h-0 w-full max-w-full min-w-0 overflow-x-hidden">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="title-2">{{ column.title }}</h2>
                        <span :class="column.badgeClass">{{ column.items.length }}</span>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto task-scroll-hide space-y-3">
                        <div v-if="!column.items.length" class="flex min-h-full flex-col items-center justify-center gap-2 context text-center">
                            <img v-if="column.key === 'pending'" src="/img/maskot/with_notebook.png" class="object-contain w-28" alt="">
                            <img v-else-if="column.key === 'in_progress'" src="/img/maskot/with_panel.png" class="object-contain w-40" alt="">
                            <img v-else-if="column.key === 'done'" src="/img/maskot/jump.png" class="object-contain w-28" alt="">
                            <span>Пока нет задач</span>
                        </div>

                        <TaskCard
                            v-for="task in column.items"
                            :key="task.id"
                            :task="task"
                            :title-color="column.titleColor"
                            variant="mobile"
                            :can-move-left="columnIndex > 0"
                            :can-move-right="columnIndex < boardColumns.length - 1"
                            @edit="(t) => emit('edit-task', t)"
                            @move-left="moveTaskByStep(task, columnIndex, -1)"
                            @move-right="moveTaskByStep(task, columnIndex, 1)"
                            @toggle-task="(task, checked) => emit('toggle-task', task, checked)"
                            @toggle-subtask="(subtask, checked) => emit('toggle-subtask', subtask, checked)"
                        />
                    </div>
                </div>
            </SwiperSlide>
        </Swiper>
    </div>
</template>
