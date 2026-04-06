<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type Recharge = {
    id: number;
    username: string;
    customer: string | null;
    plan_name: string;
    router_name: string;
    method: string;
    service_type: string;
    status: string;
    recharged_at: string;
    expires_at: string;
    admin: string | null;
};

const props = defineProps<{
    recharges: Recharge[];
    filters: {
        search: string;
        status: string;
    };
    stats: {
        total: number;
        active: number;
        inactive: number;
        expiring_soon: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Recharges', href: '/admin/recharges' },
];

const filterForm = useForm({
    search: props.filters.search,
    status: props.filters.status,
});

const applyFilters = () => {
    filterForm.get('/admin/recharges', {
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

const fmt = (value: string) => new Date(value).toLocaleString();
</script>

<template>
    <Head title="Recharges" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Recharge Records</div>
                    <div class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.total }}</div>
                </div>
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <div class="text-sm text-emerald-700">Active</div>
                    <div class="mt-2 text-3xl font-semibold text-emerald-950">{{ stats.active }}</div>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
                    <div class="text-sm text-slate-600">Inactive</div>
                    <div class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.inactive }}</div>
                </div>
                <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <div class="text-sm text-amber-700">Expiring Soon</div>
                    <div class="mt-2 text-3xl font-semibold text-amber-950">{{ stats.expiring_soon }}</div>
                </div>
            </section>

            <section class="rounded-3xl border bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Package Activity</h2>
                        <p class="mt-1 text-sm text-slate-500">Track what has been provisioned, how it was recharged, and when it expires.</p>
                    </div>
                    <form class="flex w-full max-w-2xl flex-wrap gap-3" @submit.prevent="applyFilters">
                        <Input v-model="filterForm.search" placeholder="Search user, plan, router, method" class="min-w-56 flex-1" />
                        <select v-model="filterForm.status" class="h-10 rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">All statuses</option>
                            <option value="on">On</option>
                            <option value="off">Off</option>
                        </select>
                        <Button type="submit" :disabled="filterForm.processing">Filter</Button>
                        <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
                    </form>
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px] text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Subscriber</th>
                                    <th class="px-4 py-3 font-medium">Plan</th>
                                    <th class="px-4 py-3 font-medium">Router</th>
                                    <th class="px-4 py-3 font-medium">Method</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Recharged</th>
                                    <th class="px-4 py-3 font-medium">Expires</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="recharge in recharges" :key="recharge.id">
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-slate-900">{{ recharge.customer || recharge.username }}</div>
                                        <div class="text-xs text-slate-500">{{ recharge.username }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-slate-900">{{ recharge.plan_name }}</div>
                                        <div class="text-xs text-slate-500">{{ recharge.service_type }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">{{ recharge.router_name || 'Unassigned' }}</td>
                                    <td class="px-4 py-4">
                                        <div>{{ recharge.method }}</div>
                                        <div class="text-xs text-slate-500">{{ recharge.admin || 'System' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="recharge.status === 'on' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'">
                                            {{ recharge.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-slate-500">{{ fmt(recharge.recharged_at) }}</td>
                                    <td class="px-4 py-4 text-slate-500">{{ fmt(recharge.expires_at) }}</td>
                                </tr>
                                <tr v-if="recharges.length === 0">
                                    <td colspan="7" class="px-4 py-12 text-center text-slate-500">No recharge records found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
