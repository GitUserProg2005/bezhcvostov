import { ref } from 'vue';
import axios from 'axios';

export function useFetchProjects() {
    const projects = ref([]);
    const isLoading = ref(false);
    const error = ref('');

    async function fetchProjects() {
        isLoading.value = true;
        error.value = '';

        try {
            const { data } = await axios.get(route('projects.get'));
            projects.value = Array.isArray(data?.projects) ? data.projects : [];
        } catch (e) {
            error.value = e?.response?.data?.message ?? 'Не удалось загрузить проекты.';
        } finally {
            isLoading.value = false;
        }
    }

    return {
        projects,
        isLoading,
        error,
        fetchProjects,
    };
}
