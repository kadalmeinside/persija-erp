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
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/20/solid';
import { ref, watch, computed, onMounted } from 'vue';
import { debounce } from 'lodash';

const page = usePage();
const roles = computed(() => page.props.roles);
const filters = computed(() => page.props.filters || {});
const allPermissions = computed(() => page.props.allPermissions || []);
const can = computed(() => page.props.can || {});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');
const showRoleModal = ref(false);
const isEditMode = ref(false);
const form = useForm({
    id: null,
    name: '',
    guard_name: 'web',
    permissions: [] 
});
const searchQuery = ref(filters.value.search || '');
const submitFilters = () => {
    router.get(route('admin.roles.index'), {
        search: searchQuery.value,
        page: 1,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
watch(searchQuery, debounce(submitFilters, 300));

const openCreateModal = () => {
    isEditMode.value = false;
    form.reset();
    form.guard_name = 'web';
    form.clearErrors();
    showRoleModal.value = true;
};

const openEditModal = (role) => {
    isEditMode.value = true;
    form.reset();
    form.clearErrors();
    form.id = role.id;
    form.name = role.name;
    form.guard_name = role.guard_name;
    form.permissions = role.permissions ? [...role.permissions] : [];
    showRoleModal.value = true;
};

const closeModal = () => {
    showRoleModal.value = false;
    form.reset();
    form.clearErrors();
};

const submitRoleForm = () => {
    const submissionRoute = isEditMode.value
        ? route('admin.roles.update', form.id)
        : route('admin.roles.store');
    const httpMethod = isEditMode.value ? 'put' : 'post';

    form.submit(httpMethod, submissionRoute, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            //router.reload({ preserveScroll: true });
        },
        onError: (errors) => {
            console.error('Role form submission error:', errors);
        }
    });
};

const showDeleteConfirmModal = ref(false);
const roleToDelete = ref(null);

const confirmDeleteRole = (role) => {
    roleToDelete.value = role;
    showDeleteConfirmModal.value = true;
};

const deleteRole = () => {
    if (roleToDelete.value) {
        router.delete(route('admin.roles.destroy', roleToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                roleToDelete.value = null;
                showDeleteConfirmModal.value = false;
                //router.reload({ preserveScroll: true });
            },
            onError: (errors) => {
                console.error('Delete role error:', errors);
                roleToDelete.value = null;
                showDeleteConfirmModal.value = false;
            }
        });
    }
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    searchQuery.value = urlParams.get('search') || filters.value.search || '';
});

</script>

<template>
    <Head title="Manage Roles" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Role Management</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                                <div class="flex-grow">
                                    <TextInput type="text" v-model="searchQuery" placeholder="Search role name..." class="w-full md:max-w-sm" aria-label="Search roles" />
                                </div>
                            </div>
                            <PrimaryButton @click="openCreateModal" v-if="can?.create_role" class="w-full md:w-auto mt-2 md:mt-0">
                                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" /> Add New Role
                            </PrimaryButton>
                        </div>

                         <!-- Tabel Roles -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Guard</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Permissions</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="!roles || !roles.data || roles.data.length === 0">
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No roles found.</td>
                                    </tr>
                                    <tr v-for="role in roles.data" :key="role.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ role.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ role.guard_name }}</td>
                                        <td class="px-6 py-4 whitespace-normal text-sm text-gray-500 dark:text-gray-300 break-words max-w-xs">
                                            <span v-if="role.permissions_string && role.permissions_string.length > 0"
                                                class="text-xs">
                                                {{ role.permissions_string }}
                                            </span>
                                            <span v-else class="text-xs text-gray-400">No Permissions</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="openEditModal(role)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 p-1 mr-2" v-if="can?.edit_role" title="Edit Role">
                                                <PencilIcon class="h-5 w-5" />
                                            </button>
                                            <button @click="confirmDeleteRole(role)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200 p-1" v-if="can?.delete_role" title="Delete Role">
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <div v-if="roles && roles.links && roles.links.length > 3" class="p-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex flex-wrap -mb-1 justify-center">
                                <template v-for="(link, key) in roles.links" :key="key">
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

        <!-- Modal untuk Create/Edit Role -->
        <Modal :show="showRoleModal" @close="closeModal" :maxWidth="'2xl'">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 border-b pb-3 dark:border-gray-700">
                    {{ isEditMode ? 'Edit Role' : 'Create New Role' }}
                </h2>
                <form @submit.prevent="submitRoleForm" class="space-y-6">
                    <div>
                        <InputLabel for="role_name_modal" value="Role Name" />
                        <TextInput id="role_name_modal" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="role_guard_name_modal" value="Guard Name (e.g., web, api)" />
                        <TextInput id="role_guard_name_modal" type="text" class="mt-1 block w-full" v-model="form.guard_name" placeholder="web" />
                        <InputError class="mt-2" :message="form.errors.guard_name" />
                    </div>

                    <div v-if="allPermissions && allPermissions.length > 0">
                        <InputLabel value="Permissions" class="mb-1" />
                        <div class="mt-2 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 max-h-60 overflow-y-auto p-2 border dark:border-gray-700 rounded-md">
                            <label v-for="(permissionName, index) in allPermissions" :key="'perm_opt_'+index" class="flex items-center space-x-2 cursor-pointer">
                                <Checkbox :id="'permission_modal_'+index" :value="permissionName" v-model:checked="form.permissions" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ permissionName.replace(/_/g, ' ') }}</span>
                            </label>
                        </div>
                        <InputError class="mt-2" :message="form.errors.permissions" />
                    </div>


                    <div class="mt-6 flex justify-end space-x-3 pt-4 border-t dark:border-gray-700">
                        <SecondaryButton @click="closeModal" type="button"> Cancel </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ isEditMode ? 'Update Role' : 'Create Role' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal Konfirmasi Delete -->
        <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="md">
            <div class="p-6">
                 <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Confirm Delete Role
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Are you sure you want to delete role "<span class="font-semibold">{{ roleToDelete?.name }}</span>"? This action cannot be undone.
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showDeleteConfirmModal = false" type="button"> Cancel </SecondaryButton>
                    <DangerButton @click="deleteRole" :class="{ 'opacity-25': router.processing || form.processing }" :disabled="router.processing || form.processing">
                        Delete Role
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>