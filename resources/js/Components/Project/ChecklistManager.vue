<template>
  <div class="checklist-manager bg-gradient-to-br from-white via-pink-50 to-rose-50 rounded-3xl shadow-sm p-4 border border-pink-100">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-2">
      <!-- Left Side -->
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-r from-pink-400 to-rose-500 rounded-xl flex items-center justify-center shadow-md">
          <span class="text-lg text-white">📋</span>
        </div>
        <div>
          <h3 class="font-bold text-gray-800 text-lg">Checklist Detail</h3>
          <p class="text-sm text-gray-600">
            Kelola langkah-langkah detail untuk
            <span class="font-semibold text-pink-600">{{ itemName }}</span>
            <span class="text-rose-500 font-semibold ml-1">({{ progressPercentage }}%)</span>
          </p>
        </div>
      </div>

      <!-- Progress Info -->
      <div class="flex items-center gap-3">
        <div class="text-right">
          <div class="text-sm font-semibold text-gray-700">
            {{ completedCount }} / {{ totalCount }} selesai
          </div>
          <div class="w-28 bg-gray-200 rounded-full h-2 overflow-hidden">
            <div
              class="h-2 rounded-full transition-all duration-500 bg-gradient-to-r from-pink-400 to-rose-500"
              :style="{ width: progressPercentage + '%' }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Checklist -->
    <div class="mb-4">
      <div class="flex flex-row flex-wrap items-center gap-3 bg-white/70 backdrop-blur-sm border border-pink-100 rounded-2xl p-4">
        <TextInput
          v-model="newTask"
          @keypress.enter="addChecklist"
          placeholder="Tambahkan langkah baru... (contoh: Konfirmasi menu, Kirim DP, dll)"
          class="flex-1 min-w-[180px] px-4 py-3 rounded-xl border border-pink-200 focus:ring-2 focus:ring-pink-400 focus:border-transparent bg-white transition-all"
          :disabled="adding"
        />

        <BaseButton
          @click="addChecklist"
          :loading="adding"
          class="!px-6 !py-3 bg-gradient-to-r from-pink-400 to-rose-500 text-white shadow-md hover:shadow-lg whitespace-nowrap"
        >
          <template #icon>➕</template>
          Tambah
        </BaseButton>
      </div>
    </div>


    <!-- Checklists -->
    <div class="space-y-3">
      <!-- Empty State -->
      <div
        v-if="checklists.length === 0"
        class="text-center py-4 bg-white/60 backdrop-blur-sm rounded-2xl border border-pink-100"
      >
        <div class="text-6xl mb-2">📝</div>
        <h4 class="text-lg font-semibold text-gray-700 mb-2">Belum ada checklist</h4>
        <p class="text-gray-600 max-w-md mx-auto">
          Tambahkan langkah-langkah romantis untuk mencapai tujuan {{ itemName.toLowerCase() }} ini 💖
        </p>
      </div>

      <!-- Checklist Items -->
      <div
        v-for="checklist in sortedChecklists"
        :key="checklist.id"
        class="group bg-white/80 backdrop-blur-sm rounded-2xl p-4 border-2 transition-all duration-300 hover:shadow-lg"
        :class="[
          checklist.is_completed 
            ? 'border-green-200 bg-gradient-to-r from-green-50 to-emerald-50' 
            : 'border-pink-100 hover:border-pink-200'
        ]"
      >
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
          <!-- Edit Mode -->
          <div v-if="editingId === checklist.id" class="flex-1 flex items-center gap-3">
            <input
              ref="editInput"
              v-model="editingText"
              @keypress.enter="saveEdit(checklist)"
              @blur="saveEdit(checklist)"
              @keypress.escape="cancelEdit"
              class="flex-1 px-3 py-2 rounded-lg border border-blue-300 focus:ring-2 focus:ring-blue-400 focus:border-transparent"
            />
            <div class="flex gap-2">
              <BaseButton
                @click="saveEdit(checklist)"
                class="w-8 h-8 rounded-full flex items-center justify-center bg-green-500 text-white hover:bg-green-600"
                title="Simpan"
              >
                ✓
              </BaseButton>
              <BaseButton
                @click="cancelEdit"
                class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-500 text-white hover:bg-gray-600"
                title="Batal"
              >
                ✕
              </BaseButton>
            </div>
          </div>

          <!-- View Mode -->
