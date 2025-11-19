<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import Toast from '@/Components/Toast.vue';
// Jika Anda membuat komponen Pagination.vue, uncomment dan gunakan. Jika tidak, render manual.
// import Pagination from '@/Components/Pagination.vue';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/20/solid';
import { ref, watch, computed, onMounted } from 'vue';
import { debounce } from 'lodash';

const page = usePage();

// Menggunakan computed untuk memastikan reaktivitas props
const users = computed(() => page.props.users);
const filters = computed(() => page.props.filters || {}); // Default ke objek kosong jika tidak ada
const allRoles = computed(() => page.props.allRoles || []);
const can = computed(() => page.props.can || {});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

// State untuk Modal
const showUserModal = ref(false);
const isEditMode = ref(false);

const form = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] // Array nama role
});

// Search and Filter
const searchQuery = ref(filters.value.search || '');
const selectedRole = ref(filters.value.role || '');

const submitFilters = () => {
    console.log('Applying filters:', { search: searchQuery.value, role: selectedRole.value });
    router.get(route('admin.users.index'), {
        search: searchQuery.value,
        role: selectedRole.value,
        page: 1, // Selalu kembali ke halaman 1 saat filter/search baru
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Debounce search
watch([searchQuery, selectedRole], debounce(submitFilters, 300));

// Fungsi Modal
const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.clearErrors();
    showUserModal.value = true;
};

const openEditModal = (user) => {
    isEditMode.value = true;
    form.reset();
    form.clearErrors();
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.password_confirmation = '';
    form.roles = user.roles_array ? [...user.roles_array] : []; // Ambil dari roles_array
    showUserModal.value = true;
};

const closeModal = () => {
    showUserModal.value = false;
    form.reset();
    form.clearErrors();
};

const submitUserForm = () => {
    const submissionRoute = isEditMode.value
        ? route('admin.users.update', form.id)
        : route('admin.users.store');
    const httpMethod = isEditMode.value ? 'put' : 'post';

    form.submit(httpMethod, submissionRoute, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            console.log('Form submitted successfully. Reloading page props...');
            //router.reload({ preserveScroll: true }); // Reload semua props
        },
        onError: (errors) => {
            console.error('Form submission error:', errors);
        }
    });
};

// Delete User
const showDeleteConfirmModal = ref(false);
const userToDelete = ref(null);

const confirmDeleteUser = (user) => {
    userToDelete.value = user;
    showDeleteConfirmModal.value = true;
};

const deleteUser = () => {
    if (userToDelete.value) {
        router.delete(route('admin.users.destroy', userToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                userToDelete.value = null;
                showDeleteConfirmModal.value = false;
                console.log('User deleted successfully. Reloading page props...');
                router.reload({ preserveScroll: true }); // Reload semua props
            },
            onError: (errors) => {
                console.error('Delete error:', errors);
                userToDelete.value = null;
                showDeleteConfirmModal.value = false;
            }
        });
    }
};

// Inisialisasi filter dari URL saat komponen dimuat
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || filters.value.search || '';
    selectedRole.value = urlParams.get('role') || filters.value.role || '';
    console.log('Mounted. Initial Users:', users.value?.data?.length);
});

// Debugging (hapus jika sudah tidak perlu)
watch(() => page.props.users, (newUsers) => {
    console.log('PAGE.PROPS.USERS (prop) changed:', newUsers);
}, { deep: true });

watch(users, (newComputedUsers) => {
    console.log('COMPUTED USERS changed:', newComputedUsers);
}, { deep: true });

</script>

