<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    pageTitle: String,
    allKelas: Array,
    registrationFee: Number,
    errors: Object,
});

const form = useForm({
    nama_siswa: '',
    tanggal_lahir: '',
    id_kelas: '',
    user_name: '', // Nama Wali
    email_wali: '',
    nomor_telepon_wali: '',
    user_password: '',
    user_password_confirmation: '',
    terms: false,
});

// State untuk modal Syarat & Ketentuan
const showTermsModal = ref(false);

const openTermsModal = () => {
    showTermsModal.value = true;
};

const acceptTerms = () => {
    form.terms = true;
    showTermsModal.value = false;
};


const submit = () => {
    // Validasi frontend sebelum mengirim ke server
    form.clearErrors();
    let hasClientErrors = false;

    if (!form.nama_siswa) { form.setError('nama_siswa', 'Nama Lengkap Siswa wajib diisi.'); hasClientErrors = true; }
    if (!form.tanggal_lahir) { form.setError('tanggal_lahir', 'Tanggal Lahir wajib diisi.'); hasClientErrors = true; }
    if (!form.id_kelas) { form.setError('id_kelas', 'Silakan pilih cabang/kelas.'); hasClientErrors = true; }
    if (!form.user_name) { form.setError('user_name', 'Nama Lengkap Wali wajib diisi.'); hasClientErrors = true; }
    if (!form.email_wali) { form.setError('email_wali', 'Alamat Email Wali wajib diisi.'); hasClientErrors = true; }
    if (!form.nomor_telepon_wali) { form.setError('nomor_telepon_wali', 'Nomor WhatsApp Wali wajib diisi.'); hasClientErrors = true; }
    if (!form.user_password) { form.setError('user_password', 'Password wajib diisi.'); hasClientErrors = true; }
    if (form.user_password && form.user_password !== form.user_password_confirmation) {
        form.setError('user_password_confirmation', 'Konfirmasi password tidak cocok.');
        hasClientErrors = true;
    }
    if (!form.terms) { form.setError('terms', 'Anda harus menyetujui syarat dan ketentuan.'); hasClientErrors = true; }

    if (hasClientErrors) {
        return; // Hentikan proses jika ada error di frontend
    }

    form.post(route('pendaftaran.store'), {
        onError: () => {
            form.reset('user_password', 'user_password_confirmation');
        },
    });
};

const formattedFee = computed(() => {
    if (props.registrationFee === undefined || props.registrationFee === null) {
        return 'Rp 0';
    }
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(props.registrationFee);
});
</script>