<template v-else>
  <div class="flex flex-row flex-wrap items-center justify-between gap-2 bg-white/70 border border-pink-100 rounded-2xl p-3 group transition-all">
    <!-- ✅ ToggleInput -->
    <ToggleInput
      v-model="checklist.is_completed"
      :label="checklist.task_description"
      :description="checklist.is_completed ? 'Selesai dikerjakan 💫' : 'Belum selesai'"
      @update:modelValue="toggleChecklist(checklist)"
      class="flex-1 min-w-[200px]"
    />

    <!-- 🎯 Actions -->
    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
      <BaseButton
        @click="startEditing(checklist)"
        class="w-8 h-8 rounded-full flex items-center justify-center text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
        title="Edit checklist"
      >
        ✏️
      </BaseButton>

      <BaseButton
        @click="showDeleteModal(checklist)"
        :disabled="deletingId === checklist.id"
        class="w-8 h-8 rounded-full flex items-center justify-center text-gray-500 hover:text-red-600 hover:bg-red-50 transition-colors"
        title="Hapus checklist"
      >
        🗑️
      </BaseButton>
    </div>
  </div>
</template>

        </div>

        <!-- Completed Timestamp -->
        <div
          v-if="checklist.is_completed && checklist.completed_at"
          class="flex items-center gap-2 mt-2 pl-1 text-xs text-green-600"
        >
          <span>✅ Selesai: {{ formatDate(checklist.completed_at) }}</span>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="showDeleteModalFlag"
      class="fixed inset-0 flex items-center justify-center z-50 bg-black/50 backdrop-blur-sm"
    >
      <div class="bg-white rounded-3xl p-6 text-center max-w-sm mx-4 shadow-lg" @click.stop>
        <div class="text-4xl mb-4">⚠️</div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Checklist?</h3>
        <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus checklist ini? Tindakan ini tidak dapat dibatalkan.</p>
        <div class="flex gap-3 justify-center">
          <BaseButton 
            @click="cancelDelete" 
            class="!px-6 bg-gray-500 text-white hover:bg-gray-600"
          >
            Batal
          </BaseButton>
          <BaseButton 
            @click="confirmDelete" 
            :loading="deletingId === checklistToDelete?.id"
            class="!px-6 bg-gradient-to-r from-red-400 to-red-500 text-white hover:from-red-500 hover:to-red-600"
          >
            <template #icon>🗑️</template>
            Hapus
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Celebration Modal -->
    <div
      v-if="showCelebration"
      class="fixed inset-0 flex items-center justify-center z-50 bg-black/50 backdrop-blur-sm"
      @click="showCelebration = false"
    >
      <div class="bg-white rounded-3xl p-8 text-center max-w-sm mx-4 animate-bounce shadow-lg">
        <div class="text-6xl mb-4">🎉</div>
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Selamat! 🎊</h3>
        <p class="text-gray-600 mb-4">Semua checklist sudah selesai 💖</p>
        <BaseButton @click="showCelebration = false" class="!px-6 bg-gradient-to-r from-pink-400 to-rose-500 text-white shadow">
          <template #icon>✨</template>
          Lanjutkan
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import BaseButton from '../Base/BaseButton.vue'
import ToggleInput from '../Form/ToggleInput.vue'
import TextInput from '../TextInput.vue'

const props = defineProps({
  projectItem: Object,
  checklists: Array,
  itemName: String,
})

const emits = defineEmits(['checklist-updated'])

const checklists = ref(props.checklists || [])
const newTask = ref('')
const adding = ref(false)
const togglingId = ref(null)
const deletingId = ref(null)
const editingId = ref(null)
const editingText = ref('')
const showCelebration = ref(false)
const showDeleteModalFlag = ref(false)
const checklistToDelete = ref(null)
const editInput = ref(null)

