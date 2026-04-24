<script setup>
import { ref } from 'vue';
import axios from 'axios';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import InsightModal from './components/InsightModal.vue';

const props = defineProps({
    insights: {
        type: Array,
        default: () => [],
    },
});

const isLoading = ref(false);
const selectedInsight = ref(null);
const showModal = ref(false);

async function openInsight(item) {
    isLoading.value = true;
    try {
        const { data } = await axios.get(route('video.insights.show', item.id));
        if (data?.success) {
            selectedInsight.value = data.insight;
            showModal.value = true;
        }
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <DashboardLayout>
        <div class="p-4 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h1 class="title">История звонков</h1>
            </div>

            <div v-if="!insights.length" class="content-glass p-6 text-center">
                <img src="/img/maskot/maskot_with_pen.png" class="mx-auto w-28 object-contain" alt="">
                <p class="context mt-2">Пока нет инсайтов звонков.</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-3">
                <button
                    v-for="item in insights"
                    :key="item.id"
                    type="button"
                    class="content-outline p-4 text-left transition hover:translate-y-[-1px]"
                    @click="openInsight(item)"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="title-2 text-base">{{ item.title }}</h3>
                        <span class="context text-xs">{{ item.created_at }}</span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="badge badge-neutral">{{ item.room?.title }}</span>
                        <a
                            v-if="item.room?.link_id"
                            class="context underline decoration-dotted"
                            :href="route('video.join.room', item.room.link_id)"
                            @click.stop
                        >
                            Перейти в комнату
                        </a>
                    </div>
                </button>
            </div>
        </div>

        <InsightModal :show="showModal" :insight="selectedInsight" @close="showModal = false" />

        <div
            v-if="isLoading"
            class="fixed inset-0 z-[300] flex items-center justify-center bg-black/40"
        >
            <div class="content-glass px-5 py-3">Загружаем инсайт...</div>
        </div>
    </DashboardLayout>
</template>

