<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    programs: Array
});

const form = useForm({
    bulan_periode: new Date().toISOString().slice(0, 7), // YYYY-MM
    tgl_payroll: new Date().toISOString().slice(0, 10),
});

const submit = () => {
    form.post(route('admin.payrolls.store'));
};
</script>

<template>
    <Head title="Buat Payroll Baru" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Buat Payroll Baru</h2>
        </template>

        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <InputLabel for="bulan_periode" value="Bulan Periode" />
                                <TextInput
                                    id="bulan_periode"
                                    type="month"
                                    class="mt-1 block w-full"
                                    v-model="form.bulan_periode"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.bulan_periode" />
                            </div>

                            <div>
                                <InputLabel for="tgl_payroll" value="Tanggal Payroll" />
                                <TextInput
                                    id="tgl_payroll"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.tgl_payroll"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.tgl_payroll" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6 gap-4">
                            <Link :href="route('admin.payrolls.index')" class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">Batal</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Generate Draft Payroll
                            </PrimaryButton>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
