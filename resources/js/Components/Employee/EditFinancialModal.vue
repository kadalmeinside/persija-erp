<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { PlusIcon, TrashIcon, BanknotesIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    employee: Object,
    masterKomponenGaji: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    gaji_pokok: 0,
    status_ptkp: 'TK/0',
    no_bpjs_ketenagakerjaan: '',
    no_bpjs_kesehatan: '',
    nama_bank: '',
    nomor_rekening: '',
    atas_nama_rekening: '',
    komponen: [],
});

watch(() => props.show, (newVal) => {
    if (newVal && props.employee) {
        form.gaji_pokok = props.employee.gaji_pokok || 0;
        form.status_ptkp = props.employee.status_ptkp || 'TK/0';
        form.no_bpjs_ketenagakerjaan = props.employee.no_bpjs_ketenagakerjaan || '';
        form.no_bpjs_kesehatan = props.employee.no_bpjs_kesehatan || '';
        
        if (props.employee.primary_bank) {
            form.nama_bank = props.employee.primary_bank.nama_bank || '';
            form.nomor_rekening = props.employee.primary_bank.nomor_rekening || '';
            form.atas_nama_rekening = props.employee.primary_bank.atas_nama_rekening || '';
        } else {
            form.nama_bank = '';
            form.nomor_rekening = '';
            form.atas_nama_rekening = '';
        }

        // Populate existing recurring salary components
        if (props.employee.komponen_gaji && props.employee.komponen_gaji.length > 0) {
            form.komponen = props.employee.komponen_gaji.map(item => ({
                id_komponen_gaji: item.id_komponen_gaji,
                nominal: item.nominal,
                keterangan: item.keterangan || ''
            }));
        } else {
            form.komponen = [];
        }

        form.clearErrors();
    }
});

const addKomponen = () => {
    if (props.masterKomponenGaji.length === 0) {
        Swal.fire('Info', 'Belum ada data master komponen gaji di sistem. Silakan atur di modul Payroll terlebih dahulu.', 'info');
        return;
    }
    form.komponen.push({
        id_komponen_gaji: props.masterKomponenGaji[0].id,
        nominal: 0,
        keterangan: 'Tunjangan Tetap'
    });
};

const removeKomponen = (index) => {
    form.komponen.splice(index, 1);
};

