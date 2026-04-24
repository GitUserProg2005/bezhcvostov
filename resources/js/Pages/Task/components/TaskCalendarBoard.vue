<script setup>
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    tasks: { type: Array, required: true },
});

const emit = defineEmits(['task-updated', 'open-edit']);

const dragTaskId = ref(null);

function formatYmd(d) {
    if (!d) {
        return null;
    }
    const x = d instanceof Date ? d : new Date(d);
    if (Number.isNaN(x.getTime())) {
        return null;
    }
    const y = x.getFullYear();
    const m = String(x.getMonth() + 1).padStart(2, '0');
    const day = String(x.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function startOfLocalDay(d) {
    const x = d instanceof Date ? d : new Date(d);
    return new Date(x.getFullYear(), x.getMonth(), x.getDate());
}

function parseYmdLocal(ymd) {
    if (!ymd || typeof ymd !== 'string') {
        return null;
    }
    const [y, m, dd] = ymd.split('-').map(Number);
    if (!y || !m || !dd) {
        return null;
    }
    return new Date(y, m - 1, dd);
}

function addLocalDays(d, n) {
    const x = new Date(startOfLocalDay(d));
    x.setDate(x.getDate() + n);
    return x;
}

function daysDiff(a, b) {
    return Math.round((startOfLocalDay(a) - startOfLocalDay(b)) / 86400000);
}

function taskRangeKeys(task) {
    const startStr = task.deadline ? formatYmd(new Date(task.deadline)) : task.end_at ?? null;
    if (!startStr) {
        return null;
    }
    const endStr = task.end_at ?? startStr;
    return startStr <= endStr ? { start: startStr, end: endStr } : { start: endStr, end: startStr };
}

function taskOnDay(task, dayYmd) {
    const range = taskRangeKeys(task);
    if (!range) {
        return false;
    }
    return dayYmd >= range.start && dayYmd <= range.end;
}

const unscheduledTasks = computed(() => props.tasks.filter((t) => !taskRangeKeys(t)));

function tasksForDay(day) {
    const key = formatYmd(day.startDate ?? day.date ?? day.noonDate);
    if (!key) {
        return [];
    }
    return props.tasks.filter((t) => taskOnDay(t, key));
}

const calendarAttributes = computed(() => {
    const dots = [];
    for (const task of props.tasks) {
        const range = taskRangeKeys(task);
        if (!range) {
            continue;
        }
        dots.push({
            key: `task-${task.id}`,
            dates: { start: parseYmdLocal(range.start), end: parseYmdLocal(range.end) },
            dot: { color: task.difficulty === 'hard' ? '#ee5862' : task.difficulty === 'easy' ? '#58ee69' : '#eeec58' },
        });
    }
    return dots;
});

function onDragStart(task, event) {
    dragTaskId.value = task.id;
    event.dataTransfer?.setData('text/task-id', String(task.id));
    event.dataTransfer.effectAllowed = 'move';
}

function onDragEnd() {
    dragTaskId.value = null;
}

async function persistTask(task, overrides) {
    const endAt =
        Object.prototype.hasOwnProperty.call(overrides, 'end_at') ? overrides.end_at : (task.end_at ?? null);

    const { data } = await axios.post(route('tasks.update'), {
        task_id: task.id,
        title: task.title,
        description: task.description ?? null,
        difficulty: task.difficulty ?? 'medium',
        estimated_minutes: task.estimated_minutes ?? null,
        deadline: Object.prototype.hasOwnProperty.call(overrides, 'deadline')
            ? overrides.deadline
            : (task.deadline ? new Date(task.deadline).toISOString() : null),
        end_at: endAt,
        status: task.status ?? 'pending',
        source_type: task.source_type ?? 'text',
        ai_generated: Boolean(task.ai_generated),
    });
    if (data.success) {
        emit('task-updated', data.task);
    }
}

async function onDropOnDay(day, event) {
    event.preventDefault();
    const raw = event.dataTransfer?.getData('text/task-id');
    const id = raw ? Number(raw) : dragTaskId.value;
    if (!id) {
        return;
    }
    const task = props.tasks.find((t) => t.id === id);
    if (!task) {
        return;
    }
    const dropDay = startOfLocalDay(day.startDate ?? day.date ?? day.noonDate);
    const range = taskRangeKeys(task);

    try {
        if (!range) {
            const ymd = formatYmd(dropDay);
            await persistTask(task, {
                deadline: dropDay.toISOString(),
                end_at: ymd,
            });
            return;
        }

        const oldStart = task.deadline ? startOfLocalDay(new Date(task.deadline)) : parseYmdLocal(task.end_at);
        if (!oldStart) {
            const ymd = formatYmd(dropDay);
            await persistTask(task, {
                deadline: dropDay.toISOString(),
                end_at: ymd,
            });
            return;
        }

        const delta = daysDiff(dropDay, oldStart);
        const newDeadline = task.deadline ? addLocalDays(new Date(task.deadline), delta) : dropDay;
        const payload = { deadline: newDeadline.toISOString() };
        if (task.end_at) {
            const oldEnd = parseYmdLocal(task.end_at);
            if (oldEnd) {
                payload.end_at = formatYmd(addLocalDays(oldEnd, delta));
            }
        }

        await persistTask(task, payload);
    } catch {
        /* parent may toast later */
    }
}

function allowDrop(event) {
    event.preventDefault();
}
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-hidden md:min-h-[28rem]">
        <div
            v-if="unscheduledTasks.length"
            class="content-outline shrink-0 space-y-2 rounded-2xl p-3"
            @dragover="allowDrop"
            @drop.prevent
        >
            <p class="title-2 text-sm">Без срока — перетащите на день в календаре</p>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="task in unscheduledTasks"
                    :key="task.id"
                    type="button"
                    draggable="true"
                    class="max-w-full cursor-grab truncate rounded-xl border border-content bg-content px-3 py-1.5 text-left text-xs text-white active:cursor-grabbing"
                    @dragstart="onDragStart(task, $event)"
                    @dragend="onDragEnd"
                    @click="emit('open-edit', task)"
                >
                    {{ task.title }}
                </button>
            </div>
        </div>

        <div class="task-board-vcal content-glass min-h-0 min-w-0 flex-1 overflow-auto rounded-2xl border border-content p-2 md:p-4">
            <VCalendar
                expanded
                borderless
                :transparent="false"
                class="task-board-vcal-inner w-full min-w-0"
                :attributes="calendarAttributes"
                title-position="left"
                :is-dark="true"
            >
                <template #day-content="{ day }">
                    <div
                        class="flex min-h-[4.5rem] flex-col gap-1 px-0.5 pb-1 pt-0.5"
                        @dragover="allowDrop"
                        @drop="onDropOnDay(day, $event)"
                    >
                        <span class="text-[0.65rem] font-medium text-content-ice">{{ day.label }}</span>
                        <div class="flex flex-1 flex-col gap-1 overflow-y-auto">
                            <button
                                v-for="task in tasksForDay(day)"
                                :key="task.id"
                                type="button"
                                draggable="true"
                                class="w-full cursor-grab truncate rounded-lg tag text-sm px-1.5 py-1 text-left text-[0.7rem] leading-tight text-white active:cursor-grabbing"
                                :class="{ 'ring-1 ring-accent/60': dragTaskId === task.id }"
                                @dragstart="onDragStart(task, $event)"
                                @dragend="onDragEnd"
                                @click="emit('open-edit', task)"
                            >
                                {{ task.title }}
                            </button>
                        </div>
                    </div>
                </template>
            </VCalendar>
        </div>
    </div>
</template>

<style scoped>
/* Сетка дней v-calendar: границы ячеек (неделя — grid из 7 .vc-day, .vc-weeknumber вне потока) */
.task-board-vcal-inner :deep(.vc-weekdays) {
    border-bottom: 1px solid color-mix(in srgb, var(--content-primary) 14%, transparent);
}

.task-board-vcal-inner :deep(.vc-weekdays > .vc-weekday:not(:last-child)) {
    border-right: 1px solid color-mix(in srgb, var(--content-primary) 12%, transparent);
}

.task-board-vcal-inner :deep(.vc-week > .vc-day) {
    border-bottom: 1px solid color-mix(in srgb, var(--content-primary) 12%, transparent);
}

.task-board-vcal-inner :deep(.vc-week > .vc-day:not(:last-child)) {
    border-right: 1px solid color-mix(in srgb, var(--content-primary) 12%, transparent);
}

.task-board-vcal-inner :deep(.vc-week:last-child > .vc-day) {
    border-bottom: none;
}
</style>
