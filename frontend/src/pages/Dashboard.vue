<template>
  <div class="min-h-screen bg-gray-50 rtl">
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <h1 class="text-2xl font-bold text-gray-900">داشبورد هشدارهای قیمت</h1>
          <nuxt-link to="/watches/new" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            افزودن نظارت جدید
          </nuxt-link>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div v-if="watches.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">هیچ نظارتی یافت نشد</h3>
        <p class="mt-1 text-sm text-gray-500">شروع کنید با ایجاد اولین نظارت قیمت خود.</p>
        <nuxt-link to="/watches/new" class="mt-6 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
          ایجاد نظارت اول
        </nuxt-link>
      </div>

      <div v-else class="space-y-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <div v-for="watch in watches" :key="watch.id" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-4">
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                  <h3 class="text-lg font-medium text-gray-900 truncate">{{ watch.title || 'بدون عنوان' }}</h3>
                  <p class="mt-1 text-sm text-gray-500 truncate">{{ watch.product_url }}</p>
                </div>
                <span :class="watch.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" 
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ watch.is_active ? 'فعال' : 'غیرفعال' }}
                </span>
              </div>

              <div class="mt-4 flex items-center justify-between">
                <div>
                  <span class="text-2xl font-bold text-gray-900">{{ formatPrice(watch.current_price) }}</span>
                  <span class="ml-2 text-sm text-gray-500">{{ watch.currency || 'تومان' }}</span>
                </div>
                <div class="text-left">
                  <p class="text-xs text-gray-500">آخرین بررسی</p>
                  <p class="text-xs text-gray-900">{{ formatDate(watch.last_checked_at) }}</p>
                </div>
              </div>

              <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                <span class="text-sm text-gray-500">هشدارها: {{ watch.alert_count || 0 }}</span>
                <div class="flex space-x-2 space-x-reverse">
                  <nuxt-link :to="`/watches/${watch.id}`" class="text-blue-600 hover:text-blue-900 text-sm font-medium">مشاهده</nuxt-link>
                  <button @click="toggleWatch(watch)" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                    {{ watch.is_active ? 'غیرفعال' : 'فعال' }}
                  </button>
                  <button @click="deleteWatch(watch.id)" class="text-red-600 hover:text-red-900 text-sm font-medium">حذف</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '~/store/pinia/authStore'

const watches = ref([])
const loading = ref(false)
const authStore = useAuthStore()

const fetchWatches = async () => {
  loading.value = true
  try {
    const response = await $fetch('/api/watches', {
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    watches.value = response.data || []
  } catch (error) {
    console.error('Failed to fetch watches:', error)
  } finally {
    loading.value = false
  }
}

const toggleWatch = async (watch) => {
  try {
    await $fetch(`/api/watches/${watch.id}/toggle`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    await fetchWatches()
  } catch (error) {
    console.error('Failed to toggle watch:', error)
  }
}

const deleteWatch = async (id) => {
  if (!confirm('آیا از حذف این نظارت اطمینان دارید؟')) return
  try {
    await $fetch(`/api/watches/${id}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    await fetchWatches()
  } catch (error) {
    console.error('Failed to delete watch:', error)
  }
}

const formatPrice = (price) => {
  if (!price) return 'نامشخص'
  return new Intl.NumberFormat('fa-IR').format(price)
}

const formatDate = (dateString) => {
  if (!dateString) return 'هرگز'
  return new Date(dateString).toLocaleDateString('fa-IR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  if (authStore.isAuthenticated) {
    fetchWatches()
  }
})
</script>