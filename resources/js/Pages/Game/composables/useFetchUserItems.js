import { ref } from 'vue';
import axios from 'axios';

export function useFetchUserItems() {
    const activeItems = ref([]);
    const inactiveItems = ref([]);
    const loading = ref(false);
    const errorMessage = ref('');

    const fetchUserItems = async () => {
        loading.value = true;
        errorMessage.value = '';

        try {
            const response = await axios.get(route('game.slot-items'));
            activeItems.value = response.data?.items?.active ?? [];
            inactiveItems.value = response.data?.items?.inactive ?? [];
        } catch (error) {
            activeItems.value = [];
            inactiveItems.value = [];
            errorMessage.value = 'Не удалось загрузить вещи';
        } finally {
            loading.value = false;
        }
    };

    return {
        activeItems,
        inactiveItems,
        loading,
        errorMessage,
        fetchUserItems,
    };
}