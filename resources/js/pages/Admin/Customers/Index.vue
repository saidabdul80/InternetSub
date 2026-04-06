<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Plus, Search } from 'lucide-vue-next';
import { ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type Customer = {
    id: number;
    username: string;
    full_name: string;
    email: string | null;
    phone_number: string | null;
    service_type: string;
    account_type: string;
    status: string;
    balance: string;
    auto_renewal: boolean;
    address: string | null;
    created_at: string;
    last_login_at: string | null;
};

const props = defineProps<{
    customers: Customer[];
    filters: {
        search: string;
        status: string;
    };
    stats: {
        total: number;
        active: number;
        suspended: number;
        hotspot: number;
    };
}>();

const page = usePage();
const flash = page.props.flash as { success?: string; error?: string };
const createDialogOpen = ref(false);
const editDialogOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Customers', href: '/admin/customers' },
];

const filterForm = useForm({
    search: props.filters.search,
    status: props.filters.status,
});

const createForm = useForm({
    username: '',
    full_name: '',
    email: '',
    phone_number: '',
    password: '',
    address: '',
    account_type: 'Personal',
    service_type: 'Hotspot',
    status: 'Active',
    balance: '0',
    auto_renewal: true,
});

const editForm = useForm({
    id: 0,
    username: '',
    full_name: '',
    email: '',
    phone_number: '',
    password: '',
    address: '',
    account_type: 'Personal',
    service_type: 'Hotspot',
    status: 'Active',
    balance: '0',
    auto_renewal: true,
});

const formatMoney = (value: string) => `NGN ${Number(value).toFixed(2)}`;

const formatDate = (value: string | null) => {
    if (!value) return 'Never';
    return new Date(value).toLocaleString();
};

const applyFilters = () => {
    filterForm.get('/admin/customers', {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.status = '';
    applyFilters();
};

const createCustomer = () => {
    createForm.post('/admin/customers', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createDialogOpen.value = false;
        },
    });
};

const startCreate = () => {
    createForm.reset();
    createForm.clearErrors();
    createDialogOpen.value = true;
};

const startEdit = (customer: Customer) => {
    editForm.id = customer.id;
    editForm.username = customer.username;
    editForm.full_name = customer.full_name;
    editForm.email = customer.email ?? '';
    editForm.phone_number = customer.phone_number ?? '';
    editForm.password = '';
    editForm.address = customer.address ?? '';
    editForm.account_type = customer.account_type;
    editForm.service_type = customer.service_type;
    editForm.status = customer.status;
    editForm.balance = customer.balance;
    editForm.auto_renewal = customer.auto_renewal;
    editForm.clearErrors();
    editDialogOpen.value = true;
};

