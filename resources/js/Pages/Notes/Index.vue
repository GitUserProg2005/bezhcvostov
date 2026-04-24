<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ContextMenu from '@/Components/ContextMenu.vue';
import Modal from '@/Components/Modal.vue';
import Folder from './components/Folder.vue';
import MilkdownEditor from './components/MilkdownEditor.vue';

import { onMounted, ref } from 'vue';
import axios from 'axios';

const treeItems = ref([]);
const selectedNote = ref(null);
const selectedNoteContent = ref('');
const loadingTree = ref(false);
const loadingNote = ref(false);
const saving = ref(false);
const isMobileNoteOpen = ref(false);
const isStartedUpdateTitle = ref(false);

const isActionModalOpen = ref(false);
const actionModalType = ref('create-note');
const actionModalNode = ref(null);
const actionModalParentFolderId = ref(null);
const actionModalTitleInput = ref('');
const actionModalContentInput = ref('');
const actionModalBusy = ref(false);

async function loadTree() {
    loadingTree.value = true;
    try {
        const { data } = await axios.get(route('folders.tree'));
        treeItems.value = Array.isArray(data?.items) ? data.items : [];
    } finally {
        loadingTree.value = false;
    }
}

async function loadNote(noteId) {
    loadingNote.value = true;
    try {
        const { data } = await axios.get(route('notes.show', noteId));
        selectedNote.value = data;
        selectedNoteContent.value = data?.content ?? '';
    } finally {
        loadingNote.value = false;
    }
}

function onSelectNote(node) {
    isMobileNoteOpen.value = true;
    loadNote(node.id);
}

function openCreateFolder(parentId = null) {
    actionModalType.value = 'create-folder';
    actionModalNode.value = null;
    actionModalParentFolderId.value = parentId;
    actionModalTitleInput.value = '';
    actionModalContentInput.value = '';
    isActionModalOpen.value = true;
}

function openCreateNote(folderId = null) {
    actionModalType.value = 'create-note';
    actionModalNode.value = null;
    actionModalParentFolderId.value = folderId;
    actionModalTitleInput.value = '';
    actionModalContentInput.value = '';
    isActionModalOpen.value = true;
}

async function renameItem(node) {
    actionModalType.value = 'rename';
    actionModalNode.value = node;
    actionModalParentFolderId.value = null;
    actionModalTitleInput.value = node.title;
    actionModalContentInput.value = '';
    isActionModalOpen.value = true;
}

async function deleteItem(node) {
    actionModalType.value = 'delete';
    actionModalNode.value = node;
    actionModalParentFolderId.value = null;
    actionModalTitleInput.value = node.title;
    actionModalContentInput.value = '';
    isActionModalOpen.value = true;
}

async function saveSelectedNote() {
    if (!selectedNote.value) {
        return;
    }

    saving.value = true;
    try {
        const { data } = await axios.post(route('notes.update'), {
            id: selectedNote.value.id,
            title: selectedNote.value.title,
            content: selectedNoteContent.value,
            folder_id: selectedNote.value.folder_id,
            task_id: selectedNote.value.task_id,
        });

        selectedNote.value = data;
        selectedNoteContent.value = data?.content ?? '';
        isStartedUpdateTitle.value = false;    
        await loadTree();
    } finally {
        saving.value = false;
    }
}

function closeMobileNote() {
    isMobileNoteOpen.value = false;
}

function closeActionModal() {
    isActionModalOpen.value = false;
    actionModalBusy.value = false;
}

async function submitActionModal() {
    if (actionModalBusy.value) {
        return;
    }

    actionModalBusy.value = true;
    const title = actionModalTitleInput.value.trim();

    try {
        if (actionModalType.value === 'create-folder') {
            if (!title) return;
            await axios.post(route('folders.create'), {
                title,
                parent_id: actionModalParentFolderId.value,
            });
            await loadTree();
            closeActionModal();
            return;
        }

        if (actionModalType.value === 'create-note') {
            if (!title) return;
            const { data } = await axios.post(route('notes.create'), {
                title,
                content: actionModalContentInput.value,
                folder_id: actionModalParentFolderId.value,
            });
            await loadTree();
            closeActionModal();
            await loadNote(data.id);
            return;
        }

        if (actionModalType.value === 'rename' && actionModalNode.value) {
            if (!title || title === actionModalNode.value.title) {
                closeActionModal();
                return;
            }

            if (actionModalNode.value.type === 'folder') {
                await axios.post(route('folders.update-title'), {
                    id: actionModalNode.value.id,
                    title,
                });
            } else {
                await axios.post(route('notes.update-title'), {
                    id: actionModalNode.value.id,
                    title,
                });
                if (selectedNote.value?.id === actionModalNode.value.id) {
                    selectedNote.value = {
                        ...selectedNote.value,
                        title,
                    };
                }
            }

            await loadTree();
            closeActionModal();
            return;
        }

        if (actionModalType.value === 'delete' && actionModalNode.value) {
            if (actionModalNode.value.type === 'folder') {
                await axios.post(route('folders.delete'), { id: actionModalNode.value.id });
            } else {
                await axios.post(route('notes.delete'), { id: actionModalNode.value.id });
                if (selectedNote.value?.id === actionModalNode.value.id) {
                    selectedNote.value = null;
                    selectedNoteContent.value = '';
                }
            }

            await loadTree();
            closeActionModal();
        }
    } finally {
        actionModalBusy.value = false;
    }
}

