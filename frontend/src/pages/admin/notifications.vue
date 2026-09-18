<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-bold">مدیریت اطلاع‌رسانی‌ها</h2>
      <div class="flex gap-3">
        <select v-model="statusFilter" class="px-3 py-1 border rounded">
          <option value="all">همه وضعیت‌ها</option>
          <option value="sent">ارسال شده</option>
          <option value="failed">ناموفق</option>
          <option value="pending">در انتظار</option>
        </select>
        <select v-model="channelFilter" class="px-3 py-1 border rounded">
          <option value="all">همه کانال‌ها</option>
          <option value="sms">SMS</option>
          <option value="email">ایمیل</option>
        </select>
      </div>
    </div>

    <!-- Notifications Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">شناسه</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">کاربر</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">پیگیری</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">کانال</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">وضعیت</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">ارسال شده</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عملیات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="notification in filteredNotifications" :key="notification.id">
            <td class="px-4 py-3 text-sm text-gray-500">#{{ notification.id }}</td>
            <td class="px-4 py-3 font-medium">{{ notification.user?.name || 'نامشخص' }}</td>
            <td class="px-4 py-3 text-sm">{{ notification.watch?.name || notification.watch_id }}</td>
            <td class="px-4 py-3">
              <span
                :class="notification.channel === 'sms' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'"
                class="px-2 py-1 rounded text-xs"
              >
                {{ notification.channel === 'sms' ? 'SMS' : 'ایمیل' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span
                :class="getStatusClass(notification.status)"
                class="px-3 py-1 rounded-full text-xs"
              >
                {{ getStatusText(notification.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ notification.sent_at || 'هنوز ارسال نشده' }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button
                  v-if="notification.status === 'pending'"
                  @click="markAsSent(notification)"
                  class="px-2 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700"
                >
                  علامت‌گذاری به عنوان ارسال شده
                </button>
                <button
                  v-if="notification.status === 'pending'"
                  @click="markAsFailed(notification)"
                  class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700"
                >
                  علامت‌گذاری به عنوان ناموفق
                </button>
                <button @click="viewDetails(notification)" class="px-2 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-700">
                  مشاهده جزئیات
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between items-center">
      <div class="text-sm text-gray-500">
        {{ (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage, notifications.length) }} از {{ notifications.length }} اطلاع‌رسانی
      </div>
      <div class="flex gap-2">
        <button @click="prevPage" :disabled="currentPage === 1" class="px-3 py-1 border rounded disabled:opacity-50">قبلی</button>
        <button @click="nextPage" :disabled="currentPage * perPage >= notifications.length" class="px-3 py-1 border rounded disabled:opacity-50">بعدی</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Notification {
  id: number
  user: { name: string } | null
  watch_id: number
  watch?: { name: string }
  channel: 'sms' | 'email'
  status: 'pending' | 'sent' | 'failed'
  sent_at?: string
  recipient?: string
}

const statusFilter = ref('all')
const channelFilter = ref('all')
const currentPage = ref(1)
const perPage = ref(15)

const notifications = ref<Notification[]>([
  { id: 1, user: { name: 'علی احمدی' }, watch_id: 5, channel: 'sms', status: 'sent', sent_at: '2025-01-15T10:30:00Z' },
  { id: 2, user: { name: 'مریم حسینی' }, watch_id: 3, channel: 'sms', status: 'pending' },
  { id: 3, user: { name: 'رضا کریمی' }, watch_id: 1, channel: 'email', status: 'failed', sent_at: '2025-01-14T15:45:00Z' },
  { id: 4, user: null, watch_id: 2, channel: 'sms', status: 'sent', sent_at: '2025-01-14T09:00:00Z' },
  { id: 5, user: { name: 'نیلوفر زمانی' }, watch_id: 7, channel: 'email', status: 'pending' },
])

const filteredNotifications = computed(() => {
  const filtered = notifications.value.filter(n => {
    const matchesStatus = statusFilter.value === 'all' || n.status === statusFilter.value
    const matchesChannel = channelFilter.value === 'all' || n.channel === channelFilter.value
    return matchesStatus && matchesChannel
  })

  const start = (currentPage.value - 1) * perPage.value
  return filtered.slice(start, start + perPage.value)
})

function getStatusClass(status: string): string {
  switch (status) {
    case 'sent': return 'bg-green-100 text-green-800'
    case 'failed': return 'bg-red-100 text-red-800'
    case 'pending': return 'bg-yellow-100 text-yellow-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

function getStatusText(status: string): string {
  switch (status) {
    case 'sent': return 'ارسال شده'
    case 'failed': return 'ناموفق'
    case 'pending': return 'در انتظار'
    default: return status
  }
}

function markAsSent(notification: Notification) {
  notification.status = 'sent'
  notification.sent_at = new Date().toISOString()
  console.log('Mark as sent:', notification.id)
}

function markAsFailed(notification: Notification) {
  notification.status = 'failed'
  notification.sent_at = new Date().toISOString()
  console.log('Mark as failed:', notification.id)
}

function viewDetails(notification: Notification) {
  console.log('View details:', notification.id)
}

function prevPage() {
  if (currentPage.value > 1) currentPage.value--
}

function nextPage() {
  if (currentPage.value * perPage.value < notifications.value.length) currentPage.value++
}
</script>