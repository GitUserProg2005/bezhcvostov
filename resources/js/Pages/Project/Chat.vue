<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import axios from 'axios';
import { Link, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Avatar from '@/Components/Avatar.vue';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    chat: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const currentUserId = page.props.auth?.user?.id ?? null;
const messages = ref([]);
const content = ref('');
const isLoading = ref(false);
const isSending = ref(false);
const error = ref('');
const messagesContainer = ref(null);

async function scrollToBottom() {
    await nextTick();
    if (!messagesContainer.value) return;
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
}

async function loadMessages() {
    isLoading.value = true;
    error.value = '';

    try {
        const { data } = await axios.get(route('projects.chats.messages', [props.project.id, props.chat.id]));
        messages.value = Array.isArray(data?.messages) ? data.messages : [];
        await scrollToBottom();
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Не удалось загрузить сообщения.';
    } finally {
        isLoading.value = false;
    }
}

async function sendMessage() {
    const text = content.value.trim();
    if (!text) return;

    isSending.value = true;
    error.value = '';

    try {
        const { data } = await axios.post(route('projects.chats.messages.add', [props.project.id, props.chat.id]), {
            content: text,
        });
        if (data?.message) {
            messages.value.push(data.message);
        }
        content.value = '';
        await scrollToBottom();
    } catch (e) {
        error.value = e?.response?.data?.message ?? 'Не удалось отправить сообщение.';
    } finally {
        isSending.value = false;
    }
}

function subscribeToChat() {
    if (!window.Echo) return;

    window.Echo.private(`projects.chat.${props.chat.id}`)
        .listen('.message.sent', async (event) => {
            if (event?.message) {
                messages.value.push(event.message);
                await scrollToBottom();
            }
        });
}

function unsubscribeFromChat() {
    if (!window.Echo) return;
    window.Echo.leave(`private-projects.chat.${props.chat.id}`);
}

onMounted(async () => {
    await loadMessages();
    subscribeToChat();
});

onBeforeUnmount(() => {
    unsubscribeFromChat();
});
</script>

<template>
    <DashboardLayout>
        <div class="flex h-[calc(100vh-6.5rem)] flex-col gap-4 p-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link :href="route('projects.show', project.id)" class="round-btn round-btn-accent">
                        <i class="fa-solid fa-arrow-left"></i>
                    </Link>
                    /
                    <h1 class="title">{{ chat.title }}</h1>
                </div>
            </div>

            <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

            <section class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <div ref="messagesContainer" class="custom-scroll flex-1 space-y-3 overflow-y-auto">
                    <p v-if="isLoading" class="context">Загружаем сообщения...</p>
                    <p v-else-if="!messages.length" class="context">Сообщений пока нет.</p>

                    <article
                        v-for="message in messages"
                        :key="message.id"
                        class="max-w-[75%] lg:max-w-[25%] p-2 rounded-2xl"
                        :class="message.user?.id === currentUserId ? 'ml-auto bg-content-accent' : 'bg-body'"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <Avatar
                                :name="message.user?.name ?? 'Unknown'"
                                :src="message.user?.avatar_url ?? null"
                                :user-id="message.user?.id ?? null"
                                size="sm"
                            />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold truncate">{{ message.user?.name ?? 'Unknown' }}</p>
                            </div>
                        </div>
                        <p class="text-sm whitespace-pre-wrap break-words">{{ message.content }}</p>
                    </article>
                </div>

                <div class="border-content-outline mt-4">
                    <div class="flex items-end gap-2">
                        <input
                            v-model="content"
                            class="input w-full !px-4 !py-2 text-sm"
                            placeholder="Введите сообщение..."
                            @keydown.enter.prevent="sendMessage"
                        />
                        <button
                            type="button"
                            class="primary-btn !px-4 !py-2 shrink-0"
                            :disabled="isSending"
                            @click="sendMessage"
                        >
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </DashboardLayout>
</template>
