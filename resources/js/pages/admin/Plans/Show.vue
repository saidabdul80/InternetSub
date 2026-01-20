<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { edit, index } from '@/routes/admin/plans';

interface Props {
    plan: {
        id: number;
        name: string;
        price_kobo: number;
        speed_mbps: number;
        duration_minutes: number;
        mikrotik_profile: string;
        is_active: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin dashboard',
        href: '/admin',
    },
    {
        title: 'Plans',
        href: index().url,
    },
    {
        title: props.plan.name,
        href: '',
    },
];

const formatPrice = (priceKobo: number) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(priceKobo / 100);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Plan Details" />

        <div class="flex flex-col gap-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        {{ plan.name }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Plan pricing and MikroTik profile details.
                    </p>
                </div>

                <div class="flex gap-2">
                    <Button variant="ghost" as-child>
                        <Link :href="index().url">Back to plans</Link>
                    </Button>
                    <Button as-child>
                        <Link :href="edit(plan.id).url">Edit</Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 rounded-lg border border-border p-6 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">Price</span>
                    <span class="font-medium">
                        {{ formatPrice(plan.price_kobo) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">Speed</span>
                    <span class="font-medium">
                        {{ plan.speed_mbps }} Mbps
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">Duration</span>
                    <span class="font-medium">
                        {{ plan.duration_minutes }} minutes
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">MikroTik profile</span>
                    <span class="font-medium">
                        {{ plan.mikrotik_profile }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-muted-foreground">Status</span>
                    <Badge :variant="plan.is_active ? 'default' : 'secondary'">
                        {{ plan.is_active ? 'Active' : 'Disabled' }}
                    </Badge>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
