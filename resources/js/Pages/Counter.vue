<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    count: { type: Number, default: 0 },
});

const count = ref(props.count);

function increment() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    axios.post(route('counter.increment'), {}, {
        headers: { 'X-CSRF-TOKEN': csrfToken },
    })
        .then(({ data }) => { count.value = data.count; })
        .catch(() => {});
}

let echoChannel = null;

onMounted(() => {
    if (typeof window.Echo !== 'undefined') {
        echoChannel = window.Echo.channel('counter')
            .listen('.counter.updated', (e) => {
                // Диагностика: событие должно прилетать сюда из Reverb
                console.log('[counter.updated] payload', e);
                count.value = e.count ?? count.value;
            });

        echoChannel.subscribed(() => {
            console.log('✅ Подписка на канал counter прошла успешно!');
        });

        // ❌ Ошибка подписки
        echoChannel.error((err) => {
            console.error('❌ Ошибка при подписке на канал counter:', err);
        });
    }
});

onUnmounted(() => {
    if (echoChannel && typeof window.Echo !== 'undefined') {
        window.Echo.leave('counter');
    }
});
</script>

<template>
    <Head title="Тест WebSocket — счётчик" />
    <AppLayout>
        <div class="p-4 lg:p-6">
            <h1 class="title mb-4">Тест WebSocket</h1>
            <p class="context mb-6">Счётчик хранится в кэше (не в БД). Обновление приходит по Reverb/Echo.</p>
            <div class="flex items-center gap-4">
                <span class="text-4xl font-bold tabular-nums">{{ count }}</span>
                <button
                    type="button"
                    class="primary-btn"
                    @click="increment"
                >
                    +1
                </button>
            </div>
            <p class="context mt-4 text-sm text-gray-500">Откройте страницу в двух вкладках и нажимайте +1 — значение синхронизируется через WebSocket.</p>
        </div>
    </AppLayout>
</template>
