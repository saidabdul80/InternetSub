<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

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
        auto_renewal: boolean;
    };
    active_recharges: Array<{
        id: number;
        plan_name: string;
        router_name: string;
        method: string;
        recharged_at: string;
        expires_at: string;
        status: string;
    }>;
    recent_transactions: Array<{
        id: number;
        invoice: string;
        plan_name: string;
        price: string;
        method: string;
        created_at: string;
        expires_at: string;
    }>;
    recent_payments: Array<{
        id: number;
        reference: string;
        amount: number;
        status: string;
        gateway: string | null;
        paid_at: string | null;
        created_at: string;
    }>;
    messages: Array<{
        id: number;
        subject: string;
        from_type: string;
        read_at: string | null;
        created_at: string;
    }>;
}>();

const money = (value: string) => `NGN ${Number(value).toFixed(2)}`;
const amount = (value: number) => `NGN ${(value / 100).toFixed(2)}`;
const fmt = (value: string | null) => (value ? new Date(value).toLocaleString() : 'Pending');
</script>

<template>
    <MemberLayout title="Member Dashboard" :customer="customer">
        <div class="space-y-6">
            <section class="rounded-3xl border border-white/10 bg-[linear-gradient(135deg,rgba(14,165,233,0.18),rgba(34,197,94,0.08),transparent)] p-6 backdrop-blur">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-200">Member Home</p>
                        <h1 class="mt-2 text-3xl font-semibold text-white">Track your service, buy more data, and connect fast.</h1>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                        <a href="/member/connect" class="rounded-2xl bg-cyan-400 px-4 py-3 text-center text-sm font-medium text-slate-950 transition hover:bg-cyan-300">
                            Connect Internet
                        </a>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                    <div class="text-sm text-slate-300">Balance</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ money(customer.balance) }}</div>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                    <div class="text-sm text-slate-300">Active Packages</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ active_recharges.length }}</div>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                    <div class="text-sm text-slate-300">Auto Renewal</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ customer.auto_renewal ? 'On' : 'Off' }}</div>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                    <div class="text-sm text-slate-300">Account Status</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ customer.status }}</div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <h2 class="text-xl font-semibold text-white">Active Packages</h2>
                    <p class="mt-1 text-sm text-slate-300">Your currently active connectivity and recharge records.</p>

                    <div class="mt-5 space-y-4">
                        <div v-for="item in active_recharges" :key="item.id" class="rounded-2xl border border-white/10 bg-slate-950/30 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="text-lg font-medium text-white">{{ item.plan_name }}</div>
                                    <div class="text-sm text-slate-300">{{ item.router_name || 'No router assigned' }} · {{ item.method }}</div>
                                </div>
                                <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-medium text-emerald-200">{{ item.status }}</span>
                            </div>
                            <div class="mt-4 grid gap-3 text-sm text-slate-300 sm:grid-cols-2">
                                <div>Recharged: {{ fmt(item.recharged_at) }}</div>
                                <div>Expires: {{ fmt(item.expires_at) }}</div>
                            </div>
                        </div>
                        <div v-if="active_recharges.length === 0" class="rounded-2xl border border-dashed border-white/10 px-4 py-10 text-center text-sm text-slate-400">
                            No active packages yet.
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                        <h2 class="text-xl font-semibold text-white">Recent Payments</h2>
                        <div class="mt-5 space-y-3">
                            <div v-for="payment in recent_payments" :key="payment.id" class="rounded-2xl border border-white/10 bg-slate-950/30 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="font-mono text-sm text-white">{{ payment.reference }}</div>
                                    <div class="text-sm text-slate-200">{{ amount(payment.amount) }}</div>
                                </div>
                                <div class="mt-2 text-xs text-slate-400">{{ payment.gateway || 'manual' }} · {{ payment.status }} · {{ fmt(payment.paid_at || payment.created_at) }}</div>
                            </div>
                            <div v-if="recent_payments.length === 0" class="text-sm text-slate-400">No payment records linked to your phone number yet.</div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                        <h2 class="text-xl font-semibold text-white">Inbox</h2>
                        <div class="mt-5 space-y-3">
                            <div v-for="message in messages" :key="message.id" class="rounded-2xl border border-white/10 bg-slate-950/30 p-4">
                                <div class="font-medium text-white">{{ message.subject }}</div>
                                <div class="mt-2 text-xs text-slate-400">{{ message.from_type }} · {{ fmt(message.created_at) }} · {{ message.read_at ? 'Read' : 'Unread' }}</div>
                            </div>
                            <div v-if="messages.length === 0" class="text-sm text-slate-400">No inbox messages yet.</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                <h2 class="text-xl font-semibold text-white">Recent Transactions</h2>
                <div class="mt-5 overflow-hidden rounded-2xl border border-white/10">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-sm">
                            <thead class="bg-white/5 text-left text-slate-300">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Invoice</th>
                                    <th class="px-4 py-3 font-medium">Plan</th>
                                    <th class="px-4 py-3 font-medium">Amount</th>
                                    <th class="px-4 py-3 font-medium">Method</th>
                                    <th class="px-4 py-3 font-medium">Purchased</th>
                                    <th class="px-4 py-3 font-medium">Expiry</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/10">
                                <tr v-for="transaction in recent_transactions" :key="transaction.id">
                                    <td class="px-4 py-4 font-mono text-white">{{ transaction.invoice }}</td>
                                    <td class="px-4 py-4 text-slate-200">{{ transaction.plan_name }}</td>
                                    <td class="px-4 py-4 text-slate-200">{{ money(transaction.price) }}</td>
                                    <td class="px-4 py-4 text-slate-300">{{ transaction.method }}</td>
                                    <td class="px-4 py-4 text-slate-400">{{ fmt(transaction.created_at) }}</td>
                                    <td class="px-4 py-4 text-slate-400">{{ fmt(transaction.expires_at) }}</td>
                                </tr>
                                <tr v-if="recent_transactions.length === 0">
                                    <td colspan="6" class="px-4 py-12 text-center text-slate-400">No transactions yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </MemberLayout>
</template>
