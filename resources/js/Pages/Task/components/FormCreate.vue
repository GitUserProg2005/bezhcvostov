<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';
import {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxOption,
    ComboboxOptions,
} from '@headlessui/vue';

const emit = defineEmits(['created']);

const showModal = ref(false);
const isSubmitting = ref(false);
const difficultyQuery = ref('');

const difficultyOptions = [
    { id: 'easy', label: 'Easy' },
    { id: 'medium', label: 'Medium' },
    { id: 'hard', label: 'Hard' },
];

/** v-calendar range: { start, end } | null */
const dateRange = ref(null);

const form = ref({
    task: {
        title: '',
        description: '',
        difficulty: difficultyOptions[1],
        estimated_minutes: null,
        status: 'pending',
        source_type: 'text',
        ai_generated: false,
    },
    subtasks: [],
});

const errors = ref({});

const filteredDifficulties = computed(() => {
    if (!difficultyQuery.value) {
        return difficultyOptions;
    }

    return difficultyOptions.filter((item) =>
        item.label.toLowerCase().includes(difficultyQuery.value.toLowerCase()),
    );
});

function openModal() {
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    errors.value = {};
}

function addSubtask() {
    form.value.subtasks.push({
        title: '',
        description: '',
        order: form.value.subtasks.length,
        estimated_minutes: null,
        status: 'pending',
    });
}

function removeSubtask(index) {
    form.value.subtasks.splice(index, 1);
    form.value.subtasks.forEach((item, idx) => {
        item.order = idx;
    });
}

function resetForm() {
    dateRange.value = null;
    form.value = {
        task: {
            title: '',
            description: '',
            difficulty: difficultyOptions[1],
            estimated_minutes: null,
            status: 'pending',
            source_type: 'text',
            ai_generated: false,
        },
        subtasks: [],
    };
    difficultyQuery.value = '';
    errors.value = {};
}

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

function rangeToDeadlineAndEndAt() {
    const r = dateRange.value;
    if (!r || (!r.start && !r.end)) {
        return { deadline: null, end_at: null };
    }
    const start = r.start ?? r.end;
    const end = r.end ?? r.start;
    return {
        deadline: formatDateOnly(start),
        end_at: formatDateOnly(end),
    };
}

