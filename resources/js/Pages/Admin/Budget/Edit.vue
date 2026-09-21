<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputCurrency from '@/Components/InputCurrency.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    budget: Object,
});

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

const months = ref([]);
const form = useForm({
    pacing: [],
});

onMounted(() => {
    // Generate months based on budget period
    months.value = generateMonths(props.budget.periode.tanggal_mulai, props.budget.periode.tanggal_selesai);

    // Map existing details to form
    form.pacing = months.value.map(m => {
        const existing = props.budget.details.find(d => d.bulan === m.bulan && d.tahun === m.tahun);
        return {
            bulan: m.bulan,
            tahun: m.tahun,
            nominal: existing ? parseFloat(existing.nominal_pacing) : 0
        };
    });
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
    form.put(route('admin.budget.update', props.budget.id));
};
</script>

<template>
    <Head title="Edit Anggaran" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Anggaran</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Detail Anggaran</h3>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="block text-gray-500">Periode:</span>
                                    <span class="font-semibold">{{ budget.periode.nama_periode }}</span>
                                </div>
                                <div>
                                    <span class="block text-gray-500">Departemen:</span>
                                    <span class="font-semibold">{{ budget.pos_anggaran?.program_kerja?.departemen?.nama_departemen || '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-gray-500">Program Kerja:</span>
                                    <span class="font-semibold">{{ budget.pos_anggaran?.program_kerja?.nama_program || '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-gray-500">Akun GL:</span>
                                    <span class="font-semibold">{{ budget.pos_anggaran?.akun_gl?.kode_akun }} - {{ budget.pos_anggaran?.akun_gl?.nama_akun }}</span>
                                </div>
                                <div>
                                    <span class="block text-gray-500">Terpakai (YTD):</span>
                                    <span class="font-semibold text-blue-600">{{ formatCurrency(parseFloat(budget.anggaran_terikat_ytd) + parseFloat(budget.anggaran_realisasi_ytd)) }}</span>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="submit">
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
                                
                                <InputError class="mt-2" :message="form.errors.pacing" />
                            </div>

                            <div class="flex items-center justify-end mt-6">
                                <Link :href="route('admin.budget.index')" class="text-gray-600 hover:text-gray-900 mr-4">
                                    Batal
                                </Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Simpan Perubahan
                                </PrimaryButton>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
