import { ref, onMounted } from 'vue';
import axios from 'axios';


export function useTasksFetch() {
    const tasks = ref([]);
    const isLoading = ref(false);

    const fetchTasks = async () => {
        isLoading.value = true;
        try {
            const response = await axios.get(route('tasks.get'));

            if (response.data.success) {
                tasks.value = response.data.tasks;
            }
        } catch (error) {
            console.error('Error fetching tasks:', error);
        } finally {
            isLoading.value = false;
        }
    };

    onMounted(fetchTasks);

    return { tasks, isLoading, fetchTasks };
}