async function submitForm() {
    isSubmitting.value = true;
    errors.value = {};

    const { deadline, end_at } = rangeToDeadlineAndEndAt();

    const payload = {
        task: {
            title: form.value.task.title,
            description: form.value.task.description || null,
            difficulty: form.value.task.difficulty?.id ?? 'medium',
            estimated_minutes: form.value.task.estimated_minutes || null,
            deadline,
            end_at,
            status: form.value.task.status,
            source_type: form.value.task.source_type,
            ai_generated: form.value.task.ai_generated,
        },
        subtasks: form.value.subtasks.map((subtask) => ({
            title: subtask.title,
            description: subtask.description || null,
            order: subtask.order,
            estimated_minutes: subtask.estimated_minutes || null,
            deadline,
            status: subtask.status,
        })),
    };

    try {
        const { data } = await axios.post(route('tasks.create-form'), payload);

        if (data.success) {
            emit('created', data.task);
            closeModal();
            resetForm();
        }
    } catch (error) {
        errors.value = error?.response?.data?.errors ?? {};
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <button type="button" class="primary-btn-white-blur w-full flex justify-center items-center gap-2 text-sm" @click="openModal">
        Создать задачу
        <i class="fa-solid fa-plus" />
    </button>

    <Modal :show="showModal" max-width="5xl" @close="closeModal">
        <div class="custom-scroll max-h-[90vh] space-y-4 overflow-y-auto p-4 md:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="title-2">Создать задачу</h2>
                <button type="button" class="shrink-0 border-0 bg-transparent p-1 leading-none text-inherit" @click="closeModal">
                    <i class="fa-solid fa-xmark text-xl" />
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <h3 class="title-2">Название</h3>
                    <input v-model="form.task.title" class="input mt-2 w-full" type="text" placeholder="Название задачи" />
                    <p v-if="errors['task.title']" class="t-mini mt-2 text-red-400">{{ errors['task.title'][0] }}</p>
                </div>

                <div class="md:col-span-2">
                    <h3 class="title-2">Описание</h3>
                    <textarea v-model="form.task.description" class="input mt-2 w-full min-h-20" placeholder="Описание задачи" />
                </div>

                <div>
                    <h3 class="title-2">Сложность</h3>
                    <Combobox v-model="form.task.difficulty" as="div" class="relative mt-2">
                        <div class="relative">
                            <ComboboxInput
                                class="input w-full pr-10"
                                placeholder="Выберите сложность"
                                :displayValue="(item) => item?.label ?? ''"
                                @change="difficultyQuery = $event.target.value"
                            />
                            <ComboboxButton class="absolute inset-y-0 right-0 flex items-center px-3">
                                <i class="fa-solid fa-chevron-down text-xs opacity-70" />
                            </ComboboxButton>
                        </div>
                        <ComboboxOptions class="absolute z-50 mt-2 max-h-56 w-full overflow-auto rounded-2xl border border-content bg-content-outline p-2 shadow-xl">
                            <ComboboxOption v-for="item in filteredDifficulties" :key="item.id" :value="item" as="template" v-slot="{ active }">
                                <li
                                    class="cursor-pointer rounded-xl px-3 py-2 text-sm"
                                    :class="active ? 'bg-content-dark' : ''"
                                >
                                    {{ item.label }}
                                </li>
                            </ComboboxOption>
                        </ComboboxOptions>
                    </Combobox>
                </div>

                <div>
                    <h3 class="title-2">Оценка (мин.)</h3>
                    <input v-model.number="form.task.estimated_minutes" class="input mt-2 w-full" type="number" min="1" placeholder="Например: 45" />
                </div>

                <div class="md:col-span-2 w-full min-w-0">
                    <h3 class="title-2">Сроки (диапазон дат)</h3>
                    <p class="context mt-1 text-sm">Выберите начало и конец периода (один день — тот же день как начало и конец).</p>
                    <div class="task-form-vcal content-glass mt-2 w-full min-w-0 rounded-2xl border border-content p-2 md:p-4">
                        <VDatePicker
                            v-model="dateRange"
                            mode="date"
                            is-range
                            :popover="false"
                            borderless
                            transparent
                            expanded
                            title-position="left"
                            :is-dark="true"
                            class="w-full min-w-0"
                        />
                    </div>
                    <p v-if="errors['task.deadline']" class="t-mini mt-2 text-red-400">{{ errors['task.deadline'][0] }}</p>
                    <p v-if="errors['task.end_at']" class="t-mini mt-2 text-red-400">{{ errors['task.end_at'][0] }}</p>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="title-2">Подзадачи</h3>
                    <button type="button" class="round-btn round-btn-accent" @click="addSubtask">
                        <i class="fa-solid fa-plus text-xs" />
                    </button>
                </div>

                <div v-if="!form.subtasks.length" class="content-outline p-4">
                    <p class="context">Пока нет подзадач</p>
                </div>

                <div v-for="(subtask, index) in form.subtasks" :key="index" class="content-outline space-y-3 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="font-semibold">Подзадача #{{ index + 1 }}</h4>
                        <button type="button" class="round-btn round-btn-accent" @click="removeSubtask(index)">
                            <i class="fa-solid fa-trash text-xs" />
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_9rem]">
                        <input
                            v-model="subtask.title"
                            class="input w-full"
                            type="text"
                            placeholder="Название подзадачи"
                        />
                        <input
                            v-model.number="subtask.estimated_minutes"
                            class="input w-full md:w-36 shrink-0"
                            type="number"
                            min="1"
                            placeholder="Минуты"
                        />
                    </div>

                    <div>
                        <textarea v-model="subtask.description" class="input w-full min-h-16" placeholder="Описание подзадачи" />
                    </div>

                    <!--
                    <div>
                        <h5 class="t-small mb-2">Deadline подзадачи</h5>
                        <VDatePicker v-model="subtask.deadline" mode="dateTime" />
                    </div>
                    -->
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" class="primary-btn-white-blur" @click="closeModal">Отмена</button>
                <button type="button" class="primary-btn" :disabled="isSubmitting" @click="submitForm">
                    {{ isSubmitting ? 'Сохраняем...' : 'Создать задачу' }}
                </button>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.task-form-vcal :deep(.vc-container) {
    width: 100%;
    border: none;
    background: transparent;
}
.task-form-vcal :deep(.vc-pane-layout) {
    width: 100%;
}
</style>

