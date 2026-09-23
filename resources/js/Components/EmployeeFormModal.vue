<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { CameraIcon, UserIcon } from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import { useClientValidation } from '@/Composables/useClientValidation';

const props = defineProps({
    show: Boolean,
    employee: Object, // If null, create mode
    departemens: Array,
    lokasiKantors: Array,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    id: null,
    nama_lengkap: '',
    jenis_kelamin: '',
    nomor_induk_karyawan: '',
    id_departemen: '',
    jabatan: '',
    email: '', 
    create_user: false,
    // Add salary fields
    gaji_pokok: 0,
    status_ptkp: 'TK/0',
    // Bank Info
    nama_bank: '',
    nomor_rekening: '',
    atas_nama_rekening: '',
    
    tgl_bergabung: '',
    tempat_lahir: '',
    tgl_lahir: '',
    alamat: '',
    status_karyawan: 'Tetap',
    foto: null,
    id_lokasi_kantor: '',
    is_strict_location: false
});

const { clientErrors, validate, clearClientError, clearAllClientErrors, hasClientErrors } = useClientValidation();

const employeeRules = {
    nomor_induk_karyawan: ['required', 'string', 'max:100'],
    nama_lengkap:         ['required', 'string', 'max:255'],
    jenis_kelamin:        ['required', 'in:L,P'],
    id_departemen:        ['required'],
    jabatan:              ['required', 'string', 'max:100'],
    status_karyawan:      ['required', 'in:Tetap,Kontrak,Probation,Magang'],
    gaji_pokok:           ['numeric', 'min:0'],
    status_ptkp:          ['required'],
    email:                ['required_if:create_user,true', 'email'],
};

const photoPreview = ref(null);

const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        // Validate file size (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            form.setError('foto', 'Maksimal ukuran foto adalah 2MB.');
            e.target.value = ''; // Reset input file
            form.foto = null;
            photoPreview.value = isEditing.value && props.employee ? props.employee.foto_url : null;
            return;
        }

        form.clearErrors('foto');
        clearClientError('foto');
        form.foto = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const modalTitle = ref('');
const isEditing = ref(false);

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.employee) {
            isEditing.value = true;
            modalTitle.value = 'Edit Data Karyawan';
            form.id = props.employee.id;
            form.nama_lengkap = props.employee.nama_lengkap;
            form.jenis_kelamin = props.employee.jenis_kelamin || '';
            form.nomor_induk_karyawan = props.employee.nomor_induk_karyawan;
            form.id_departemen = props.employee.id_departemen;
            form.jabatan = props.employee.jabatan;
            form.gaji_pokok = props.employee.gaji_pokok || 0;
            form.status_ptkp = props.employee.status_ptkp || 'TK/0';
            
            // Handle Bank Info (Primary Bank)
            const primaryBank = props.employee.primary_bank || {};
            form.nama_bank = primaryBank.nama_bank || '';
            form.nomor_rekening = primaryBank.nomor_rekening || '';
            form.atas_nama_rekening = primaryBank.atas_nama_rekening || '';

            form.tgl_bergabung = props.employee.tgl_bergabung || '';
            form.tempat_lahir = props.employee.tempat_lahir || '';
            form.tgl_lahir = props.employee.tgl_lahir || '';
            form.alamat = props.employee.alamat || '';
            form.status_karyawan = props.employee.status_karyawan || 'Tetap';
            form.id_lokasi_kantor = props.employee.id_lokasi_kantor || '';
            form.is_strict_location = !!props.employee.is_strict_location;
            form.create_user = false; 
            form.foto = null;
            photoPreview.value = props.employee.foto_url || null;
        } else {
            isEditing.value = false;
            modalTitle.value = 'Tambah Karyawan Baru';
            form.reset();
            form.clearErrors();
            clearAllClientErrors();
            photoPreview.value = null;
        }
    }
});