const totalCount = computed(() => checklists.value.length)
const completedCount = computed(() => checklists.value.filter(c => c.is_completed).length)
const progressPercentage = computed(() =>
  totalCount.value > 0 ? Math.round((completedCount.value / totalCount.value) * 100) : 0
)

const sortedChecklists = computed(() => {
  return [...checklists.value].sort((a, b) => {
    if (a.is_completed && !b.is_completed) return 1
    if (!a.is_completed && b.is_completed) return -1
    return new Date(a.created_at) - new Date(b.created_at)
  })
})

watch(progressPercentage, (newVal, oldVal) => {
  if (newVal === 100 && oldVal < 100 && totalCount.value > 0) {
    showCelebration.value = true
    setTimeout(() => (showCelebration.value = false), 3000)
  }
})

const addChecklist = async () => {
  if (!newTask.value.trim()) return
  adding.value = true
  try {
    const response = await axios.post(route('project-items.checklists.store', {
      projectItem: props.projectItem.id,
    }), {
      task_description: newTask.value.trim(),
    })
    if (response.data.success) {
      checklists.value.push(response.data.checklist)
      newTask.value = ''
      emits('checklist-updated')
    }
  } catch (error) {
    console.error('Error adding checklist:', error)
    alert('Gagal menambahkan checklist')
  } finally {
    adding.value = false
  }
}

const toggleChecklist = async (checklist) => {
  togglingId.value = checklist.id
  try {
    const response = await axios.patch(route('project-items.checklists.toggle', {
      projectItem: props.projectItem.id,
      checklist: checklist.id,
    }))
    if (response.data.success) {
      const index = checklists.value.findIndex(c => c.id === checklist.id)
      if (index !== -1) {
        checklists.value[index] = response.data.checklist
      }
      emits('checklist-updated')
    }
  } catch (error) {
    console.error('Error toggling checklist:', error)
    // Revert the toggle on error
    checklist.is_completed = !checklist.is_completed
  } finally {
    togglingId.value = null
  }
}

const startEditing = (checklist) => {
  editingId.value = checklist.id
  editingText.value = checklist.task_description
  nextTick(() => {
    if (editInput.value) {
      editInput.value.focus()
      editInput.value.select()
    }
  })
}

const saveEdit = async (checklist) => {
  if (!editingText.value.trim()) {
    cancelEdit()
    return
  }

  try {
    const response = await axios.put(route('project-items.checklists.update', {
      projectItem: props.projectItem.id,
      checklist: checklist.id,
    }), {
      task_description: editingText.value.trim(),
    })

    if (response.data.success) {
      const index = checklists.value.findIndex(c => c.id === checklist.id)
      if (index !== -1) {
        checklists.value[index] = response.data.checklist
      }
      emits('checklist-updated')
    }
  } catch (error) {
    console.error('Error updating checklist:', error)
    alert('Gagal memperbarui checklist')
  } finally {
    editingId.value = null
    editingText.value = ''
  }
}

const cancelEdit = () => {
  editingId.value = null
  editingText.value = ''
}

const showDeleteModal = (checklist) => {
  checklistToDelete.value = checklist
  showDeleteModalFlag.value = true
}

const cancelDelete = () => {
  showDeleteModalFlag.value = false
  checklistToDelete.value = null
}

const confirmDelete = async () => {
  if (!checklistToDelete.value) return
  
  deletingId.value = checklistToDelete.value.id
  try {
    const response = await axios.delete(route('project-items.checklists.destroy', {
      projectItem: props.projectItem.id,
      checklist: checklistToDelete.value.id,
    }))
    
    if (response.data.success) {
      checklists.value = checklists.value.filter(c => c.id !== checklistToDelete.value.id)
      emits('checklist-updated')
    }
  } catch (error) {
    console.error('Error deleting checklist:', error)
    alert('Gagal menghapus checklist')
  } finally {
    deletingId.value = null
    showDeleteModalFlag.value = false
    checklistToDelete.value = null
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<style scoped>
.checklist-manager::-webkit-scrollbar {
  width: 6px;
}
.checklist-manager::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #f472b6, #ec4899);
  border-radius: 10px;
}
</style>