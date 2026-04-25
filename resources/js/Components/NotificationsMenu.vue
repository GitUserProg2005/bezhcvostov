<script setup>
import { onMounted, ref } from 'vue';
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue';
import { useInAppNotifications } from '@/composables/useInAppNotifications.js';

const { notifications, unreadCount, loading, load } = useInAppNotifications();

const loadError = ref('');

function onBellClick() {
  loadError.value = '';
  load().catch(() => {
    loadError.value = 'Не удалось загрузить уведомления.';
  });
}

function bodyText(n) {
  const raw = n.data;
  if (raw == null) return '';
  if (typeof raw === 'string') {
    try {
      const parsed = JSON.parse(raw);
      return parsed?.message ?? parsed?.task_title ?? raw;
    } catch {
      return raw;
    }
  }
  return raw.message ?? raw.task_title ?? '';
}

function formatCreatedAt(n) {
  if (!n?.created_at) return '';
  const date = new Date(n.created_at);
  if (Number.isNaN(date.getTime())) return '';
  return date.toLocaleString();
}

onMounted(() => {
  load().catch(() => {});
});
</script>

<template>
  <Popover class="relative shrink-0">
    <PopoverButton
      type="button"
      class="relative w-10 h-10 rounded-full bg-[#e97358]/10 text-[#e97358] flex items-center justify-center outline-none focus-visible:ring-2 focus-visible:ring-[#e97358]/40"
      aria-label="Уведомления"
      @click="onBellClick"
      @contextmenu.prevent
    >
      <i class="fa-solid fa-bell text-sm" />
      <span
        v-if="unreadCount > 0"
        class="absolute top-2 right-2 w-2 h-2 bg-[#e97358] rounded-full"
      />
    </PopoverButton>

    <transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <PopoverPanel
        class="absolute right-0 z-[200] mt-2 w-[min(100vw-2rem,22rem)] max-h-80 overflow-y-auto content-p-0 shadow-lg"
      >
        <div class="px-4 py-3 border-b border-[var(--glass-border)]">
          <p class="text-sm font-semibold">Уведомления</p>
        </div>

        <div v-if="loading" class="px-4 py-6 t-small text-gray-500">Загрузка…</div>
        <div v-else-if="loadError" class="px-4 py-4 text-sm text-red-600">{{ loadError }}</div>
        <div v-else-if="!notifications.length" class="px-4 py-6 t-small text-gray-500">
          Пока пусто.
        </div>
        <ul v-else class="divide-y divide-[var(--glass-border)]">
          <li
            v-for="n in notifications"
            :key="n.id"
            class="px-4 py-3 text-left"
          >
            <p class="text-sm font-medium">{{ n.title || 'Уведомление' }}</p>
            <p v-if="bodyText(n)" class="t-small text-gray-600 mt-1">{{ bodyText(n) }}</p>
            <p v-if="n.created_at" class="t-mini text-gray-400 mt-2">{{ formatCreatedAt(n) }}</p>
          </li>
        </ul>
      </PopoverPanel>
    </transition>
  </Popover>
</template>
