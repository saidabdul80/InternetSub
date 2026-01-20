<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { index } from '@/routes/admin/payments';

interface PaymentRow {
    id: number;
    reference: string;
    amount_kobo: number;
    currency: string;
    status: string;
    paid_at: string | null;
    user: {
        name: string;
        email: string | null;
    };
    plan: {
        name: string;
    };
}

interface Props {
    payments: {
        data: PaymentRow[];
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin dashboard',
        href: '/admin',
    },
    {
        title: 'Payments',
        href: index().url,
    },
];

const formatPrice = (amountKobo: number, currency: string) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
        minimumFractionDigits: 0,
    }).format(amountKobo / 100);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Payments" />

        <div class="flex flex-col gap-6">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">Payments</h1>
                <p class="text-sm text-muted-foreground">
                    Review Paystack transactions and statuses.
                </p>
            </div>

            <div class="overflow-hidden rounded-lg border border-border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="payment in payments.data"
                            :key="payment.id"
                            class="border-t border-border"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ payment.user.name }}
                                <div class="text-xs text-muted-foreground">
                                    {{ payment.user.email ?? 'No email' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ payment.plan.name }}</td>
                            <td class="px-4 py-3">
                                {{ formatPrice(payment.amount_kobo, payment.currency) }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="secondary">
                                    {{ payment.status }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ payment.reference }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    payment.paid_at
                                        ? new Date(payment.paid_at).toLocaleString()
                                        : '—'
                                }}
                            </td>
                        </tr>

                        <tr v-if="payments.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No payments yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
