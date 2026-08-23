<template>
  <div class="min-h-screen bg-gray-50 rtl">
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <h1 class="text-2xl font-bold text-gray-900">ویرایش نظارت</h1>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div v-if="watch" class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form @submit.prevent="updateWatch" class="p-6">
          <div class="space-y-6">
            <div>
              <h2 class="text-lg font-medium text-gray-900 mb-4">جزئیات نظارت</h2>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 rtl">آدرس محصول</label>
              <div class="mt-2">
                <p class="text-gray-900">{{ watch.product_url }}</p>
              </div>
            </div>

            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 rtl">نام نظارت</label>
              <div class="mt-2">
                <input type="text" id="name" v-model="form.name" 
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl"
                       placeholder="نام دلخواه برای نظارت">
              </div>
            </div>

            <div>
              <label for="workflow_type" class="block text-sm font-medium text-gray-700 rtl">نوع هشدار</label>
              <div class="mt-2">
                <span class="text-gray-700">{{ workflowTypeLabels[form.workflow_type] || 'بدون تنظیم' }}</span>
              </div>
            </div>

            <div>
              <label for="is_active" class="flex items-center gap-2">
                <input type="checkbox" id="is_active" v-model="form.is_active" 
                       class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                <span class="text-sm text-gray-700 rtl">فعال</span>
              </label>
            </div>

            <div>
              <label for="frequency_minutes" class="block text-sm font-medium text-gray-700 rtl">فرکانس بررسی</label>
              <div class="mt-2">
                <select id="frequency_minutes" v-model.number="form.frequency_minutes" 
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl">
                  <option :value="1">هر دقیقه</option>
                  <option :value="5">هر 5 دقیقه</option>
                  <option :value="10">هر 10 دقیقه</option>
                  <option :value="15" selected>هر 15 دقیقه</option>
                  <option :value="30">هر 30 دقیقه</option>
                  <option :value="60">هر ساعت</option>
                  <option :value="120">هر 2 ساعت</option>
                </select>
              </div>
            </div>

            <div v-if="form.workflow_type === 'PRICE_BELOW_ALERT' || form.workflow_type === 'PRICE_ABOVE_ALERT'">
              <label for="price_threshold" class="block text-sm font-medium text-gray-700 rtl">آستانه قیمت</label>
              <div class="mt-2">
                <input type="number" id="price_threshold" v-model.number="form.price_threshold"
                       min="0" step="1000"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl">
              </div>
            </div>

            <div v-if="form.workflow_type === 'PRICE_RANGE_ALERT'">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="number" id="min_price" v-model.number="form.min_price"
                       min="0" step="1000"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl"
                       placeholder="حداقل قیمت">
                <input type="number" id="max_price" v-model.number="form.max_price"
                       min="0" step="1000"
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl"
                       placeholder="حداکثر قیمت">
              </div>
            </div>

            <div class="flex justify-end space-x-3 space-x-reverse pt-6 border-t border-gray-200">
              <nuxt-link to="/" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                بازگشت
              </nuxt-link>
              <button type="submit" :disabled="loading" 
                      class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                ذخیره تغییرات
              </button>
            </div>
          </div>
        </form>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '~/store/pinia/authStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const loading = ref(false)
const watch = ref(null)

const workflowTypeLabels = {
  PRICE_BELOW_ALERT: 'قیمت کمتر از',
  PRICE_ABOVE_ALERT: 'قیمت بزرگتر از',
  PRICE_RANGE_ALERT: 'محدوده قیمت',
  AVAILABILITY_ALERT: 'وضعیت موجودی',
  SELLER_ALERT: 'فروشنده',
}

const form = ref({
  name: '',
  product_url: '',
  workflow_type: '',
  is_active: true,
  frequency_minutes: 15,
  price_threshold: null,
  min_price: null,
  max_price: null,
})

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }

  const watchId = route.params.id
  await fetchWatch(watchId)
})

const fetchWatch = async (id) => {
  try {
    const response = await $fetch(`/api/watches/${id}`, {
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    
    const data = response.data || response
    watch.value = data
    
    form.value = {
      name: data.name || '',
      product_url: data.product_url || '',
      workflow_type: data.workflow_type || '',
      is_active: data.is_active ?? true,
      frequency_minutes: data.frequency_minutes || 15,
      price_threshold: data.price_threshold || null,
      min_price: data.min_price || null,
      max_price: data.max_price || null,
    }
  } catch (error) {
    alert('نذرافت نشد')
    router.push('/')
  }
}

const updateWatch = async () => {
  if (!watch.value) return

  loading.value = true
  try {
    await $fetch(`/api/watches/${watch.value.id}`, {
      method: 'PUT',
      body: form.value,
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    
    router.push('/')
  } catch (error) {
    alert(error?.data?.message || 'خطا در به‌روزرسانی')
  } finally {
    loading.value = false
  }
}
</script>