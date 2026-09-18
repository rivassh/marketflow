<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold">تنظیمات سیستم</h2>

    <div class="bg-white rounded-xl shadow p-6">
      <h3 class="font-bold mb-4">تنظیمات کلی</h3>
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-500 mb-2">نام سرویس</label>
            <input v-model="settings.serviceName" type="text" class="w-full px-3 py-2 border rounded" dir="rtl" />
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">تولیدکننده</label>
            <input v-model="settings.developer" type="text" class="w-full px-3 py-2 border rounded" dir="rtl" />
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">ایمیل مدیریت</label>
            <input v-model="settings.adminEmail" type="email" class="w-full px-3 py-2 border rounded" dir="rtl" />
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">شماره تماس</label>
            <input v-model="settings.supportPhone" type="text" class="w-full px-3 py-2 border rounded" dir="rtl" />
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-500 mb-2">آدرس وب‌سایت</label>
          <input v-model="settings.websiteUrl" type="url" class="w-full px-3 py-2 border rounded" dir="rtl" />
        </div>

        <div>
          <label class="block text-sm text-gray-500 mb-2">توضیحات</label>
          <textarea v-model="settings.description" class="w-full px-3 py-2 border rounded" rows="3" dir="rtl"></textarea>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
      <h3 class="font-bold mb-4">تنظیمات اعلان‌ها</h3>
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-500 mb-2">محدودیت روزانه (تعداد)</label>
            <input v-model="notificationSettings.dailyLimit" type="number" min="1" class="w-full px-3 py-2 border rounded" />
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">مهلت خنثی‌سازی (دقیقه)</label>
            <input v-model="notificationSettings.cooldownMinutes" type="number" min="0" class="w-full px-3 py-2 border rounded" />
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-500 mb-2">فعال کردن اعلان‌های سیستم</label>
          <select v-model="notificationSettings.enabled" class="w-full px-3 py-2 border rounded">
            <option :value="true">بله</option>
            <option :value="false">خیر</option>
          </select>
        </div>

        <div>
          <label class="block text-sm text-gray-500 mb-2">پیشوند کلید کش (Redis)</label>
          <input v-model="notificationSettings.cacheKeyPrefix" type="text" class="w-full px-3 py-2 border rounded" dir="rtl" />
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
      <h3 class="font-bold mb-4">تنظیمات ارائه‌دهندگان</h3>
      <div class="space-y-4">
        <div class="flex items-center justify-between py-3 border-b">
          <div>
            <h4 class="font-medium">تنظیمات فعال‌سازی خودکار</h4>
            <p class="text-sm text-gray-500">فعال‌سازی خودکار ارائه‌دهندگان جدید</p>
          </div>
          <select v-model="providerSettings.autoActivate" class="px-3 py-2 border rounded">
            <option :value="true">بله</option>
            <option :value="false">خیر</option>
          </select>
        </div>

        <div class="flex items-center justify-between py-3 border-b">
          <div>
            <h4 class="font-medium">محدودیت سرعت بررسی</h4>
            <p class="text-sm text-gray-500">محدودیت بررسی‌های همزمان</p>
          </div>
          <input v-model="providerSettings.maxConcurrentChecks" type="number" min="1" class="w-24 px-3 py-2 border rounded" />
        </div>

        <div class="flex items-center justify-between py-3 border-b">
          <div>
            <h4 class="font-medium">اعتبارسنجی URL</h4>
            <p class="text-sm text-gray-500">اعتبارسنجی URL ارائه‌دهنده قبل از فعال‌سازی</p>
          </div>
          <select v-model="providerSettings.validateUrls" class="px-3 py-2 border rounded">
            <option :value="true">بله</option>
            <option :value="false">خیر</option>
          </select>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
      <h3 class="font-bold mb-4">تنظیمات Redis</h3>
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-500 mb-2">آدرس Redis</label>
            <input v-model="redisSettings.host" type="text" class="w-full px-3 py-2 border rounded" dir="rtl" />
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">پورت</label>
            <input v-model="redisSettings.port" type="number" class="w-full px-3 py-2 border rounded" />
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">پاس‌ورڈ</label>
            <input v-model="redisSettings.password" type="password" class="w-full px-3 py-2 border rounded" dir="rtl" />
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">دیتابیس</label>
            <input v-model="redisSettings.database" type="number" class="w-full px-3 py-2 border rounded" />
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-500 mb-2">مسیر دروازه (پروکسی)</label>
          <input v-model="redisSettings.path" type="text" class="w-full px-3 py-2 border rounded" dir="rtl" />
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
      <h3 class="font-bold mb-4">تنظیمات پایگاه داده</h3>
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-500 mb-2">نوع پایگاه داده</label>
            <select v-model="dbSettings.databaseType" class="w-full px-3 py-2 border rounded">
              <option value="pgsql">PostgreSQL</option>
              <option value="sqlite">SQLite</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">پورت</label>
            <input v-model="dbSettings.port" type="number" class="w-full px-3 py-2 border rounded" />
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-500 mb-2">هوست</label>
          <input v-model="dbSettings.host" type="text" class="w-full px-3 py-2 border rounded" dir="rtl" />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-500 mb-2">نام کاربری</label>
            <input v-model="dbSettings.username" type="text" class="w-full px-3 py-2 border rounded" dir="rtl" />
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">پاس‌ورڈ</label>
            <input v-model="dbSettings.password" type="password" class="w-full px-3 py-2 border rounded" dir="rtl" />
          </div>
        </div>

        <div>
          <label class="block text-sm text-gray-500 mb-2">نام پایگاه داده</label>
          <input v-model="dbSettings.databaseName" type="text" class="w-full px-3 py-2 border rounded" dir="rtl" />
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-3">
      <button @click="resetForm" class="px-6 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">بازنشانی</button>
      <button @click="saveSettings" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">ذخیره تنظیمات</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Settings {
  serviceName: string
  developer: string
  adminEmail: string
  supportPhone: string
  websiteUrl: string
  description: string
}

