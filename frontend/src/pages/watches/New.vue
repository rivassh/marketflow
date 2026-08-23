<template>
  <div class="min-h-screen bg-gray-50 rtl">
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <h1 class="text-2xl font-bold text-gray-900">ساخت نظارت قیمت جدید</h1>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form @submit.prevent="submitWatch" class="p-6">
          <div class="space-y-6">
            <div>
              <label for="product_url" class="block text-sm font-medium text-gray-700 rtl">آدرس محصول</label>
              <div class="mt-2">
                <input type="url" id="product_url" v-model="form.product_url" required 
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl"
                       placeholder="https://torob.com/p/12345/نام-محصول/">
              </div>
              <p class="mt-2 text-sm text-gray-500">آدرس محصول را در وارد کنید تا قیمت آن را نظارت شود</p>
              
              <div v-if="parsedUrl.provider" class="mt-3 p-3 bg-green-50 rounded-lg">
                <p class="text-sm text-green-800">✓ فراهم‌کننده: {{ parsedUrl.provider }}</p>
                <p class="text-sm text-green-800">شناسه محصول: {{ parsedUrl.externalId }}</p>
              </div>
            </div>

            <div>
              <label for="workflow_type" class="block text-sm font-medium text-gray-700 rtl">نوع هشدار</label>
              <div class="mt-2">
                <select id="workflow_type" v-model="form.workflow_type" required 
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl">
                  <option value="">انتخاب نوع هشدار</option>
                  <option value="PRICE_BELOW_ALERT">قیمت کمتر از</option>
                  <option value="PRICE_ABOVE_ALERT">قیمت بزرگتر از</option>
                  <option value="PRICE_RANGE_ALERT">محدوده قیمت</option>
                  <option value="AVAILABILITY_ALERT">وضعیت موجودی</option>
                  <option value="SELLER_ALERT">فروشنده</option>
                </select>
              </div>
            </div>

            <div v-if="form.workflow_type === 'PRICE_BELOW_ALERT' || form.workflow_type === 'PRICE_ABOVE_ALERT'">
              <label for="price_threshold" class="block text-sm font-medium text-gray-700 rtl">آستانه قیمت (تومان)</label>
              <div class="mt-2">
                <input type="number" id="price_threshold" v-model.number="form.price_threshold" 
                       min="0" step="1000" required 
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl"
                       placeholder="مثال: 5000000">
              </div>
            </div>

            <div v-if="form.workflow_type === 'PRICE_RANGE_ALERT'">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="min_price" class="block text-sm font-medium text-gray-700 rtl">حداقل قیمت (تومان)</label>
                  <div class="mt-2">
                    <input type="number" id="min_price" v-model.number="form.min_price"
                           min="0" step="1000" required 
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl"
                           placeholder="حداقل">
                  </div>
                </div>
                <div>
                  <label for="max_price" class="block text-sm font-medium text-gray-700 rtl">حداکثر قیمت (تومان)</label>
                  <div class="mt-2">
                    <input type="number" id="max_price" v-model.number="form.max_price"
                           min="0" step="1000" required
                           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl"
                           placeholder="حداکثر">
                  </div>
                </div>
              </div>
            </div>

            <div v-if="form.workflow_type === 'AVAILABILITY_ALERT'">
              <label for="availability" class="block text-sm font-medium text-gray-700 rtl">وضعیت موجودی موردنظر</label>
              <div class="mt-2">
                <select id="availability" v-model="form.availability" required 
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl">
                  <option value="">انتخاب وضعیت</option>
                  <option value="available">موجود</option>
                  <option value="unavailable">ناموجود</option>
                </select>
              </div>
            </div>

            <div v-if="form.workflow_type === 'SELLER_ALERT'">
              <label for="seller_name" class="block text-sm font-medium text-gray-700 rtl">نام فروشنده</label>
              <div class="mt-2">
                <input type="text" id="seller_name" v-model="form.seller_name" required 
                       class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl"
                       placeholder="نام فروشنده را وارد کنید">
              </div>
            </div>

            <div>
              <label for="frequency" class="block text-sm font-medium text-gray-700 rtl">فرکانس بررسی</label>
              <div class="mt-2">
                <select id="frequency" v-model="form.frequency_minutes" 
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 rtl">
                  <option :value="5">هر 5 دقیقه</option>
                  <option :value="10">هر 10 دقیقه</option>
                  <option :value="15" selected>هر 15 دقیقه</option>
                  <option :value="30">هر 30 دقیقه</option>
                  <option :value="60">هر ساعت</option>
                </select>
              </div>
            </div>

            <div class="flex justify-end space-x-3 space-x-reverse">
              <nuxt-link to="/" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                انصراف
              </nuxt-link>
              <button type="submit" :disabled="loading || !isValid" 
                      class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50">
                <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                ذخیره نظارت
              </button>
            </div>
          </div>
        </form>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '~/store/pinia/authStore'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(false)
const parsedUrl = ref({ provider: null, externalId: null })

const form = ref({
  product_url: '',
  workflow_type: '',
  price_threshold: '',
  min_price: '',
  max_price: '',
  availability: '',
  seller_name: '',
  frequency_minutes: 15
})

const isValid = computed(() => {
  if (!form.value.product_url || !form.value.workflow_type) return false
  return parsedUrl.value.provider !== null
})

const parseUrl = async (url) => {
  if (!url || !url.includes('torob.com')) {
    parsedUrl.value = { provider: null, externalId: null }
    return
  }

  try {
    const response = await $fetch('/api/providers/parse-url', {
      method: 'POST',
      body: { url },
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    parsedUrl.value = response.data || { provider: null, externalId: null }
  } catch (error) {
    console.warn('URL parsing failed, using fallback regex')
    const matches = url.match(/torob\.com\/p\/(\d+)/)
    parsedUrl.value = matches 
      ? { provider: 'torob', externalId: matches[1] } 
      : { provider: null, externalId: null }
  }
}

const submitWatch = async () => {
  if (!isValid.value) return

  loading.value = true
  try {
    const response = await $fetch('/api/watches', {
      method: 'POST',
      body: form.value,
      headers: {
        Authorization: `Bearer ${authStore.token}`
      }
    })
    
    router.push('/')
  } catch (error) {
    console.error('Failed to create watch:', error)
    alert(error?.data?.message || 'خطا در ایجاد نظارت')
  } finally {
    loading.value = false
  }
}

watch(form.value.product_url, (newVal) => {
  if (newVal) {
    parseUrl(newVal)
  }
})
</script>