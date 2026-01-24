<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { fulfill, reverify } from '@/actions/App/Http/Controllers/Admin/PaymentController';
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
    phone_number: string | null;
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
    filters: {
        phone_number?: string | null;
        status?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
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

const actionForm = useForm({});

const filterForm = useForm({
    phone_number: props.filters.phone_number ?? '',
    status: props.filters.status ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const page = usePage();
const flash = page.props.flash as { success?: string; error?: string };

const showUpload = ref(false);
const showPlan = ref(false)
const activeTab = ref<'payments' | 'vouchers'>('payments');

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

const getStatusColor = (status: string) => {
    const statusLower = status.toLowerCase();
    if (statusLower.includes('success') || statusLower.includes('completed') || statusLower === 'used') {
        return 'bg-green-100 text-green-800 border-green-200';
    }
    if (statusLower.includes('pending') || statusLower === 'reserved') {
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    }
    if (statusLower.includes('failed') || statusLower.includes('cancelled')) {
        return 'bg-red-100 text-red-800 border-red-200';
    }
    return 'bg-gray-100 text-gray-800 border-gray-200';
};

const applyFilters = () => {
    filterForm.get(adminVouchersIndex().url, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    filterForm.phone_number = '';
    filterForm.status = '';
    filterForm.date_from = '';
    filterForm.date_to = '';
    filterForm.get(adminVouchersIndex().url, {
        preserveScroll: true,
        replace: true,
    });
};

const reverifyPayment = (paymentId: number) => {
    actionForm.submit(reverify(paymentId), {
        preserveScroll: true,
        preserveState: true,
    });
};

const fulfillPayment = (paymentId: number) => {
    actionForm.submit(fulfill(paymentId), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <Head title="Voucher Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-8 p-4">
            <!-- Header Section -->

            <!-- Upload Section -->
            <div class="rounded-2xl  transition-all mb-3">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                         <Button
                        type="button"
                        :variant="showPlan ? 'secondary' : 'default'"
                        @click="showPlan = !showPlan"
                        class="transition-all duration-200"
                    >
                        {{ showPlan ? 'Hide Plans' : 'Show Plans' }}
                        <span class="ml-2">{{ showPlan ? '−' : '+' }}</span>
                    </Button>
                    </div>
                    <!-- <div>
                        <h2 class="text-xl font-semibold">Upload Vouchers</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Bulk upload vouchers via CSV, JSON, or TXT files
                        </p>
                    </div> -->
                    <Button
                        type="button"
                        :variant="showUpload ? 'secondary' : 'default'"
                        @click="showUpload = !showUpload"
                        class="transition-all duration-200"
                    >
                        {{ showUpload ? 'Hide Upload' : 'Upload Vouchers' }}
                        <span class="ml-2">{{ showUpload ? '−' : '+' }}</span>
                    </Button>
                </div>
                
                <div v-if="showUpload" class="mt-6 space-y-6 shadow-sm p-3">
                    <div class="rounded-lg bg-gray-200 p-4">
                        <h3 class="font-medium">File Format Requirements</h3>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <p class="text-sm font-medium">CSV/TXT Format:</p>
                                <code class="block rounded bg-background px-3 py-2 text-sm font-mono">
                                    code,plan_type
                                </code>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-medium">JSON Format:</p>
                                <code class="block rounded bg-background px-3 py-2 text-sm font-mono">
                                    [{ "code": "0066983371", "plan_type": 1 }]
                                </code>
                            </div>
                        </div>
                    </div>

                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="space-y-4">
                            <div>
                                <Label for="voucher-file" class="text-base">Select File</Label>
                                <div class="mt-2">
                                    <Input
                                        id="voucher-file"
                                        type="file"
                                        accept=".csv,.json,.txt"
                                        @change="onFileChange"
                                        class="cursor-pointer file:mr-4 file:rounded file:border-0 file:bg-primary file:px-4 file:py-2 file:text-primary-foreground file:cursor-pointer hover:file:bg-primary/90"
                                    />
                                </div>
                                <p v-if="form.errors.file" class="mt-2 text-sm text-destructive">
                                    {{ form.errors.file }}
                                </p>
                            </div>
                            
                            <!-- Flash Messages -->
                            <div v-if="flash?.error || flash?.success" class="rounded-lg border p-4" :class="flash.error ? 'border-destructive/20 bg-destructive/5' : 'border-green-200 bg-green-50'">
                                <p class="font-medium" :class="flash.error ? 'text-destructive' : 'text-green-800'">
                                    {{ flash.error || flash.success }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Button 
                                :disabled="form.processing || !form.file" 
                                class="min-w-[120px] transition-all duration-200"
                                :class="form.file ? 'hover:shadow-md' : ''"
                            >
                                <span v-if="form.processing">
                                    <svg class="mr-2 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Uploading...
                                </span>
                                <span v-else>Upload File</span>
                            </Button>
                            <p v-if="!form.file" class="text-sm text-muted-foreground">
                                Select a file to begin upload
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Plans Section -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm" v-if="showPlan">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold">Available Plans</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Overview of all subscription plans
                    </p>
                </div>
                
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="plan in props.plans"
                        :key="plan.plan_type"
                        class="group rounded-xl border border-border bg-background p-5 transition-all duration-200 hover:border-primary/50 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                        Plan {{ plan.plan_type }}
                                    </span>
                                </div>
                                <h3 class="mt-3 text-lg font-semibold">{{ plan.name }}</h3>
                                <p class="mt-2 text-2xl font-bold">
                                    {{ formatAmount(plan.amount, plan.currency) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Section -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">Recent Activity</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Monitor payments and voucher usage in real-time
                        </p>
                    </div>
                    
                    <div class="inline-flex rounded-lg border p-1">
                        <Button
                            type="button"
                            :variant="activeTab === 'payments' ? 'default' : 'ghost'"
                            @click="activeTab = 'payments'"
                            class="rounded-md px-4 py-2 transition-all duration-200"
                        >
                            Payments
                            <span class="ml-2 rounded-full bg-primary/20 px-2 py-0.5 text-xs">
                                {{ props.payments.length }}
                            </span>
                        </Button>
                        <Button
                            type="button"
                            :variant="activeTab === 'vouchers' ? 'default' : 'ghost'"
                            @click="activeTab = 'vouchers'"
                            class="rounded-md px-4 py-2 transition-all duration-200"
                        >
                            Vouchers
                            <span class="ml-2 rounded-full bg-primary/20 px-2 py-0.5 text-xs">
                                {{ props.vouchers.length }}
                            </span>
                        </Button>
                    </div>
                </div>

                <!-- Payments Tab -->
                <div v-if="activeTab === 'payments'" class="mt-8">
                    <!-- Filters -->
                    <div class="mb-6 rounded-lg border bg-muted/30 p-4">
                        <h3 class="mb-4 font-medium">Filter Payments</h3>
                        <form @submit.prevent="applyFilters" class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                <div class="space-y-2">
                                    <Label for="filter-phone">Phone Number</Label>
                                    <Input
                                        id="filter-phone"
                                        v-model="filterForm.phone_number"
                                        type="text"
                                        placeholder="+2348012345678"
                                        class="w-full"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="filter-status">Status</Label>
                                    <select
                                        id="filter-status"
                                        v-model="filterForm.status"
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                                    >
                                        <option value="">All statuses</option>
                                        <option value="pending">Pending</option>
                                        <option value="paid">Paid</option>
                                        <option value="fulfilled">Fulfilled</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <Label for="filter-date-from">Date From</Label>
                                    <Input
                                        id="filter-date-from"
                                        v-model="filterForm.date_from"
                                        type="date"
                                        class="w-full"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="filter-date-to">Date To</Label>
                                    <Input
                                        id="filter-date-to"
                                        v-model="filterForm.date_to"
                                        type="date"
                                        class="w-full"
                                    />
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Button 
                                    :disabled="filterForm.processing" 
                                    type="submit"
                                    class="min-w-[100px]"
                                >
                                    {{ filterForm.processing ? 'Applying...' : 'Apply Filters' }}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="clearFilters"
                                >
                                    Clear All
                                </Button>
                            </div>
                        </form>
                    </div>

                    <!-- Payments Table -->
                    <div class="overflow-hidden rounded-xl border">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[900px]">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Reference
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Phone
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Plan & Amount
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Status
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Voucher
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Paid At
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="payment in props.payments"
                                        :key="payment.id"
                                        class="group transition-colors hover:bg-muted/30"
                                    >
                                        <td class="px-6 py-4">
                                            <div class="font-mono text-sm font-medium">
                                                {{ payment.reference }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm">
                                                {{ payment.phone_number || '-' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <div class="font-medium">
                                                    {{ payment.plan.name || 'Unknown' }}
                                                </div>
                                                <div class="text-sm text-muted-foreground">
                                                    Plan {{ payment.plan.plan_type ?? '-' }}
                                                </div>
                                                <div class="font-medium">
                                                    {{ formatAmount(payment.amount, payment.currency) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span 
                                                class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium"
                                                :class="getStatusColor(payment.status)"
                                            >
                                                {{ payment.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <div class="font-mono text-sm">
                                                    {{ payment.voucher.code || 'Not assigned' }}
                                                </div>
                                                <div v-if="payment.voucher.status" class="text-xs text-muted-foreground">
                                                    {{ payment.voucher.status }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm">
                                                {{ payment.paid_at || '-' }}
                                            </div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ payment.created_at }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    :disabled="actionForm.processing"
                                                    @click="reverifyPayment(payment.id)"
                                                >
                                                    Reverify
                                                </Button>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    :disabled="actionForm.processing"
                                                    @click="fulfillPayment(payment.id)"
                                                >
                                                    Mark Fulfilled
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="props.payments.length === 0">
                                        <td colspan="7" class="px-6 py-12 text-center">
                                            <div class="space-y-2">
                                                <div class="mx-auto h-12 w-12 rounded-full bg-muted flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                    </svg>
                                                </div>
                                                <p class="text-muted-foreground">No payments found</p>
                                                <p class="text-sm text-muted-foreground">Try adjusting your filters or check back later</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Vouchers Tab -->
                <div v-else class="mt-8">
                    <div class="overflow-hidden rounded-xl border">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[900px]">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Code
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Plan
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Status
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Payment
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Timeline
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="voucher in props.vouchers"
                                        :key="voucher.id"
                                        class="group transition-colors hover:bg-muted/30"
                                    >
                                        <td class="px-6 py-4">
                                            <div class="font-mono font-medium">
                                                {{ voucher.code }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <div class="font-medium">
                                                    {{ voucher.plan.name || 'Unknown' }}
                                                </div>
                                                <div class="text-sm text-muted-foreground">
                                                    Plan {{ voucher.plan.plan_type ?? '-' }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span 
                                                class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium"
                                                :class="getStatusColor(voucher.status)"
                                            >
                                                {{ voucher.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <div class="font-mono text-sm">
                                                    {{ voucher.payment.reference || 'Not assigned' }}
                                                </div>
                                                <div v-if="voucher.payment.status" class="text-xs text-muted-foreground">
                                                    {{ voucher.payment.status }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-2">
                                                <div class="text-sm">
                                                    <span class="font-medium">Reserved:</span>
                                                    {{ voucher.reserved_at || '-' }}
                                                </div>
                                                <div class="text-sm">
                                                    <span class="font-medium">Used:</span>
                                                    {{ voucher.used_at || '-' }}
                                                </div>
                                                <div class="text-xs text-muted-foreground">
                                                    Created: {{ voucher.created_at }}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="props.vouchers.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="space-y-2">
                                                <div class="mx-auto h-12 w-12 rounded-full bg-muted flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                    </svg>
                                                </div>
                                                <p class="text-muted-foreground">No vouchers found</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
