<template>
  <div class="space-y-6">
    <!-- Search and Filters -->
    <div class="flex gap-4 items-center">
      <div class="relative flex-1">
        <input
          type="text"
          v-model="search"
          placeholder="جستجو بر اساس شماره موبایل یا ایمیل..."
          class="w-full pr-10 pl-4 py-2 border rounded-lg"
          dir="rtl"
        />
        <span class="absolute left-3 top-2.5 text-gray-400">🔍</span>
      </div>

      <select v-model="statusFilter" class="px-4 py-2 border rounded-lg">
        <option value="all">همه وضعیت‌ها</option>
        <option value="active">فعال</option>
        <option value="suspended">مسدود شده</option>
      </select>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-4 text-right text-xs font-medium text-gray-500">شناسه</th>
            <th class="px-6 py-4 text-right text-xs font-medium text-gray-500">نام</th>
            <th class="px-6 py-4 text-right text-xs font-medium text-gray-500">موبایل</th>
            <th class="px-6 py-4 text-right text-xs font-medium text-gray-500">وضعیت</th>
            <th class="px-6 py-4 text-right text-xs font-medium text-gray-500">تاریخ ثبت</th>
            <th class="px-6 py-4"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="user in filteredUsers" :key="user.id">
            <td class="px-6 py-4 text-sm text-gray-500">#{{ user.id }}</td>
            <td class="px-6 py-4 font-medium">{{ user.name }}</td>
            <td class="px-6 py-4">{{ user.mobile }}</td>
            <td class="px-6 py-4">
              <span
                :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                class="px-3 py-1 rounded-full text-xs"
              >
                {{ user.is_active ? 'فعال' : 'مسدود شده' }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ user.created_at }}</td>
            <td class="px-6 py-4 text-left">
              <button
                @click="toggleStatus(user)"
                :class="user.is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'"
                class="px-3 py-1 text-sm text-white rounded"
              >
                {{ user.is_active ? 'مسدود' : 'فعال' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center">
      <div class="text-sm text-gray-500">
        {{ (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage, users.length) }} از {{ users.length }} کاربر
      </div>
      <div class="flex gap-2">
        <button @click="prevPage" :disabled="currentPage === 1" class="px-3 py-1 border rounded disabled:opacity-50">قبلی</button>
        <button @click="nextPage" :disabled="currentPage * perPage >= users.length" class="px-3 py-1 border rounded disabled:opacity-50">بعدی</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface User {
  id: number
  name: string
  mobile: string
  email: string | null
  is_active: boolean
  created_at: string
}

const search = ref('')
const statusFilter = ref('all')
const currentPage = ref(1)
const perPage = ref(15)

const users = ref<User[]>([
  { id: 1, name: 'علی احمدی', mobile: '+989123456789', email: 'ali@example.com', is_active: true, created_at: '2025-01-15' },
  { id: 2, name: 'مریم حسینی', mobile: '+989876543210', email: null, is_active: true, created_at: '2025-01-20' },
  { id: 3, name: 'رضا کریمی', mobile: '+989112233445', email: null, is_active: false, created_at: '2025-02-01' },
])

const filteredUsers = computed(() => {
  const filtered = users.value.filter(u => {
    const matchesSearch = u.name.includes(search.value) || u.mobile.includes(search.value)
    const matchesStatus = statusFilter.value === 'all' ||
      (statusFilter.value === 'active' && u.is_active) ||
      (statusFilter.value === 'suspended' && !u.is_active)
    return matchesSearch && matchesStatus
  })

  const start = (currentPage.value - 1) * perPage.value
  return filtered.slice(start, start + perPage.value)
})

function toggleStatus(user: User) {
  user.is_active = !user.is_active
}

function prevPage() {
  if (currentPage.value > 1) currentPage.value--
}

function nextPage() {
  if (currentPage.value * perPage.value < users.value.length) currentPage.value++
}
</script>