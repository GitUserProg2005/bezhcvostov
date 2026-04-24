<script setup>
import { computed, nextTick, ref } from 'vue';
import ContextMenu from '@/Components/ContextMenu.vue';

defineOptions({ name: 'Folder' });

const props = defineProps({
    node: {
        type: Object,
        required: true,
    },
    depth: {
        type: Number,
        default: 0,
    },
    selectedNoteId: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits([
    'select-note',
    'create-folder',
    'create-note',
    'rename-item',
    'delete-item',
]);

const isFolder = computed(() => props.node?.type === 'folder');
const isSelectedNote = computed(() => props.node?.type === 'note' && props.selectedNoteId === props.node?.id);
const isOpen = ref(true);
const isEditingTitle = ref(false);
const editTitleValue = ref('');
const editInputRef = ref(null);

const rowPaddingStyle = computed(() => ({
    paddingLeft: `${8 + props.depth * 14}px`,
}));

function onRowClick() {
    if (isFolder.value) {
        isOpen.value = !isOpen.value;
        return;
    }

    emit('select-note', props.node);
}

function onRename() {
    emit('rename-item', props.node);
}

function onDelete() {
    emit('delete-item', props.node);
}

async function startInlineRename() {
    editTitleValue.value = props.node?.title ?? '';
    isEditingTitle.value = true;
    await nextTick();
    editInputRef.value?.focus();
    editInputRef.value?.select();
}

function cancelInlineRename() {
    isEditingTitle.value = false;
    editTitleValue.value = '';
}

function submitInlineRename() {
    const title = editTitleValue.value.trim();
    if (!title || title === props.node.title) {
        cancelInlineRename();
        return;
    }

    emit('rename-item', {
        ...props.node,
        title,
    });
    cancelInlineRename();
}
</script>

<template>
    <div class="select-none">
        <ContextMenu position="right">
            <template #default>
                <button
                    v-if="!isEditingTitle"
                    type="button"
                    class="w-full flex items-center gap-2 rounded-xl px-2 py-2 transition-colors"
                    :class="isSelectedNote ? 'bg-[#e97358]/15 text-[#e97358]' : 'hover:bg-content'"
                    :style="rowPaddingStyle"
                    @click="onRowClick"
                    @dblclick.stop="startInlineRename"
                >
                    <i
                        v-if="isFolder"
                        class="fa-solid text-xs"
                        :class="isOpen ? 'fa-chevron-down' : 'fa-chevron-right'"
                    />
                    <i
                        class="fa-regular"
                        :class="isFolder ? 'fa-folder text-[#e97358]' : 'fa-note-sticky'"
                    />
                    <span class="truncate text-sm">{{ node.title }}</span>
                </button>

                <div
                    v-else
                    class="w-full flex items-center gap-2 rounded-xl px-2 py-2"
                    :style="rowPaddingStyle"
                >
                    <i
                        v-if="isFolder"
                        class="fa-solid text-xs"
                        :class="isOpen ? 'fa-chevron-down' : 'fa-chevron-right'"
                    />
                    <i
                        class="fa-regular"
                        :class="isFolder ? 'fa-folder text-[#e97358]' : 'fa-note-sticky'"
                    />
                    <input
                        ref="editInputRef"
                        v-model="editTitleValue"
                        type="text"
                        class="input w-full !px-3 !py-2 !text-sm"
                        @keydown.enter.prevent="submitInlineRename"
                        @keydown.esc.prevent="cancelInlineRename"
                        @blur="submitInlineRename"
                    >
                </div>
            </template>

            <template #menu="{ closeMenu }">
                <div class="w-56 p-2 space-y-1">
                    <button
                        v-if="isFolder"
                        type="button"
                        class="w-full rounded-lg px-3 py-2 text-left hover:bg-content"
                        @click="emit('create-folder', node.id); closeMenu()"
                    >
                        Новая папка
                    </button>
                    <button
                        v-if="isFolder"
                        type="button"
                        class="w-full rounded-lg px-3 py-2 text-left hover:bg-content"
                        @click="emit('create-note', node.id); closeMenu()"
                    >
                        Новая заметка
                    </button>
                    <button
                        type="button"
                        class="w-full rounded-lg px-3 py-2 text-left hover:bg-content"
                        @click="startInlineRename(); closeMenu()"
                    >
                        Переименовать
                    </button>
                    <button
                        type="button"
                        class="w-full rounded-lg px-3 py-2 text-left text-red-500 hover:bg-content"
                        @click="onDelete(); closeMenu()"
                    >
                        Удалить
                    </button>
                </div>
            </template>
        </ContextMenu>

        <div v-if="isFolder && isOpen && Array.isArray(node.children)">
            <Folder
                v-for="child in node.children"
                :key="`${child.type}-${child.id}`"
                :node="child"
                :depth="depth + 1"
                :selected-note-id="selectedNoteId"
                @select-note="emit('select-note', $event)"
                @create-folder="emit('create-folder', $event)"
                @create-note="emit('create-note', $event)"
                @rename-item="emit('rename-item', $event)"
                @delete-item="emit('delete-item', $event)"
            />
        </div>
    </div>
</template>
