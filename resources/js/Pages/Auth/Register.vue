<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  avatar: null,
});

const avatarPreview = ref(null);
const isDragging = ref(false);

function processFile(file, onOk) {
  if (!file.type.startsWith('image/')) return;
  if (file.size > 5 * 1024 * 1024) return;
  const reader = new FileReader();
  reader.onload = (e) => onOk(file, e.target.result);
  reader.readAsDataURL(file);
}

function handleFileSelect(event) {
  const file = event.target.files?.[0];
  if (file) processFile(file, (f, dataUrl) => { form.avatar = f; avatarPreview.value = dataUrl; });
}
function handleDrop(event) {
  event.preventDefault();
  isDragging.value = false;
  const file = event.dataTransfer?.files?.[0];
  if (file) processFile(file, (f, dataUrl) => { form.avatar = f; avatarPreview.value = dataUrl; });
}

const submit = () => {
  form.post(route('register'), {
    forceFormData: true,
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Регистрация" />

    <h1 class="title mb-2">Регистрация</h1>
    <p class="context mb-6">Создайте аккаунт для работы в сервисе.</p>

    <form @submit.prevent="submit" class="space-y-5">
      <div
        @drop="handleDrop"
        @dragover.prevent="isDragging = true"
        @dragleave="isDragging = false"
        :class="['border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer bg-gray-50', isDragging ? 'border-gray-800 bg-gray-100' : 'border-gray-300 hover:border-gray-400']"
        @click="$refs.avatarInput.click()"
      >
        <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="handleFileSelect" />
        <div v-if="!avatarPreview" class="space-y-2">
          <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400"></i>
          <p class="t-small text-gray-600">Аватар (необязательно)</p>
        </div>
        <img v-else :src="avatarPreview" alt="Preview" class="w-24 h-24 rounded-full object-cover mx-auto" />
      </div>
      <InputError class="mt-2" :message="form.errors.avatar" />

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="t-mini text-gray-600 mb-1 block">Имя</label>
          <input v-model="form.name" type="text" class="input block w-full" required autocomplete="name" />
          <InputError class="mt-2" :message="form.errors.name" />
        </div>
        <div>
          <label class="t-mini text-gray-600 mb-1 block">Email</label>
          <input v-model="form.email" type="email" class="input block w-full" required autocomplete="username" />
          <InputError class="mt-2" :message="form.errors.email" />
        </div>
      </div>

      <div>
        <label class="t-mini text-gray-600 mb-1 block">Телефон</label>
        <input v-model="form.phone" type="tel" class="input block w-full" placeholder="79001234567" />
        <InputError class="mt-2" :message="form.errors.phone" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="t-mini text-gray-600 mb-1 block">Пароль</label>
          <input v-model="form.password" type="password" class="input block w-full" required autocomplete="new-password" />
          <InputError class="mt-2" :message="form.errors.password" />
        </div>
        <div>
          <label class="t-mini text-gray-600 mb-1 block">Подтверждение пароля</label>
          <input v-model="form.password_confirmation" type="password" class="input block w-full" required autocomplete="new-password" />
          <InputError class="mt-2" :message="form.errors.password_confirmation" />
        </div>
      </div>

      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
        <Link :href="route('login')" class="t-small text-gray-600 hover:text-gray-900 transition order-2 sm:order-1">Уже есть аккаунт?</Link>
        <button type="submit" class="primary-btn w-full sm:w-auto order-1 sm:order-2" :disabled="form.processing">Зарегистрироваться</button>
      </div>
    </form>
  </GuestLayout>
</template>
