<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Store from './components/Store.vue';
import { computed, onMounted, ref } from 'vue';
import { useFetchUserItems } from './composables/useFetchUserItems';

const isStoreOpen = ref(false);
const { activeItems, loading, fetchUserItems } = useFetchUserItems();

const visibleItems = computed(() => activeItems.value ?? []);

const getStyle = (item) => {
    const slot = item.slot ?? {};

    return {
        left: `${(slot.x ?? 0) * 100}%`,
        top: `${(slot.y ?? 0) * 100}%`,
        transform: `translate(-50%, -50%) scale(${slot.scale ?? 1.1})`,
        zIndex: 10,
    };
};

onMounted(() => {
    fetchUserItems();
});
</script>

<template>
    <DashboardLayout>
        <div class="p-4 overflow-x-auto task-scroll-hide touch-pan-x">
            <div class="relative inline-block">
                <img 
                    src="/img/game/nora.png" 
                    class="max-w-3xl pointer-events-none border-b rounded-3xl"
                >

                <button
                    type="button"
                    class="absolute right-[4%] top-[17%] w-28 h-24 rounded-xl bg-transparent"
                    aria-label="Open store"
                    @click="isStoreOpen = true"
                />

                <!-- Активные предметы -->
                <div
                    v-for="item in visibleItems"
                    :key="item.id"
                    class="absolute"
                    :style="getStyle(item)"
                >
                    <div :class="item.slot?.type === 'transport' ? 'animate-transport' : ''">
                        <img
                            :src="item.picture_url || '/img/game/store/kaktus.png'"
                            :alt="item.title"
                            class="w-24 h-24 object-contain"
                        >
                    </div>
                </div>

                <div v-if="loading" class="absolute left-4 bottom-4 text-xs text-white/80">
                    Загружаем вещи...
                </div>
            </div>
        </div>

        <Store :show="isStoreOpen" @close="isStoreOpen = false" />
    </DashboardLayout>
</template>

<style>
@keyframes transport-float {
    0%, 100% {
        transform: translate3d(0, 0, 0) rotate(-1deg);
    }
    50% {
        transform: translate3d(0, -8px, 0) rotate(1deg);
    }
}

.animate-transport {
    animation: transport-float 2s ease-in-out infinite;
    transform-origin: center bottom;
    will-change: transform;
}
</style> 