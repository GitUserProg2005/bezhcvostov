<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Avatar from '@/Components/Avatar.vue';

const requests = ref([]);
const loading = ref(false);
const actingId = ref(null);
const errorMessage = ref('');

function csrfHeader() {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  return token ? { 'X-CSRF-TOKEN': token } : {};
}

async function loadPending() {
  loading.value = true;
  errorMessage.value = '';
  try {
    const { data } = await axios.get(route('control.request.pending'));
    requests.value = data?.requests ?? [];
  } catch (e) {
    console.error('control.request.pending', e);
    errorMessage.value = 'Не удалось загрузить заявки.';
    requests.value = [];
  } finally {
    loading.value = false;
  }
}

async function updateStatus(request, status) {
  actingId.value = request.id;
  errorMessage.value = '';
  try {
    await axios.post(
      route('control.request.update-status', request.id),
      { status },
      { headers: { ...csrfHeader() } }
    );
    await loadPending();
  } catch (e) {
    errorMessage.value =
      e.response?.data?.message ?? 'Не удалось обновить заявку.';
  } finally {
    actingId.value = null;
  }
}

onMounted(() => {
  loadPending();
});
</script>

<template>
  <div class="content space-y-3">
    <div class="flex items-center justify-between gap-2">
      <h3 class="title-3 text-base font-semibold">Запросы на контроль</h3>
    </div>

    <p v-if="loading" class="t-small text-gray-500">Загрузка…</p>
    <p v-if="errorMessage" class="text-sm text-red-600">{{ errorMessage }}</p>

    <div v-if="!loading && !requests.length" class="t-small text-gray-500">
      Нет входящих запросов.
    </div>

    <div v-else class="flex flex-col gap-4">
      <div
        v-for="request in requests"
        :key="request.id"
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="flex items-center gap-3 min-w-0">
          <Avatar
            :name="request.sender?.name"
            :src="request.sender?.avatar_url"
            :user-id="request.sender?.id"
            size="sm"
          />
          <div class="min-w-0">
            <p class="text-sm font-medium truncate">{{ request.sender?.name }}</p>
            <p v-if="request.sender?.account_code" class="t-mini text-gray-500">
              Код: {{ request.sender.account_code }}
            </p>
          </div>
        </div>
        <div class="flex shrink-0 gap-2">
          <button
            type="button"
            class="t-small text-gray-600 hover:text-gray-900 px-3 py-1.5 disabled:opacity-50"
            :disabled="actingId === request.id"
            @click="updateStatus(request, 'refused')"
          >
            Отклонить
          </button>
          <button
            type="button"
            class="primary-btn text-xs px-3 py-1.5"
            :disabled="actingId === request.id"
            @click="updateStatus(request, 'approved')"
          >
            {{ actingId === request.id ? '…' : 'Принять' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