<template>
    <Head title="Manage Users" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">User Management</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            <div class="flex flex-col sm:flex-row items-center gap-3 flex-grow">
                                <TextInput type="text" v-model="searchQuery" placeholder="Search name or email..." class="w-full sm:w-auto md:flex-grow lg:max-w-xs" aria-label="Search users" />
                                <select v-model="selectedRole" class="w-full sm:w-auto md:flex-grow lg:max-w-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" aria-label="Filter by role">
                                    <option value="">All Roles</option>
                                    <option v-for="roleName in allRoles" :key="roleName" :value="roleName">{{ roleName }}</option>
                                </select>
                            </div>
                            <PrimaryButton @click="openCreateModal" v-if="can?.create_user" class="w-full md:w-auto mt-2 md:mt-0">
                                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" /> Add New User
                            </PrimaryButton>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Roles</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="!users || !users.data || users.data.length === 0">
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No users found.</td>
                                    </tr>
                                    <tr v-for="user in users.data" :key="user.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ user.email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span v-if="user.roles_string && user.roles_string.length > 0"
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ user.roles_string }}
                                            </span>
                                            <span v-else class="text-xs text-gray-400">No Roles</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="openEditModal(user)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 p-1 mr-2" v-if="can?.edit_user" title="Edit User">
                                                <PencilIcon class="h-5 w-5" />
                                            </button>
                                            <button @click="confirmDeleteUser(user)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200 p-1" v-if="can?.delete_user" title="Delete User">
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="users && users.links && users.links.length > 3" class="p-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex flex-wrap -mb-1 justify-center">
                                <template v-for="(link, key) in users.links" :key="key">
                                    <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm leading-4 text-gray-400 dark:text-gray-500 border rounded dark:border-gray-600 select-none" v-html="link.label" />
                                    <Link v-else
                                        class="mr-1 mb-1 px-3 py-2 text-sm leading-4 border rounded dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 focus:border-indigo-500 dark:focus:border-indigo-700 focus:text-indigo-500 dark:focus:text-indigo-300"
                                        :class="{ 'bg-indigo-500 text-white dark:bg-indigo-600 dark:text-white dark:border-indigo-700': link.active }"
                                        :href="link.url"
                                        v-html="link.label"
                                        preserve-scroll />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showUserModal" @close="closeModal" :maxWidth="'2xl'">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 border-b pb-3 dark:border-gray-700">
                    {{ isEditMode ? 'Edit User' : 'Create New User' }}
                </h2>
                <form @submit.prevent="submitUserForm" class="space-y-6">
                    <div>
                        <InputLabel for="name_modal_user_form" value="Name" />
                        <TextInput id="name_modal_user_form" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus autocomplete="name" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="email_modal_user_form" value="Email" />
                        <TextInput id="email_modal_user_form" type="email" class="mt-1 block w-full" v-model="form.email" required autocomplete="email" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                    <div>
                        <InputLabel for="password_modal_user_form" :value="isEditMode ? 'New Password (Optional)' : 'Password'" />
                        <TextInput id="password_modal_user_form" type="password" class="mt-1 block w-full" v-model="form.password" :required="!isEditMode" autocomplete="new-password" />
                        <InputError class="mt-2" :message="form.errors.password" />
                        <p v-if="isEditMode && !form.password" class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave blank to keep current password.</p>
                    </div>
                    <div v-if="form.password || !isEditMode">
                        <InputLabel for="password_confirmation_modal_user_form" value="Confirm Password" />
                        <TextInput id="password_confirmation_modal_user_form" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" :required="form.password !== '' || !isEditMode" autocomplete="new-password" />
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>

                    <div v-if="allRoles && allRoles.length > 0">
                        <InputLabel value="Roles" class="mb-1" />
                        <div class="mt-2 grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-2 max-h-32 overflow-y-auto p-2 border dark:border-gray-700 rounded-md">
                            <label v-for="(roleName, index) in allRoles" :key="'role_option_'+index" class="flex items-center space-x-2 cursor-pointer">
                                <Checkbox :id="'role_modal_user_form_'+index" :value="roleName" v-model:checked="form.roles" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ roleName }}</span>
                            </label>
                        </div>
                        <InputError class="mt-2" :message="form.errors.roles" />
                    </div>

                    <div class="mt-6 flex justify-end space-x-3 pt-4 border-t dark:border-gray-700">
                        <SecondaryButton @click="closeModal" type="button"> Cancel </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ isEditMode ? 'Update User' : 'Create User' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="md">
            <div class="p-6">
                 <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Confirm Delete User
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Are you sure you want to delete user "<span class="font-semibold">{{ userToDelete?.name }}</span>"? This action cannot be undone.
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showDeleteConfirmModal = false" type="button"> Cancel </SecondaryButton>
                    <DangerButton @click="deleteUser" :class="{ 'opacity-25': router.processing || form.processing }" :disabled="router.processing || form.processing">
                        Delete User
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>