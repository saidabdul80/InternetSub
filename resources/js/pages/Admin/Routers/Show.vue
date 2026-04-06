<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    router: {
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
    stats: {
        recharge_count: number;
        active_recharge_count: number;
        transaction_count: number;
        transaction_total: string;
    };
    recent_recharges: Array<{
        id: number;
        username: string;
        customer: string | null;
        plan_name: string;
        method: string;
        status: string;
        recharged_at: string;
        expires_at: string;
    }>;
    recent_transactions: Array<{
        id: number;
        invoice: string;
        username: string;
        customer: string | null;
        plan_name: string;
        price: string;
        method: string;
        service_type: string;
        recharged_at: string;
        expires_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Routers', href: '/admin/routers' },
    { title: props.router.name, href: `/admin/routers/${props.router.id}` },
];

const money = (value: string) => `NGN ${Number(value).toFixed(2)}`;
const fmt = (value: string | null) => (value ? new Date(value).toLocaleString() : 'Never');
</script>

<template>
    <Head :title="router.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <section class="rounded-3xl border border-slate-200 bg-[linear-gradient(135deg,rgba(14,165,233,0.12),rgba(59,130,246,0.10),transparent)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-700">Router Detail</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ router.name }}</h1>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Track subscriber activity and transaction throughput per device without leaving the admin workspace.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link href="/admin/routers">
                            <Button variant="outline">Back to Routers</Button>
                        </Link>
                        <Link href="/admin/recharges">
                            <Button>Open Recharges</Button>
                        </Link>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Status</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ router.status }}</div>
                </div>
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Enabled</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ router.enabled ? 'Yes' : 'No' }}</div>
                </div>
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Coverage</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ router.coverage }}</div>
                </div>
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Active Recharges</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ stats.active_recharge_count }}</div>
                </div>
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Transactions</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ stats.transaction_count }}</div>
                </div>
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Revenue</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ money(stats.transaction_total) }}</div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <div class="space-y-6">
                    <div class="rounded-3xl border bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-slate-950">Router Profile</h2>
                        <div class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-slate-500">IP Address</div>
                                <div class="mt-1 font-medium text-slate-950">{{ router.ip_address }}</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-slate-500">API Username</div>
                                <div class="mt-1 font-medium text-slate-950">{{ router.username }}</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-slate-500">Coordinates</div>
                                <div class="mt-1 font-medium text-slate-950">{{ router.coordinates || 'Not set' }}</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-slate-500">Last Seen</div>
                                <div class="mt-1 font-medium text-slate-950">{{ fmt(router.last_seen_at) }}</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 sm:col-span-2">
                                <div class="text-slate-500">Description</div>
                                <div class="mt-1 font-medium text-slate-950">{{ router.description || 'No description provided' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-slate-950">Recent Recharges</h2>
                        <div class="mt-5 space-y-3">
                            <div v-for="recharge in recent_recharges" :key="recharge.id" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium text-slate-950">{{ recharge.customer || recharge.username }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ recharge.plan_name }} · {{ recharge.method }}</div>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-xs font-medium" :class="recharge.status === 'on' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'">
                                        {{ recharge.status }}
                                    </span>
                                </div>
                                <div class="mt-3 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                                    <div>Recharged: {{ fmt(recharge.recharged_at) }}</div>
                                    <div>Expires: {{ fmt(recharge.expires_at) }}</div>
                                </div>
                            </div>
                            <div v-if="recent_recharges.length === 0" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                No recharge activity on this router yet.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-950">Recent Transactions</h2>
                    <div class="mt-5 space-y-3">
                        <div v-for="transaction in recent_transactions" :key="transaction.id" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-slate-950">{{ transaction.customer || transaction.username }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ transaction.plan_name }} · {{ transaction.service_type }}</div>
                                </div>
                                <div class="text-sm font-medium text-slate-950">{{ money(transaction.price) }}</div>
                            </div>
                            <div class="mt-3 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                                <div class="font-mono text-xs text-slate-500">{{ transaction.invoice }}</div>
                                <div>{{ transaction.method }}</div>
                                <div>Purchased: {{ fmt(transaction.recharged_at) }}</div>
                                <div>Expires: {{ fmt(transaction.expires_at) }}</div>
                            </div>
                        </div>
                        <div v-if="recent_transactions.length === 0" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                            No transaction history on this router yet.
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