<template>
    <Head :title="pageTitle" />
    <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-gray-100 via-white to-gray-200 dark:from-gray-900 dark:via-black dark:to-slate-900 flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        
        <!-- Ilustrasi Latar Belakang -->
        <div class="absolute top-0 left-0 w-1/3 h-full bg-no-repeat opacity-5 dark:opacity-10" style="background-image: url('https://www.transparenttextures.com/patterns/football-no-lines.png');"></div>
        <div class="absolute -bottom-24 -right-24 w-72 h-72 rounded-full bg-red-600/10 blur-3xl"></div>
        <div class="absolute -top-24 -left-24 w-72 h-72 rounded-full bg-red-600/10 blur-3xl"></div>
        
        <div class="w-full max-w-4xl space-y-8 z-10">
            <div class="text-center">
                <div class="flex justify-center items-center gap-4">
                    <div class="h-10 w-10 bg-white dark:bg-slate-700 rounded-full shadow-inner flex flex-wrap items-center justify-center p-1">
                        <div class="h-[45%] w-[45%] bg-black dark:bg-slate-300 rounded-tl-full"></div>
                        <div class="h-[45%] w-[45%] bg-black dark:bg-slate-300 rounded-tr-full"></div>
                        <div class="h-[45%] w-[45%] bg-black dark:bg-slate-300 rounded-bl-full"></div>
                        <div class="h-[45%] w-[45%] bg-black dark:bg-slate-300 rounded-br-full"></div>
                    </div>
                    <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ pageTitle }}</h2>
                </div>
                <p class="mt-2 text-md text-gray-600 dark:text-gray-400">
                    Selesaikan pendaftaran untuk bergabung dengan sekolah sepak bola kami.
                </p>
            </div>

            <form @submit.prevent="submit" novalidate class="mt-8 bg-white/70 dark:bg-gray-800/70 backdrop-blur-xl shadow-2xl rounded-2xl p-8 space-y-6">
                <div v-if="errors.general" class="p-4 bg-red-100 text-red-700 rounded-md">
                    {{ errors.general }}
                </div>
                
                <!-- Data Siswa -->
                <fieldset class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <legend class="text-lg font-medium text-gray-900 dark:text-white">1. Data Diri Siswa</legend>
                    <div class="mt-4 grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                        <div class="sm:col-span-2">
                            <InputLabel for="nama_siswa" value="Nama Lengkap Siswa" required/>
                            <TextInput id="nama_siswa" v-model="form.nama_siswa" @input="form.clearErrors('nama_siswa')" type="text" class="mt-1 block w-full" placeholder="Cth: Budi Santoso" required />
                            <InputError :message="form.errors.nama_siswa" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="tanggal_lahir" value="Tanggal Lahir" required/>
                            <TextInput id="tanggal_lahir" v-model="form.tanggal_lahir" @input="form.clearErrors('tanggal_lahir')" type="date" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.tanggal_lahir" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="id_kelas" value="Pilih Cabang/Kelas" required/>
                            <select id="id_kelas" v-model="form.id_kelas" @change="form.clearErrors('id_kelas')" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" required>
                                <option value="" disabled>-- Pilih Cabang --</option>
                                <option v-for="kelas in allKelas" :key="kelas.id_kelas" :value="kelas.id_kelas">{{ kelas.nama_kelas }}</option>
                            </select>
                             <InputError :message="form.errors.id_kelas" class="mt-2" />
                        </div>
                    </div>
                </fieldset>

                <!-- Data Wali & Akun -->
                <fieldset class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <legend class="text-lg font-medium text-gray-900 dark:text-white">2. Data Wali & Akun Login</legend>
                    <div class="mt-4 grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
                        <div>
                            <InputLabel for="user_name" value="Nama Lengkap Wali" required/>
                            <TextInput id="user_name" v-model="form.user_name" @input="form.clearErrors('user_name')" type="text" class="mt-1 block w-full" placeholder="Cth: Ayah Budi" required />
                            <InputError :message="form.errors.user_name" class="mt-2" />
                        </div>
                         <div>
                            <InputLabel for="nomor_telepon_wali" value="Nomor WhatsApp Wali" required/>
                            <TextInput id="nomor_telepon_wali" v-model="form.nomor_telepon_wali" @input="form.clearErrors('nomor_telepon_wali')" type="tel" class="mt-1 block w-full" placeholder="Cth: 081234567890" required />
                            <InputError :message="form.errors.nomor_telepon_wali" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel for="email_wali" value="Alamat Email Wali (untuk login)" required/>
                            <TextInput id="email_wali" v-model="form.email_wali" @input="form.clearErrors('email_wali')" type="email" class="mt-1 block w-full" placeholder="Cth: wali.budi@email.com" required />
                            <InputError :message="form.errors.email_wali" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="user_password" value="Buat Password" required/>
                            <TextInput id="user_password" v-model="form.user_password" @input="form.clearErrors('user_password')" type="password" class="mt-1 block w-full" placeholder="••••••••" required />
                            <InputError :message="form.errors.user_password" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="user_password_confirmation" value="Konfirmasi Password" required/>
                            <TextInput id="user_password_confirmation" v-model="form.user_password_confirmation" @input="form.clearErrors('user_password_confirmation')" type="password" class="mt-1 block w-full" placeholder="••••••••" required />
                            <InputError :message="form.errors.user_password_confirmation" class="mt-2" />
                        </div>
                    </div>
                </fieldset>
                
                <!-- Konfirmasi & Pembayaran -->
                 <fieldset class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <legend class="text-lg font-medium text-gray-900 dark:text-white">3. Konfirmasi & Pembayaran</legend>
                     <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-700/50 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-md font-medium text-gray-800 dark:text-gray-200">Biaya Pendaftaran:</span>
                            <span class="text-xl font-bold text-gray-900 dark:text-white">{{ formattedFee }}</span>
                        </div>
                     </div>
                     <div class="mt-6">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.terms" @change="form.clearErrors('terms')" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer">
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Saya menyetujui 
                                <button type="button" @click.prevent="openTermsModal" class="font-medium text-red-600 hover:underline">syarat dan ketentuan</button>
                                yang berlaku.
                            </span>
                        </label>
                        <InputError :message="form.errors.terms" class="mt-2" />
                     </div>
                </fieldset>

                <div class="pt-5 relative h-12">
                    <!-- Bola Animasi di atas tombol -->
                    <div class="absolute inset-x-0 -top-5 w-full h-10 flex justify-center items-end">
                        <div class="w-8 h-8 ball-track">
                            <div class="w-full h-full rounded-full bg-gradient-to-br from-red-600 to-black shadow-lg flex flex-wrap items-center justify-center p-1">
                                <div class="h-[45%] w-[45%] bg-white/80 rounded-tl-full"></div>
                                <div class="h-[45%] w-[45%] bg-white/80 rounded-tr-full"></div>
                                <div class="h-[45%] w-[45%] bg-white/80 rounded-bl-full"></div>
                                <div class="h-[45%] w-[45%] bg-white/80 rounded-br-full"></div>
                            </div>
                        </div>
                    </div>
                    <PrimaryButton class="w-full justify-center text-lg bg-red-600 hover:bg-red-700 focus:ring-red-500" :disabled="form.processing || !form.terms" :class="{ 'opacity-50 cursor-not-allowed': !form.terms }">
                        <span v-if="form.processing">Memproses Pembayaran...</span>
                        <span v-else>Lanjut ke Pembayaran</span>
                    </PrimaryButton>
                </div>
            </form>
        </div>

        <!-- Modal untuk Syarat & Ketentuan -->
        <Modal :show="showTermsModal" @close="showTermsModal = false" maxWidth="2xl">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Syarat dan Ketentuan</h2>
                <div class="mt-4 prose prose-sm max-w-none text-gray-600 dark:text-gray-300 overflow-y-auto max-h-[60vh]">
                    <p>Selamat datang di Sekolah Sepak Bola kami. Dengan melanjutkan pendaftaran, Anda menyetujui semua poin di bawah ini:</p>
                    <p><strong>1. Biaya Pendaftaran:</strong> Biaya pendaftaran yang dibayarkan tidak dapat dikembalikan dengan alasan apapun.</p>
                    <p><strong>2. Keanggotaan:</strong> Status keanggotaan siswa akan aktif setelah pembayaran pendaftaran berhasil diverifikasi oleh administrasi.</p>
                    <p><strong>3. Data Pribadi:</strong> Anda menjamin bahwa semua data yang diisikan adalah benar dan dapat dipertanggungjawabkan. Kami akan menjaga kerahasiaan data Anda sesuai dengan kebijakan privasi yang berlaku.</p>
                    <p><strong>4. Kesehatan dan Keselamatan:</strong> Wali murid bertanggung jawab penuh atas kondisi kesehatan siswa. Sekolah tidak bertanggung jawab atas cedera yang terjadi selama latihan atau pertandingan di luar kelalaian pihak sekolah.</p>
                    <p><strong>5. Jadwal Latihan:</strong> Jadwal latihan dapat berubah sewaktu-waktu dengan pemberitahuan sebelumnya dari pihak manajemen sekolah.</p>
                    <p><strong>6. Peraturan dan Disiplin:</strong> Siswa diwajibkan untuk mengikuti semua peraturan dan menjaga disiplin selama berada di lingkungan sekolah.</p>
                    <p>Terima kasih atas kepercayaan Anda.</p>
                </div>

                <div class="mt-6 flex justify-end space-x-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                    <SecondaryButton @click="showTermsModal = false">Tidak Setuju</SecondaryButton>
                    <PrimaryButton @click="acceptTerms" class="bg-red-600 hover:bg-red-700 focus:ring-red-500">Setuju</PrimaryButton>
                </div>
            </div>
        </Modal>
    </div>
</template>

<style scoped>
@keyframes move-across {
  0%, 100% {
    transform: translateX(-60px);
    animation-timing-function: ease-in-out;
  }
  50% {
    transform: translateX(60px);
    animation-timing-function: ease-in-out;
  }
}
.ball-track {
    animation: move-across 4s infinite;
}
</style>
