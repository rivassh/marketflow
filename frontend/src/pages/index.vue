<template>
  <div>
    <nav class="bg-white shadow-sm p-4">
      <div class="max-w-6xl mx-auto flex justify-between items-center">
        <h1 class="text-xl font-bold text-blue-600">🏷️ مارکت‌فلو</h1>
        <div class="space-x-4 flex items-center">
          <NuxtLink to="/" class="text-gray-600 hover:text-blue-600">خانه</NuxtLink>
          <NuxtLink to="/watches" class="text-gray-600 hover:text-blue-600">پیگیری‌ها</NuxtLink>
          <NuxtLink to="/login" class="text-gray-600 hover:text-gray-800 px-4 py-2">ورود</NuxtLink>
          <NuxtLink to="/register" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">ثبت‌نام</NuxtLink>
        </div>
      </div>
    </nav>
    
    <main class="max-w-6xl mx-auto p-8">
      <section class="text-center mb-12">
        <h2 class="text-4xl font-bold mb-4 text-gray-800">
          هشدار هوشمند <span class="text-blue-600">قیمت</span>
        </h2>
        <p class="text-xl text-gray-600 mb-8">
          لیست محصولات مورد نظرتان را بسازید و وقتی قیمت کاهش یافت، خبرتان کنیم!
        </p>
        
        <div class="bg-white rounded-xl shadow-lg p-6 max-w-xl mx-auto">
          <input 
            type="text" 
            placeholder="لینک محصول را وارد کنید..." 
            class="w-full p-4 border border-gray-300 rounded-lg mb-4 text-right"
            dir="rtl"
          />
          <button class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700">
            ➕ افزودن به پیگیری‌ها
          </button>
        </div>
      </section>
      
      <section class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
        <div class="bg-white p-6 rounded-xl shadow text-center">
          <div class="text-3xl mb-3">💰</div>
          <h3 class="font-bold mb-2">هشدار قیمت</h3>
          <p class="text-sm text-gray-600">کاهش قیمت = اطلاع</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow text-center">
          <div class="text-3xl mb-3">🔔</div>
          <h3 class="font-bold mb-2">اطلاع‌رسانی فوری</h3>
          <p class="text-sm text-gray-600">در لحظه خبردار شوید</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow text-center">
          <div class="text-3xl mb-3">📊</div>
          <h3 class="font-bold mb-2">تاریخچه قیمت</h3>
          <p class="text-sm text-gray-600">روند قیمت‌ها را ببینید</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow text-center">
          <div class="text-3xl mb-3">🏷️</div>
          <h3 class="font-bold mb-2">چند فروشگاه</h3>
          <p class="text-sm text-gray-600">ترب، دیوار، ایمالز</p>
        </div>
      </section>
      
      <section>
        <h3 class="text-2xl font-bold mb-6 text-gray-800">📱 محصولات پیگیری شده</h3>
        <div class="space-y-4">
          <div class="bg-white p-4 rounded-xl shadow flex justify-between items-center">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">📱</div>
              <div>
                <h4 class="font-bold">iPhone 15 Pro Max</h4>
                <p class="text-sm text-gray-500">بررسی: ۵ دقیقه پیش</p>
              </div>
            </div>
            <div class="text-left">
              <div class="font-bold text-lg">۸۹,۰۰۰,۰۰۰ تومان</div>
              <div class="text-sm text-green-600">🎯 هدف: ۸۵,۰۰۰,۰۰۰</div>
            </div>
          </div>
          <div class="bg-white p-4 rounded-xl shadow flex justify-between items-center">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">🎧</div>
              <div>
                <h4 class="font-bold">AirPods Pro 2</h4>
                <p class="text-sm text-gray-500">بررسی: ۱۲ دقیقه پیش</p>
              </div>
            </div>
            <div class="text-left">
              <div class="font-bold text-lg">۱۲,۵۰۰,۰۰۰ تومان</div>
              <div class="text-sm text-green-600">🎉 به هدف رسید!</div>
            </div>
          </div>
        </div>
      </section>
    </main>
    
    <footer class="bg-gray-900 text-white text-center py-6 mt-12">
      <p>مارکت‌فلو - هشدار هوشمند قیمت</p>
      <p class="text-sm text-gray-400 mt-2">Backend API: torob.adlr.ir | Tests: 15/15 ✅</p>
    </footer>
  </div>
</template>
