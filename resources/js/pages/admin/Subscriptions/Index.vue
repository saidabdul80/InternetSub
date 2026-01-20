<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { create, index } from '@/routes/admin/subscriptions';

interface SubscriptionRow {
    id: number;
    status: string;
    starts_at: string | null;
    expires_at: string | null;
    user: {
        name: string;
        email: string | null;
    };
    plan: {
        name: string;
    };
    payment: {
        status: string;
    } | null;
}

interface Props {
    subscriptions: {
        data: SubscriptionRow[];
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin dashboard',
        href: '/admin',
    },
    {
        title: 'Subscriptions',
        href: index().url,
    },
];

const formatDate = (value: string | null) =>
    value ? new Date(value).toLocaleString() : '—';
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Subscriptions" />

        <div class="flex flex-col gap-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        Subscriptions
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Track active and pending hotspot access.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="create().url">Manual subscribe</Link>
                </Button>
            </div>

            <div class="overflow-hidden rounded-lg border border-border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Period</th>
                            <th class="px-4 py-3">Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="subscription in subscriptions.data"
                            :key="subscription.id"
                            class="border-t border-border"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ subscription.user.name }}
                                <div class="text-xs text-muted-foreground">
                                    {{ subscription.user.email ?? 'No email' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                {{ subscription.plan.name }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="secondary">
                                    {{ subscription.status }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col text-xs text-muted-foreground">
                                    <span>Start: {{ formatDate(subscription.starts_at) }}</span>
                                    <span>End: {{ formatDate(subscription.expires_at) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                {{ subscription.payment?.status ?? '—' }}
                            </td>
                        </tr>

                        <tr v-if="subscriptions.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No subscriptions yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