const updateCustomer = () => {
    editForm.put(`/admin/customers/${editForm.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false;
        },
    });
};
</script>

<template>
    <Head title="Customers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <section class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Customers</h1>
                    <p class="text-sm text-slate-500">Search, open, create, and edit subscriber records without leaving the table.</p>
                </div>
                <Button type="button" class="gap-2" @click="startCreate">
                    <Plus class="h-4 w-4" />
                    Create Customer
                </Button>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Customers</div>
                    <div class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.total }}</div>
                </div>
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <div class="text-sm text-emerald-700">Active</div>
                    <div class="mt-2 text-3xl font-semibold text-emerald-950">{{ stats.active }}</div>
                </div>
                <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <div class="text-sm text-amber-700">Suspended</div>
                    <div class="mt-2 text-3xl font-semibold text-amber-950">{{ stats.suspended }}</div>
                </div>
                <div class="rounded-3xl border border-cyan-200 bg-cyan-50 p-5 shadow-sm">
                    <div class="text-sm text-cyan-700">Hotspot Users</div>
                    <div class="mt-2 text-3xl font-semibold text-cyan-950">{{ stats.hotspot }}</div>
                </div>
            </section>

            <div v-if="flash?.error" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ flash.error }}
            </div>
            <div v-if="flash?.success" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ flash.success }}
            </div>

            <section class="rounded-3xl border bg-white p-6 shadow-sm">
                <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Customer Directory</h2>
                        <p class="text-sm text-slate-500">Keep the table in view and use actions when needed.</p>
                    </div>

                    <form class="grid gap-3 sm:grid-cols-[minmax(260px,1fr)_180px_auto_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input v-model="filterForm.search" placeholder="Search username, phone, email" class="pl-9" />
                        </div>
                        <select v-model="filterForm.status" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">All statuses</option>
                            <option value="Active">Active</option>
                            <option value="Suspended">Suspended</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Banned">Banned</option>
                        </select>
                        <Button type="submit" :disabled="filterForm.processing">Filter</Button>
                        <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
                    </form>
                </div>

                <div class="overflow-hidden rounded-2xl border">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[860px] text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Identity</th>
                                    <th class="px-4 py-3 font-medium">Contact</th>
                                    <th class="px-4 py-3 font-medium">Service</th>
                                    <th class="px-4 py-3 font-medium">Balance</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Last Login</th>
                                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="customer in customers" :key="customer.id" class="bg-white">
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-slate-900">{{ customer.full_name }}</div>
                                        <div class="text-xs text-slate-500">{{ customer.username }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div>{{ customer.phone_number || 'No phone' }}</div>
                                        <div class="text-xs text-slate-500">{{ customer.email || 'No email' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div>{{ customer.service_type }}</div>
                                        <div class="text-xs text-slate-500">{{ customer.account_type }}</div>
                                    </td>
                                    <td class="px-4 py-4 font-medium text-slate-900">{{ formatMoney(customer.balance) }}</td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="customer.status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'">
                                            {{ customer.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-slate-500">{{ formatDate(customer.last_login_at) }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-1">
                                            <Link :href="`/admin/customers/${customer.id}`">
                                                <Button type="button" variant="ghost" size="icon-sm" title="Open customer">
                                                    <ExternalLink class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Button type="button" variant="ghost" size="icon-sm" title="Edit customer" @click="startEdit(customer)">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="customers.length === 0">
                                    <td colspan="7" class="px-4 py-12 text-center text-slate-500">No customers found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <Dialog :open="createDialogOpen" @update:open="createDialogOpen = $event">
                <DialogContent class="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Create Customer</DialogTitle>
                    </DialogHeader>

                    <form class="space-y-4" @submit.prevent="createCustomer">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label>Username</Label>
                                <Input v-model="createForm.username" />
                                <p v-if="createForm.errors.username" class="text-sm text-rose-600">{{ createForm.errors.username }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Full Name</Label>
                                <Input v-model="createForm.full_name" />
                                <p v-if="createForm.errors.full_name" class="text-sm text-rose-600">{{ createForm.errors.full_name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Email</Label>
                                <Input v-model="createForm.email" type="email" />
                                <p v-if="createForm.errors.email" class="text-sm text-rose-600">{{ createForm.errors.email }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Phone Number</Label>
                                <Input v-model="createForm.phone_number" />
                                <p v-if="createForm.errors.phone_number" class="text-sm text-rose-600">{{ createForm.errors.phone_number }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Password</Label>
                                <Input v-model="createForm.password" type="password" />
                                <p v-if="createForm.errors.password" class="text-sm text-rose-600">{{ createForm.errors.password }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Opening Balance</Label>
                                <Input v-model="createForm.balance" type="number" min="0" step="0.01" />
                                <p v-if="createForm.errors.balance" class="text-sm text-rose-600">{{ createForm.errors.balance }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Account Type</Label>
                                <select v-model="createForm.account_type" class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="Personal">Personal</option>
                                    <option value="Business">Business</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label>Service Type</Label>
                                <select v-model="createForm.service_type" class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="Hotspot">Hotspot</option>
                                    <option value="PPPoE">PPPoE</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label>Status</Label>
                                <select v-model="createForm.status" class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Suspended">Suspended</option>
                                    <option value="Disabled">Disabled</option>
                                    <option value="Banned">Banned</option>
                                    <option value="Limited">Limited</option>
                                </select>
                            </div>
                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                <input v-model="createForm.auto_renewal" type="checkbox" class="size-4 rounded border-slate-300" />
                                Enable auto renewal
                            </label>
                        </div>

                        <div class="space-y-2">
                            <Label>Address</Label>
                            <textarea v-model="createForm.address" class="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
                            <p v-if="createForm.errors.address" class="text-sm text-rose-600">{{ createForm.errors.address }}</p>
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button type="button" variant="outline">Cancel</Button>
                            </DialogClose>
                            <Button type="submit" :disabled="createForm.processing">
                                {{ createForm.processing ? 'Creating...' : 'Create Customer' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog :open="editDialogOpen" @update:open="editDialogOpen = $event">
                <DialogContent class="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Edit Customer</DialogTitle>
                    </DialogHeader>

                    <form class="space-y-4" @submit.prevent="updateCustomer">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label>Username</Label>
                                <Input v-model="editForm.username" />
                                <p v-if="editForm.errors.username" class="text-sm text-rose-600">{{ editForm.errors.username }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Full Name</Label>
                                <Input v-model="editForm.full_name" />
                                <p v-if="editForm.errors.full_name" class="text-sm text-rose-600">{{ editForm.errors.full_name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Email</Label>
                                <Input v-model="editForm.email" type="email" />
                                <p v-if="editForm.errors.email" class="text-sm text-rose-600">{{ editForm.errors.email }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Phone Number</Label>
                                <Input v-model="editForm.phone_number" />
                                <p v-if="editForm.errors.phone_number" class="text-sm text-rose-600">{{ editForm.errors.phone_number }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>New Password</Label>
                                <Input v-model="editForm.password" type="password" placeholder="Leave blank to keep current password" />
                                <p v-if="editForm.errors.password" class="text-sm text-rose-600">{{ editForm.errors.password }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Balance</Label>
                                <Input v-model="editForm.balance" type="number" min="0" step="0.01" />
                                <p v-if="editForm.errors.balance" class="text-sm text-rose-600">{{ editForm.errors.balance }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Account Type</Label>
                                <select v-model="editForm.account_type" class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="Personal">Personal</option>
                                    <option value="Business">Business</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label>Service Type</Label>
                                <select v-model="editForm.service_type" class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="Hotspot">Hotspot</option>
                                    <option value="PPPoE">PPPoE</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label>Status</Label>
                                <select v-model="editForm.status" class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Suspended">Suspended</option>
                                    <option value="Disabled">Disabled</option>
                                    <option value="Banned">Banned</option>
                                    <option value="Limited">Limited</option>
                                </select>
                            </div>
                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                                <input v-model="editForm.auto_renewal" type="checkbox" class="size-4 rounded border-slate-300" />
                                Enable auto renewal
                            </label>
                        </div>

                        <div class="space-y-2">
                            <Label>Address</Label>
                            <textarea v-model="editForm.address" class="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
                            <p v-if="editForm.errors.address" class="text-sm text-rose-600">{{ editForm.errors.address }}</p>
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button type="button" variant="outline">Cancel</Button>
                            </DialogClose>
                            <Button type="submit" :disabled="editForm.processing">
                                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
