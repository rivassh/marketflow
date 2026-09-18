<template>
  <div class="space-y-6">
    <h2 class="text-2xl font-bold mb-6">📦 مدیریت ارائه‌دهندگان</h2>

    <!-- Provider Form -->
    <div class="bg-white rounded-xl shadow p-6 mb-8">
      <h3 class="font-bold mb-4">افزودن/ویرایش ارائه‌دهنده</h3>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm text-gray-500 mb-2">اسم</label>
          <input v-model="form.name" type="text" class="w-full px-3 py-2 border rounded" dir="rtl" />
        </div>
        <div>
          <label class="block text-sm text-gray-500 mb-2">باس‌های URL</label>
          <input v-model="form.supported_urls" type="text" placeholder="https://torob.com/p/*" class="w-full px-3 py-2 border rounded" dir="rtl" />
        </div>
        <div class="col-span-2">
          <label class="block text-sm text-gray-500 mb-2">Timeout (ثانیه)</label>
          <input v-model="form.timeout" type="number" class="w-full px-3 py-2 border rounded" />
        </div>
        <div class="col-span-2">
          <label class="block text-sm text-gray-500 mb-2">Retry Count</label>
          <input v-model="form.retry_count" type="number" class="w-full px-3 py-2 border rounded" />
        </div>
      </div>

      <button @click="saveProvider" class="mt-6 w-full bg-blue-600 text-white py-3 rounded font-medium hover:bg-blue-700">
        ذخیره ارائه‌دهنده
      </button>
    </div>

    <!-- Providers List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="provider in providers" :key="provider.id" class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-3">
          <h4 class="font-bold">{{ provider.name }}</h4>
          <span
            :class="provider.is_enabled ? 'bg-green-100 text-green-800 px-2 py-1 rounded text-xs' : 'bg-red-100 text-red-800 px-2 py-1 rounded text-xs'"
          >
            {{ provider.is_enabled ? 'فعال' : 'غیرفعال' }}
          </span>
        </div>

        <p class="text-sm text-gray-600 mb-3">Status: <strong>{{ provider.health_status }}</strong></p>

        <div class="flex gap-2">
          <button
            @click="toggleProvider(provider)"
            :class="provider.is_enabled ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-green-600 hover:bg-green-700 text-white'"
            class="px-3 py-1 rounded text-xs"
          >
            {{ provider.is_enabled ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
          </button>
          <button class="px-3 py-1 bg-gray-600 rounded text-xs hover:bg-gray-700">تنظیمات</button>
        </div>

        <div class="mt-3 pt-3 border-t">
          <p class="text-xs text-gray-500">Rate Limit: {{ provider.rate_limit }}</p>
          <p class="text-xs text-gray-500">Cache TTL: {{ provider.cache_ttl }} ثانیه</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Provider {
  id: number
  name: string
  is_enabled: boolean
  health_status: 'healthy' | 'unhealthy' | 'unknown'
  rate_limit: string
  cache_ttl: number
}

const providers = ref<Provider[]>([
  { id: 1, name: 'ترب', is_enabled: true, health_status: 'healthy', rate_limit: '10/min', cache_ttl: 300 },
  { id: 2, name: 'دیوار', is_enabled: false, health_status: 'unhealthy', rate_limit: '5/min', cache_ttl: 600 },
])

const form = ref({
  name: '',
  supported_urls: 'https://torob.com/p/*',
  timeout: 10,
  retry_count: 2
})

function toggleProvider(provider: Provider) {
  provider.is_enabled = !provider.is_enabled
}

function saveProvider() {
  console.log('Saving provider:', form.value)
}
</script>