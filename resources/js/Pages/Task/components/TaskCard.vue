<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Difficulty from './Difficulty.vue';

const props = defineProps({
    task: { type: Object, required: true },
    titleColor: { type: String, required: true },
    variant: { type: String, default: 'desktop' },
    canMoveLeft: { type: Boolean, default: false },
    canMoveRight: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle-subtask', 'toggle-task', 'move-left', 'move-right', 'edit']);
const isMobile = computed(() => props.variant === 'mobile');
const expandedSubtasks = ref(false);

const difficultyStripeColorMap = { easy: '#58ee69', medium: '#eeec58', hard: '#ee5862' };
const stripeColor = computed(() => difficultyStripeColorMap[props.task?.difficulty] ?? difficultyStripeColorMap.medium);

const formattedDeadline = computed(() => {
    const deadline = props.task?.deadline;
    const endAt = props.task?.end_at;
    if (!deadline && !endAt) {
        return null;
    }
    const d0 = deadline ? new Date(deadline) : null;
    const d1 = endAt ? new Date(`${endAt}T12:00:00`) : null;
    const s0 = d0 && !Number.isNaN(d0.getTime()) ? d0.toISOString().slice(0, 10) : null;
    const s1 = d1 && !Number.isNaN(d1.getTime()) ? d1.toISOString().slice(0, 10) : null;
    if (s0 && s1 && s0 !== s1) {
        return `${s0} — ${s1}`;
    }
    return s0 ?? s1;
});

const hasSubtasks = computed(() => (props.task?.subtasks?.length ?? 0) > 0);
const totalItems = computed(() => (hasSubtasks.value ? props.task.subtasks.length : 1));
const doneItems = computed(() => {
    if (!hasSubtasks.value) return props.task.status === 'done' ? 1 : 0;
    return (props.task.subtasks ?? []).filter((subtask) => subtask.status === 'done').length;
});
const progressPercent = computed(() => Math.round((doneItems.value / Math.max(totalItems.value, 1)) * 100));

function openTaskPage() {
    router.visit(route('tasks.show', props.task.id));
}

function toggleSubtasksVisible() {
    expandedSubtasks.value = !expandedSubtasks.value;
}
</script>

<template>
    <div class="bg-content p-4 rounded-2xl space-y-2 w-full max-w-full min-w-0 overflow-x-hidden relative pl-5 text-white">
        <span
            class="absolute left-0 top-3 bottom-3 w-1 rounded-full"
            :style="{ backgroundColor: stripeColor }"
        />
        
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-bold min-w-0 break-words text-white">
                {{ task.title }}
            </h3>

            <div class="flex flex-col items-center gap-2">
                <Difficulty :difficulty="task.difficulty" />

                <div class="flex items-center gap-1">
                    <button type="button" class="round-btn round-btn-accent" @click.stop="emit('edit', task)" aria-label="Редактировать">
                        <i class="fa-solid fa-pen text-xs" />
                    </button>
                    <button type="button" class="round-btn round-btn-accent" @click.stop="openTaskPage" aria-label="Открыть задачу">
                        <i class="fa-solid fa-align-justify text-xs" />
                    </button>
                </div> 
            </div>
        </div>

        <div v-if="formattedDeadline" class="flex items-center gap-2 text-content-ice text-sm">
            <i class="fa-regular fa-calendar" />
            <span>{{ formattedDeadline }}</span>
        </div>

        <div v-if="isMobile" class="flex items-center">
            <button
                type="button"
                class="primary-btn-arrow primary-btn-arrow-left w-full disabled:opacity-30 disabled:cursor-not-allowed"
                :disabled="!canMoveLeft"
                @click.stop="emit('move-left')"
                aria-label="Переместить влево"
            >
                <i class="fa-solid fa-arrow-left text-xs" />
            </button>
            <button
                type="button"
                class="primary-btn-arrow primary-btn-arrow-right w-full disabled:opacity-30 disabled:cursor-not-allowed"
                :disabled="!canMoveRight"
                @click.stop="emit('move-right')"
                aria-label="Переместить вправо"
            >
                <i class="fa-solid fa-arrow-right text-xs" />
            </button>
        </div>

        <div class="pt-2 space-y-2">
            <div class="space-y-1">
                <div class="h-2 w-full rounded-full bg-content-dark">
                    <div
                        class="h-2 rounded-full transition-all duration-300"
                        :style="{
                            width: `${progressPercent}%`,
                            background: 'linear-gradient(90deg, var(--accent) 0%, var(--accent) 100%)',
                        }"
                    />
                </div>

                <p class="text-sm text-white">
                    Выполнено {{ doneItems }}/{{ totalItems }}
                </p>
            </div>

            <div v-if="hasSubtasks" class="space-y-2">
                <button type="button" class="inline-flex items-center gap-2 text-sm text-white" @click.stop="toggleSubtasksVisible">
                    <i class="fa-solid" :class="expandedSubtasks ? 'fa-chevron-up' : 'fa-chevron-down'" />
                    {{ expandedSubtasks ? 'Скрыть подзадачи' : 'Показать подзадачи' }}
                </button>

                <div v-if="expandedSubtasks" class="space-y-2">
                    <div v-for="(subtask, index) in task.subtasks" :key="subtask.id" class="p-3 relative">
                        <img
                            v-if="subtask && index !== task.subtasks.length - 1"
                            src="/img/arrow.png"
                            class="w-4 absolute -left-2 -bottom-5"
                            alt=""
                        >
                        <label class="flex items-center gap-2" @click.stop>
                            <input
                                type="checkbox"
                                class="task-subtask-checkbox"
                                :checked="subtask.status === 'done'"
                                @change="emit('toggle-subtask', subtask, $event.target.checked)"
                            />
                            <p class="text-sm font-medium min-w-0 break-words text-white" :class="{ 'line-through opacity-70': subtask.status === 'done' }">
                                {{ subtask.title }}
                            </p>
                        </label>
                    </div>
                </div>
            </div>

            <label v-else class="flex items-center gap-2" @click.stop>
                <input
                    type="checkbox"
                    class="task-subtask-checkbox"
                    :checked="task.status === 'done'"
                    @change="emit('toggle-task', task, $event.target.checked)"
                />
                <span class="text-sm text-white">Отметить задачу выполненной</span>
            </label>
        </div>
    </div>
</template>
