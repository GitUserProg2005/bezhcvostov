<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    task: { type: Object, default: null },
});

const emit = defineEmits(['close', 'updated', 'deleted']);

const isSaving = ref(false);
const isDeleting = ref(false);
const errors = ref({});

const form = ref({
    title: '',
    description: '',
    difficulty: 'medium',
    estimated_minutes: null,
    deadline: null,
    end_at: null,
    status: 'pending',
});

const difficultyOptions = [
    { id: 'easy', label: 'Easy' },
    { id: 'medium', label: 'Medium' },
    { id: 'hard', label: 'Hard' },
];

const statusOptions = [
    { id: 'pending', label: 'Нужно сделать' },
    { id: 'in_progress', label: 'В работе' },
    { id: 'done', label: 'Сделано' },
];

function formatDateOnly(value) {
    if (!value) {
        return null;
    }
    const d = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(d.getTime())) {
        return null;
    }
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function syncFormFromTask(task) {
    if (!task) {
        return;
    }
    form.value = {
        title: task.title ?? '',
        description: task.description ?? '',
        difficulty: task.difficulty ?? 'medium',
        estimated_minutes: task.estimated_minutes ?? null,
        deadline: task.deadline ? new Date(task.deadline) : null,
        end_at: task.end_at ? new Date(`${task.end_at}T12:00:00`) : null,
        status: task.status ?? 'pending',
    };
    errors.value = {};
}

watch(
    () => [props.show, props.task],
    () => {
        if (props.show && props.task) {
            syncFormFromTask(props.task);
        }
    },
    { immediate: true },
);

const canSubmit = computed(() => Boolean(props.task?.id && form.value.title?.trim()));

function close() {
    emit('close');
}

async function save() {
    if (!props.task?.id || !canSubmit.value) {
        return;
    }
    isSaving.value = true;
    errors.value = {};
    try {
        const { data } = await axios.post(route('tasks.update'), {
            task_id: props.task.id,
            title: form.value.title.trim(),
            description: form.value.description || null,
            difficulty: form.value.difficulty,
            estimated_minutes: form.value.estimated_minutes || null,
            deadline: form.value.deadline ? form.value.deadline.toISOString() : null,
            end_at: formatDateOnly(form.value.end_at),
            status: form.value.status,
            source_type: props.task.source_type ?? 'text',
            ai_generated: Boolean(props.task.ai_generated),
        });
        if (data.success) {
            emit('updated', data.task);
            close();
        }
    } catch (e) {
        errors.value = e?.response?.data?.errors ?? {};
    } finally {
        isSaving.value = false;
    }
}

async function remove() {
    if (!props.task?.id) {
        return;
    }
    if (!window.confirm('Удалить задачу? Это действие нельзя отменить.')) {
        return;
    }
    isDeleting.value = true;
    try {
        const { data } = await axios.post(route('tasks.delete'), { task_id: props.task.id });
        if (data.success) {
            emit('deleted', props.task.id);
            close();
        }
    } catch (e) {
        errors.value = e?.response?.data?.errors ?? {};
    } finally {
        isDeleting.value = false;
    }
}
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="close">
        <div class="custom-scroll max-h-[90vh] space-y-4 overflow-y-auto p-4 md:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="title-2">Редактировать задачу</h2>
                <button type="button" class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit" @click="close">
                    <i class="fa-solid fa-xmark text-xl" />
                </button>
            </div>

            <div v-if="task" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <h3 class="title-2">Название</h3>
                    <input v-model="form.title" class="input mt-2 w-full" type="text" />
                    <p v-if="errors.title" class="t-mini mt-2 text-red-400">{{ errors.title[0] }}</p>
                </div>

                <div class="md:col-span-2">
                    <h3 class="title-2">Описание</h3>
                    <textarea v-model="form.description" class="input mt-2 w-full min-h-24" />
                </div>

                <div>
                    <h3 class="title-2">Сложность</h3>
                    <select v-model="form.difficulty" class="input mt-2 w-full">
                        <option v-for="opt in difficultyOptions" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
                    </select>
                </div>

                <div>
                    <h3 class="title-2">Статус</h3>
                    <select v-model="form.status" class="input mt-2 w-full">
                        <option v-for="opt in statusOptions" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
                    </select>
                </div>

                <div>
                    <h3 class="title-2">Оценка (мин.)</h3>
                    <input v-model.number="form.estimated_minutes" class="input mt-2 w-full" type="number" min="1" />
                </div>

                <div class="md:col-span-2">
                    <h3 class="title-2">Дедлайн</h3>
                    <div class="task-update-vcal content-glass mt-2 rounded-2xl border border-content p-3">
                        <VDatePicker
                            v-model="form.deadline"
                            mode="dateTime"
                            :popover="false"
                            borderless
                            transparent
                            expanded
                            title-position="left"
                            :is-dark="true"
                            class="w-full"
                        />
                    </div>
                </div>

                <div class="md:col-span-2">
                    <h3 class="title-2">Дата окончания (end_at)</h3>
                    <div class="task-update-vcal content-glass mt-2 rounded-2xl border border-content p-3">
                        <VDatePicker
                            v-model="form.end_at"
                            mode="date"
                            :popover="false"
                            borderless
                            transparent
                            expanded
                            title-position="left"
                            :is-dark="true"
                            class="w-full"
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-content pt-4">
                <button
                    type="button"
                    class="rounded-xl border border-red-500/50 px-4 py-2 text-sm text-red-400 transition hover:bg-red-500/10"
                    :disabled="isDeleting || !task"
                    @click="remove"
                >
                    {{ isDeleting ? 'Удаляем...' : 'Удалить' }}
                </button>
                <div class="flex gap-2">
                    <button type="button" class="primary-btn-white-blur" @click="close">Отмена</button>
                    <button type="button" class="primary-btn" :disabled="isSaving || !canSubmit" @click="save">
                        {{ isSaving ? 'Сохраняем...' : 'Сохранить' }}
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.task-update-vcal :deep(.vc-container) {
    width: 100%;
    border: none;
    background: transparent;
}
</style>

