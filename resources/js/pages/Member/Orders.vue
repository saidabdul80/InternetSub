<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { Input } from '@/components/ui/input';
import MemberLayout from '@/layouts/MemberLayout.vue';

const props = defineProps<{
    customer: {
        id: number;
        username: string;
        full_name: string;
        email: string | null;
        phone_number: string | null;
        balance: string;
        status: string;
    };
    plans: Array<{
        id: number;
        plan_type: number;
        name: string;
        amount: number;
        currency: string;
    }>;
    transactions: Array<{
        id: number;
        invoice: string;
        plan_name: string;
        price: string;
        method: string;
        router_name: string;
        service_type: string;
        recharged_at: string;
        expires_at: string;
    }>;
    payments: Array<{
        id: number;
        reference: string;
        status: string;
        amount: number;
        gateway: string | null;
        plan_name: string | null;
        paid_at: string | null;
        created_at: string;
    }>;
}>();

const search = ref('');
const filteredTransactions = computed(() =>
    props.transactions.filter((transaction) =>
        `${transaction.invoice} ${transaction.plan_name} ${transaction.method} ${transaction.router_name}`.toLowerCase().includes(search.value.toLowerCase()),
    ),
);

const filteredPayments = computed(() =>
    props.payments.filter((payment) =>
        `${payment.reference} ${payment.plan_name ?? ''} ${payment.gateway ?? ''} ${payment.status}`.toLowerCase().includes(search.value.toLowerCase()),
    ),
);

const fmt = (value: string | null) => (value ? new Date(value).toLocaleString() : 'Pending');
const money = (value: string) => `NGN ${Number(value).toFixed(2)}`;
const amount = (value: number) => `NGN ${(value / 100).toFixed(2)}`;
</script>

<template>
    <MemberLayout title="Orders & History" :customer="customer">
        <div class="space-y-6">
            <section class="rounded-3xl border border-white/10 bg-[linear-gradient(135deg,rgba(56,189,248,0.16),rgba(251,191,36,0.10),transparent)] p-6 backdrop-blur">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-200">Orders & Billing</p>
                        <h1 class="mt-2 text-3xl font-semibold text-white">Browse plans, inspect purchases, and verify payment attempts from one screen.</h1>
                        <p class="mt-3 text-sm leading-6 text-slate-200">
                            Direct member checkout is still being migrated, but the browsing and history side now has a cleaner review flow.
                        </p>
                    </div>
                    <Link href="/app" class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white transition hover:-translate-y-0.5 hover:bg-white/15">
                        Buy or Renew Plan
                    </Link>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                    <div class="text-sm text-slate-300">Available Plans</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ plans.length }}</div>
                </div>
                <div class="rounded-3xl border border-emerald-400/20 bg-emerald-400/10 p-5 backdrop-blur">
                    <div class="text-sm text-emerald-100">Transactions</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ transactions.length }}</div>
                </div>
                <div class="rounded-3xl border border-cyan-400/20 bg-cyan-400/10 p-5 backdrop-blur">
                    <div class="text-sm text-cyan-100">Payment Attempts</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ payments.length }}</div>
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-white">History Search</h2>
                        <p class="mt-1 text-sm text-slate-300">Filter invoices, plan names, references, or gateways across both tables below.</p>
                    </div>
                    <Input v-model="search" placeholder="Search invoice, plan, reference, gateway" class="border-white/10 bg-white/5 text-white lg:min-w-96" />
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                <h2 class="text-xl font-semibold text-white">Available Plans</h2>
                <p class="mt-1 text-sm text-slate-300">Browse what is available now, then use the purchase flow when you want to renew.</p>

                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div v-for="plan in plans" :key="plan.id" class="rounded-2xl border border-white/10 bg-slate-950/30 p-4">
                        <div class="text-sm text-cyan-200">Plan {{ plan.plan_type }}</div>
                        <div class="mt-2 text-lg font-semibold text-white">{{ plan.name }}</div>
                        <div class="mt-4 text-2xl font-semibold text-white">{{ amount(plan.amount) }}</div>
                        <Link href="/app" class="mt-4 inline-flex rounded-xl border border-white/10 px-3 py-2 text-sm text-slate-100 transition hover:bg-white/5">
                            Select Plan
                        </Link>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                <h2 class="text-xl font-semibold text-white">Order History</h2>
                <div class="mt-5 overflow-hidden rounded-2xl border border-white/10">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px] text-sm">
                            <thead class="bg-white/5 text-left text-slate-300">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Invoice</th>
                                    <th class="px-4 py-3 font-medium">Plan</th>
                                    <th class="px-4 py-3 font-medium">Amount</th>
                                    <th class="px-4 py-3 font-medium">Method</th>
                                    <th class="px-4 py-3 font-medium">Router</th>
                                    <th class="px-4 py-3 font-medium">Purchased</th>
                                    <th class="px-4 py-3 font-medium">Expires</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <tr v-for="transaction in filteredTransactions" :key="transaction.id">
                                    <td class="px-4 py-4 font-mono text-white">{{ transaction.invoice }}</td>
                                    <td class="px-4 py-4">
                                        <div class="text-white">{{ transaction.plan_name }}</div>
                                        <div class="text-xs text-slate-400">{{ transaction.service_type }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-200">{{ money(transaction.price) }}</td>
                                    <td class="px-4 py-4 text-slate-300">{{ transaction.method }}</td>
                                    <td class="px-4 py-4 text-slate-300">{{ transaction.router_name || 'Unassigned' }}</td>
                                    <td class="px-4 py-4 text-slate-400">{{ fmt(transaction.recharged_at) }}</td>
                                    <td class="px-4 py-4 text-slate-400">{{ fmt(transaction.expires_at) }}</td>
                                </tr>
                                <tr v-if="filteredTransactions.length === 0">
                                    <td colspan="7" class="px-4 py-12 text-center text-slate-400">No completed transactions yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                <h2 class="text-xl font-semibold text-white">Payment Attempts</h2>
                <div class="mt-5 overflow-hidden rounded-2xl border border-white/10">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-sm">
                            <thead class="bg-white/5 text-left text-slate-300">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Reference</th>
                                    <th class="px-4 py-3 font-medium">Plan</th>
                                    <th class="px-4 py-3 font-medium">Amount</th>
                                    <th class="px-4 py-3 font-medium">Gateway</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <tr v-for="payment in filteredPayments" :key="payment.id">
                                    <td class="px-4 py-4 font-mono text-white">{{ payment.reference }}</td>
                                    <td class="px-4 py-4 text-slate-200">{{ payment.plan_name || 'Plan unavailable' }}</td>
                                    <td class="px-4 py-4 text-slate-200">{{ amount(payment.amount) }}</td>
                                    <td class="px-4 py-4 text-slate-300">{{ payment.gateway || 'manual' }}</td>
                                    <td class="px-4 py-4 text-slate-300">{{ payment.status }}</td>
                                    <td class="px-4 py-4 text-slate-400">{{ fmt(payment.paid_at || payment.created_at) }}</td>
                                </tr>
                                <tr v-if="filteredPayments.length === 0">
                                    <td colspan="6" class="px-4 py-12 text-center text-slate-400">No payment attempts linked to your phone number.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </MemberLayout>
</template>