const submit = () => {
    const errors = validate(form, employeeRules, {
        'nomor_induk_karyawan.label': 'NIK',
        'id_departemen.required':     'Departemen wajib dipilih.',
        'email.required_if':          'Email wajib diisi jika membuat akun.',
    });
    if (hasClientErrors(errors)) return;

    if (isEditing.value) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT'
        })).post(route('admin.karyawan.update', form.id), {
            forceFormData: true,
            onSuccess: () => {
                emit('saved');
                emit('close');
                Swal.fire('Berhasil', 'Data karyawan berhasil diperbarui', 'success');
            }
        });
    } else {
        form.post(route('admin.karyawan.store'), {
            forceFormData: true,
            onSuccess: () => {
                emit('saved');
                emit('close');
                Swal.fire('Berhasil', 'Karyawan baru berhasil ditambahkan', 'success');
            }
        });
    }
};

const close = () => {
    emit('close');
    form.reset();
    clearAllClientErrors();
};
</script>

<template>
    <Modal :show="show" maxWidth="4xl" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                {{ modalTitle }}
            </h2>

            <div class="mb-4 flex flex-col items-center justify-center">
                <InputLabel value="Foto Profil Karyawan" class="mb-2" />
                <div class="relative w-28 h-28 group rounded-full overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-center bg-gray-100 dark:bg-gray-800">
                    <img v-if="photoPreview" :src="photoPreview" alt="Profile Photo" class="w-full h-full object-cover" />
                    <UserIcon v-else class="w-14 h-14 text-gray-400" />
                    
                    <div class="absolute inset-0 bg-black bg-opacity-40 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 cursor-pointer">
                        <CameraIcon class="w-8 h-8 text-white" />
                        <span class="text-white text-xs mt-1 font-medium">Ubah Foto</span>
                        <input type="file" accept="image/*" @change="handlePhotoChange" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                    </div>
                </div>
                <InputError class="mt-2 text-center" :message="clientErrors.foto || form.errors.foto" />
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- NIK -->
                <div>
                    <InputLabel for="nik" value="Nomor Induk Karyawan (NIK)" />
                    <TextInput
                        id="nik"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.nomor_induk_karyawan" @input="clearClientError('nomor_induk_karyawan')" :class="{'border-red-500': clientErrors.nomor_induk_karyawan || form.errors.nomor_induk_karyawan}"
                        required
                        placeholder="Contoh: 2023001"
                    />
                    <InputError class="mt-2" :message="clientErrors.nomor_induk_karyawan || form.errors.nomor_induk_karyawan" />
                </div>

                <!-- Nama Lengkap -->
                <div>
                    <InputLabel for="nama" value="Nama Lengkap" />
                    <TextInput
                        id="nama"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.nama_lengkap" @input="clearClientError('nama_lengkap')" :class="{'border-red-500': clientErrors.nama_lengkap || form.errors.nama_lengkap}"
                        required
                        placeholder="Nama sesuai KTP"
                    />
                    <InputError class="mt-2" :message="clientErrors.nama_lengkap || form.errors.nama_lengkap" />
                </div>

                <!-- Jenis Kelamin -->
                <div>
                    <InputLabel for="jenis_kelamin" value="Jenis Kelamin" />
                    <select 
                        id="jenis_kelamin" 
                        v-model="form.jenis_kelamin" @input="clearClientError('jenis_kelamin')" :class="{'border-red-500': clientErrors.jenis_kelamin || form.errors.jenis_kelamin}" 
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        required
                    >
                        <option value="" disabled>Pilih Jenis Kelamin</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                    <InputError class="mt-2" :message="clientErrors.jenis_kelamin || form.errors.jenis_kelamin" />
                </div>

                <!-- Departemen -->
                <div>
                    <InputLabel for="departemen" value="Departemen" />
                    <select 
                        id="departemen" 
                        v-model="form.id_departemen" @input="clearClientError('id_departemen')" :class="{'border-red-500': clientErrors.id_departemen || form.errors.id_departemen}" 
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        required
                    >
                        <option value="" disabled>Pilih Departemen</option>
                        <option v-for="dept in departemens" :key="dept.id" :value="dept.id">{{ dept.nama_departemen }}</option>
                    </select>
                    <InputError class="mt-2" :message="clientErrors.id_departemen || form.errors.id_departemen" />
                </div>

                <!-- Jabatan -->
                <div>
                    <InputLabel for="jabatan" value="Jabatan" />
                    <TextInput
                        id="jabatan"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.jabatan" @input="clearClientError('jabatan')" :class="{'border-red-500': clientErrors.jabatan || form.errors.jabatan}"
                        required
                        placeholder="Contoh: Staff Finance"
                    />
                    <InputError class="mt-2" :message="clientErrors.jabatan || form.errors.jabatan" />
                </div>

                <!-- Status Karyawan -->
                <div>
                    <InputLabel for="status_karyawan" value="Status Karyawan" />
                    <select 
                        id="status_karyawan" 
                        v-model="form.status_karyawan" @input="clearClientError('status_karyawan')" :class="{'border-red-500': clientErrors.status_karyawan || form.errors.status_karyawan}" 
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                    >
                        <option value="Tetap">Tetap</option>
                        <option value="Kontrak">Kontrak</option>
                        <option value="Magang">Magang</option>
                    </select>
                    <InputError class="mt-2" :message="clientErrors.status_karyawan || form.errors.status_karyawan" />
                </div>

                <!-- Penempatan Kantor -->
                <div>
                    <InputLabel for="id_lokasi_kantor" value="Penempatan Kantor" />
                    <select 
                        id="id_lokasi_kantor" 
                        v-model="form.id_lokasi_kantor"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                    >
                        <option value="">Pusat / Bebas (Default)</option>
                        <option v-for="lokasi in lokasiKantors" :key="lokasi.id" :value="lokasi.id">{{ lokasi.nama_kantor }}</option>
                    </select>
                </div>

                <!-- Strict Location Toggle -->
                <div class="col-span-1 md:col-span-2 bg-yellow-50 dark:bg-yellow-900/30 p-4 rounded-lg border border-yellow-100 dark:border-yellow-800" v-if="form.id_lokasi_kantor">
                    <label class="flex items-start">
                        <input type="checkbox" v-model="form.is_strict_location" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mt-1" />
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Wajib Absen di Lokasi Penempatan (Strict Mode)</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-1">Jika diaktifkan, karyawan ini <strong>HANYA BISA</strong> absen di radius kantor yang dipilih di atas. Jika dimatikan, karyawan bisa absen di cabang mana pun (Roaming).</span>
                        </div>
                    </label>
                </div>

                <!-- Tanggal Bergabung -->
                <div>
                    <InputLabel for="tgl_bergabung" value="Tanggal Bergabung" />
                    <TextInput
                        id="tgl_bergabung"
                        type="date"
                        class="mt-1 block w-full"
                        v-model="form.tgl_bergabung" @input="clearClientError('tgl_bergabung')" :class="{'border-red-500': clientErrors.tgl_bergabung || form.errors.tgl_bergabung}"
                    />
                    <InputError class="mt-2" :message="clientErrors.tgl_bergabung || form.errors.tgl_bergabung" />
                </div>

                <!-- Tempat Lahir -->
                <div>
                    <InputLabel for="tempat_lahir" value="Tempat Lahir" />
                    <TextInput
                        id="tempat_lahir"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.tempat_lahir" @input="clearClientError('tempat_lahir')" :class="{'border-red-500': clientErrors.tempat_lahir || form.errors.tempat_lahir}"
                    />
                    <InputError class="mt-2" :message="clientErrors.tempat_lahir || form.errors.tempat_lahir" />
                </div>

                <!-- Tanggal Lahir -->
                <div>
                    <InputLabel for="tgl_lahir" value="Tanggal Lahir" />
                    <TextInput
                        id="tgl_lahir"
                        type="date"
                        class="mt-1 block w-full"
                        v-model="form.tgl_lahir" @input="clearClientError('tgl_lahir')" :class="{'border-red-500': clientErrors.tgl_lahir || form.errors.tgl_lahir}"
                    />
                    <InputError class="mt-2" :message="clientErrors.tgl_lahir || form.errors.tgl_lahir" />
                </div>
                
                <!-- Alamat -->
                <div class="md:col-span-2">
                    <InputLabel for="alamat" value="Alamat Lengkap" />
                    <textarea 
                        id="alamat" 
                        v-model="form.alamat" @input="clearClientError('alamat')" :class="{'border-red-500': clientErrors.alamat || form.errors.alamat}"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        rows="2"
                    ></textarea>
                    <InputError class="mt-2" :message="clientErrors.alamat || form.errors.alamat" />
                </div>

                <!-- Gaji Pokok -->
                <div>
                    <InputLabel for="gaji_pokok" value="Gaji Pokok" />
                    <TextInput
                        id="gaji_pokok"
                        type="number"
                        class="mt-1 block w-full"
                        v-model="form.gaji_pokok" @input="clearClientError('gaji_pokok')" :class="{'border-red-500': clientErrors.gaji_pokok || form.errors.gaji_pokok}"
                    />
                    <InputError class="mt-2" :message="clientErrors.gaji_pokok || form.errors.gaji_pokok" />
                </div>

                <!-- Status PTKP -->
                <div>
                    <InputLabel for="status_ptkp" value="Status PTKP" />
                    <TextInput
                        id="status_ptkp"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.status_ptkp" @input="clearClientError('status_ptkp')" :class="{'border-red-500': clientErrors.status_ptkp || form.errors.status_ptkp}"
                        placeholder="TK/0, K/1, dll"
                    />
                    <InputError class="mt-2" :message="clientErrors.status_ptkp || form.errors.status_ptkp" />
                </div>

                <!-- Info Bank -->
                <div class="md:col-span-2 border-t pt-4 mt-2">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Informasi Rekening Bank</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="nama_bank" value="Nama Bank" />
                            <TextInput
                                id="nama_bank"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.nama_bank" @input="clearClientError('nama_bank')" :class="{'border-red-500': clientErrors.nama_bank || form.errors.nama_bank}"
                                placeholder="BCA"
                            />
                            <InputError class="mt-2" :message="clientErrors.nama_bank || form.errors.nama_bank" />
                        </div>
                        <div>
                            <InputLabel for="nomor_rekening" value="Nomor Rekening" />
                            <TextInput
                                id="nomor_rekening"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.nomor_rekening" @input="clearClientError('nomor_rekening')" :class="{'border-red-500': clientErrors.nomor_rekening || form.errors.nomor_rekening}"
                                placeholder="1234567890"
                            />
                            <InputError class="mt-2" :message="clientErrors.nomor_rekening || form.errors.nomor_rekening" />
                        </div>
                        <div>
                            <InputLabel for="atas_nama_rekening" value="Atas Nama" />
                            <TextInput
                                id="atas_nama_rekening"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.atas_nama_rekening" @input="clearClientError('atas_nama_rekening')" :class="{'border-red-500': clientErrors.atas_nama_rekening || form.errors.atas_nama_rekening}"
                                placeholder="Nama Pemilik"
                            />
                            <InputError class="mt-2" :message="clientErrors.atas_nama_rekening || form.errors.atas_nama_rekening" />
                        </div>
                    </div>
                </div>

            </div>

            <!-- Opsi Buat User (Hanya saat create) -->
            <div v-if="!isEditing" class="mt-6 border-t pt-4 dark:border-gray-700">
                <label class="flex items-center">
                    <input type="checkbox" v-model="form.create_user" @input="clearClientError('create_user')" :class="{'border-red-500': clientErrors.create_user || form.errors.create_user}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Buat akun login untuk karyawan ini?</span>
                </label>

                <div v-if="form.create_user" class="mt-3">
                    <InputLabel for="email" value="Email Login" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email" @input="clearClientError('email')" :class="{'border-red-500': clientErrors.email || form.errors.email}"
                        placeholder="email@persija.id"
                    />
                    <InputError class="mt-2" :message="clientErrors.email || form.errors.email" />
                    <p class="text-xs text-gray-500 mt-1">Password default: password123</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="close"> Batal </SecondaryButton>
                <PrimaryButton class="ml-3" :class="{ 'opacity-25 cursor-not-allowed': form.processing }" :disabled="form.processing" @click="submit">
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
