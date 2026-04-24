<script setup>
import Modal from '@/Components/Modal.vue';
import Insight from './Insight.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    insight: { type: Object, default: null },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Modal :show="show" max-width="6xl" @close="emit('close')">
        <div class="custom-scroll max-h-[90vh] space-y-4 overflow-y-auto p-4 md:p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="title-2">Инсайт звонка</h2>
                    <p class="context mt-1">
                        {{ insight?.room?.title ?? 'Комната' }}
                    </p>
                </div>
                <button type="button" class="round-btn round-btn-neutral" @click="emit('close')">
                    <i class="fa-solid fa-xmark text-sm" />
                </button>
            </div>

            <Insight v-if="insight" :insight="insight" />
        </div>
    </Modal>
</template>

