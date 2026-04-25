<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Search from '@/Components/Search/Search.vue';
import Avatar from '@/Components/Avatar.vue';

const sendingId = ref(null);
const flashMessage = ref('');
const flashError = ref('');

function csrfHeader() {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token ? { 'X-CSRF-TOKEN': token } : {};
}

async function searchByCode(query) {
  const trimmed = query.trim();
  if (!trimmed) return [];

  const { data } = await axios.get(route('control.request.search'), {
    params: { q: trimmed },
  });

  return Array.isArray(data) ? data : [];
}

async function sendRequestToUser(item) {
  flashMessage.value = '';
  flashError.value = '';
  sendingId.value = item.id;

  try {
    await axios.post(
      route('send.control.request'),
      { receiver_id: item.id },
      { headers: { ...csrfHeader() } }
    );
    flashMessage.value = 'Запрос отправлен.';
  } catch (e) {
    flashError.value =
      e.response?.data?.message ?? 'Не удалось отправить запрос. Попробуйте позже.';
  } finally {
    sendingId.value = null;
  }
}
</script>

<template>
  <div class="content space-y-3">
    <h3 class="title-3 text-base font-semibold">Запрос на контроль</h3>
    <p class="t-small text-gray-600">
      Найдите ученика по коду аккаунта и отправьте запрос на связь.
    </p>

    <div v-if="flashMessage" class="text-sm text-green-700">{{ flashMessage }}</div>
    <div v-if="flashError" class="text-sm text-red-600">{{ flashError }}</div>

    <div class="relative z-10">
      <Search
        :search-fn="searchByCode"
        search-label="Код ученика"
      >
        <template #item="{ item }">
          <div class="flex flex-col justify-between gap-3 py-2">
            <div class="flex items-center gap-3 min-w-0">
              <Avatar
                :name="item.name"
                :src="item.avatar_url"
                :user-id="item.id"
                size="sm"
              />
              <div class="min-w-0">
                <p class="text-sm font-medium truncate">{{ item.name }}</p>
                <p class="t-mini text-gray-500">Код: {{ item.account_code }}</p>
              </div>
            </div>

            <button
              type="button"
              class="primary-btn shrink-0 text-xs px-3 py-1.5"
              :disabled="sendingId === item.id"
              @click="sendRequestToUser(item)"
            >
              {{ sendingId === item.id ? '…' : 'Запрос' }}
              <i class="fa-regular fa-paper-plane"></i>
            </button>
          </div>
        </template>
      </Search>
    </div>
  </div>
</template>