interface NotificationSettings {
  dailyLimit: number
  cooldownMinutes: number
  enabled: boolean
  cacheKeyPrefix: string
}

interface ProviderSettings {
  autoActivate: boolean
  maxConcurrentChecks: number
  validateUrls: boolean
}

interface RedisSettings {
  host: string
  port: number
  password: string
  database: number
  path: string
}

interface DbSettings {
  databaseType: string
  host: string
  port: number
  username: string
  password: string
  databaseName: string
}

const settings = ref<Settings>({
  serviceName: 'هشدار قیمت',
  developer: 'شرکت مارکفلو',
  adminEmail: 'admin@marketflow.com',
  supportPhone: '+98 21 1234567',
  websiteUrl: 'https://marketflow.com',
  description: 'پلتفرم هشدار قیمت و محصول برای بازار ایران',
})

const notificationSettings = ref<NotificationSettings>({
  dailyLimit: 10,
  cooldownMinutes: 15,
  enabled: true,
  cacheKeyPrefix: 'alert:dup:',
})

const providerSettings = ref<ProviderSettings>({
  autoActivate: true,
  maxConcurrentChecks: 10,
  validateUrls: true,
})

const redisSettings = ref<RedisSettings>({
  host: 'redis',
  port: 6379,
  password: '',
  database: 0,
  path: '',
})

const dbSettings = ref<DbSettings>({
  databaseType: 'pgsql',
  host: 'postgres',
  port: 5432,
  username: 'postgres',
  password: 'postgres',
  databaseName: 'marketflow',
})

function saveSettings() {
  console.log('Settings saved:', {
    settings: settings.value,
    notificationSettings: notificationSettings.value,
    providerSettings: providerSettings.value,
    redisSettings: redisSettings.value,
    dbSettings: dbSettings.value,
  })
}

function resetForm() {
  settings.value = {
    serviceName: 'هشدار قیمت',
    developer: 'شرکت مارکفلو',
    adminEmail: 'admin@marketflow.com',
    supportPhone: '+98 21 1234567',
    websiteUrl: 'https://marketflow.com',
    description: 'پلتفرم هشدار قیمت و محصول برای بازار ایران',
  }

  notificationSettings.value = {
    dailyLimit: 10,
    cooldownMinutes: 15,
    enabled: true,
    cacheKeyPrefix: 'alert:dup:',
  }

  providerSettings.value = {
    autoActivate: true,
    maxConcurrentChecks: 10,
    validateUrls: true,
  }

  redisSettings.value = {
    host: 'redis',
    port: 6379,
    password: '',
    database: 0,
    path: '',
  }

  dbSettings.value = {
    databaseType: 'pgsql',
    host: 'postgres',
    port: 5432,
    username: 'postgres',
    password: 'postgres',
    databaseName: 'marketflow',
  }
}
</script>
