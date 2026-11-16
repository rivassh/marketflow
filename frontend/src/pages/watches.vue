<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-bold">پیگیری‌های من</h2>
      <NuxtLink to="/watches/new" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        ➕ افزودن پیگیری جدید
      </NuxtLink>
    </div>

    <div v-if="pending" class="text-center py-10">در حال بارگذاری...</div>

    <div v-else-if="watches && watches.length === 0" class="text-center py-10 text-gray-500">
      هنوز پیگیری‌ای ندارید. یک لینک محصول اضافه کنید.
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="watch in watches"
        :key="watch.id"
        class="bg-white rounded-xl shadow p-4 border border-gray-100"
      >
        <div class="flex justify-between items-start mb-2">
          <h3 class="font-bold text-sm truncate">{{ watch.product?.title || 'محصول' }}</h3>
          <span
            :class="watch.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
            class="px-2 py-1 rounded-full text-xs"
          >
            {{ watch.is_active ? 'عالی' : 'غیرعال' }}
          </span>
        </div>

        <p class="text-xs text-gray-500 mb-2 truncate">{{ watch.product_url }}</p>

        <div class="flex justify-between items-center text-sm">
          <span class="font-bold text-blue-600">
            {{ formatPrice(watch.price) }}
          </span>
          <span class="text-xs text-gray-400">
            {{ formatDate(watch.last_checked_at) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const { data: watches, pending } = await useAsyncData('user-watches', () =>
  $fetch('/watches', { baseURL: config.public.apiBase })
)

function formatPrice(watch: any): string {
  const price = watch?.price
  if (!price) return 'ناشناخته'
  return price.display || `${price.amount} IRR`
}

function formatDate(date: string | null): string {
  if (!date) return 'بررسی نشده'
  return new Date(date).toLocaleDateString('fa-IR')
}
</script>