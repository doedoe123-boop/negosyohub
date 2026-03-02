<script setup>
import { useAuthStore } from "@/stores/auth";

const auth = useAuthStore();
</script>

<template>
  <div class="mx-auto max-w-lg px-4 py-10 sm:px-6">
    <h1 class="mb-6 text-2xl font-bold text-gray-900">My Profile</h1>
    <div class="rounded-2xl border bg-white p-6">
      <dl class="space-y-4 text-sm">
        <div class="flex justify-between">
          <dt class="font-medium text-gray-600">Name</dt>
          <dd class="text-gray-900">{{ auth.user?.name }}</dd>
        </div>
        <div class="flex justify-between">
          <dt class="font-medium text-gray-600">Email</dt>
          <dd class="text-gray-900">{{ auth.user?.email }}</dd>
        </div>
        <div class="flex justify-between">
          <dt class="font-medium text-gray-600">Role</dt>
          <dd class="text-gray-900 capitalize">{{ auth.user?.role }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>
