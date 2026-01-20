<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { index, create, destroy, edit, show } from '@/routes/admin/plans';

interface Plan {
    id: number;
    name: string;
    price_kobo: number;
    speed_mbps: number;
    duration_minutes: number;
    mikrotik_profile: string;
    is_active: boolean;
}

interface Props {
    plans: {
        data: Plan[];
    };
    errors?: Record<string, string>;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin dashboard',
        href: '/admin',
    },
    {
        title: 'Plans',
        href: index().url,
    },
];

const formatPrice = (priceKobo: number) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(priceKobo / 100);

const handleDelete = (planId: number) => {
    if (!confirm('Delete this plan? This cannot be undone.')) {
        return;
    }

    router.delete(destroy.url(planId));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Plans" />

        <div class="flex flex-col gap-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        Plans
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Create, edit, and disable hotspot subscription plans.
                    </p>
                </div>

                <Button as-child>
                    <Link :href="create().url">New plan</Link>
                </Button>
            </div>

            <InputError v-if="errors?.general" :message="errors.general" />

            <div class="overflow-hidden rounded-lg border border-border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3">Speed</th>
                            <th class="px-4 py-3">Duration</th>
                            <th class="px-4 py-3">Profile</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="plan in plans.data"
                            :key="plan.id"
                            class="border-t border-border"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ plan.name }}
                            </td>
                            <td class="px-4 py-3">
                                {{ formatPrice(plan.price_kobo) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ plan.speed_mbps }} Mbps
                            </td>
                            <td class="px-4 py-3">
                                {{ plan.duration_minutes }} mins
                            </td>
                            <td class="px-4 py-3">
                                {{ plan.mikrotik_profile }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="plan.is_active ? 'default' : 'secondary'"
                                >
                                    {{ plan.is_active ? 'Active' : 'Disabled' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link :href="show(plan.id).url">
                                            View
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="sm" as-child>
                                        <Link :href="edit(plan.id).url">
                                            Edit
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="handleDelete(plan.id)"
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="plans.data.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No plans yet. Create your first plan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
