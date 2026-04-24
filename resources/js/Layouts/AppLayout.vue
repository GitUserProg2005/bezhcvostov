<script setup>
// AppLayout is used by multiple pages as the common authenticated wrapper.
// We intentionally delegate to DashboardLayout to keep sidebar/header styling consistent.
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
</script>

<template>
  <DashboardLayout>
    <slot />
  </DashboardLayout>
</template>

