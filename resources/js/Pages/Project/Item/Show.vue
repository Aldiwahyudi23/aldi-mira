<template>
    <AppLayout :title="`${item.name} - ${project.name}`">
        <div class="py-2 min-h-screen relative overflow-hidden">
            <!-- PERBAIKAN: Padding lebih kecil untuk mobile -->
            <div class="w-full px-3 sm:px-4 lg:px-6 relative z-10">
                <!-- Flash Message -->
                <div 
                    v-if="$page.props.flash?.message" 
                    class="mb-4 md:mb-6 p-3 md:p-4 rounded-xl md:rounded-2xl border backdrop-blur-sm transition-all duration-300"
                    :class="{
                        'bg-green-50 border-green-200 text-green-800': $page.props.flash.message?.type === 'success',
                        'bg-red-50 border-red-200 text-red-800': $page.props.flash.message?.type === 'error'
                    }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 md:gap-3">
                            <span class="text-lg md:text-xl">
                                {{ $page.props.flash.message?.type === 'success' ? '✅' : '⚠️' }}
                            </span>
                            <span class="font-medium text-sm md:text-base">{{ $page.props.flash.message?.message }}</span>
                        </div>
                        <button 
                            @click="$page.props.flash.message = null"
                            class="text-gray-500 hover:text-gray-700 transition-colors text-lg"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Breadcrumb -->
                <nav class="flex mb-4" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2 text-sm">
                        <li>
                            <Link 
                                :href="route('projects.index')" 
                                class="text-gray-600 hover:text-pink-600 transition-colors flex items-center gap-1"
                            >
                                <span class="text-base md:text-lg">💼</span>
                                <span class="hidden xs:inline">Projects</span>
                            </Link>
                        </li>
                        <li class="flex items-center">
                            <span class="text-gray-400 mx-2">›</span>
                            <Link 
                                :href="route('projects.items.index', { project: project.id })" 
                                class="text-gray-600 hover:text-pink-600 transition-colors text-xs md:text-sm"
                            >
                                {{ project.name }}
                            </Link>
                        </li>
                        <li class="flex items-center">
                            <span class="text-gray-400 mx-2">›</span>
                            <span class="text-gray-700 font-medium text-xs md:text-sm truncate max-w-[120px] md:max-w-none">{{ item.name }}</span>
                        </li>
                    </ol>
                </nav>

                <!-- 🌸 INFORMASI PROJECT ELEGAN RESPONSIF -->
                <div class="relative overflow-hidden bg-gradient-to-br from-pink-50 via-white to-blue-50 rounded-xl md:rounded-2xl border border-pink-100/40 shadow-md p-4 mb-4">
                    <!-- Ornamen Latar -->
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-pink-100/40 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-blue-100/40 rounded-full blur-3xl"></div>

                    <!-- Header -->
                    <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-3 md:gap-0 mb-3 md:mb-2">
                        <div class="flex items-center gap-3 md:gap-4">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-r from-pink-400 to-rose-500 rounded-lg md:rounded-xl flex items-center justify-center shadow-md">
                                <span class="text-xl md:text-2xl text-white">{{ item.item_type_icon }}</span>
                            </div>
                            <div>
                                <h1 class="text-lg md:text-2xl font-bold text-gray-800 line-clamp-2">{{ item.name }}</h1>
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    <span 
                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                        :class="item.item_type_badge"
                                    >
                                        {{ formatItemType(item.item_type) }}
                                    </span>
                                    <span 
                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                        :class="item.status_badge"
                                    >
                                        {{ item.status_icon }} {{ formatStatus(item.status) }}
                                    </span>
                                    <!-- TAMBAHKAN KATEGORI DI SINI -->
                                    <span 
                                        v-if="item.item_category && item.item_category !== 'Uncategorized'"
                                        class="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                                    >
                                        📂 {{ item.item_category }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2">
                            <BaseButton
                                v-if="canEditItem"
                                @click="openEditModal"
                                variant="primary"
                                size="sm"
                                class="!px-3 !py-2 text-xs"
                            >
                                <template #icon>✏️</template>
                                <span class="hidden xs:inline">Edit Item</span>
                            </BaseButton>
                            <div v-else class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">
                                🔒 Read Only
                            </div>
                        </div>
                    </div>

                    <!-- Progress Stats -->
                    <div class="relative grid grid-cols-2 md:grid-cols-4 gap-2 mt-3 md:mt-4">
                        <div class="text-center p-3 md:p-4 bg-white/80 backdrop-blur-sm rounded-lg md:rounded-xl border border-blue-200/50 shadow-sm">
                            <div class="text-xl md:text-2xl mb-2">💰</div>
                            <div class="text-base md:text-xl font-bold text-blue-700">{{ item.formatted_planned_amount }}</div>
                            <div class="text-xs md:text-sm text-gray-600">Rencana Biaya</div>
                        </div>
                        <div class="text-center p-3 md:p-4 bg-white/80 backdrop-blur-sm rounded-lg md:rounded-xl border border-orange-200/50 shadow-sm">
                            <div class="text-xl md:text-2xl mb-2">💸</div>
                            <div class="text-base md:text-xl font-bold text-orange-700">{{ item.formatted_actual_spent }}</div>
                            <div class="text-xs md:text-sm text-gray-600">Realisasi</div>
                        </div>
                        <div class="text-center p-3 md:p-4 bg-white/80 backdrop-blur-sm rounded-lg md:rounded-xl border border-green-200/50 shadow-sm">
                            <div class="text-xl md:text-2xl mb-2">📊</div>
                            <div class="text-base md:text-xl font-bold" :class="item.remaining_amount >= 0 ? 'text-green-700' : 'text-red-700'">
                                {{ item.formatted_remaining_amount }}
                            </div>
                            <div class="text-xs md:text-sm text-gray-600">Sisa Anggaran</div>
                        </div>
                        <!-- TAMBAHKAN PROGRESS PERCENTAGE -->
                        <div class="text-center p-3 md:p-4 bg-white/80 backdrop-blur-sm rounded-lg md:rounded-xl border border-purple-200/50 shadow-sm">
                            <div class="text-xl md:text-2xl mb-2">📈</div>
                            <div class="text-base md:text-xl font-bold text-purple-700">{{ item.progress_percentage.toFixed(1) }}%</div>
                            <div class="text-xs md:text-sm text-gray-600">Progress</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 md:gap-4">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-3 md:space-y-4">
                        
                        <!-- Quantity dan Harga Satuan untuk Goods dan Material -->
                        <div v-if="isItemGoodsOrMaterial && item.details" 
                             class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl md:rounded-2xl p-4 md:p-6 border border-indigo-100">
                            <h3 class="font-semibold text-gray-800 text-base md:text-lg mb-3 md:mb-4 flex items-center gap-2">
                                <span>📊</span>
                                Informasi Kuantitas & Harga
                            </h3>
                            <div class="grid grid-cols-2 gap-3 md:gap-4">
                                <!-- Quantity -->
                                <div class="space-y-1">
                                    <span class="text-xs md:text-sm text-gray-600 flex items-center gap-2">
                                        <span class="text-base md:text-lg">📦</span>
                                        Quantity
                                    </span>
                                    <p class="font-bold text-gray-800 text-base md:text-lg">
                                        {{ item.details.quantity || 0 }}
                                        <span class="text-xs font-normal text-gray-500">unit</span>
                                    </p>
                                </div>

                                <!-- Harga Satuan -->
                                <div class="space-y-1">
                                    <span class="text-xs md:text-sm text-gray-600 flex items-center gap-2">
                                        <span class="text-base md:text-lg">💰</span>
                                        Harga Satuan
                                    </span>
                                    <p class="font-bold text-green-600 text-base md:text-lg">
                                        {{ formatCurrency(item.details.unit_price || 0) }}
                                    </p>
                                </div>

                                <!-- Perhitungan Total -->
                                <div class="col-span-2 space-y-1">
                                    <span class="text-xs md:text-sm text-gray-600 flex items-center gap-2">
                                        <span class="text-base md:text-lg">🧮</span>
                                        Perhitungan Total
                                    </span>
                                    <div class="bg-white/70 rounded-lg md:rounded-xl p-2 md:p-3 border border-gray-200">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs md:text-sm text-gray-600">
                                                {{ item.details.quantity || 0 }} × {{ formatCurrency(item.details.unit_price || 0) }}
                                            </span>
                                            <span class="font-bold text-blue-600 text-base md:text-lg">
                                                = {{ formatCurrency(item.planned_amount) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Total rencana biaya dihitung otomatis dari quantity × harga satuan
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-xl md:rounded-2xl p-3 md:p-4 border border-white/20 shadow-lg">
                            <h2 class="font-semibold text-lg md:text-xl text-gray-800 mb-3 md:mb-4 flex items-center gap-2">
                                <span>📈</span>
                                Progress Keuangan
                            </h2>
                            <div class="space-y-3 md:space-y-4">
                                <div class="flex justify-between text-xs md:text-sm">
                                    <span class="font-medium text-gray-700">{{ item.progress_percentage.toFixed(1) }}%</span>
                                    <span class="text-gray-600">{{ item.formatted_actual_spent }} / {{ item.formatted_planned_amount }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3 md:h-4">
                                    <div 
                                        class="h-3 md:h-4 rounded-full transition-all duration-1000 bg-gradient-to-r from-pink-400 to-rose-500 shadow-lg"
                                        :style="{ width: Math.min(item.progress_percentage, 100) + '%' }"
                                    ></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span>Sisa: {{ item.formatted_remaining_amount }}</span>
                                    <span v-if="item.remaining_amount < 0" class="text-red-500 font-semibold">
                                        ⚠️ Melebihi budget
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="item.description" class="bg-white/80 backdrop-blur-sm rounded-xl md:rounded-2xl p-3 md:p-4 border border-white/20 shadow-lg">
                            <h2 class="font-semibold text-lg md:text-xl text-gray-800 mb-3 md:mb-4 flex items-center gap-2">
                                <span>📄</span>
                                Deskripsi
                            </h2>
                            <p class="text-gray-700 leading-relaxed text-sm md:text-base">{{ item.description }}</p>
                        </div>

                        <!-- Purchase Info untuk Barang -->
                        <div v-if="item.item_type === 'goods' && item.details && hasPurchaseDetails" 
                             class="bg-white/80 backdrop-blur-sm rounded-xl md:rounded-2xl p-3 md:p-4 border border-white/20 shadow-lg">
                            <h2 class="font-semibold text-lg md:text-xl text-gray-800 mb-3 md:mb-4 flex items-center gap-2">
                                <span>🛍️</span>
                                Informasi Pembelian
                            </h2>
                            
                            <div class="space-y-3 md:space-y-4">
                                <!-- Purchase Type -->
                                <div v-if="item.details.purchase_type" class="flex items-center justify-between p-3 md:p-4 bg-gradient-to-r from-pink-50 to-rose-50 rounded-lg md:rounded-xl border border-pink-200">
                                    <span class="text-xs md:text-sm text-gray-600 font-medium">Cara Pembelian:</span>
                                    <span class="font-medium text-gray-800 flex items-center gap-2 text-sm">
                                        <span v-if="item.details.purchase_type === 'online'" class="text-base md:text-lg">🛒</span>
                                        <span v-else class="text-base md:text-lg">🏪</span>
                                        {{ item.details.purchase_type === 'online' ? 'Beli Online' : 'Beli di Toko' }}
                                    </span>
                                </div>

                                <!-- Online Purchase Details -->
                                <div v-if="item.details.purchase_type === 'online'" class="space-y-3 md:space-y-4">
                                    <div v-if="item.details.ecommerce_platform" class="flex items-center justify-between p-3 md:p-4 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg md:rounded-xl border border-blue-200">
                                        <span class="text-xs md:text-sm text-gray-600 font-medium">Platform:</span>
                                        <span class="font-medium flex items-center gap-2 text-sm" :class="ecommercePlatforms[item.details.ecommerce_platform]?.color">
                                            <span class="text-base md:text-lg">{{ ecommercePlatforms[item.details.ecommerce_platform]?.icon }}</span>
                                            {{ ecommercePlatforms[item.details.ecommerce_platform]?.name }}
                                        </span>
                                    </div>
                                    
                                    <div v-if="item.details.online_link" class="p-3 md:p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg md:rounded-xl border border-green-200">
                                        <div class="flex items-center justify-between mb-2 md:mb-3">
                                            <span class="text-xs md:text-sm text-gray-600 font-medium">Link Produk:</span>
                                            <BaseButton
                                                @click="openLink(item.details.online_link)"
                                                variant="primary"
                                                size="sm"
                                                class="!px-3 !py-2 text-xs"
                                            >
                                                <template #icon>🔗</template>
                                                <span class="hidden xs:inline">Buka Link</span>
                                            </BaseButton>
                                        </div>
                                        <p class="text-xs text-gray-500 break-all bg-white/50 p-2 rounded-lg">{{ item.details.online_link }}</p>
                                    </div>
                                </div>

                                <!-- Store Purchase Details -->
                                <div v-if="item.details.purchase_type === 'store'" class="space-y-3 md:space-y-4">
                                    <div v-if="item.details.store_maps" class="p-3 md:p-4 bg-gradient-to-r from-purple-50 to-violet-50 rounded-lg md:rounded-xl border border-purple-200">
                                        <div class="flex items-center justify-between mb-2 md:mb-3">
                                            <span class="text-xs md:text-sm text-gray-600 font-medium">Lokasi Toko:</span>
                                            <BaseButton
                                                @click="openLink(item.details.store_maps)"
                                                variant="primary"
                                                size="sm"
                                                class="!px-3 !py-2 text-xs"
                                            >
                                                <template #icon>🗺️</template>
                                                <span class="hidden xs:inline">Buka Maps</span>
                                            </BaseButton>
                                        </div>
                                        <p class="text-xs text-gray-500 break-all bg-white/50 p-2 rounded-lg">{{ item.details.store_maps }}</p>
                                    </div>
                                    
                                    <div v-if="item.details.store_address" class="p-3 md:p-4 bg-gradient-to-r from-orange-50 to-amber-50 rounded-lg md:rounded-xl border border-orange-200">
                                        <span class="text-xs md:text-sm text-gray-600 font-medium block mb-2 md:mb-3">Alamat Toko:</span>
                                        <p class="text-xs md:text-sm text-gray-700 bg-white/50 p-2 md:p-3 rounded-lg">{{ item.details.store_address }}</p>
                                    </div>
                                </div>

                                <!-- Common Goods Details -->
                                <div class="grid grid-cols-2 gap-2" v-if="hasGoodsDetails">
                                    <div v-for="field in ['ukuran', 'warna', 'material', 'merk']" :key="field">
                                        <div v-if="item.details[field]" class="p-2 md:p-3 bg-white rounded-lg md:rounded-xl border border-gray-200 text-center hover:shadow-md transition-shadow">
                                            <span class="text-xs text-gray-500 block capitalize mb-2">{{ field }}</span>
                                            <span class="font-medium text-gray-800 text-xs md:text-sm">{{ item.details[field] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Other Details untuk non-goods -->
                        <div v-if="item.details && Object.keys(item.details).length > 0 && item.item_type !== 'goods'" 
                             class="bg-white/80 backdrop-blur-sm rounded-xl md:rounded-2xl p-4 md:p-6 border border-white/20 shadow-lg">
                            <h2 class="font-semibold text-lg md:text-xl text-gray-800 mb-3 md:mb-4 flex items-center gap-2">
                                <span>🔧</span>
                                Detail Tambahan
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                                <div 
                                    v-for="(value, key) in item.details" 
                                    :key="key" 
                                    class="p-3 md:p-4 bg-gradient-to-r from-gray-50 to-blue-50 rounded-lg md:rounded-xl border border-gray-200 hover:shadow-md transition-shadow"
                                >
                                    <span class="text-xs text-gray-500 block capitalize mb-2">{{ key.replace('_', ' ') }}</span>
                                    <span class="font-medium text-gray-800 text-xs md:text-sm">{{ value }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- ✅ CHECKLIST MANAGER - Hanya untuk item_type selain goods dan material -->
                        <ChecklistManager
                            v-if="!isItemGoodsOrMaterial"
                            :project-item="item"
                            :checklists="item.checklists || []"
                            :item-name="item.name"
                            @checklist-updated="refreshData"
                        />

                        <!-- Payment Manager -->
                        <PaymentManager
                            :project-item="item"
                            :payments="item.payments || []"
                            :item-name="item.name"
                            @payment-updated="loadPayments"
                        />

                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-3 md:space-y-4">
                        
                        <!-- Project Info -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-xl md:rounded-2xl p-3 md:p-4 border border-white/20 shadow-lg">
                            <h3 class="font-semibold text-base md:text-lg text-gray-800 mb-3 md:mb-4 flex items-center gap-2">
                                <span>💼</span>
                                Project Info
                            </h3>
                            <div class="space-y-3 md:space-y-4">
                                <div class="flex items-center justify-between p-2 md:p-3 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg md:rounded-xl">
                                    <span class="text-xs md:text-sm text-gray-600">Project</span>
                                    <p class="font-medium text-gray-800 text-xs md:text-sm">{{ project.name }}</p>
                                </div>
                                <div class="flex items-center justify-between p-2 md:p-3 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg md:rounded-xl">
                                    <span class="text-xs md:text-sm text-gray-600">Kategori</span>
                                    <p class="font-medium text-gray-800 text-xs md:text-sm">{{ project.category.name }}</p>
                                </div>
                                <div class="flex items-center justify-between p-2 md:p-3 bg-gradient-to-r from-purple-50 to-violet-50 rounded-lg md:rounded-xl">
                                    <span class="text-xs md:text-sm text-gray-600">Status Project</span>
                                    <p class="font-medium text-gray-800 text-xs md:text-sm capitalize">{{ formatProjectStatus(project.status) }}</p>
                                </div>
                                <div class="flex items-center justify-between p-2 md:p-3 bg-gradient-to-r from-orange-50 to-amber-50 rounded-lg md:rounded-xl">
                                    <span class="text-xs md:text-sm text-gray-600">Target Budget</span>
                                    <p class="font-bold text-orange-700 text-xs md:text-sm">{{ formatCurrency(project.target_total_amount) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-xl md:rounded-2xl p-3 md:p-4 border border-white/20 shadow-lg">
                            <h3 class="font-semibold text-base md:text-lg text-gray-800 mb-3 md:mb-4 flex items-center gap-2">
                                <span>⚡</span>
                                Quick Actions
                            </h3>
                            <div class="space-y-2 md:space-y-3">
                                <BaseButton
                                    v-if="canEditItem"
                                    @click="openEditModal"
                                    variant="primary"
                                    class="w-full justify-center !px-3 !py-2 text-xs md:text-sm"
                                >
                                    <template #icon>✏️</template>
                                    <span class="hidden xs:inline">Edit Item</span>
                                </BaseButton>
                                <div v-else class="text-center p-2 md:p-3 bg-gray-50 rounded-lg md:rounded-xl">
                                    <p class="text-xs text-gray-600">Item sudah selesai, tidak dapat diedit</p>
                                </div>
                            </div>
                        </div>

                        <!-- Status Timeline -->
                        <div class="bg-white/80 backdrop-blur-sm rounded-xl md:rounded-2xl p-3 md:p-4 border border-white/20 shadow-lg">
                            <h3 class="font-semibold text-base md:text-lg text-gray-800 mb-3 md:mb-4 flex items-center gap-2">
                                <span>🕒</span>
                                Timeline
                            </h3>
                            <div class="space-y-3 md:space-y-4">
                                <div class="flex items-center gap-2 md:gap-3">
                                    <div class="w-6 h-6 md:w-8 md:h-8 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-white text-xs">✓</span>
                                    </div>
                                    <div>
                                        <p class="text-xs md:text-sm font-medium text-gray-800">Dibuat</p>
                                        <p class="text-xs text-gray-500">{{ item.created_at }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 md:gap-3">
                                    <div class="w-6 h-6 md:w-8 md:h-8 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-white text-xs">↻</span>
                                    </div>
                                    <div>
                                        <p class="text-xs md:text-sm font-medium text-gray-800">Terakhir Diupdate</p>
                                        <p class="text-xs text-gray-500">{{ item.updated_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Edit Modal -->
                <BaseModal
                    v-model:show="showEditModal"
                    :title="'Edit Item Project'"
                    :description="'Perbarui informasi item project ' + item.name"
                    :icon="'✏️'"
                    :confirm-text="'Perbarui'"
                    :confirm-loading="form.processing"
                    @confirm="updateItem"
                    @close="closeModal"
                    size="xl"
                >
                    <div class="space-y-4 md:space-y-6 max-h-[50vh] md:max-h-[60vh] overflow-y-auto pr-2">
                        <!-- Item Type -->
                        <SelectInput
                            v-model="form.item_type"
                            label="Jenis Item"
                            placeholder="Pilih jenis item"
                            :options="itemTypeOptions"
                            :error="form.errors.item_type"
                            icon="🏷️"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Item Category -->
                        <div class="space-y-3 md:space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                    <span class="text-base md:text-lg">📂</span>
                                    Kategori Item
                                </label>
                                
                                <!-- Toggle antara input baru dan pilih yang ada -->
                                <div v-if="hasExistingCategories" class="flex gap-2">
                                    <BaseButton
                                        @click="showCategoryInput = false"
                                        variant="secondary"
                                        size="xs"
                                        :class="!shouldShowCategoryInput ? 'ring-2 ring-pink-400' : ''"
                                    >
                                        <template #icon>📋</template>
                                        <span class="hidden xs:inline">Pilih</span>
                                    </BaseButton>
                                    <BaseButton
                                        @click="showCategoryInput = true"
                                        variant="secondary"
                                        size="xs"
                                        :class="shouldShowCategoryInput ? 'ring-2 ring-pink-400' : ''"
                                    >
                                        <template #icon>➕</template>
                                        <span class="hidden xs:inline">Baru</span>
                                    </BaseButton>
                                </div>
                            </div>

                            <!-- Select Input untuk memilih kategori yang ada -->
                            <div v-if="!shouldShowCategoryInput && hasExistingCategories">
                                <SelectInput
                                    v-model="form.item_category"
                                    label="Pilih Kategori"
                                    placeholder="Pilih kategori yang sudah ada"
                                    :options="categoryOptions"
                                    :error="form.errors.item_category"
                                    icon="📂"
                                    :disabled="form.processing"
                                />
                                <p class="text-xs text-gray-500 mt-1">
                                    Pilih dari {{ availableCategories.length }} kategori yang sudah ada
                                </p>
                            </div>

                            <!-- Text Input untuk kategori baru -->
                            <div v-else>
                                <TextInput
                                    v-model="form.item_category"
                                    label="Kategori Item"
                                    placeholder="Contoh: Perlengkapan Kamar, Dekorasi, Katering, dll."
                                    :error="form.errors.item_category"
                                    icon="📂"
                                    :disabled="form.processing"
                                />
                                <p class="text-xs text-gray-500 mt-1" v-if="hasExistingCategories">
                                    Atau <button type="button" @click="showCategoryInput = false" class="text-pink-500 hover:text-pink-700 underline">
                                    pilih dari kategori yang sudah ada
                                    </button>
                                </p>
                            </div>
                        </div>

                        <!-- Name -->
                        <TextInput
                            v-model="form.name"
                            label="Nama Item"
                            placeholder="Contoh: Cincin Nikah, Vendor Katering, IMB, Semen Portland"
                            :error="form.errors.name"
                            icon="📝"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Description -->
                        <TextAreaInput
                            v-model="form.description"
                            label="Deskripsi"
                            placeholder="Deskripsi detail tentang item ini..."
                            :error="form.errors.description"
                            icon="📄"
                            :disabled="form.processing"
                        />

                        <!-- Input untuk Goods dan Material -->
                        <div v-if="isGoodsOrMaterial" class="space-y-3 md:space-y-4">
                            <!-- Qty dan Unit Price -->
                            <div class="grid grid-cols-2 gap-3 md:gap-4">
                                <!-- Quantity -->
                                <AccountInput
                                    label="Qty Barang"
                                    v-model="form.details.quantity"
                                    placeholder="Jumlah barang"
                                    icon="💰"
                                    required
                                    min="1"
                                    :disabled="form.processing"
                                    @input="updatePlannedAmount"
                                />

                                <!-- Harga Satuan -->
                                <AccountInput
                                    label="Rencana Biaya"
                                    v-model="form.details.unit_price"
                                    placeholder="Harga per unit"
                                    icon="💰"
                                    required
                                    account-type="account_number"
                                    min="0"
                                    :disabled="form.processing"
                                    @input="updatePlannedAmount"
                                />
                            </div>

                            <!-- Hasil Perhitungan -->
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl md:rounded-2xl p-3 md:p-4 border border-green-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Total Rencana Biaya:</p>
                                        <p class="text-xs text-gray-500">
                                            {{ form.details.quantity || 0 }} × {{ formatCurrency(form.details.unit_price || 0) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg md:text-xl font-bold text-green-600">
                                            {{ formatCurrency(calculatedPlannedAmount) }}
                                        </p>
                                        <p class="text-xs text-gray-500">Terhitung otomatis</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden planned_amount -->
                            <input type="hidden" v-model="form.planned_amount" />
                        </div>

                        <!-- Input Planned Amount untuk jenis item lainnya -->
                        <div v-else class="grid grid-cols-1 gap-3 md:gap-4">
                            <!-- Planned Amount -->
                            <AccountInput
                                v-model="form.planned_amount"
                                label="Rencana Biaya"
                                placeholder="Biaya yang direncanakan"
                                :error="form.errors.planned_amount"
                                icon="💰"
                                account-type="account_number"
                                :disabled="form.processing"
                            />
                        </div>

                        <!-- Hidden Actual Spent (sesuai database untuk edit) -->
                        <input type="hidden" v-model="form.actual_spent" />

                        <!-- Status -->
                        <SelectInput
                            v-model="form.status"
                            label="Status Item"
                            placeholder="Pilih status item"
                            :options="statusOptions"
                            :error="form.errors.status"
                            icon="🟢"
                            required
                            :disabled="form.processing"
                        />

                        <!-- Dynamic Detail Fields -->
                        <div v-if="detailFields.length > 0" class="space-y-3 md:space-y-4">
                            <div class="bg-gradient-to-r from-pink-50 to-rose-50 rounded-xl md:rounded-2xl p-3 md:p-4 border border-pink-100">
                                <h4 class="font-semibold text-gray-800 flex items-center gap-2 text-base md:text-lg mb-3 md:mb-4">
                                    <span>✨</span>
                                    Detail Tambahan
                                </h4>
                                
                                <div class="space-y-3 md:space-y-4">
                                    <div v-for="field in detailFields" :key="field.key" class="space-y-2">
                                        <!-- Radio Button untuk Purchase Type -->
                                        <div v-if="field.type === 'radio'" class="space-y-2 md:space-y-3">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                <span class="text-base md:text-lg">{{ field.icon }}</span>
                                                {{ field.label }}
                                            </label>
                                            <div class="flex gap-2 md:gap-4">
                                                <label 
                                                    v-for="option in field.options" 
                                                    :key="option.value"
                                                    class="flex items-center space-x-2 cursor-pointer p-2 md:p-3 rounded-lg md:rounded-xl border-2 transition-all"
                                                    :class="form.details[field.key] === option.value 
                                                        ? 'border-pink-400 bg-pink-50 shadow-sm' 
                                                        : 'border-gray-200 hover:border-pink-200'"
                                                >
                                                    <input
                                                        type="radio"
                                                        :value="option.value"
                                                        v-model="form.details[field.key]"
                                                        class="text-pink-500 focus:ring-pink-400"
                                                    >
                                                    <span class="text-xs md:text-sm font-medium text-gray-700">
                                                        {{ option.label }}
                                                    </span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Select Input -->
                                        <div v-else-if="field.type === 'select'" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                <span class="text-base md:text-lg">{{ field.icon }}</span>
                                                {{ field.label }}
                                            </label>
                                            <select
                                                v-model="form.details[field.key]"
                                                class="w-full px-3 md:px-4 py-2 md:py-3 rounded-xl md:rounded-2xl border border-gray-300 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all bg-white text-sm"
                                            >
                                                <option value="">Pilih {{ field.label.toLowerCase() }}</option>
                                                <option v-for="option in field.options" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Text Input -->
                                        <div v-else-if="field.type === 'text' || field.type === 'url'" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                <span class="text-base md:text-lg">{{ field.icon }}</span>
                                                {{ field.label }}
                                            </label>
                                            <input
                                                :type="field.type"
                                                v-model="form.details[field.key]"
                                                :placeholder="field.placeholder || `Masukkan ${field.label.toLowerCase()}`"
                                                class="w-full px-3 md:px-4 py-2 md:py-3 rounded-xl md:rounded-2xl border border-gray-300 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all text-sm"
                                            >
                                        </div>

                                        <!-- Textarea Input -->
                                        <div v-else-if="field.type === 'textarea'" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                <span class="text-base md:text-lg">{{ field.icon }}</span>
                                                {{ field.label }}
                                            </label>
                                            <textarea
                                                v-model="form.details[field.key]"
                                                :placeholder="field.placeholder || `Masukkan ${field.label.toLowerCase()}`"
                                                rows="3"
                                                class="w-full px-3 md:px-4 py-2 md:py-3 rounded-xl md:rounded-2xl border border-gray-300 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all resize-none text-sm"
                                            ></textarea>
                                        </div>

                                        <!-- Number Input -->
                                        <div v-else-if="field.type === 'number'" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                <span class="text-base md:text-lg">{{ field.icon }}</span>
                                                {{ field.label }}
                                            </label>
                                            <input
                                                type="number"
                                                v-model="form.details[field.key]"
                                                :placeholder="field.placeholder || `Masukkan ${field.label.toLowerCase()}`"
                                                class="w-full px-3 md:px-4 py-2 md:py-3 rounded-xl md:rounded-2xl border border-gray-300 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all text-sm"
                                            >
                                        </div>

                                        <!-- Date Input -->
                                        <div v-else-if="field.type === 'date'" class="space-y-2">
                                            <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
                                                <span class="text-base md:text-lg">{{ field.icon }}</span>
                                                {{ field.label }}
                                            </label>
                                            <input
                                                type="date"
                                                v-model="form.details[field.key]"
                                                class="w-full px-3 md:px-4 py-2 md:py-3 rounded-xl md:rounded-2xl border border-gray-300 focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all text-sm"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="p-3 md:p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl md:rounded-2xl border border-blue-200">
                            <p class="text-xs md:text-sm text-gray-700 flex items-start gap-2">
                                <span class="text-base md:text-lg mt-0.5">💡</span>
                                <span>
                                    <strong class="block">Jenis Item:</strong>
                                    • <strong>Barang 🛍️</strong>: Physical goods yang dibeli (hitung otomatis dari quantity × harga satuan)<br>
                                    • <strong>Jasa 👨‍💼</strong>: Services dari vendor/penyedia jasa<br>
                                    • <strong>Dokumen 📄</strong>: Legal documents dan surat-surat<br>
                                    • <strong>Tugas ✅</strong>: Tasks dan pekerjaan yang perlu diselesaikan<br>
                                    • <strong>Material 🏗️</strong>: Bahan bangunan dan material konstruksi (hitung otomatis dari quantity × harga satuan)
                                </span>
                            </p>
                        </div>

                        <!-- Error Summary -->
                        <div v-if="form.hasErrors" class="p-2 bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-xl md:rounded-2xl">
                            <p class="text-xs md:text-sm text-red-600 flex items-center gap-2">
                                <span>⚠️</span>
                                Terdapat kesalahan dalam pengisian form. Silakan periksa kembali input Anda.
                            </p>
                        </div>
                    </div>
                </BaseModal>

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed, watch, onMounted  } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import BaseButton from '@/Components/Base/BaseButton.vue'
import BaseModal from '@/Components/Base/BaseModal.vue'
import ChecklistManager from '@/Components/Project/ChecklistManager.vue'
import SelectInput from '@/Components/Form/SelectInput.vue'
import TextInput from '@/Components/Form/TextInput.vue'
import TextAreaInput from '@/Components/Form/TextAreaInput.vue'
import AccountInput from '@/Components/Form/AccountInput.vue'
import PaymentManager from '@/Components/Project/PaymentManager.vue'
import axios from 'axios'; // TAMBAHKAN INI JIKA BELUM ADA

const props = defineProps({
    item: Object,
    project: Object,
    canEdit: Boolean
})

// State
const showEditModal = ref(false)
const qty = ref(1)
const unitPrice = ref(0)

const showCategoryInput = ref(false) // TAMBAHKAN INI
const availableCategories = ref([]) // TAMBAHKAN INI



// Initialize data
onMounted(() => {
    loadCategories(); // LOAD CATEGORIES SAAT KOMPONEN MOUNT
});
// Forms
const form = useForm({
    item_type: props.item.item_type,
     item_category: props.item.item_category || '',
    name: props.item.name,
    description: props.item.description || '',
    planned_amount: props.item.planned_amount,
    actual_spent: props.item.actual_spent,
    status: props.item.status,
    details: props.item.details || {},
});

// Computed untuk kategori
const hasExistingCategories = computed(() => {
    return availableCategories.value && availableCategories.value.length > 0;
});

const shouldShowCategoryInput = computed(() => {
    return showCategoryInput.value || !hasExistingCategories.value;
});

const categoryOptions = computed(() => {
    if (!availableCategories.value || !Array.isArray(availableCategories.value)) {
        return [];
    }
    return availableCategories.value.map(cat => ({ value: cat, label: cat }));
});

// Load categories dari API
const loadCategories = async () => {
    try {
        console.log('Loading categories for project:', props.project.id);
        const response = await axios.get(route('projects.items.api.categories', { 
            project: props.project.id 
        }));
        
        console.log('Categories API response:', response.data);
        
        // Handle response yang berbeda-beda
        if (response.data && response.data.success) {
            availableCategories.value = response.data.categories || [];
        } else if (Array.isArray(response.data)) {
            availableCategories.value = response.data;
        } else if (response.data && Array.isArray(response.data.categories)) {
            availableCategories.value = response.data.categories;
        } else {
            console.warn('Unexpected categories response format:', response.data);
            availableCategories.value = [];
        }
        
        console.log('Final available categories:', availableCategories.value);
        
    } catch (error) {
        console.error('Error loading categories:', error);
        availableCategories.value = [];
    }
};

// Computed untuk form
const isGoodsOrMaterial = computed(() => {
    return ['goods', 'material'].includes(form.item_type);
});

const calculatedPlannedAmount = computed(() => {
    if (!isGoodsOrMaterial.value) return form.planned_amount;
    
    const quantity = parseFloat(form.details.quantity) || 0;
    const unitPrice = parseFloat(form.details.unit_price) || 0;
    return quantity * unitPrice;
});

// Computed untuk UI
const canEditItem = computed(() => {
    return props.canEdit && props.item.status !== 'complete';
});

const isItemGoodsOrMaterial = computed(() => {
    return ['goods', 'material'].includes(props.item.item_type);
});

const hasGoodsDetails = computed(() => {
    if (!props.item.details) return false
    const goodsFields = ['ukuran', 'warna', 'material', 'merk']
    return goodsFields.some(field => props.item.details[field])
})

const hasPurchaseDetails = computed(() => {
    if (!props.item.details) return false;
    
    const details = { ...props.item.details };
    // Hapus quantity dan unit_price dari pengecekan
    delete details.quantity;
    delete details.unit_price;
    
    return Object.keys(details).length > 0;
});

// Item type options
const itemTypeOptions = [
    { value: 'goods', label: '🛍️ Barang' },
    { value: 'service', label: '👨‍💼 Jasa' },
    { value: 'document', label: '📄 Dokumen' },
    { value: 'task', label: '✅ Tugas' },
    { value: 'material', label: '🏗️ Material' },
];

// Status options
const statusOptions = [
    { value: 'needed', label: '⏳ Diperlukan' },
    { value: 'in_progress', label: '🚧 Dalam Proses' },
    { value: 'ready', label: '📦 Siap' },
    { value: 'complete', label: '✅ Selesai' },
    { value: 'cancelled', label: '❌ Dibatalkan' },
];

// Purchase type options untuk barang
const purchaseTypeOptions = [
    { value: 'online', label: '🛒 Beli Online' },
    { value: 'store', label: '🏪 Beli di Toko' },
];

// E-commerce platforms
const ecommercePlatforms = {
    'shopee': { name: 'Shopee', icon: '🟠', color: 'text-orange-500' },
    'tokopedia': { name: 'Tokopedia', icon: '🟢', color: 'text-green-500' },
    'lazada': { name: 'Lazada', icon: '🔵', color: 'text-blue-500' },
    'blibli': { name: 'Blibli', icon: '🔴', color: 'text-red-500' },
    'bukalapak': { name: 'Bukalapak', icon: '🟡', color: 'text-yellow-500' },
    'other': { name: 'Lainnya', icon: '🛒', color: 'text-gray-500' }
}

// Dynamic detail fields berdasarkan item type
const detailFields = computed(() => {
    const baseFields = {
        goods: [
            // Purchase Type Radio
            { 
                key: 'purchase_type', 
                label: 'Cara Pembelian', 
                type: 'radio', 
                icon: '🛒',
                options: purchaseTypeOptions
            },
            // Online fields
            ...(form.details?.purchase_type === 'online' ? [
                { 
                    key: 'ecommerce_platform', 
                    label: 'Platform Online', 
                    type: 'select', 
                    icon: '📱',
                    options: Object.entries(ecommercePlatforms).map(([value, data]) => ({
                        value,
                        label: `${data.icon} ${data.name}`
                    }))
                },
                { 
                    key: 'online_link', 
                    label: 'Link Produk', 
                    type: 'url', 
                    icon: '🔗',
                    placeholder: 'https://...'
                }
            ] : []),
            // Store fields
            ...(form.details?.purchase_type === 'store' ? [
                { 
                    key: 'store_maps', 
                    label: 'Link Google Maps', 
                    type: 'url', 
                    icon: '🗺️',
                    placeholder: 'https://maps.google.com/...'
                },
                { 
                    key: 'store_address', 
                    label: 'Alamat Toko', 
                    type: 'textarea', 
                    icon: '🏪',
                    placeholder: 'Alamat lengkap toko...'
                }
            ] : []),
            // Common goods fields
            { key: 'ukuran', label: 'Ukuran', type: 'text', icon: '📏' },
            { key: 'warna', label: 'Warna', type: 'text', icon: '🎨' },
            { key: 'material', label: 'Material', type: 'text', icon: '⚙️' },
            { key: 'merk', label: 'Merk', type: 'text', icon: '🏷️' },
        ],
        service: [
            { key: 'vendor_kontak', label: 'Kontak Vendor', type: 'text', icon: '📞' },
            { key: 'tanggal_kontrak', label: 'Tanggal Kontrak', type: 'date', icon: '📅' },
            { key: 'sisa_pembayaran', label: 'Sisa Pembayaran', type: 'number', icon: '💰' },
            { key: 'menu_pilihan', label: 'Menu Pilihan', type: 'text', icon: '🍽️' },
            { key: 'lokasi', label: 'Lokasi', type: 'text', icon: '📍' },
        ],
        document: [
            { key: 'tanggal_kadaluarsa', label: 'Tanggal Kadaluarsa', type: 'date', icon: '📅' },
            { key: 'lokasi_fisik', label: 'Lokasi Fisik', type: 'text', icon: '📁' },
            { key: 'status_legalitas', label: 'Status Legalitas', type: 'text', icon: '⚖️' },
            { key: 'nomor_dokumen', label: 'Nomor Dokumen', type: 'text', icon: '🔢' },
        ],
        task: [
            { key: 'pihak_pengurus', label: 'Pihak Pengurus', type: 'text', icon: '👤' },
            { key: 'biaya_administrasi', label: 'Biaya Administrasi', type: 'number', icon: '💰' },
            { key: 'progress_persentase', label: 'Progress (%)', type: 'number', icon: '📊' },
            { key: 'deadline', label: 'Deadline', type: 'date', icon: '⏰' },
        ],
        material: [
            { key: 'kuantitas_target', label: 'Kuantitas Target', type: 'number', icon: '📦' },
            { key: 'satuan', label: 'Satuan', type: 'text', icon: '⚖️' },
            { key: 'supplier_preferensi', label: 'Supplier Preferensi', type: 'text', icon: '🏪' },
            { key: 'spesifikasi', label: 'Spesifikasi', type: 'text', icon: '📋' },
        ]
    };
    return baseFields[form.item_type] || [];
});

// Watch untuk update planned_amount
watch([() => form.details.quantity, () => form.details.unit_price], () => {
    if (isGoodsOrMaterial.value) {
        form.planned_amount = calculatedPlannedAmount.value;
    }
});

// watch(() => form.item_type, (newType) => {
//     if (['goods', 'material'].includes(newType)) {
//         // Set default values untuk quantity dan unit_price jika belum ada
//         if (!form.details.quantity) form.details.quantity = 1;
//         if (!form.details.unit_price) form.details.unit_price = 0;
//         form.planned_amount = calculatedPlannedAmount.value;
//     } else {
//         form.planned_amount = form.planned_amount || 0;
//     }
// });

// Watch untuk reset details ketika item_type berubah
watch(() => form.item_type, (newType, oldType) => {
    if (newType !== oldType) {
        // Reset details yang tidak relevan dengan type baru
        if (newType !== 'goods') {
            const { purchase_type, ecommerce_platform, online_link, store_maps, store_address, ...otherDetails } = form.details;
            form.details = otherDetails;
        }
        
        // Reset planned amount calculation
        if (['goods', 'material'].includes(newType)) {
            if (!form.details.quantity) form.details.quantity = 1;
            if (!form.details.unit_price) form.details.unit_price = 0;
            form.planned_amount = calculatedPlannedAmount.value;
        }
    }
});

// Methods
const openEditModal = () => {
    // Reset form dengan data item saat ini
    form.item_type = props.item.item_type;
    form.item_category = props.item.item_category || '';
    form.name = props.item.name;
    form.description = props.item.description || '';
    form.planned_amount = props.item.planned_amount;
    form.actual_spent = props.item.actual_spent;
    form.status = props.item.status;
    form.details = props.item.details || {};

    // Untuk edit, default ke input text
    showCategoryInput.value = true;
    
    // Set qty dan unitPrice untuk goods dan material
    if (['goods', 'material'].includes(props.item.item_type)) {
        form.details.quantity = props.item.details?.quantity || 1;
        form.details.unit_price = props.item.details?.unit_price || 0;
    }
    
    showEditModal.value = true;
}

const updateItem = () => {
    form.put(route('projects.items.update', { 
        project: props.project.id, 
        item: props.item.id 
    }), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            form.reset();
            // Refresh halaman untuk menampilkan data terbaru
            router.reload();
        },
        onError: (errors) => {
            console.error('Update error:', errors);
        }
    });
}

const closeModal = () => {
    showEditModal.value = false;
    form.clearErrors();
    form.reset();
}

const refreshData = () => {
    router.reload({ only: ['item'] })
}

const openLink = (url) => {
    if (url) {
        window.open(url, '_blank')
    }
}

const updatePlannedAmount = () => {
    if (isGoodsOrMaterial.value) {
        form.planned_amount = calculatedPlannedAmount.value;
    }
};

// Format functions
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount)
}

const formatItemType = (type) => {
    const typeMap = {
        goods: 'Barang',
        service: 'Jasa',
        document: 'Dokumen',
        task: 'Tugas',
        material: 'Material'
    }
    return typeMap[type] || type
}

const formatStatus = (status) => {
    const statusMap = {
        needed: 'Diperlukan',
        in_progress: 'Dalam Proses',
        ready: 'Siap',
        complete: 'Selesai',
        cancelled: 'Dibatalkan'
    }
    return statusMap[status] || status
}

const formatProjectStatus = (status) => {
    const statusMap = {
        planning: 'Perencanaan',
        on_going: 'Berjalan',
        completed: 'Selesai',
        cancelled: 'Dibatalkan'
    }
    return statusMap[status] || status
}
</script>

<style scoped>
/* Utility classes untuk mobile */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Improve scrolling on mobile */
@media (max-width: 640px) {
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .overflow-x-auto::-webkit-scrollbar {
        display: none;
    }
}

/* Breakpoint untuk screen sangat kecil */
@media (max-width: 475px) {
    .xs\:inline {
        display: inline !important;
    }
    
    .xs\:hidden {
        display: none !important;
    }
    
    .xs\:col-span-2 {
        grid-column: span 2 / span 2;
    }
    
    .xs\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* Custom scrollbar untuk modal */
.max-h-\[50vh\]::-webkit-scrollbar,
.max-h-\[60vh\]::-webkit-scrollbar {
    width: 4px;
}

.max-h-\[50vh\]::-webkit-scrollbar-track,
.max-h-\[60vh\]::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}

.max-h-\[50vh\]::-webkit-scrollbar-thumb,
.max-h-\[60vh\]::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #f472b6, #60a5fa);
    border-radius: 8px;
}

.max-h-\[50vh\]::-webkit-scrollbar-thumb:hover,
.max-h-\[60vh\]::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #ec4899, #3b82f6);
}

/* Animasi untuk floating hearts */
@keyframes bounce-slow {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(15px) rotate(5deg); }
}
@keyframes bounce-slow2 {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-12px) rotate(-3deg); }
}
@keyframes bounce-slow3 {
    0%, 100% { transform: translateX(0) translateY(0); }
    33% { transform: translateX(10px) translateY(8px); }
    66% { transform: translateX(-5px) translateY(-10px); }
}
@keyframes bounce-slow4 {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(20px) scale(1.1); }
}

.animate-bounce-slow {
    animation: bounce-slow 8s infinite ease-in-out;
}
.animate-bounce-slow2 {
    animation: bounce-slow2 10s infinite ease-in-out;
    animation-delay: 1s;
}
.animate-bounce-slow3 {
    animation: bounce-slow3 12s infinite ease-in-out;
    animation-delay: 2s;
}
.animate-bounce-slow4 {
    animation: bounce-slow4 9s infinite ease-in-out;
    animation-delay: 0.5s;
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, transform, opacity;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}
</style>