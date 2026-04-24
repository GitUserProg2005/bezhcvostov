<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    searchFn: {
        type: Function,
        required: true,
    },
    debounce: {
        type: Number,
        default: 400,
    },
    searchLabel: {
        type: String,
        default: 'Что ищем сегодня?',
    },
});

const query = ref('');
const results = ref([]);
const isSearching = ref(false);

let timeout = null;

watch(query, (value) => {
    clearTimeout(timeout);

    if (!value.trim()) {
        results.value = [];
        return;
    }

    timeout = setTimeout(async () => {
        isSearching.value = true;

        try {
            results.value = await props.searchFn(value);
        } catch (e) {
            console.error('Search error:', e);
            results.value = [];
        } finally {
            isSearching.value = false;
        }
    }, props.debounce);
});
</script>

<template>
    <div class="flex w-full lg:block justify-between items-center relative">
        <!-- INPUT -->
        <div class="flex w-full relative">
            <input
                v-model="query"
                type="text"
                :placeholder="searchLabel"
                class="search-input w-full px-4 py-2 pr-10 border rounded"
            />

            <span class="absolute inset-y-1/4 right-2 text-gray-400 pointer-events-none">
                <i
                    class="fa-solid"
                    :class="isSearching ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"
                />
            </span>
        </div>

        <!-- DROPDOWN -->
        <div
            v-if="query"
            class="w-full absolute left-0 top-12
                   bg-body rounded-xl p-4 shadow-xl z-50"
        >
            <h3 class="mb-2 text-sm text-gray-400">Найдено:</h3>

            <div v-if="results.length">
                <div class="space-y-2">
                    <template 
                        v-for="item in results"
                        :key="item.id"
                    >
                        <slot
                            name="item"
                            :item="item"
                        />
                    </template>
                </div>
            </div>

            <div v-else-if="!isSearching" class="text-sm text-gray-400">
                Ничего не найдено :(
            </div>
        </div>
    </div>
</template>
