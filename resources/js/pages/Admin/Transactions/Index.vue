<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type Transaction = {
    id: number;
    invoice: string;
    username: string;
    plan_name: string;
    price: string;
    method: string;
    router_name: string;
    service_type: string;
    note: string;
    recharged_at: string;
    expires_at: string;
    customer: string | null;
    admin: string | null;
};

const props = defineProps<{
    transactions: Transaction[];
    filters: {
        search: string;
    };
    stats: {
        total: number;
        revenue: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Transactions', href: '/admin/transactions' },
];

const filterForm = useForm({
    search: props.filters.search,
});

const applyFilters = () => {
    filterForm.get('/admin/transactions', {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    applyFilters();
};

const formatMoney = (value: string) => `NGN ${Number(value).toFixed(2)}`;
const formatDate = (value: string) => new Date(value).toLocaleString();
</script>

<template>
    <Head title="Transactions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <section class="grid gap-4 md:grid-cols-2">
                <div class="rounded-3xl border bg-white p-6 shadow-sm">
                    <div class="text-sm text-slate-500">Transactions</div>
                    <div class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.total }}</div>
                </div>
                <div class="rounded-3xl border bg-white p-6 shadow-sm">
                    <div class="text-sm text-slate-500">Revenue Captured</div>
                    <div class="mt-2 text-3xl font-semibold text-slate-900">{{ formatMoney(stats.revenue) }}</div>
                </div>
            </section>

            <section class="rounded-3xl border bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Transaction Ledger</h2>
                        <p class="text-sm text-slate-500">Review invoice history, plan activations, and recharge metadata.</p>
                    </div>
                    <form class="flex w-full max-w-xl gap-3" @submit.prevent="applyFilters">
                        <Input v-model="filterForm.search" placeholder="Search invoice, username, plan, router" />
                        <Button type="submit" :disabled="filterForm.processing">Filter</Button>
                        <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
                    </form>
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px] text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Invoice</th>
                                    <th class="px-4 py-3 font-medium">Customer</th>
                                    <th class="px-4 py-3 font-medium">Plan</th>
                                    <th class="px-4 py-3 font-medium">Amount</th>
                                    <th class="px-4 py-3 font-medium">Method</th>
                                    <th class="px-4 py-3 font-medium">Router</th>
                                    <th class="px-4 py-3 font-medium">Recharge</th>
                                    <th class="px-4 py-3 font-medium">Expiry</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="transaction in transactions" :key="transaction.id">
                                    <td class="px-4 py-4 font-mono text-slate-900">{{ transaction.invoice }}</td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-slate-900">{{ transaction.customer || transaction.username }}</div>
                                        <div class="text-xs text-slate-500">{{ transaction.username }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-slate-900">{{ transaction.plan_name }}</div>
                                        <div class="text-xs text-slate-500">{{ transaction.service_type }}</div>
                                    </td>
                                    <td class="px-4 py-4 font-medium text-slate-900">{{ formatMoney(transaction.price) }}</td>
                                    <td class="px-4 py-4">
                                        <div>{{ transaction.method }}</div>
                                        <div class="text-xs text-slate-500">{{ transaction.admin || 'System' }}</div>
                                    </td>
                                    <td class="px-4 py-4">{{ transaction.router_name || 'Unassigned' }}</td>
                                    <td class="px-4 py-4 text-slate-500">{{ formatDate(transaction.recharged_at) }}</td>
                                    <td class="px-4 py-4 text-slate-500">{{ formatDate(transaction.expires_at) }}</td>
                                </tr>
                                <tr v-if="transactions.length === 0">
                                    <td colspan="8" class="px-4 py-12 text-center text-slate-500">No transactions found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
