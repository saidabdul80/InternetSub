<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { index, store } from '@/routes/admin/plans';

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
        title: 'New plan',
        href: '',
    },
];

const form = useForm({
    name: '',
    price_kobo: '',
    speed_mbps: '',
    duration_minutes: '',
    mikrotik_profile: '',
    is_active: true,
});

const submit = () => {
    form.submit(store());
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="New Plan" />

        <div class="flex flex-col gap-6">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    Create plan
                </h1>
                <p class="text-sm text-muted-foreground">
                    Define pricing, speed, and MikroTik profile details.
                </p>
            </div>

            <form class="grid gap-6" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Plan name</Label>
                    <Input id="name" v-model="form.name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="price_kobo">Price (kobo)</Label>
                        <Input
                            id="price_kobo"
                            type="number"
                            min="100"
                            v-model="form.price_kobo"
                        />
                        <InputError :message="form.errors.price_kobo" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="speed_mbps">Speed (Mbps)</Label>
                        <Input
                            id="speed_mbps"
                            type="number"
                            min="1"
                            v-model="form.speed_mbps"
                        />
                        <InputError :message="form.errors.speed_mbps" />
                    </div>
                </div>

                <div class="grid gap-2 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="duration_minutes">Duration (minutes)</Label>
                        <Input
                            id="duration_minutes"
                            type="number"
                            min="1"
                            v-model="form.duration_minutes"
                        />
                        <InputError :message="form.errors.duration_minutes" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="mikrotik_profile">MikroTik profile</Label>
                        <Input
                            id="mikrotik_profile"
                            v-model="form.mikrotik_profile"
                        />
                        <InputError :message="form.errors.mikrotik_profile" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="is_active">Plan status</Label>
                    <select
                        id="is_active"
                        v-model="form.is_active"
                        class="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                    >
                        <option :value="true">Active</option>
                        <option :value="false">Disabled</option>
                    </select>
                    <InputError :message="form.errors.is_active" />
                </div>

                <div class="flex flex-wrap gap-3">
                    <Button type="submit" :disabled="form.processing">
                        Save plan
                    </Button>
                    <Button variant="ghost" as-child>
                        <Link :href="index().url">Cancel</Link>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
