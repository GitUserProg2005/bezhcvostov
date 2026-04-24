<script setup>
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';
import axios from 'axios';
import OutputMessage from '@/Pages/AiChat/components/OutputMessage.vue';
import MascotEmotionAvatar from '@/Pages/AiChat/components/MascotEmotionAvatar.vue';

const x = ref(20);
const y = ref(20);
const isDragging = ref(false);
const offsetX = ref(0);
const offsetY = ref(0);
const chatIsOpened = ref(false);
const isLoadingMessages = ref(false);
const isSending = ref(false);
const messageText = ref('');
const messages = ref([]);
const messagesContainer = ref(null);

const isTopHalf = computed(() => y.value < window.innerHeight / 2);

const getMainRect = () => {
    const main = document.querySelector('main');
    if (!main) {
        return {
            left: 0,
            top: 0,
            right: window.innerWidth,
            bottom: window.innerHeight,
        };
    }

    return main.getBoundingClientRect();
};

const clampPosition = (nextX, nextY) => {
    const rect = getMainRect();
    const size = 56;

    const minX = rect.left;
    const minY = rect.top;
    const maxX = rect.right - size;
    const maxY = rect.bottom - size;

    return {
        x: Math.min(Math.max(nextX, minX), maxX),
        y: Math.min(Math.max(nextY, minY), maxY),
    };
};

const scrollToBottom = async () => {
    await nextTick();
    if (!messagesContainer.value) {
        return;
    }

    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
};

const fetchMessages = async () => {
    isLoadingMessages.value = true;

    try {
        const { data } = await axios.get(route('ai.messages.get'));
        messages.value = Array.isArray(data?.messages) ? data.messages : [];
        await scrollToBottom();
    } finally {
        isLoadingMessages.value = false;
    }
};

const toggleChat = async () => {
    chatIsOpened.value = !chatIsOpened.value;

    if (chatIsOpened.value && messages.value.length === 0) {
        await fetchMessages();
    }
};

const startDrag = (event) => {
    isDragging.value = true;
    offsetX.value = event.clientX - x.value;
    offsetY.value = event.clientY - y.value;

    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
};

const onDrag = (event) => {
    if (!isDragging.value) {
        return;
    }

    const nextX = event.clientX - offsetX.value;
    const nextY = event.clientY - offsetY.value;
    const clamped = clampPosition(nextX, nextY);

    x.value = clamped.x;
    y.value = clamped.y;
};

const stopDrag = () => {
    isDragging.value = false;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
};

const resolveHref = (value) => {
    if (!value || typeof value !== 'string') {
        return '#';
    }

    const trimmed = value.trim();
    if (trimmed === '') {
        return '#';
    }

    if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('/')) {
        return trimmed;
    }

    if (typeof route === 'function') {
        try {
            return route(trimmed);
        } catch (error) {
            return '#';
        }
    }

    return '#';
};

const processMessage = async () => {
    const text = messageText.value.trim();
    if (!text || isSending.value) {
        return;
    }

    messageText.value = '';
    isSending.value = true;

    try {
        const { data } = await axios.post(route('ai.messages.process'), { text });
        const newMessages = Array.isArray(data?.messages) ? data.messages : [];
        messages.value.push(...newMessages);
        await scrollToBottom();
    } finally {
        isSending.value = false;
    }
};

onBeforeUnmount(() => {
    stopDrag();
});
</script>

<template>
    <div class="fixed z-50" :style="{ left: `${x}px`, top: `${y}px` }">
        <div
            v-if="!chatIsOpened"
            class="round-btn round-btn-accent cursor-move"
            :style="{ backgroundColor: 'var(--bg-card)' }"
            @mousedown="startDrag"
            @click="toggleChat"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg>
        </div>

        <div v-else class="relative">
            <div
                class="round-btn round-btn-accent cursor-move"
                :style="{ backgroundColor: 'var(--bg-card)' }"
                @mousedown="startDrag"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"></path></svg>
            </div>

            <div
                class="absolute w-[18rem] h-[30rem] bg-content-glass rounded-2xl overflow-hidden flex flex-col"
                :class="['left-full ml-2', isTopHalf ? 'top-0' : 'bottom-0']"
            >
                <div class="flex items-center justify-between p-4 border-b border-white/10">
                    <h3 class="title-font-4">Хвостик</h3>
                    <button @click="toggleChat">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div ref="messagesContainer" class="flex-1 overflow-y-auto p-3 space-y-2 custom-scroll">
                    <p v-if="isLoadingMessages" class="context">Загрузка...</p>
                    <p v-else-if="messages.length === 0" class="context">Напиши сообщение Хвостику.</p>

                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="w-full flex"
                        :class="message.sender === 'user' ? 'justify-end' : 'justify-start'"
                    >
                    <MascotEmotionAvatar v-if="message.sender === 'ai'" :emotion="message.emotion" />
                        <div
                            class="max-w-[85%] rounded-2xl px-3 py-2 overflow-hidden"
                            :class="message.sender === 'user' ? 'bg-content-accent' : 'bg-content-glass'"
                        >
                            <p v-if="message.sender === 'user'" class="text-sm whitespace-pre-wrap break-words">
                                {{ message.message }}
                            </p>
                            <template v-else>
                                <div class="flex items-center gap-2">
                                    <div class="min-w-0">
                                        <p class="whitespace-pre-wrap break-words text-sm">
                                            {{ message.output?.text || message.message }}
                                        </p>

                                        <a
                                            v-if="message.output?.link"
                                            :href="resolveHref(message.output.link)"
                                            class="context underline decoration-dotted w-fit"
                                        >
                                            {{ message.output.link }}
                                        </a>
                                    </div>
                                </div>

                                <OutputMessage
                                    :output="{ buttons: message.output?.buttons || [] }"
                                    fallback-text=""
                                />
                            </template>
                        </div>
                    </div>
                </div>

                <div class="p-3 border-t border-white/10">
                    <div class="flex items-center gap-2">
                        <input
                            v-model="messageText"
                            type="text"
                            class="input w-full !px-4 !py-2 text-sm"
                            placeholder="Напиши сообщение..."
                            @keydown.enter.prevent="processMessage"
                        >
                        <button
                            class="round-btn round-btn-accent shrink-0"
                            :disabled="isSending"
                            @click="processMessage"
                        >
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>