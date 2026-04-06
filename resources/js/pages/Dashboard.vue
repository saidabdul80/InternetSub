<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    stats: {
        today_count: number;
        today_amount: number;
        month_count: number;
        month_amount: number;
        year_count: number;
        year_amount: number;
        total_subscribers: number;
        total_amount: number;
        total_payments: number;
        paid_payments: number;
        pending_payments: number;
        failed_payments: number;
        voucher_totals: {
            total: number;
            available: number;
            reserved: number;
            used: number;
        };
        expected_amount: number;
        currency: string;
        customer_count: number;
        active_customer_count: number;
        router_count: number;
        online_router_count: number;
        transaction_count: number;
        recharge_count: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div class="rounded-3xl border border-sidebar-border/70 bg-[linear-gradient(135deg,rgba(14,165,233,0.12),rgba(34,197,94,0.08),transparent)] p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-600">Platform Overview</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Billing, provisioning, customers, and member self-service in one workspace.</h1>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            This is now the migration hub for the Laravel replacement. Use the quick actions below to move between inventory, subscribers, recharges, and transaction review.
                        </p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <Link href="/admin/customers" class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 text-sm font-medium text-slate-900 shadow-sm transition hover:-translate-y-0.5 hover:shadow">
                            Manage Customers
                        </Link>
                        <Link href="/admin/routers" class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 text-sm font-medium text-slate-900 shadow-sm transition hover:-translate-y-0.5 hover:shadow">
                            Review Routers
                        </Link>
                        <Link href="/admin/recharges" class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 text-sm font-medium text-slate-900 shadow-sm transition hover:-translate-y-0.5 hover:shadow">
                            Audit Recharges
                        </Link>
                        <Link href="/admin/transactions" class="rounded-2xl border border-white/70 bg-white/80 px-4 py-3 text-sm font-medium text-slate-900 shadow-sm transition hover:-translate-y-0.5 hover:shadow">
                            Open Ledger
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Today</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.today_count }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        payments
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Today Revenue</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{
                            props.stats.currency +
                            ' ' +
                            (props.stats.today_amount / 100).toFixed(2)
                        }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        generated
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">This Month</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.month_count }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        payments
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Month Revenue</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{
                            props.stats.currency +
                            ' ' +
                            (props.stats.month_amount / 100).toFixed(2)
                        }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        generated
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">This Year</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.year_count }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        payments
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Year Revenue</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{
                            props.stats.currency +
                            ' ' +
                            (props.stats.year_amount / 100).toFixed(2)
                        }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        generated
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">
                        Total Subscribers
                    </div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.total_subscribers }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        unique phone numbers
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">
                        Total Amount
                    </div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{
                            props.stats.currency +
                            ' ' +
                            (props.stats.total_amount / 100).toFixed(2)
                        }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        generated
                    </div>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Total Payments</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.total_payments }}
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Paid Payments</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.paid_payments }}
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Pending Payments</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.pending_payments }}
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Failed Payments</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.failed_payments }}
                    </div>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Vouchers Total</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.voucher_totals.total }}
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Available Vouchers</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.voucher_totals.available }}
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Reserved Vouchers</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.voucher_totals.reserved }}
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Used Vouchers</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.voucher_totals.used }}
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Expected Amount</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{
                            props.stats.currency +
                            ' ' +
                            (props.stats.expected_amount / 100).toFixed(2)
                        }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        based on all vouchers
                    </div>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Customers</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.customer_count }}
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Active Customers</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.active_customer_count }}
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Routers</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.router_count }}
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Online Routers</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.online_router_count }}
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Transactions</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.transaction_count }}
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 p-5">
                    <div class="text-sm text-muted-foreground">Recharges</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.recharge_count }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
