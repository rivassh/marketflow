<template>
  <div class="space-y-8">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="text-sm text-gray-500">کل کاربران</div>
          <div class="text-3xl font-bold">{{ stats.totalUsers }}</div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="text-sm text-gray-500">فعال پیگیری‌ها</div>
          <div class="text-3xl font-bold text-green-600">{{ stats.activeWatches }}</div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="text-sm text-gray-500">بررسی‌های 24 ساعته اخیر</div>
          <div class="text-3xl font-bold">{{ stats.checksLast24h }}</div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="text-sm text-gray-500">اعلان‌های ناموفق</div>
          <div class="text-3xl font-bold text-red-600">{{ stats.notificationsFailed }}</div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Notifications Trend -->
      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-bold mb-4">روند اطلاع‌رسانی‌ها (7 روز)</h3>
        <div class="h-48 bg-gray-50 rounded-lg" id="notifications-chart">
          <!-- Chart would go here -->
          <div class="flex h-full items-center justify-center text-gray-400">
            نمودار روند اطلاع‌رسانی‌ها
          </div>
        </div>
      </div>

      <!-- Provider Health -->
      <div class="bg-white rounded-xl shadow p-6">
        <h3 class="font-bold mb-4">وضعیت ارائه‌دهندگان</h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
            <span>Torob</span>
            <span class="px-3 py-1 rounded-full bg-green-100 text-green-800 text-xs">
              فعال
            </span>
          </div>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
            <span>Divar (در حال توسعه)</span>
            <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 text-xs">
              غیرفعال
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow p-6">
      <h3 class="font-bold mb-4">فعالیت‌های اخیر</h3>
      <div class="space-y-4">
        <div class="p-4 bg-gray-50 rounded">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 flex items-center justify-center bg-blue-100 text-blue-600 rounded">
              👤
            </div>
            <div class="flex-1">
            <p class="font-small">کاربر جدید ثبت نام کرد: +989123456789</p>
            <p class="text-xs text-gray-500">2 دقیقه پیش</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Mock stats - in real implementation, these would come from API
const stats = {
  totalUsers: 1247,
  activeWatches: 342,
  checksLast24h: 2891,
  notificationsFailed: 12
}
</script>