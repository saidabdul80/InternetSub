<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    counts: {
        users: number;
        plans: number;
        subscriptions: number;
        payments: number;
        vouchers: number;
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin dashboard',
        href: '/admin',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Admin Dashboard" />

        <div class="flex flex-col gap-6">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    Admin Dashboard
                </h1>
                <p class="text-sm text-muted-foreground">
                    Monitor customers, subscriptions, and payments at a glance.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <Card>
                    <CardHeader>
                        <CardTitle>Users</CardTitle>
                    </CardHeader>
                    <CardContent class="text-2xl font-semibold">
                        {{ counts.users }}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Plans</CardTitle>
                    </CardHeader>
                    <CardContent class="text-2xl font-semibold">
                        {{ counts.plans }}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Subscriptions</CardTitle>
                    </CardHeader>
                    <CardContent class="text-2xl font-semibold">
                        {{ counts.subscriptions }}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Payments</CardTitle>
                    </CardHeader>
                    <CardContent class="text-2xl font-semibold">
                        {{ counts.payments }}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Vouchers</CardTitle>
                    </CardHeader>
                    <CardContent class="text-2xl font-semibold">
                        {{ counts.vouchers }}
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle>Manage plans</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3 text-sm text-muted-foreground">
                        Keep pricing and speed profiles aligned with hotspot demand.
                        <Button class="w-fit" as-child>
                            <Link href="/admin/plans">View plans</Link>
                        </Button>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Manual subscription</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3 text-sm text-muted-foreground">
                        Add access for a user that paid offline or by transfer.
                        <Button class="w-fit" as-child>
                            <Link href="/admin/subscriptions/create">Subscribe user</Link>
                        </Button>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Review payments</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3 text-sm text-muted-foreground">
                        Confirm Paystack activity and troubleshoot failed payments.
                        <Button class="w-fit" as-child>
                            <Link href="/admin/payments">View payments</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
