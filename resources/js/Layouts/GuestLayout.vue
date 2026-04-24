<script setup>
import '../../css/custom.css';
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <div class="min-h-screen bg-body flex items-center justify-center">
    <div class="w-full max-w-5xl block-base bg-cover bg-no-repeat bg-right rounded-3xl overflow-hidden min-h-screen md:min-h-0"
         style="background-image: url('https://purizmo.com/wp-content/uploads/2024/03/11-2-1024x536.webp');">
      <div class="flex flex-col h-full">
        <div class="flex-1 flex items-center">
          <div class="w-full md:max-w-md content-glass">
            <slot />
          </div>

          <div class="hidden md:block flex-1" />
        </div>
      </div>
    </div>
  </div>
</template>
