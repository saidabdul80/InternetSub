<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    customer: {
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
        city: string | null;
        district: string | null;
        state: string | null;
        zip: string | null;
        created_at: string;
        last_login_at: string | null;
    };
    stats: {
        recharge_count: number;
        active_recharge_count: number;
        transaction_count: number;
        transaction_total: string;
        message_count: number;
        unread_message_count: number;
    };
    recent_recharges: Array<{
        id: number;
        plan_name: string;
        router_name: string | null;
        method: string;
        status: string;
        recharged_at: string;
        expires_at: string;
    }>;
    recent_transactions: Array<{
        id: number;
        invoice: string;
        plan_name: string;
        price: string;
        method: string;
        router_name: string | null;
        service_type: string;
        recharged_at: string;
        expires_at: string;
        note: string | null;
    }>;
    messages: Array<{
        id: number;
        subject: string;
        body: string | null;
        from_type: string;
        read_at: string | null;
        created_at: string;
    }>;
    payments: Array<{
        id: number;
        reference: string;
        amount: number;
        status: string;
        gateway: string | null;
        paid_at: string | null;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Customers', href: '/admin/customers' },
    { title: props.customer.full_name, href: `/admin/customers/${props.customer.id}` },
];

const money = (value: string) => `NGN ${Number(value).toFixed(2)}`;
const amount = (value: number) => `NGN ${(value / 100).toFixed(2)}`;
const fmt = (value: string | null) => (value ? new Date(value).toLocaleString() : 'Never');
const fullAddress = [props.customer.address, props.customer.city, props.customer.district, props.customer.state, props.customer.zip]
    .filter(Boolean)
    .join(', ');
</script>

<template>
    <Head :title="customer.full_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <section class="rounded-3xl border border-slate-200 bg-[linear-gradient(135deg,rgba(14,165,233,0.12),rgba(16,185,129,0.10),transparent)] p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-700">Customer Profile</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ customer.full_name }}</h1>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Inspect subscriber health, recent package activity, billing history, and member inbox context in one place.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link href="/admin/customers">
                            <Button variant="outline">Back to Customers</Button>
                        </Link>
                        <Link href="/admin/recharges">
                            <Button>Review Recharges</Button>
                        </Link>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Status</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ customer.status }}</div>
                </div>
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Balance</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ money(customer.balance) }}</div>
                </div>
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Active Packages</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ stats.active_recharge_count }}</div>
                </div>
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Transactions</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ stats.transaction_count }}</div>
                </div>
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Spend</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ money(stats.transaction_total) }}</div>
                </div>
                <div class="rounded-3xl border bg-white p-5 shadow-sm">
                    <div class="text-sm text-slate-500">Unread Messages</div>
                    <div class="mt-2 text-2xl font-semibold text-slate-950">{{ stats.unread_message_count }}</div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <div class="space-y-6">
                    <div class="rounded-3xl border bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-slate-950">Identity</h2>
                        <div class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-slate-500">Username</div>
                                <div class="mt-1 font-medium text-slate-950">{{ customer.username }}</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-slate-500">Phone</div>
                                <div class="mt-1 font-medium text-slate-950">{{ customer.phone_number || 'Not set' }}</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-slate-500">Email</div>
                                <div class="mt-1 font-medium text-slate-950">{{ customer.email || 'Not set' }}</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-slate-500">Account</div>
                                <div class="mt-1 font-medium text-slate-950">{{ customer.account_type }} · {{ customer.service_type }}</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-slate-500">Auto Renewal</div>
                                <div class="mt-1 font-medium text-slate-950">{{ customer.auto_renewal ? 'Enabled' : 'Disabled' }}</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <div class="text-slate-500">Last Login</div>
                                <div class="mt-1 font-medium text-slate-950">{{ fmt(customer.last_login_at) }}</div>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4 sm:col-span-2">
                                <div class="text-slate-500">Address</div>
                                <div class="mt-1 font-medium text-slate-950">{{ fullAddress || 'No address on record' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-950">Message Timeline</h2>
                                <p class="mt-1 text-sm text-slate-500">Recent support and system notices tied to this customer.</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ stats.message_count }} total</span>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div v-for="message in messages" :key="message.id" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium text-slate-950">{{ message.subject }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ message.from_type }} · {{ fmt(message.created_at) }}</div>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-xs font-medium" :class="message.read_at ? 'bg-slate-200 text-slate-700' : 'bg-amber-100 text-amber-800'">
                                        {{ message.read_at ? 'Read' : 'Unread' }}
                                    </span>
                                </div>
                                <p v-if="message.body" class="mt-3 text-sm leading-6 text-slate-600">{{ message.body }}</p>
                            </div>
                            <div v-if="messages.length === 0" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                No customer messages yet.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl border bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-slate-950">Recent Recharges</h2>
                        <div class="mt-5 space-y-3">
                            <div v-for="recharge in recent_recharges" :key="recharge.id" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium text-slate-950">{{ recharge.plan_name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ recharge.router_name || 'Unassigned router' }} · {{ recharge.method }}</div>
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
                                No recharge activity yet.
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-slate-950">Recent Transactions</h2>
                        <div class="mt-5 space-y-3">
                            <div v-for="transaction in recent_transactions" :key="transaction.id" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium text-slate-950">{{ transaction.plan_name }}</div>
                                        <div class="mt-1 font-mono text-xs text-slate-500">{{ transaction.invoice }}</div>
                                    </div>
                                    <div class="text-sm font-medium text-slate-950">{{ money(transaction.price) }}</div>
                                </div>
                                <div class="mt-3 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                                    <div>{{ transaction.method }} · {{ transaction.service_type }}</div>
                                    <div>{{ transaction.router_name || 'Unassigned router' }}</div>
                                    <div>Purchased: {{ fmt(transaction.recharged_at) }}</div>
                                    <div>Expires: {{ fmt(transaction.expires_at) }}</div>
                                </div>
                                <p v-if="transaction.note" class="mt-3 text-sm text-slate-500">{{ transaction.note }}</p>
                            </div>
                            <div v-if="recent_transactions.length === 0" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                No transaction history yet.
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-slate-950">Recent Payments</h2>
                        <div class="mt-5 space-y-3">
                            <div v-for="payment in payments" :key="payment.id" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="font-mono text-sm text-slate-950">{{ payment.reference }}</div>
                                    <div class="text-sm font-medium text-slate-950">{{ amount(payment.amount) }}</div>
                                </div>
                                <div class="mt-2 text-xs text-slate-500">{{ payment.gateway || 'manual' }} · {{ payment.status }} · {{ fmt(payment.paid_at || payment.created_at) }}</div>
                            </div>
                            <div v-if="payments.length === 0" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                No payment records found for this customer phone number.
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
