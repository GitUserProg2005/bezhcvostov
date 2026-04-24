<script setup>
import Modal from '@/Components/Modal.vue';
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);
const loading = ref(false);
const buyingItemId = ref(null);
const errorMessage = ref('');
const items = ref([]);

const groupedItems = computed(() => {
    return items.value.reduce((acc, item) => {
        const type = item.slot?.type ?? 'other';

        if (!acc[type]) {
            acc[type] = [];
        }

        acc[type].push(item);
        return acc;
    }, {});
});

const typeTitles = {
    computer: 'Компьютер',
    bed: 'Кровать',
    larder: 'Кладовка',
    accessory: 'Аксессуары',
    transport: 'Транспорт',
    other: 'Другое',
};

async function getStoreItems() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.get(route('game.items'));
        items.value = response.data?.items ?? [];
    } catch (error) {
        items.value = [];
        errorMessage.value = 'Не удалось загрузить товары';
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    getStoreItems();
});

async function addItemToUser(itemId) {
    buyingItemId.value = itemId;
    errorMessage.value = '';

    try {
        await axios.post(route('game.items.add'), { item_id: itemId });
    } catch (error) {
        errorMessage.value = 'Не удалось купить предмет';
    } finally {
        buyingItemId.value = null;
    }
}
</script>

<template>
    <Modal :show="show" max-width="6xl" @close="emit('close')">
        <section class="bg-content rounded-3xl p-4 sm:p-6 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="title-2">Магазин</h2>
                <button
                    type="button"
                    class="w-8 h-8 rounded-full bg-content flex items-center justify-center hover:opacity-80 transition"
                    @click="emit('close')"
                    aria-label="Close store"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div v-if="loading" class="text-sm text-gray-400">
                Загружаем товары...
            </div>

            <div v-else-if="errorMessage" class="text-sm text-red-400">
                {{ errorMessage }}
            </div>

            <div v-else-if="items.length === 0" class="text-sm text-gray-400">
                В магазине пока нет товаров
            </div>

            <div v-else class="space-y-6 max-h-[75vh] overflow-y-auto pr-1">
                <div
                    v-for="(typedItems, type) in groupedItems"
                    :key="type"
                    class="space-y-3"
                >
                    <h3 class="title-3">{{ typeTitles[type] ?? typeTitles.other }}</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                        <article
                            v-for="item in typedItems"
                            :key="item.id"
                            class="rounded-2xl p-3 bg-body border border-white/10"
                        >
                            <img
                                :src="item.picture_url || '/img/game/store/kaktus.png'"
                                :alt="item.title"
                                class="w-full h-28 object-contain rounded-xl bg-content p-2"
                            >

                            <div class="mt-3 space-y-1">
                                <h4 class="font-semibold leading-tight">{{ item.title }}</h4>
                                <p class="text-gray-400 min-h-8">{{ item.description || 'Без описания' }}</p>
                            </div>

                            <div class="mt-3 flex items-center justify-between">
                                <span class="inline-flex items-center gap-1 text-sm font-semibold">
                                    <img src="/img/crystal.png" alt="crystals" class="w-4 h-4 object-contain">
                                    {{ item.price }}
                                </span>

                                <button
                                    type="button"
                                    class="primary-btn"
                                    :disabled="buyingItemId === item.id"
                                    @click="addItemToUser(item.id)"
                                >
                                    {{ buyingItemId === item.id ? '...' : 'Купить' }}
                                </button>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>
    </Modal>
</template>