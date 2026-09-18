<template>
  <div class="min-h-screen bg-gray-100" dir="rtl">
    <div class="flex">
      <!-- Sidebar -->
      <aside class="w-64 bg-gray-900 text-white min-h-screen p-4">
        <h2 class="text-xl font-bold mb-6">🔧 پنل مدیریت</h2>
        <nav class="space-y-2">
          <NuxtLink to="/admin" class="block px-4 py-2 rounded bg-blue-600">داشبورد</NuxtLink>
          <NuxtLink to="/admin/users" class="block px-4 py-2 rounded hover:bg-gray-800">کاربران</NuxtLink>
          <NuxtLink to="/admin/watches" class="block px-4 py-2 rounded hover:bg-gray-800">پیگیری‌ها</NuxtLink>
          <NuxtLink to="/admin/providers" class="block px-4 py-2 rounded hover:bg-gray-800">ارائه‌دهندگان</NuxtLink>
          <NuxtLink to="/admin/workflows" class="block px-4 py-2 rounded hover:bg-gray-800">روش‌های کاری</NuxtLink>
          <NuxtLink to="/admin/notifications" class="block px-4 py-2 rounded hover:bg-gray-800">اطلاع‌رسانی‌ها</NuxtLink>
          <NuxtLink to="/admin/settings" class="block px-4 py-2 rounded hover:bg-gray-800"> تنظیمات</NuxtLink>
        </nav>
        <button @click="logout" class="mt-8 px-4 py-2 bg-red-600 rounded hover:bg-red-700 w-full">خروج</button>
      </aside>

      <!-- Main content -->
      <main class="flex-1 p-8">
        <header class="mb-6">
          <h1 class="text-3xl font-bold text-gray-800">{{ currentPageTitle }}</h1>
          <p class="text-gray-600">خوش آمدید {{ authStore.user?.name || 'مدیر' }}</p>
        </header>

        <slot></slot>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
const authStore = useAuthStore()
const route = useRoute()

const titles: Record<string, string> = {
  '/admin': 'داشبورد',
  '/admin/users': 'مدیریت کاربران',
  '/admin/watches': 'مدیریت پیگیری‌ها',
  '/admin/providers': 'مدیریت ارائه‌دهندگان',
  '/admin/workflows': 'مدیریت روش‌های کاری',
  '/admin/notifications': 'مدیریت اطلاع‌رسانی‌ها',
  '/admin/settings': 'تنظیمات سیستم',
}

const currentPageTitle = computed(() => titles[route.path] || 'پنل مدیریت')

async function logout() {
  await authStore.logout()
  await navigateTo('/login')
}
</script>