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

type RouterRecord = {
    id: number;
    name: string;
    ip_address: string;
    username: string;
    description: string | null;
    coordinates: string;
    status: string;
    coverage: string;
    enabled: boolean;
    last_seen_at: string | null;
    created_at: string;
};

const props = defineProps<{
    routers: RouterRecord[];
    filters: {
        search: string;
        status: string;
    };
    stats: {
        total: number;
        online: number;
        offline: number;
        enabled: number;
    };
}>();

const page = usePage();
const flash = page.props.flash as { success?: string; error?: string };
const createDialogOpen = ref(false);
const editDialogOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Routers', href: '/admin/routers' },
];

const filterForm = useForm({
    search: props.filters.search,
    status: props.filters.status,
});

const createForm = useForm({
    name: '',
    ip_address: '',
    username: '',
    password: '',
    description: '',
    coordinates: '',
    status: 'Online',
    coverage: '0',
    enabled: true,
});

const editForm = useForm({
    id: 0,
    name: '',
    ip_address: '',
    username: '',
    password: '',
    description: '',
    coordinates: '',
    status: 'Online',
    coverage: '0',
    enabled: true,
});

const formatDate = (value: string | null) => (value ? new Date(value).toLocaleString() : 'Never');

const applyFilters = () => {
    filterForm.get('/admin/routers', {
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

const createRouter = () => {
    createForm.post('/admin/routers', {
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

const startEdit = (router: RouterRecord) => {
    editForm.id = router.id;
    editForm.name = router.name;
    editForm.ip_address = router.ip_address;
    editForm.username = router.username;
    editForm.password = '';
    editForm.description = router.description ?? '';
    editForm.coordinates = router.coordinates;
    editForm.status = router.status;
    editForm.coverage = router.coverage;
    editForm.enabled = router.enabled;
    editForm.clearErrors();
    editDialogOpen.value = true;
};

const updateRouter = () => {
    editForm.put(`/admin/routers/${editForm.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false;
        },
    });
};
</script>

<template>
    <Head title="Routers" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <section class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-950">Routers</h1>
                    <p class="text-sm text-slate-500">Keep the device table in focus and edit from modal actions.</p>
                </div>
                <Button type="button" class="gap-2" @click="startCreate">
                    <Plus class="h-4 w-4" />
                    Create Router
                </Button>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Routers</div>
                    <div class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.total }}</div>
                </div>
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <div class="text-sm text-emerald-700">Online</div>
                    <div class="mt-2 text-3xl font-semibold text-emerald-950">{{ stats.online }}</div>
                </div>
                <div class="rounded-3xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                    <div class="text-sm text-rose-700">Offline</div>
                    <div class="mt-2 text-3xl font-semibold text-rose-950">{{ stats.offline }}</div>
                </div>
                <div class="rounded-3xl border border-cyan-200 bg-cyan-50 p-5 shadow-sm">
                    <div class="text-sm text-cyan-700">Enabled</div>
                    <div class="mt-2 text-3xl font-semibold text-cyan-950">{{ stats.enabled }}</div>
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
                        <h2 class="text-xl font-semibold text-slate-900">Router Inventory</h2>
                        <p class="text-sm text-slate-500">Keep search and actions on top of the table.</p>
                    </div>

                    <form class="grid gap-3 sm:grid-cols-[minmax(260px,1fr)_180px_auto_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input v-model="filterForm.search" placeholder="Search router name or IP" class="pl-9" />
                        </div>
                        <select v-model="filterForm.status" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">All statuses</option>
                            <option value="Online">Online</option>
                            <option value="Offline">Offline</option>
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
                                    <th class="px-4 py-3 font-medium">Router</th>
                                    <th class="px-4 py-3 font-medium">Access</th>
                                    <th class="px-4 py-3 font-medium">Coverage</th>
                                    <th class="px-4 py-3 font-medium">State</th>
                                    <th class="px-4 py-3 font-medium">Last Seen</th>
                                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="router in routers" :key="router.id">
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-slate-900">{{ router.name }}</div>
                                        <div class="text-xs text-slate-500">{{ router.description || 'No description' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div>{{ router.ip_address }}</div>
                                        <div class="text-xs text-slate-500">{{ router.username }}</div>
                                    </td>
                                    <td class="px-4 py-4">{{ router.coverage }}</td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="router.status === 'Online' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'">
                                            {{ router.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-slate-500">{{ formatDate(router.last_seen_at) }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-1">
                                            <Link :href="`/admin/routers/${router.id}`">
                                                <Button type="button" variant="ghost" size="icon-sm" title="Open router">
                                                    <ExternalLink class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Button type="button" variant="ghost" size="icon-sm" title="Edit router" @click="startEdit(router)">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="routers.length === 0">
                                    <td colspan="6" class="px-4 py-12 text-center text-slate-500">No routers found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <Dialog :open="createDialogOpen" @update:open="createDialogOpen = $event">
                <DialogContent class="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Create Router</DialogTitle>
                    </DialogHeader>

                    <form class="space-y-4" @submit.prevent="createRouter">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label>Name</Label>
                                <Input v-model="createForm.name" />
                                <p v-if="createForm.errors.name" class="text-sm text-rose-600">{{ createForm.errors.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>IP Address</Label>
                                <Input v-model="createForm.ip_address" />
                                <p v-if="createForm.errors.ip_address" class="text-sm text-rose-600">{{ createForm.errors.ip_address }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Username</Label>
                                <Input v-model="createForm.username" />
                                <p v-if="createForm.errors.username" class="text-sm text-rose-600">{{ createForm.errors.username }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Password</Label>
                                <Input v-model="createForm.password" type="password" />
                                <p v-if="createForm.errors.password" class="text-sm text-rose-600">{{ createForm.errors.password }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Status</Label>
                                <select v-model="createForm.status" class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="Online">Online</option>
                                    <option value="Offline">Offline</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label>Coverage</Label>
                                <Input v-model="createForm.coverage" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label>Description</Label>
                            <Input v-model="createForm.description" />
                            <p v-if="createForm.errors.description" class="text-sm text-rose-600">{{ createForm.errors.description }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label>Coordinates</Label>
                            <Input v-model="createForm.coordinates" placeholder="lat,lng" />
                            <p v-if="createForm.errors.coordinates" class="text-sm text-rose-600">{{ createForm.errors.coordinates }}</p>
                        </div>
                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                            <input v-model="createForm.enabled" type="checkbox" class="size-4 rounded border-slate-300" />
                            Router enabled
                        </label>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button type="button" variant="outline">Cancel</Button>
                            </DialogClose>
                            <Button type="submit" :disabled="createForm.processing">
                                {{ createForm.processing ? 'Creating...' : 'Create Router' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog :open="editDialogOpen" @update:open="editDialogOpen = $event">
                <DialogContent class="sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Edit Router</DialogTitle>
                    </DialogHeader>

                    <form class="space-y-4" @submit.prevent="updateRouter">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label>Name</Label>
                                <Input v-model="editForm.name" />
                                <p v-if="editForm.errors.name" class="text-sm text-rose-600">{{ editForm.errors.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>IP Address</Label>
                                <Input v-model="editForm.ip_address" />
                                <p v-if="editForm.errors.ip_address" class="text-sm text-rose-600">{{ editForm.errors.ip_address }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Username</Label>
                                <Input v-model="editForm.username" />
                                <p v-if="editForm.errors.username" class="text-sm text-rose-600">{{ editForm.errors.username }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Password</Label>
                                <Input v-model="editForm.password" type="password" placeholder="Leave blank to keep current password" />
                                <p v-if="editForm.errors.password" class="text-sm text-rose-600">{{ editForm.errors.password }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Status</Label>
                                <select v-model="editForm.status" class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="Online">Online</option>
                                    <option value="Offline">Offline</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label>Coverage</Label>
                                <Input v-model="editForm.coverage" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label>Description</Label>
                            <Input v-model="editForm.description" />
                            <p v-if="editForm.errors.description" class="text-sm text-rose-600">{{ editForm.errors.description }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label>Coordinates</Label>
                            <Input v-model="editForm.coordinates" />
                            <p v-if="editForm.errors.coordinates" class="text-sm text-rose-600">{{ editForm.errors.coordinates }}</p>
                        </div>
                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 text-sm text-slate-700">
                            <input v-model="editForm.enabled" type="checkbox" class="size-4 rounded border-slate-300" />
                            Router enabled
                        </label>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button type="button" variant="outline">Cancel</Button>
                            </DialogClose>
                            <Button type="submit" :disabled="editForm.processing">
                                {{ editForm.processing ? 'Saving...' : 'Save Router' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
