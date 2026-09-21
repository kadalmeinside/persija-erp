<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputCurrency from '@/Components/InputCurrency.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    periodes: Array,
    departemens: Array,
    // programs & akuns removed, fetched via API
});

const form = useForm({
    id_periode_anggaran: props.periodes.length > 0 ? props.periodes[0].id : '',
    id_departemen: '',
    id_program: '',
    id_akun: '',
    pacing: [], // Array of { bulan, tahun, nominal }
});

const months = ref([]);
const availablePrograms = ref([]);
const availableAkuns = ref([]);
const isLoadingPrograms = ref(false);
const isLoadingAkuns = ref(false);

// Helper to generate months between dates
const generateMonths = (startDate, endDate) => {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const result = [];
    
    let current = new Date(start.getFullYear(), start.getMonth(), 1);
    const endMonth = new Date(end.getFullYear(), end.getMonth(), 1);

    while (current <= endMonth) {
        result.push({
            bulan: current.getMonth() + 1,
            tahun: current.getFullYear(),
            label: current.toLocaleString('id-ID', { month: 'long', year: 'numeric' })
        });
        current.setMonth(current.getMonth() + 1);
    }
    return result;
};

// Watch Periode Change
watch(() => form.id_periode_anggaran, (newVal) => {
    if (!newVal) {
        months.value = [];
        form.pacing = [];
        return;
    }

    const periode = props.periodes.find(p => p.id === newVal);
    if (periode) {
        const generated = generateMonths(periode.tanggal_mulai, periode.tanggal_selesai);
        months.value = generated;
        
        // Reset pacing form data
        form.pacing = generated.map(m => ({
            bulan: m.bulan,
            tahun: m.tahun,
            nominal: 0
        }));
    }
}, { immediate: true });

// Watch Departemen Change -> Fetch Programs
watch(() => form.id_departemen, async (newVal) => {
    form.id_program = '';
    form.id_akun = '';
    availablePrograms.value = [];
    availableAkuns.value = [];

    if (!newVal) return;

    isLoadingPrograms.value = true;
    try {
        const response = await axios.get(route('admin.budget.options'), {
            params: { type: 'program', id_departemen: newVal }
        });
        availablePrograms.value = response.data;
    } catch (error) {
        console.error('Error fetching programs:', error);
    } finally {
        isLoadingPrograms.value = false;
    }
});

// Watch Program Change -> Fetch Akun
watch(() => form.id_program, async (newVal) => {
    form.id_akun = '';
    availableAkuns.value = [];

    if (!newVal) return;

    isLoadingAkuns.value = true;
    try {
        const response = await axios.get(route('admin.budget.options'), {
            params: { 
                type: 'akun', 
                id_program: newVal,
                id_departemen: form.id_departemen // Needed for delegation check
            }
        });
        availableAkuns.value = response.data;
    } catch (error) {
        console.error('Error fetching accounts:', error);
    } finally {
        isLoadingAkuns.value = false;
    }
});

const totalAnggaran = computed(() => {
    return form.pacing.reduce((sum, item) => sum + Number(item.nominal || 0), 0);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const submit = () => {
    form.post(route('admin.budget.store'));
};
</script>

<template>
    <Head title="Alokasi Anggaran Baru" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Alokasi Anggaran Baru</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Periode -->
                                <div>
                                    <InputLabel for="periode" value="Periode Anggaran" />
                                    <select 
                                        id="periode" 
                                        v-model="form.id_periode_anggaran" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option v-for="p in periodes" :key="p.id" :value="p.id">
                                            {{ p.nama_periode }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.id_periode_anggaran" />
                                </div>

                                <!-- Departemen -->
                                <div>
                                    <InputLabel for="departemen" value="Departemen" />
                                    <select 
                                        id="departemen" 
                                        v-model="form.id_departemen" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="" disabled>Pilih Departemen</option>
                                        <option v-for="d in departemens" :key="d.id" :value="d.id">{{ d.nama_departemen }}</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.id_departemen" />
                                </div>

                                <!-- Program Kerja -->
                                <div>
                                    <InputLabel for="program" value="Program Kerja" />
                                    <select 
                                        id="program" 
                                        v-model="form.id_program" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                        :disabled="!form.id_departemen || isLoadingPrograms"
                                    >
                                        <option value="" disabled>{{ isLoadingPrograms ? 'Memuat...' : 'Pilih Program Kerja' }}</option>
                                        <option v-for="p in availablePrograms" :key="p.id" :value="p.id">{{ p.nama_program }}</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.id_program" />
                                </div>

                                <!-- Akun GL -->
                                <div>
                                    <InputLabel for="akun" value="Akun GL (Pos Anggaran)" />
                                    <select 
                                        id="akun" 
                                        v-model="form.id_akun" 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                        :disabled="!form.id_program || isLoadingAkuns"
                                    >
                                        <option value="" disabled>{{ isLoadingAkuns ? 'Memuat...' : 'Pilih Akun GL' }}</option>
                                        <option v-for="a in availableAkuns" :key="a.id" :value="a.id">
                                            {{ a.full_label }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.id_akun" />
                                </div>
                            </div>

                            <!-- Monthly Pacing Section -->
                            <div class="border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Alokasi Bulanan (Pacing)</h3>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    <div v-for="(month, index) in months" :key="index" class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            {{ month.label }}
                                        </label>
                                        <InputCurrency
                                            v-model="form.pacing[index].nominal"
                                            class="block w-full text-sm"
                                            placeholder="0"
                                        />
                                    </div>
                                </div>

                                <div class="mt-6 p-4 bg-indigo-50 rounded-lg flex justify-between items-center">
                                    <span class="text-indigo-900 font-medium">Total Anggaran Tahunan:</span>
                                    <span class="text-2xl font-bold text-indigo-700">{{ formatCurrency(totalAnggaran) }}</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-6">
                                <Link :href="route('admin.budget.index')" class="text-gray-600 hover:text-gray-900 mr-4">
                                    Batal
                                </Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Simpan Alokasi
                                </PrimaryButton>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
