<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    stats: {
        today_count: number;
        month_count: number;
        year_count: number;
        total_subscribers: number;
        total_amount: number;
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
                    <div class="text-sm text-muted-foreground">This Month</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ props.stats.month_count }}
                    </div>
                    <div class="text-xs text-muted-foreground">
                        payments
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
        </div>
    </AppLayout>
</template>