onMounted(async () => {
    await loadTree();
});
</script>

<template>
<DashboardLayout>
    <div class="p-4 pb-24 md:pb-4 space-y-4 w-full max-w-full min-w-0 overflow-x-hidden md:h-[calc(100vh-6.5rem)] md:flex md:flex-col">
        <div class="flex items-center justify-between">
            <h3 class="title">Заметки</h3>
            
            <ContextMenu position="left">
                <template #default="{ toggleOpen }">
                    <button type="button" class="round-btn round-btn-accent" @click="toggleOpen">
                        <i class="fa-solid fa-plus text-sm" />
                    </button>
                </template>
                <template #menu>
                    <div class="w-56 p-2 space-y-1">
                        <button type="button" class="w-full rounded-lg px-3 py-2 text-left hover:bg-content" @click="openCreateFolder(null)">
                            Новая папка
                        </button>
                        <button type="button" class="w-full rounded-lg px-3 py-2 text-left hover:bg-content" @click="openCreateNote(null)">
                            Новая заметка
                        </button>
                    </div>
                </template>
            </ContextMenu>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[12rem_1fr] gap-2 h-full min-h-0">
            <section class="bg-content rounded-3xl p-2 md:p-2 min-h-[22rem] md:min-h-0 md:h-full overflow-y-auto custom-scroll">
                <div v-if="loadingTree" class="context">Загружаю иерархию...</div>
                <div v-else-if="!treeItems.length" class="h-full min-h-[16rem] flex flex-col items-center justify-center gap-2 context text-center px-4">
                    <span>Папок и заметок пока нет.</span>
                </div>
                <div v-else class="space-y-1">
                    <Folder
                        v-for="node in treeItems"
                        :key="`${node.type}-${node.id}`"
                        :node="node"
                        :selected-note-id="selectedNote?.id ?? null"
                        @select-note="onSelectNote"
                        @create-folder="openCreateFolder"
                        @create-note="openCreateNote"
                        @rename-item="renameItem"
                        @delete-item="deleteItem"
                    />
                </div>
            </section>

            <section class="hidden lg:flex bg-body rounded-3xl p-3 md:p-4 min-h-[22rem] md:min-h-0 md:h-full flex-col gap-3 overflow-hidden">
                <template v-if="selectedNote">
                    <div @dblclick="isStartedUpdateTitle = true" v-if="!isStartedUpdateTitle">
                        <h3 class="title-2">{{ selectedNote.title }}</h3>
                    </div>

                    <div v-else class="flex items-center justify-between gap-3">
                        <input
                            v-model="selectedNote.title"
                            class="input w-full"
                            type="text"
                            placeholder="Название заметки"
                        >
                        <button type="button" class="primary-btn whitespace-nowrap" :disabled="saving" @click="saveSelectedNote">
                            {{ saving ? 'Сохраняю...' : 'Сохранить' }}
                        </button>
                    </div>

                    <div v-if="loadingNote" class="context">Загружаю заметку...</div>
                    <div v-else class="flex-1 min-h-0 overflow-y-auto custom-scroll">
                        <MilkdownEditor
                            :vmodel="selectedNoteContent"
                            @update:vmodel="selectedNoteContent = $event"
                        />
                    </div>
                </template>

                <div v-else class="h-full bg-body rounded-3xl flex items-center justify-center flex flex-col gap-2 context text-center px-4">
                    <img :src="!treeItems.length ? '/img/maskot/maskot_with_pen.png' : '/img/maskot/with_panel.png'" class="object-contain w-48" alt="">
                    <span class="text-center">
                        {{ !treeItems.length ? 'Пока нет папок и заметок. Создайте первую заметку.' : 'Выберите заметку слева или создайте новую.' }}
                    </span>
                </div>
            </section>
        </div>

        <div
            v-if="isMobileNoteOpen"
            class="fixed inset-0 z-[100] bg-black/40 lg:hidden"
            @click="closeMobileNote"
        />

        <aside
            class="fixed inset-0 right-0 w-full bg-content p-2 transform transition-transform duration-300 z-[110] lg:hidden overflow-y-auto"
            :class="isMobileNoteOpen ? 'translate-x-0' : 'translate-x-full'"
        >
            <div class="flex items-center gap-4 ml-4 mb-6">
                <button type="button" @click="closeMobileNote">
                    <i class="fa-solid fa-arrow-left" />
                </button>
                <h2 class="title-2">Заметка</h2>
            </div>

            <section class="bg-content rounded-3xl p-3 min-h-[calc(100dvh-7rem)] flex flex-col gap-3 overflow-hidden">
                <template v-if="selectedNote">
                    <div class="flex items-center justify-between gap-3">
                        <input
                            v-model="selectedNote.title"
                            class="input w-full"
                            type="text"
                            placeholder="Название заметки"
                        >
                        <button type="button" class="primary-btn whitespace-nowrap" :disabled="saving" @click="saveSelectedNote">
                            {{ saving ? 'Сохраняю...' : 'Сохранить' }}
                        </button>
                    </div>

                    <div v-if="loadingNote" class="context">Загружаю заметку...</div>
                    <div v-else class="flex-1 min-h-0 overflow-y-auto custom-scroll">
                        <MilkdownEditor
                            :vmodel="selectedNoteContent"
                            @update:vmodel="selectedNoteContent = $event"
                        />
                    </div>
                </template>

                <div v-else class="h-full flex flex-col items-center justify-center gap-2 context text-center px-4">
                    <img v-if="!treeItems.length" src="/img/maskot/maskot_with_pen.png" class="object-contain w-28" alt="">
                    {{ !treeItems.length ? 'Пока нет папок и заметок.' : 'Выберите заметку слева.' }}
                </div>
            </section>
        </aside>

        <div class="fixed bottom-3 left-1/2 z-[90] w-[calc(100%-1.5rem)] max-w-md -translate-x-1/2 lg:hidden">
            <div class="bg-content-glass rounded-2xl p-2">
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" class="primary-btn-white-blur w-full" @click="openCreateFolder(null)">
                        <i class="fa-solid fa-folder-plus mr-2 text-xs" />
                        Папка
                    </button>
                    <button type="button" class="primary-btn w-full" @click="openCreateNote(null)">
                        <i class="fa-solid fa-note-sticky mr-2 text-xs" />
                        Заметка
                    </button>
                </div>
            </div>
        </div>

        <Modal :show="isActionModalOpen" max-width="5xl" :mobile-full-screen="true" @close="closeActionModal">
            <div class="min-h-dvh md:min-h-0 p-4 md:p-6 flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <h2 class="title-2">
                        {{
                            actionModalType === 'create-folder' ? 'Создать папку'
                            : actionModalType === 'create-note' ? 'Создать заметку'
                            : actionModalType === 'rename' ? 'Переименовать'
                            : 'Удаление'
                        }}
                    </h2>
                    <button type="button" class="round-btn round-btn-neutral" @click="closeActionModal">
                        <i class="fa-solid fa-xmark text-sm" />
                    </button>
                </div>

                <template v-if="actionModalType === 'delete'">
                    <p class="context">
                        Удалить "{{ actionModalTitleInput }}"?
                    </p>
                </template>

                <template v-else>
                    <input
                        v-model="actionModalTitleInput"
                        type="text"
                        class="input w-full"
                        placeholder="Название"
                    >
                </template>

                <div v-if="actionModalType === 'create-note'" class="flex-1 min-h-[45dvh] md:min-h-[26rem]">
                    <MilkdownEditor
                        :vmodel="actionModalContentInput"
                        @update:vmodel="actionModalContentInput = $event"
                    />
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="primary-btn-white-blur" @click="closeActionModal">
                        Отмена
                    </button>
                    <button
                        type="button"
                        class="primary-btn"
                        :disabled="actionModalBusy || (actionModalType !== 'delete' && !actionModalTitleInput.trim())"
                        @click="submitActionModal"
                    >
                        {{
                            actionModalBusy ? 'Выполняю...'
                            : actionModalType === 'create-folder' ? 'Создать папку'
                            : actionModalType === 'create-note' ? 'Создать заметку'
                            : actionModalType === 'rename' ? 'Сохранить'
                            : 'Удалить'
                        }}
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</DashboardLayout>
</template>