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
import Toast from '@/Components/Toast.vue';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/20/solid';
import { ref, watch, computed, onMounted } from 'vue';
import { debounce } from 'lodash';

const page = usePage();

const permissions = computed(() => page.props.permissions);
const filters = computed(() => page.props.filters || {});
const can = computed(() => page.props.can || {});
const flashMessage = computed(() => page.props.flash?.message);
const flashType = computed(() => page.props.flash?.type || 'info');

const showPermissionModal = ref(false);
const isEditMode = ref(false);

const form = useForm({
    id: null,
    name: '',
    guard_name: 'web',
});

// Search
const searchQuery = ref(filters.value.search || '');

const submitFilters = () => {
    router.get(route('admin.permissions.index'), {
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
    showPermissionModal.value = true;
};

const openEditModal = (permission) => {
    isEditMode.value = true;
    form.reset();
    form.clearErrors();
    form.id = permission.id;
    form.name = permission.name;
    form.guard_name = permission.guard_name;
    showPermissionModal.value = true;
};

const closeModal = () => {
    showPermissionModal.value = false;
    form.reset();
    form.clearErrors();
};

const submitPermissionForm = () => {
    const submissionRoute = isEditMode.value
        ? route('admin.permissions.update', form.id)
        : route('admin.permissions.store');
    const httpMethod = isEditMode.value ? 'put' : 'post';

    form.submit(httpMethod, submissionRoute, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            //router.reload({ preserveScroll: true });
        },
        onError: (errors) => {
            console.error('Permission form submission error:', errors);
        }
    });
};

const showDeleteConfirmModal = ref(false);
const permissionToDelete = ref(null);

const confirmDeletePermission = (permission) => {
    permissionToDelete.value = permission;
    showDeleteConfirmModal.value = true;
};

const deletePermission = () => {
    if (permissionToDelete.value) {
        router.delete(route('admin.permissions.destroy', permissionToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                permissionToDelete.value = null;
                showDeleteConfirmModal.value = false;
                //router.reload({ preserveScroll: true });
            },
            onError: (errors) => {
                console.error('Delete permission error:', errors);
                permissionToDelete.value = null;
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
    <Head title="Manage Permissions" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Permission Management</h2>
        </template>

        <Toast :message="flashMessage" :type="flashType" />

        <div class="pb-12 pt-4">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                            <div class="flex flex-col sm:flex-row items-center gap-3 flex-grow">
                                <TextInput type="text" v-model="searchQuery" placeholder="Search permission name..." class="w-full sm:w-auto md:flex-grow lg:max-w-xs" aria-label="Search permissions"/>
                                
                            </div>
                            <PrimaryButton @click="openCreateModal" v-if="can?.create_permission" class="w-full md:w-auto">
                                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" /> Add New Permission
                            </PrimaryButton>
                        </div>
                   
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Permission Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Guard</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-if="!permissions || !permissions.data || permissions.data.length === 0">
                                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No permissions found.</td>
                                    </tr>
                                    <tr v-for="permission in permissions.data" :key="permission.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ permission.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ permission.guard_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="openEditModal(permission)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 p-1 mr-2" v-if="can?.edit_permission" title="Edit Permission">
                                                <PencilIcon class="h-5 w-5" />
                                            </button>
                                            <button @click="confirmDeletePermission(permission)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200 p-1" v-if="can?.delete_permission" title="Delete Permission">
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="permissions && permissions.links && permissions.links.length > 3" class="p-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex flex-wrap -mb-1 justify-center">
                                <template v-for="(link, key) in permissions.links" :key="key">
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

        <Modal :show="showPermissionModal" @close="closeModal" :maxWidth="'lg'">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 border-b pb-3 dark:border-gray-700">
                    {{ isEditMode ? 'Edit Permission' : 'Create New Permission' }}
                </h2>
                <form @submit.prevent="submitPermissionForm" class="space-y-6">
                    <div>
                        <InputLabel for="permission_name_modal" value="Permission Name" />
                        <TextInput id="permission_name_modal" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus placeholder="e.g., manage_users or view_reports"/>
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="permission_guard_name_modal" value="Guard Name" />
                        <TextInput id="permission_guard_name_modal" type="text" class="mt-1 block w-full" v-model="form.guard_name" placeholder="web" />
                        <InputError class="mt-2" :message="form.errors.guard_name" />
                         <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Usually 'web' for web routes, or 'api' for API routes. Leave blank to use default ('web').</p>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3 pt-4 border-t dark:border-gray-700">
                        <SecondaryButton @click="closeModal" type="button"> Cancel </SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ isEditMode ? 'Update Permission' : 'Create Permission' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showDeleteConfirmModal" @close="showDeleteConfirmModal = false" maxWidth="md">
            <div class="p-6">
                 <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Confirm Delete Permission
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Are you sure you want to delete permission "<span class="font-semibold">{{ permissionToDelete?.name }}</span>"?
                    This might affect roles that use this permission.
                </p>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showDeleteConfirmModal = false" type="button"> Cancel </SecondaryButton>
                    <DangerButton @click="deletePermission" :class="{ 'opacity-25': router.processing || form.processing }" :disabled="router.processing || form.processing">
                        Delete Permission
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>