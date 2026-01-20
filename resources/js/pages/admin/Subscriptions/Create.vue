<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { create, index, store } from '@/routes/admin/subscriptions';

interface UserOption {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
}

interface PlanOption {
    id: number;
    name: string;
    price_kobo: number;
    duration_minutes: number;
}

interface Props {
    users: UserOption[];
    plans: PlanOption[];
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
    {
        title: 'Manual subscribe',
        href: create().url,
    },
];

const form = useForm({
    user_id: '',
    plan_id: '',
});

const formatPrice = (priceKobo: number) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(priceKobo / 100);

const submit = () => {
    form.submit(store());
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Manual Subscription" />

        <div class="flex flex-col gap-6">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    Manual subscription
                </h1>
                <p class="text-sm text-muted-foreground">
                    Assign a plan to a user and activate access immediately.
                </p>
            </div>

            <form class="grid gap-6" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="user_id">User</Label>
                    <select
                        id="user_id"
                        v-model="form.user_id"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option value="">Select a user</option>
                        <option v-for="user in users" :key="user.id" :value="user.id">
                            {{ user.name }}
                            <template v-if="user.email">
                                ({{ user.email }})
                            </template>
                            <template v-else-if="user.phone">
                                ({{ user.phone }})
                            </template>
                        </option>
                    </select>
                    <InputError :message="form.errors.user_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="plan_id">Plan</Label>
                    <select
                        id="plan_id"
                        v-model="form.plan_id"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option value="">Select a plan</option>
                        <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                            {{ plan.name }} - {{ formatPrice(plan.price_kobo) }}
                        </option>
                    </select>
                    <InputError :message="form.errors.plan_id" />
                </div>

                <div class="flex flex-wrap gap-3">
                    <Button type="submit" :disabled="form.processing">
                        Activate access
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="index().url">Cancel</Link>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
