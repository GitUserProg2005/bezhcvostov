<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Modal from '@/Components/Modal.vue';

const emit = defineEmits(['created']);

const showModal = ref(false);
const isSubmitting = ref(false);
const title = ref('');
const type = ref('public');
const errors = ref({});

function openModal() {
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    errors.value = {};
}

function resetForm() {
    title.value = '';
    type.value = 'public';
    errors.value = {};
}

async function createProject() {
    isSubmitting.value = true;
    errors.value = {};

    try {
        const { data } = await axios.post(route('projects.create'), {
            title: title.value,
            type: type.value,
        });

        emit('created', data?.project ?? null);
        closeModal();
        resetForm();
    } catch (e) {
        errors.value = e?.response?.data?.errors ?? {};
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <button type="button" class="primary-btn flex items-center gap-2" @click="openModal">
        Создать проект
        <i class="fa-solid fa-plus" />
    </button>

    <Modal :show="showModal" max-width="2xl" @close="closeModal">
        <div class="space-y-4 p-4 md:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="title-2">Новый проект</h2>
                <button type="button" class="border-0 bg-transparent p-1 leading-none text-inherit" @click="closeModal">
                    <i class="fa-solid fa-xmark text-xl" />
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <h3 class="title-2">Название</h3>
                    <input v-model="title" type="text" class="input mt-2 w-full" placeholder="Например: Математика 8А" />
                    <p v-if="errors.title" class="t-mini mt-2 text-red-400">{{ errors.title[0] }}</p>
                </div>

                <div>
                    <h3 class="title-2">Тип проекта</h3>
                    <select v-model="type" class="input mt-2 w-full">
                        <option value="public">Публичный</option>
                        <option value="private">Приватный</option>
                    </select>
                    <p v-if="errors.type" class="t-mini mt-2 text-red-400">{{ errors.type[0] }}</p>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" class="primary-btn-white-blur" @click="closeModal">Отмена</button>
                <button type="button" class="primary-btn" :disabled="isSubmitting" @click="createProject">
                    {{ isSubmitting ? 'Создаем...' : 'Создать' }}
                </button>
            </div>
        </div>
    </Modal>
</template>
