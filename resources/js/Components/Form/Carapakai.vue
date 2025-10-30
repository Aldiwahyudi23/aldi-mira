<script>

</script>
<template>
  <div class="space-y-6">
    <!-- Text Input -->
    <TextInput
      v-model="form.name"
      label="Nama Lengkap"
      placeholder="Masukkan nama lengkap"
      icon="👤"
      required
    />

    <!-- Number Input -->
    <NumberInput
      v-model="form.amount"
      label="Jumlah"
      placeholder="0"
      prefix="Rp"
      suffix=",-"
      :decimals="2"
      icon="💰"
    />

    <!-- Account Input -->
    <AccountInput
      v-model="form.username"
      label="Username"
      placeholder="username"
      :show-strength="true"
      :show-copy-button="true"
    />

    <!-- Text Area -->
    <TextAreaInput
      v-model="form.description"
      label="Deskripsi"
      placeholder="Masukkan deskripsi..."
      :rows="4"
      :show-counter="true"
      :max-length="500"
      icon="📝"
    />

    <!-- Select Input -->
    <SelectInput
      v-model="form.category"
      label="Kategori"
      placeholder="Pilih kategori"
      :options="categoryOptions"
      icon="📁"
    />

    <!-- Option Input -->
    <OptionInput
      v-model="form.permissions"
      label="Hak Akses"
      :options="permissionOptions"
      :multiple="true"
      icon="🔐"
    />

    <!-- File Input -->
    <FileInput
      v-model="form.avatar"
      label="Foto Profil"
      accept="image/*"
      :max-size="2 * 1024 * 1024"
      icon="🖼️"
    />
  </div>
</template>