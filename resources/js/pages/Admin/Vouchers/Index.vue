<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as adminVouchersIndex, upload as adminVouchersUpload } from '@/routes/admin/vouchers';
import { type BreadcrumbItem } from '@/types';

interface Plan {
    plan_type: number;
    name: string;
    amount: number;
    currency: string;
}

interface Payment {
    id: number;
    reference: string;
    status: string;
    amount: number;
    currency: string;
    plan: {
        plan_type: number | null;
        name: string | null;
    };
    voucher: {
        code: string | null;
        status: string | null;
    };
    paid_at: string | null;
    created_at: string;
}

interface Voucher {
    id: number;
    code: string;
    status: string;
    plan: {
        plan_type: number | null;
        name: string | null;
    };
    payment: {
        reference: string | null;
        status: string | null;
    };
    reserved_at: string | null;
    used_at: string | null;
    created_at: string;
}

const props = defineProps<{
    plans: Plan[];
    payments: Payment[];
    vouchers: Voucher[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Voucher Admin',
        href: adminVouchersIndex().url,
    },
];

const form = useForm({
    file: null as File | null,
});

const page = usePage();
const flash = page.props.flash as { success?: string; error?: string };

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.file = target.files ? target.files[0] : null;
};

const submit = () => {
    if (!form.file) {
        return;
    }

    form.post(adminVouchersUpload().url, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => form.reset('file'),
    });
};

const formatAmount = (amount: number, currency: string) => {
    const value = amount / 100;
    return `${currency} ${value.toFixed(2)}`;
};
</script>

<template>
    <Head title="Voucher Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6">
            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <h2 class="text-lg font-semibold">Upload Vouchers</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Upload CSV, JSON, or TXT files. CSV/TXT format:
                    <span class="font-medium">code,plan_type</span>. JSON format:
                    <span class="font-medium"
                        >[{ "code": "0066983371", "plan_type": 1 }]</span
                    >.
                </p>

                <form class="mt-4 flex flex-col gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="voucher-file">Voucher File</Label>
                        <Input
                            id="voucher-file"
                            type="file"
                            accept=".csv,.json,.txt"
                            @change="onFileChange"
                        />
                        <p v-if="form.errors.file" class="text-sm text-red-600">
                            {{ form.errors.file }}
                        </p>
                        <p v-if="flash?.error" class="text-sm text-red-600">
                            {{ flash.error }}
                        </p>
                        <p v-if="flash?.success" class="text-sm text-green-600">
                            {{ flash.success }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button :disabled="form.processing">Upload</Button>
                        <span
                            v-if="form.processing"
                            class="text-sm text-muted-foreground"
                            >Uploading...</span
                        >
                    </div>
                </form>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <h2 class="text-lg font-semibold">Plans</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="plan in props.plans"
                        :key="plan.plan_type"
                        class="rounded-lg border border-sidebar-border/70 p-4"
                    >
                        <div class="text-sm text-muted-foreground">
                            Plan {{ plan.plan_type }}
                        </div>
                        <div class="mt-1 text-base font-medium">
                            {{ plan.name }}
                        </div>
                        <div class="mt-2 text-sm">
                            {{ formatAmount(plan.amount, plan.currency) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <h2 class="text-lg font-semibold">Recent Payments</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2">Reference</th>
                                <th class="px-3 py-2">Plan</th>
                                <th class="px-3 py-2">Amount</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Voucher</th>
                                <th class="px-3 py-2">Paid At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="payment in props.payments"
                                :key="payment.id"
                                class="border-t border-sidebar-border/70"
                            >
                                <td class="px-3 py-2">
                                    {{ payment.reference }}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="text-sm">
                                        {{ payment.plan.name || 'Unknown' }}
                                    </div>
                                    <div
                                        class="text-xs text-muted-foreground"
                                    >
                                        Plan {{ payment.plan.plan_type ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    {{
                                        formatAmount(
                                            payment.amount,
                                            payment.currency,
                                        )
                                    }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ payment.status }}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="text-sm">
                                        {{
                                            payment.voucher.code ||
                                            'Not assigned'
                                        }}
                                    </div>
                                    <div
                                        v-if="payment.voucher.status"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ payment.voucher.status }}
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    {{ payment.paid_at || '-' }}
                                </td>
                            </tr>
                            <tr v-if="props.payments.length === 0">
                                <td
                                    class="px-3 py-4 text-center text-sm text-muted-foreground"
                                    colspan="6"
                                >
                                    No payments yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <h2 class="text-lg font-semibold">Recent Vouchers</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2">Code</th>
                                <th class="px-3 py-2">Plan</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Payment</th>
                                <th class="px-3 py-2">Reserved</th>
                                <th class="px-3 py-2">Used</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="voucher in props.vouchers"
                                :key="voucher.id"
                                class="border-t border-sidebar-border/70"
                            >
                                <td class="px-3 py-2">
                                    {{ voucher.code }}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="text-sm">
                                        {{ voucher.plan.name || 'Unknown' }}
                                    </div>
                                    <div
                                        class="text-xs text-muted-foreground"
                                    >
                                        Plan {{ voucher.plan.plan_type ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    {{ voucher.status }}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="text-sm">
                                        {{
                                            voucher.payment.reference ||
                                            'Not assigned'
                                        }}
                                    </div>
                                    <div
                                        v-if="voucher.payment.status"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ voucher.payment.status }}
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    {{ voucher.reserved_at || '-' }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ voucher.used_at || '-' }}
                                </td>
                            </tr>
                            <tr v-if="props.vouchers.length === 0">
                                <td
                                    class="px-3 py-4 text-center text-sm text-muted-foreground"
                                    colspan="6"
                                >
                                    No vouchers yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
