<template>
  <div class="price-alerts-container rtl" dir="rtl">
    <div class="price-alerts-card mb-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">اطلاعیه‌های قیمت</h2>
        <button @click="showAddAlertModal" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
          افزودن هشدار جدید
        </button>
      </div>
      
      <!-- Search and Filters -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex-1 sm:w-64">
          <input 
            type="text" 
            v-model="searchText" 
            placeholder="جستجو در محصولات..."
            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
          />
        </div>
        <div class="flex-1 sm:w-48">
          <select 
            v-model="sortColumn"
            @change="loadPriceAlerts"
            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
          >
            <option value="">ترتیب پیش‌فرض</option>
            <option value="product.title">عنوان محصول</option>
            <option value="min_price">قیمت کمترین</option>
            <option value="max_price">قیمت بیشترین</option>
            <option value="is_active">وضعیت فعال</option>
          </select>
        </div>
        <div class="flex-1 sm:w-48">
          <select 
            v-model="filterColumn"
            @change="loadPriceAlerts"
            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
          >
            <option value="">فیلتر پیش‌فرض</option>
            <option value="provider">نام ارائه‌دهنده</option>
            <option value="availability">وجود در انبار</option>
            <option value="stock_status">موجودی</option>
          </select>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full border-4 border-t-4 border-green-500 w-16 h-16"></div>
      </div>
      
      <!-- Alerts Table -->
      <div v-else-if="!loading && priceAlerts.length > 0" class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">محصول</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">بازه قیمت</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">فروشنده</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="alert in priceAlerts" :key="alert.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ alert.product.title || 'محصول نامشخص' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                {{ formatPrice(alert.min_price) }} - {{ formatPrice(alert.max_price) }} {{ alert.currency.toUpperCase() }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  :class="[
                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                    alert.availability === 'available' ? 'bg-green-100 text-green-800' :
                    alert.availability === 'unavailable' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'
                  ]"
                >
                  {{ alert.availability }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ alert.seller_name || '----' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button 
                  @click="editAlert(alert)"
                  class="text-indigo-600 hover:text-indigo-900 mr-3"
                >
                  ویرایش
                </button>
                <button 
                  @click="deleteAlert(alert.id)"
                  class="text-red-600 hover:text-red-900"
                >
                  حذف
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Empty State -->
      <div v-else-if="!loading && priceAlerts.length === 0" class="text-center py-12">
        <p class="text-gray-500">هیچ هشدار قیمتی یافت نشد</p>
      </div>
      
      <!-- Pagination -->
      <div v-if="!loading && totalPages > 1" class="flex justify-center items-center mt-6 space-x-2">
        <button 
          :disabled="currentPage === 1"
          @click="currentPage--"
          class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
        >
          قبلی
        </button>
        <span class="mx-3">{{ currentPage }} / {{ totalPages }}</span>
        <button 
          :disabled="currentPage === totalPages"
          @click="currentPage++"
          class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
        >
          بعدی
        </button>
      </div>
    </div>
    
    <!-- Add/Edit Alert Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="px-6 py-4 border-b">
          <h3 class="text-lg font-medium text-gray-900">
            {{ editAlertData ? 'ویرایش هشدار' : 'افزودن هشدار جدید' }}
          </h3>
          <button 
            @click="showModal = false"
            class="absolute top-3 right-3 text-gray-400 hover:text-gray-600"
          >
            ✕
          </button>
        </div>
        <div class="px-6 py-4">
          <form @submit.prevent="submitAlert">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">محصول</label>
              <select 
                v-model="formData.product_id"
                @change="loadProductDetails"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
              >
                <option value="">یک محصول را انتخاب کنید...</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.title || 'Proudct ' + product.id }}
                </option>
              </select>
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">حداقل قیمت (تومان)</label>
              <input 
                type="number"
                v-model.number="formData.min_price"
                placeholder="حداقل قیمت را وارد کنید"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                min="0"
              />
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">بیشترین قیمت (تومان)</label>
              <input 
                type="number"
                v-model.number="formData.max_price"
                placeholder="بیشترین قیمت را وارد کنید"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                min="0"
              />
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">نوع ارز</label>
              <select 
                v-model="formData.currency"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
              >
                <option value="IRR">ریال (IRR)</option>
                <option value="USD">دلار (USD)</option>
                <option value="EUR">یورو (EUR)</option>
              </select>
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">وضعیت موجودی</label>
              <select 
                v-model="formData.availability"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
              >
                <option value="available">موجود</option>
                <option value="unavailable">ناموجود</option>
                <option value="unknown">نامشخص</option>
              </select>
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">موجودی انبار</label>
              <input 
                type="text"
                v-model="formData.stock_status"
                placeholder="موجودی انبار را وارد کنید"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
              />
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">نام فروشنده</label>
              <input 
                type="text"
                v-model="formData.seller_name"
                placeholder="نام فروشنده را وارد کنید"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
              />
            </div>
            
            <div class="mb-4 flex items-center">
              <input 
                type="checkbox"
                id="is_active"
                v-model="formData.is_active"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label for="is_active" class="ml-2 block text-sm font-medium text-gray-900">
                هشدار فعال است
              </label>
            </div>
            
            <div class="mb-6 flex justify-end space-x-3">
              <button 
                @click="showModal = false"
                class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
              >
                انصراف
              </button>
              <button 
                type="submit"
                :disabled="submitting"
                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
              >
                {{ submitting ? 'در حال ذخیره...' : (editAlertData ? 'به‌روزرسانی' : 'افزودن') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data() {
    return {
      priceAlerts: [],
      products: [],
      loading: true,
      searchText: '',
      sortColumn: '',
      filterColumn: '',
      currentPage: 1,
      perPage: 10,
      totalPages: 1,
      
      // Form data for add/edit
      showModal: false,
      editAlertData: null,
      submitting: false,
      formData: {
        product_id: '',
        min_price: '',
        max_price: '',
        currency: 'IRR',
        availability: 'available',
        stock_status: '',
        seller_name: '',
        is_active: true
      }
    }
  },
  
  async mounted() {
    await this.loadPriceAlerts()
    await this.loadProducts()
  },
  
  methods: {
    async loadPriceAlerts() {
      this.loading = true
      try {
        const response = await axios.get('/api/product-price-alerts', {
          params: {
            page: this.currentPage,
            perPage: this.perPage,
            search: this.searchText,
            sort: this.sortColumn,
            filter: this.filterColumn
          }
        })
        this.priceAlerts = response.data.data || []
        this.totalPages = response.data.meta ? response.data.meta.last_page : 1
        this.loading = false
      } catch (error) {
        console.error('Failed to load price alerts:', error)
        this.loading = false
        this.$toast.error('خطا در بارگذاری هشدارهای قیمتی')
      }
    },
    
    async loadProducts() {
      try {
        const response = await axios.get('/api/products')
        this.products = response.data || []
      } catch (error) {
        console.error('Failed to load products:', error)
        this.$toast.error('خطا در بارگذاری محصولات')
      }
    },
    
    async loadProductDetails() {
      // Load product details when product is selected
      const productId = this.formData.product_id
      if (!productId) return
      
      try {
        const response = await axios.get(`/api/products/${productId}`)
        this.selectedProduct = response.data
      } catch (error) {
        console.error('Failed to load product details:', error)
      }
    },
    
    formatPrice(price) {
      if (price === null || price === undefined) return '---'
      // Convert from minor units (e.g., cents) to display format
      const priceInMajorUnits = price / 100 // Assuming stored in minor units like cents
      return new Intl.NumberFormat('fa-IR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(priceInMajorUnits)
    },
    
    showAddAlertModal() {
      this.editAlertData = null
      this.formData = {
        product_id: '',
        min_price: '',
        max_price: '',
        currency: 'IRR',
        availability: 'available',
        stock_status: '',
        seller_name: '',
        is_active: true
      }
      this.showModal = true
    },
    
    editAlert(alert) {
      this.editAlertData = alert
      this.formData = {
        product_id: alert.product_id,
        min_price: alert.min_price,
        max_price: alert.max_price,
        currency: alert.currency,
        availability: alert.availability,
        stock_status: alert.stock_status || '',
        seller_name: alert.seller_name || '',
        is_active: alert.is_active
      }
      this.showModal = true
    },
    
    async submitAlert() {
      this.submitting = true
      try {
        if (this.editAlertData) {
          // Update existing alert
          await axios.put(`/api/product-price-alerts/${this.editAlertData.id}`, this.formData)
          this.$toast.success('هشدار قیمت با موفقیت به‌روزرسانی شد')
        } else {
          // Create new alert
          await axios.post('/api/product-price-alerts', this.formData)
          this.$toast.success('هشدار قیمت با موفقیت افزوده شد')
        }
        
        this.showModal = false
        await this.loadPriceAlerts()
      } catch (error) {
        console.error('Failed to save alert:', error)
        if (error.response && error.response.data.errors) {
          // Handle validation errors
          this.$toast.error('لطفاً فرم را به‌صورت صحیح پر کنید')
        } else {
          this.$toast.error('خطا در ذخیره هشدار قیمت')
        }
      } finally {
        this.submitting = false
      }
    },
    
    async deleteAlert(id) {
      if (!confirm('آیا مطمئن هستید که می‌خواهید این هشدار را حذف کنید؟')) return
      
      try {
        await axios.delete(`/api/product-price-alerts/${id}`)
        this.$toast.success('هشدار قیمت با موفقیت حذف شد')
        await this.loadPriceAlerts()
      } catch (error) {
        console.error('Failed to delete alert:', error)
        this.$toast.error('خطا در حذف هشدار قیمت')
      }
    }
  }
}
</script>

<style scoped>
.price-alerts-container {
  min-height: 600px;
}

.price-alerts-card {
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.price-alerts-card:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.price-alerts-table {
  width: 100%;
  border-collapse: collapse;
}

.price-alerts-table th,
.price-alerts-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.price-alerts-table th {
  background-color: #f7fafc;
  font-weight: 600;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #4a5568;
}

.price-alerts-table tbody tr:hover {
  background-color: #edf2f7;
}

.price-alerts-table tbody tr:last-child td {
  border-bottom: none;
}

/* Modal Styles */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  width: 100%;
  max-width: 28rem;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #2d3748;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  padding: 1.5rem;
  text-align: right;
  border-top: 1px solid #e2e8f0;
}

/* Responsive Design */
@media (max-width: 640px) {
  .price-alerts-card {
    margin: 1rem;
  }
  
  .price-alerts-table th,
  .price-alerts-table td {
    padding: 0.5rem;
    font-size: 0.875rem;
  }
  
  .modal-content {
    margin: 1rem;
  }
}
</style>