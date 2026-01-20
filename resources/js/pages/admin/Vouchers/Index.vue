<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { index } from '@/routes/admin/vouchers';

interface VoucherRow {
    id: number;
    code: string;
    status: string;
    payment_reference: string | null;
    expires_at: string | null;
    user: {
        name: string;
    };
    plan: {
        name: string;
    };
    mikrotik_router: {
        name: string;
    } | null;
}

interface Props {
    vouchers: {
        data: VoucherRow[];
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin dashboard',
        href: '/admin',
    },
    {
        title: 'Vouchers',
        href: index().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Vouchers" />

        <div class="flex flex-col gap-6">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">Vouchers</h1>
                <p class="text-sm text-muted-foreground">
                    Track generated hotspot voucher codes.
                </p>
            </div>

            <div class="overflow-hidden rounded-lg border border-border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Router</th>
                            <th class="px-4 py-3">Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="voucher in vouchers.data"
                            :key="voucher.id"
                            class="border-t border-border"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ voucher.code }}
                                <div class="text-xs text-muted-foreground">
                                    {{ voucher.payment_reference ?? '—' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                {{ voucher.user.name }}
                            </td>
                            <td class="px-4 py-3">{{ voucher.plan.name }}</td>
                            <td class="px-4 py-3">
                                <Badge variant="secondary">
                                    {{ voucher.status }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                {{ voucher.mikrotik_router?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    voucher.expires_at
                                        ? new Date(voucher.expires_at).toLocaleString()
                                        : '—'
                                }}
                            </td>
                        </tr>

                        <tr v-if="vouchers.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No vouchers yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