const submit = () => {
    form.post(route('admin.karyawan.komponen-gaji.sync', props.employee.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            emit('close');
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Data finansial, BPJS dan tunjangan rutin berhasil diperbarui',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
};

const close = () => {
    emit('close');
    form.reset();
};
</script>

<template>
    <Modal :show="show" maxWidth="3xl" @close="close">
        <form @submit.prevent="submit" class="p-6">
            <div class="flex items-center space-x-2 pb-4 border-b border-gray-200 dark:border-gray-700 mb-5">
                <BanknotesIcon class="w-6 h-6 text-emerald-600" />
                <div>
                    <h2 class="text-base md:text-lg font-bold text-gray-900 dark:text-gray-100">
                        Edit Kompensasi, Benefit & Rekening
                    </h2>
                    <p class="text-xs text-gray-500">{{ employee?.nama_lengkap }} (#{{ employee?.nomor_induk_karyawan }})</p>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Section 1: Gaji & Pajak -->
                <div>
                    <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3 flex items-center">
                        <BanknotesIcon class="w-4 h-4 mr-1.5 text-emerald-500" />
                        Gaji Pokok & Status Pajak
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="gaji_pokok" value="Gaji Pokok Bulanan (Rp)" />
                            <TextInput
                                id="gaji_pokok"
                                type="number"
                                class="mt-1 block w-full"
                                v-model="form.gaji_pokok"
                                required
                            />
                            <InputError class="mt-1" :message="form.errors.gaji_pokok" />
                        </div>

                        <div>
                            <InputLabel for="status_ptkp" value="Status PTKP" />
                            <select 
                                id="status_ptkp" 
                                v-model="form.status_ptkp" 
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-md shadow-2xs text-sm"
                            >
                                <option value="TK/0">TK/0 - Tidak Kawin / Tanpa Tanggungan</option>
                                <option value="TK/1">TK/1 - Tidak Kawin / 1 Tanggungan</option>
                                <option value="TK/2">TK/2 - Tidak Kawin / 2 Tanggungan</option>
                                <option value="TK/3">TK/3 - Tidak Kawin / 3 Tanggungan</option>
                                <option value="K/0">K/0 - Kawin / Tanpa Tanggungan</option>
                                <option value="K/1">K/1 - Kawin / 1 Tanggungan</option>
                                <option value="K/2">K/2 - Kawin / 2 Tanggungan</option>
                                <option value="K/3">K/3 - Kawin / 3 Tanggungan</option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.status_ptkp" />
                        </div>
                    </div>
                </div>

                <!-- Section 2: Jaminan Sosial & BPJS -->
                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3 flex items-center">
                        <ShieldCheckIcon class="w-4 h-4 mr-1.5 text-blue-500" />
                        Jaminan Sosial & Asuransi (BPJS)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="no_bpjs_ketenagakerjaan" value="No. BPJS Ketenagakerjaan" />
                            <TextInput
                                id="no_bpjs_ketenagakerjaan"
                                type="text"
                                class="mt-1 block w-full text-xs"
                                placeholder="Contoh: 00012345678"
                                v-model="form.no_bpjs_ketenagakerjaan"
                            />
                            <InputError class="mt-1" :message="form.errors.no_bpjs_ketenagakerjaan" />
                        </div>

                        <div>
                            <InputLabel for="no_bpjs_kesehatan" value="No. BPJS Kesehatan" />
                            <TextInput
                                id="no_bpjs_kesehatan"
                                type="text"
                                class="mt-1 block w-full text-xs"
                                placeholder="Contoh: 00098765432"
                                v-model="form.no_bpjs_kesehatan"
                            />
                            <InputError class="mt-1" :message="form.errors.no_bpjs_kesehatan" />
                        </div>
                    </div>
                </div>

                <!-- Section 3: Tunjangan Rutin Melekat -->
                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Tunjangan Rutin / Paket Kompensasi
                            </h3>
                            <p class="text-[11px] text-gray-400">Komponen tunjangan tetap yang otomatis dimasukkan dalam kalkulasi payroll bulanan.</p>
                        </div>
                        <button 
                            type="button" 
                            @click="addKomponen" 
                            class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold flex items-center"
                        >
                            <PlusIcon class="w-3.5 h-3.5 mr-1" /> + Tunjangan
                        </button>
                    </div>

                    <div v-if="form.komponen.length > 0" class="space-y-2.5 max-h-48 overflow-y-auto pr-1">
                        <div 
                            v-for="(item, idx) in form.komponen" 
                            :key="idx"
                            class="p-3 bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-xl grid grid-cols-1 sm:grid-cols-12 gap-2 items-center text-xs"
                        >
                            <div class="sm:col-span-5">
                                <label class="text-[10px] text-gray-400 font-medium">Komponen Gaji</label>
                                <select 
                                    v-model="item.id_komponen_gaji"
                                    class="mt-0.5 block w-full text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-2xs"
                                >
                                    <option v-for="master in masterKomponenGaji" :key="master.id" :value="master.id">
                                        {{ master.nama_komponen }} ({{ master.tipe }})
                                    </option>
                                </select>
                            </div>
                            <div class="sm:col-span-4">
                                <label class="text-[10px] text-gray-400 font-medium">Nominal (Rp)</label>
                                <input 
                                    type="number" 
                                    v-model="item.nominal"
                                    class="mt-0.5 block w-full text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-2xs"
                                />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-[10px] text-gray-400 font-medium">Keterangan</label>
                                <input 
                                    type="text" 
                                    v-model="item.keterangan" 
                                    placeholder="Rutin"
                                    class="mt-0.5 block w-full text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-md shadow-2xs"
                                />
                            </div>
                            <div class="sm:col-span-1 text-center pt-3">
                                <button 
                                    type="button" 
                                    @click="removeKomponen(idx)" 
                                    class="text-gray-400 hover:text-red-500 p-1"
                                    title="Hapus komponen"
                                >
                                    <TrashIcon class="w-4 h-4 mx-auto" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400 italic py-2 text-center bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                        Belum ada tunjangan rutin tambahan. Klik tombol "+ Tunjangan" untuk menambahkan.
                    </div>
                </div>

                <!-- Section 4: Rekening Bank -->
                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">
                        Informasi Rekening Bank Pencairan Gaji
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <InputLabel for="nama_bank" value="Nama Bank" />
                            <TextInput
                                id="nama_bank"
                                type="text"
                                class="mt-1 block w-full text-xs"
                                v-model="form.nama_bank"
                                placeholder="BCA, Mandiri, BNI, dll"
                            />
                        </div>

                        <div>
                            <InputLabel for="nomor_rekening" value="Nomor Rekening" />
                            <TextInput
                                id="nomor_rekening"
                                type="text"
                                class="mt-1 block w-full text-xs"
                                v-model="form.nomor_rekening"
                                placeholder="Nomor rekening"
                            />
                        </div>

                        <div>
                            <InputLabel for="atas_nama_rekening" value="Atas Nama Rekening" />
                            <TextInput
                                id="atas_nama_rekening"
                                type="text"
                                class="mt-1 block w-full text-xs"
                                v-model="form.atas_nama_rekening"
                                placeholder="Nama pemilik rekening"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <SecondaryButton @click="close" type="button">Batal</SecondaryButton>
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
