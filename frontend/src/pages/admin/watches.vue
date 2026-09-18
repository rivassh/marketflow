<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-bold">مدیریت پیگیری‌ها</h2>
      <div class="flex gap-3">
        <select v-model="statusFilter" class="px-3 py-1 border rounded">
          <option value="all">همه وضعیت‌ها</option>
          <option value="active">فعال</option>
          <option value="paused">مکث شده</option>
          <option value="disabled">غیرفعال</option>
        </select>
        <select v-model="providerFilter" class="px-3 py-1 border rounded">
          <option value="all">همه ارائه‌دهندگان</option>
          <option value="torob">ترب</option>
          <option value="divar">دیوار</option>
        </select>
      </div>
    </div>

    <!-- Watches Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">شناسه</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">کاربر</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">محصول</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">ارائه‌دهنده</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">وضعیت</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">بررسی آخرین</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عملیات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="watch in filteredWatches" :key="watch.id">
            <td class="px-4 py-3">#{{ watch.id }}</td>
            <td class="px-4 py-3">{{ watch.user?.name || 'نامشخص' }}</td>
            <td class="px-4 py-3 text-sm">{{ watch.product?.title || watch.product_url }}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 rounded bg-blue-100 text-blue-800 text-xs">
                {{ watch.provider }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                :class="watch.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                class="px-3 py-1 rounded-full text-xs"
              >
                {{ watch.is_active ? 'فعال' : 'غیرفعال' }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ watch.last_checked_at || 'هنوز بررسی نشده' }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button @click="forceCheck(watch)" class="px-2 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700">بررسی فوری</button>
                <button @click="toggleStatus(watch)" class="px-2 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-700">
                  {{ watch.is_active ? 'مکث' : 'فعال' }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Watch {
  id: number
  user: { name: string } | null
  product: { title: string } | null
  product_url: string
  provider: string
  is_active: boolean
  last_checked_at: string | null
}

const statusFilter = ref('all')
const providerFilter = ref('all')

const watches = ref<Watch[]>([
  { id: 1, user: { name: 'علی احمدی' }, product: { title: 'آیفون 15' }, product_url: 'https://torob.com/p/123/iphone/', provider: 'torob', is_active: true, last_checked_at: '2025-01-15T10:30:00Z' },
  { id: 2, user: { name: 'مریم حسینی' }, product: null, product_url: 'https://torob.com/p/456/samsung/', provider: 'torob', is_active: false, last_checked_at: '2025-01-14T08:00:00Z' },
])

const filteredWatches = computed(() => {
  return watches.value.filter(w => {
    const matchesStatus = statusFilter.value === 'all' || (statusFilter.value === 'active' && w.is_active) || (statusFilter.value === 'paused' && !w.is_active)
    const matchesProvider = providerFilter.value === 'all' || w.provider === providerFilter.value
    return matchesStatus && matchesProvider
  })
})

function forceCheck(watch: Watch) {
  console.log('Force check:', watch.id)
}

function toggleStatus(watch: Watch) {
  watch.is_active = !watch.is_active
}
</script>