<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-bold">مدیریت روش‌های کاری</h2>
      <button @click="openCreateModal" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        روش کاری جدید
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold">{{ editingWorkflow ? 'ویرایش روش کاری' : 'ایجاد روش کاری جدید' }}</h3>
          <button @click="closeModal" class="text-gray-500 hover:text-gray-700 text-xl">×</button>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm text-gray-500 mb-2">نام</label>
            <input v-model="form.name" type="text" class="w-full px-3 py-2 border rounded" dir="rtl" />
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">توضیحات</label>
            <textarea v-model="form.description" class="w-full px-3 py-2 border rounded" dir="rtl"></textarea>
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">وضعیت</label>
            <select v-model="form.is_active" class="w-full px-3 py-2 border rounded">
              <option :value="true">فعال</option>
              <option :value="false">غیرفعال</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">شرط‌ها</label>
            <div class="space-y-2">
              <div v-for="(condition, index) in form.conditions" :key="index" class="flex gap-2">
                <select v-model="condition.field" class="flex-1 px-3 py-2 border rounded">
                  <option value="price">قیمت</option>
                  <option value="title">عنوان</option>
                  <option value="availability">موجودی</option>
                </select>
                <select v-model="condition.operator" class="flex-1 px-3 py-2 border rounded">
                  <option value="equals">برابر است</option>
                  <option value="not_equals">برابر نیست</option>
                  <option value="greater_than">بزرگتر است</option>
                  <option value="less_than">کوچکتر است</option>
                  <option value="between">بین</option>
                  <option value="contains">شامل است</option>
                  <option value="changed">تغییر کرده</option>
                </select>
                <input v-model="condition.value" type="text" class="flex-1 px-3 py-2 border rounded" dir="rtl" />
                <button @click="removeCondition(index)" class="px-2 py-2 bg-red-600 text-white rounded text-xs">حذف</button>
              </div>
              <button @click="addCondition" class="px-3 py-2 bg-gray-100 border rounded text-sm">افزودن شرط</button>
            </div>
          </div>
          <div>
            <label class="block text-sm text-gray-500 mb-2">عملیات</label>
            <select v-model="form.action_type" class="w-full px-3 py-2 border rounded">
              <option value="notify_sms">ارسال پیامک</option>
              <option value="notify_email">ارسال ایمیل</option>
              <option value="notify_both">ارسال پیامک و ایمیل</option>
            </select>
          </div>
        </div>

        <div class="flex gap-3 mt-6">
          <button @click="saveWorkflow" class="flex-1 bg-blue-600 text-white py-2 rounded hover:bg-blue-700">ذخیره</button>
          <button @click="closeModal" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded hover:bg-gray-300">انصراف</button>
        </div>
      </div>
    </div>

    <!-- Workflows List -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">شناسه</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">نام</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">شرط‌ها</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عملیات</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">وضعیت</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">آخرین اجرا</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">عملیات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="workflow in workflows" :key="workflow.id">
            <td class="px-4 py-3 text-sm text-gray-500">#{{ workflow.id }}</td>
            <td class="px-4 py-3 font-medium">{{ workflow.name }}</td>
            <td class="px-4 py-3 text-sm">{{ workflow.conditions.length }} شرط</td>
            <td class="px-4 py-3 text-sm">{{ getActionText(workflow.action_type) }}</td>
            <td class="px-4 py-3">
              <span
                :class="workflow.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                class="px-3 py-1 rounded-full text-xs"
              >
                {{ workflow.is_active ? 'فعال' : 'غیرفعال' }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ workflow.last_run_at || 'هنوز اجرا نشده' }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button @click="editWorkflow(workflow)" class="px-2 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700">ویرایش</button>
                <button @click="toggleWorkflow(workflow)" class="px-2 py-1 bg-gray-600 text-white rounded text-xs hover:bg-gray-700">
                  {{ workflow.is_active ? 'غیرفعال' : 'فعال' }}
                </button>
                <button @click="deleteWorkflow(workflow)" class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">حذف</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Condition {
  field: string
  operator: string
  value: string
}

interface Workflow {
  id: number
  name: string
  description: string
  is_active: boolean
  conditions: Condition[]
  action_type: string
  last_run_at: string | null
}

const showModal = ref(false)
const editingWorkflow = ref<Workflow | null>(null)

const workflows = ref<Workflow[]>([
  {
    id: 1,
    name: 'هشدار کاهش قیمت',
    description: 'اطلاع‌رسانی هنگام کاهش قیمت محصول',
    is_active: true,
    conditions: [
      { field: 'price', operator: 'less_than', value: '10000000' },
    ],
    action_type: 'notify_sms',
    last_run_at: '2025-01-15T10:30:00Z',
  },
  {
    id: 2,
    name: 'هشدار موجودی',
    description: 'اطلاع‌رسانی هنگام تغییر وضعیت موجودی',
    is_active: false,
    conditions: [
      { field: 'availability', operator: 'changed', value: '' },
    ],
    action_type: 'notify_email',
    last_run_at: null,
  },
])

const form = ref<Workflow>({
  id: 0,
  name: '',
  description: '',
  is_active: true,
  conditions: [
    { field: 'price', operator: 'equals', value: '' },
  ],
  action_type: 'notify_sms',
  last_run_at: null,
})

function openCreateModal() {
  editingWorkflow.value = null
  form.value = {
    id: 0,
    name: '',
    description: '',
    is_active: true,
    conditions: [
      { field: 'price', operator: 'equals', value: '' },
    ],
    action_type: 'notify_sms',
    last_run_at: null,
  }
  showModal.value = true
}

function addCondition() {
  form.value.conditions.push({ field: 'price', operator: 'equals', value: '' })
}

function removeCondition(index: number) {
  if (form.value.conditions.length > 1) {
    form.value.conditions.splice(index, 1)
  }
}

function editWorkflow(workflow: Workflow) {
  editingWorkflow.value = workflow
  form.value = JSON.parse(JSON.stringify(workflow))
  showModal.value = true
}

function saveWorkflow() {
  if (editingWorkflow.value) {
    const index = workflows.value.findIndex(w => w.id === editingWorkflow.value!.id)
    if (index !== -1) {
      workflows.value[index] = JSON.parse(JSON.stringify(form.value))
    }
  } else {
    workflows.value.push({
      ...form.value,
      id: Math.max(0, ...workflows.value.map(w => w.id)) + 1,
      last_run_at: null,
    })
  }
  showModal.value = false
}

function toggleWorkflow(workflow: Workflow) {
  workflow.is_active = !workflow.is_active
}

function deleteWorkflow(workflow: Workflow) {
  const index = workflows.value.findIndex(w => w.id === workflow.id)
  if (index !== -1) {
    workflows.value.splice(index, 1)
  }
}

function getActionText(actionType: string): string {
  switch (actionType) {
    case 'notify_sms': return 'پیامک'
    case 'notify_email': return 'ایمیل'
    case 'notify_both': return 'پیامک و ایمیل'
    default: return actionType
  }
}

function closeModal() {
  showModal.value = false
}
</script>
