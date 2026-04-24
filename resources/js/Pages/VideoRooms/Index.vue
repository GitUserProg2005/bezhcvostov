<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const isCreating = ref(false);
const errors = ref({});

async function createRoom() {
    if (isCreating.value) {
        return;
    }

    isCreating.value = true;
    errors.value = {};

    try {
        const { data } = await axios.post(route('video.create.room'), {
            title: `Звонок ${new Date().toLocaleString('ru-RU')}`,
        });

        if (data?.success && data?.link) {
            router.visit(data.link);
        }
    } catch (error) {
        errors.value = error?.response?.data?.errors ?? {};
    } finally {
        isCreating.value = false;
    }
}
</script>

<template>
    <DashboardLayout>
        <h3 class="title px-4 py-3">Коннект</h3>

        <div class="px-4">
            <a :href="route('video.insights.index')" class="primary-btn-white-blur inline-flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-xs"></i>
                История звонков
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 p-4">
            <button
                type="button"
                class="content-glass h-80 flex flex-col justify-center items-center gap-2 transition hover:scale-[1.01]"
                :disabled="isCreating"
                @click="createRoom"
            >
                <img src="/img/camera.png" class="object-contain ml-4" alt="">
                <h4 class="title-3">{{ isCreating ? 'Создаю комнату...' : 'Создать звонок с учителем' }}</h4>
            </button>

            <div class="flex flex-col f-hull justify-between gap-2">
                <div class="relative overflow-hidden content-accent h-1/2">
                    <h4 class="title-2">
                        Закрепляй знания
                    </h4>
                    <p>Хранение знаний вместе с учителем!</p>

                    <img src="/img/image.png" class="absolute -bottom-8 -right-3 w-64 rounded-2xl" alt="">
                </div>

                <div class="relative overflow-hidden content-glass h-1/2">
                    <h4 class="title-2">
                        Хвостик с тобой 
                    </h4>
                    <p class="context">Задавай вопросы и получай ответы на них!</p>

                    <img src="/img/maskot/hello.png" class="absolute -bottom-10 -right-3 w-24 rounded-2xl" alt="">
                </div>
            </div>
        </div>

        <p v-if="errors.title" class="px-4 pb-4 text-sm text-red-400">{{ errors.title[0] }}</p>
    </DashboardLayout>
</template>