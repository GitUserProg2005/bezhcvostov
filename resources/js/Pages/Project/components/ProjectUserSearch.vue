<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Search from '@/Components/Search/Search.vue';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['user-added']);

const addingId = ref(null);
const flashMessage = ref('');
const flashError = ref('');

async function searchByCode(query) {
    const trimmed = query.trim();
    if (!trimmed) return [];

    const { data } = await axios.get(route('projects.users.search', props.projectId), {
        params: { q: trimmed },
    });

    return Array.isArray(data) ? data : [];
}

async function addUser(user) {
    addingId.value = user.id;
    flashMessage.value = '';
    flashError.value = '';

    try {
        await axios.post(route('projects.users.add', props.projectId), {
            user_id: user.id,
        });

        emit('user-added', user);
        flashMessage.value = 'Пользователь добавлен в проект.';
    } catch (e) {
        flashError.value = e?.response?.data?.message ?? 'Не удалось добавить пользователя.';
    } finally {
        addingId.value = null;
    }
}
</script>

<template>
    <div class="space-y-3">
        <div v-if="flashMessage" class="text-sm text-green-700">{{ flashMessage }}</div>
        <div v-if="flashError" class="text-sm text-red-600">{{ flashError }}</div>

        <div class="relative z-10">
            <Search :search-fn="searchByCode" search-label="Код аккаунта пользователя">
                <template #item="{ item }">
                    <div class="flex items-center justify-between gap-3 py-2">
                        <div class="flex min-w-0 items-center gap-3">
                            <Avatar :name="item.name" :src="item.avatar_url" :user-id="item.id" size="sm" />
                            <div class="min-w-0">
                                <p class="text-sm font-medium truncate">{{ item.name }}</p>
                                <p class="t-mini text-gray-500">Код: {{ item.account_code }}</p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="primary-btn shrink-0 text-xs px-3 py-1.5"
                            :disabled="addingId === item.id"
                            @click="addUser(item)"
                        >
                            {{ addingId === item.id ? '...' : 'Добавить' }}
                        </button>
                    </div>
                </template>
            </Search>
        </div>
    </div>
</template>
