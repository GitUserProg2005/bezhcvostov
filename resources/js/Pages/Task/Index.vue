<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ContextMenu from '@/Components/ContextMenu.vue';
import FormCreate from './components/FormCreate.vue';
import KanbanBoard from './components/KanbanBoard.vue';
import TaskCalendarBoard from './components/TaskCalendarBoard.vue';
import TaskListBoard from './components/TaskListBoard.vue';
import TaskUpdateModal from './components/TaskUpdateModal.vue';
import VoiceRecordModal from './components/VoiceRecordModal.vue';
import PhotoSendModal from './components/PhotoSendModal.vue';

import { useTasksFetch } from './composables/useTasksFetch';

const { tasks, fetchTasks } = useTasksFetch();

const activeTab = ref('kanban');
const showEditModal = ref(false);
const editTaskId = ref(null);

const editTask = computed(() => tasks.value.find((t) => t.id === editTaskId.value) ?? null);

function openEditModal(task) {
    editTaskId.value = task.id;
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editTaskId.value = null;
}

function mergeTask(updated) {
    const idx = tasks.value.findIndex((t) => t.id === updated.id);
    if (idx >= 0) {
        tasks.value[idx] = updated;
    }
}

function removeTaskById(id) {
    tasks.value = tasks.value.filter((t) => t.id !== id);
}

function onTaskUpdated(task) {
    mergeTask(task);
}

function onTaskDeleted(id) {
    removeTaskById(id);
}

async function toggleSubtaskDone(subtask, checked) {
    const previousStatus = subtask.status;
    subtask.status = checked ? 'done' : 'pending';

    try {
        await axios.post(route('subtasks.set-done'), {
            subtask_id: subtask.id,
            done: checked,
        });
    } catch (error) {
        subtask.status = previousStatus;
    }
}

async function toggleTaskDone(task, checked) {
    const previousStatus = task.status;
    task.status = checked ? 'done' : 'pending';

    try {
        await axios.post(route('tasks.update-status'), {
            task_id: task.id,
            status: checked ? 'done' : 'pending',
        });
    } catch (error) {
        task.status = previousStatus;
    }
}

async function onChangeStatus(task, nextStatus) {
    const previousStatus = task.status;
    task.status = nextStatus;

    try {
        await axios.post(route('tasks.update-status'), {
            task_id: task.id,
            status: nextStatus,
        });
    } catch (error) {
        task.status = previousStatus;
    }
}

const tabs = [
    { id: 'kanban', label: 'Канбан' },
    { id: 'calendar', label: 'Календарь' },
    { id: 'list', label: 'Список' },
];
</script>

<template>
    <DashboardLayout>
        <div class="flex min-h-0 w-full max-w-full min-w-0 flex-col space-y-4 overflow-x-hidden p-4 md:h-[calc(100vh-6.5rem)]">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="title font-semibold">Задачи</h1>

                <div class="relative flex items-center gap-4">
                    <ContextMenu position="left">
                        <template #default="{ toggleOpen }">
                            <button class="round-btn round-btn-accent" type="button" @click="toggleOpen">
                                <i class="fa-solid fa-plus text-sm"></i>
                            </button>
                        </template>

                        <template #menu>
                            <div class="w-60 space-y-2 p-2">
                                <FormCreate @created="fetchTasks" />

                                <VoiceRecordModal />

                                <PhotoSendModal @created="fetchTasks" />
                            </div>
                        </template>
                    </ContextMenu>
                </div>
            </div>

            <div class="tag-cyber-group shrink-0">
                <button
                    v-for="(tab, i) in tabs"
                    :key="tab.id"
                    type="button"
                    class="tag-cyber"
                    :class="[
                        i === 0 ? 'tag-cyber--start' : i === tabs.length - 1 ? 'tag-cyber--end' : 'tag-cyber--mid',
                        i > 0 ? 'tag-cyber--overlap' : '',
                        activeTab === tab.id ? 'tag-cyber--active' : '',
                    ]"
                    @click="activeTab = tab.id"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
                <div
                    v-show="activeTab === 'kanban'"
                    class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden"
                >
                    <KanbanBoard
                        :tasks="tasks"
                        @toggle-task="toggleTaskDone"
                        @toggle-subtask="toggleSubtaskDone"
                        @change-status="onChangeStatus"
                        @edit-task="openEditModal"
                    />
                </div>

                <div
                    v-show="activeTab === 'calendar'"
                    class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden"
                >
                    <TaskCalendarBoard :tasks="tasks" @task-updated="onTaskUpdated" @open-edit="openEditModal" />
                </div>

                <div
                    v-show="activeTab === 'list'"
                    class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden"
                >
                    <TaskListBoard
                        :tasks="tasks"
                        @toggle-task="toggleTaskDone"
                        @toggle-subtask="toggleSubtaskDone"
                        @edit-task="openEditModal"
                    />
                </div>
            </div>
        </div>

        <TaskUpdateModal
            :show="showEditModal"
            :task="editTask"
            @close="closeEditModal"
            @updated="onTaskUpdated"
            @deleted="onTaskDeleted"
        />
    </DashboardLayout>
</template>

<style scoped>
:deep(.task-board-vcal .vc-container) {
    width: 100%;
    border: none;
    background: transparent;
}
:deep(.task-board-vcal .vc-pane-container) {
    background: transparent;
}
</style>
