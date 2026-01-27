<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

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
        currency: string;
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
            </div>
        </div>
    </AppLayout>
</template>
