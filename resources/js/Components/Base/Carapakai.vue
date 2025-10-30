<script>
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-pink-50 via-white to-sky-50 p-6">
    <!-- BaseCard Example -->
    <BaseCard
      title="Dashboard Analytics"
      description="Ringkasan performa dan statistik terkini"
      class="mb-6"
    >
      <p>Konten card yang menarik...</p>
    </BaseCard>

    <!-- BaseTable Example -->
    <BaseTable
      title="Data Pengguna"
      description="Kelola data pengguna sistem"
      :columns="columns"
      :data="users"
      :loading="loading"
      @create="openCreateModal"
      @edit="editUser"
      @delete="deleteUser"
    >
      <template #header-action>
        <BaseButton variant="success" @click="exportData">
          <template #icon>📤</template>
          Export Data
        </BaseButton>
      </template>
    </BaseTable>

    <!-- BaseModal Example -->
    <BaseModal
      v-model:show="showModal"
      title="Tambah Pengguna"
      description="Tambahkan pengguna baru ke sistem"
      icon="👤"
      confirm-text="Simpan"
      @confirm="saveUser"
    >
      <form class="space-y-4">
        <!-- Form inputs here -->
      </form>
    </BaseModal>

    <!-- BaseButton Examples -->
    <div class="flex gap-3 mt-6 flex-wrap">
      <BaseButton variant="primary" size="lg">
        <template #icon>⭐</template>
        Primary Button
      </BaseButton>
      
      <BaseButton variant="secondary">
        Secondary
      </BaseButton>
      
      <BaseButton variant="danger" :loading="deleting">
        <template #icon>🗑️</template>
        Hapus Data
      </BaseButton>
      
      <BaseButton variant="success">
        <template #icon>✅</template>
        Success
      </BaseButton>
      
      <BaseButton variant="ghost" size="sm">
        Ghost Button
      </BaseButton>
    </div>
  </div>
</template>