<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import MemberLayout from '@/layouts/MemberLayout.vue';

const props = defineProps<{
    customer: {
        id: number;
        username: string;
        full_name: string;
        email: string | null;
        phone_number: string | null;
        address: string | null;
        city: string | null;
        state: string | null;
        district: string | null;
        zip: string | null;
        account_type: string;
        service_type: string;
        auto_renewal: boolean;
    };
}>();

const form = useForm({
    full_name: props.customer.full_name,
    email: props.customer.email ?? '',
    phone_number: props.customer.phone_number ?? '',
    address: props.customer.address ?? '',
    city: props.customer.city ?? '',
    state: props.customer.state ?? '',
    district: props.customer.district ?? '',
    zip: props.customer.zip ?? '',
    auto_renewal: props.customer.auto_renewal,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put('/member/profile');
};
</script>

<template>
    <MemberLayout title="Member Profile" :customer="customer">
        <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
            <section class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                <div class="rounded-3xl bg-[linear-gradient(180deg,_rgba(34,197,94,0.18),_rgba(34,197,94,0.04))] p-6">
                    <p class="text-sm uppercase tracking-[0.24em] text-emerald-200">Account</p>
                    <h1 class="mt-3 text-3xl font-semibold text-white">{{ customer.full_name }}</h1>
                    <div class="mt-6 space-y-3 text-sm text-slate-200">
                        <div>Username: {{ customer.username }}</div>
                        <div>Service Type: {{ customer.service_type }}</div>
                        <div>Account Type: {{ customer.account_type }}</div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                <h2 class="text-xl font-semibold text-white">Update Profile</h2>
                <p class="mt-1 text-sm text-slate-300">Keep your contact details and renewal preference up to date.</p>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label class="text-slate-200">Full Name</Label>
                            <Input v-model="form.full_name" class="border-white/10 bg-white/5 text-white" />
                            <p v-if="form.errors.full_name" class="text-sm text-rose-300">{{ form.errors.full_name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label class="text-slate-200">Email</Label>
                            <Input v-model="form.email" type="email" class="border-white/10 bg-white/5 text-white" />
                            <p v-if="form.errors.email" class="text-sm text-rose-300">{{ form.errors.email }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label class="text-slate-200">Phone Number</Label>
                            <Input v-model="form.phone_number" class="border-white/10 bg-white/5 text-white" />
                            <p v-if="form.errors.phone_number" class="text-sm text-rose-300">{{ form.errors.phone_number }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label class="text-slate-200">City</Label>
                            <Input v-model="form.city" class="border-white/10 bg-white/5 text-white" />
                        </div>
                        <div class="space-y-2">
                            <Label class="text-slate-200">State</Label>
                            <Input v-model="form.state" class="border-white/10 bg-white/5 text-white" />
                        </div>
                        <div class="space-y-2">
                            <Label class="text-slate-200">District</Label>
                            <Input v-model="form.district" class="border-white/10 bg-white/5 text-white" />
                        </div>
                        <div class="space-y-2">
                            <Label class="text-slate-200">ZIP</Label>
                            <Input v-model="form.zip" class="border-white/10 bg-white/5 text-white" />
                        </div>
                        <label class="mt-7 flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
                            <input v-model="form.auto_renewal" type="checkbox" class="size-4 rounded border-white/15" />
                            Enable auto renewal
                        </label>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-slate-200">Address</Label>
                        <textarea v-model="form.address" class="min-h-24 w-full rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm text-white"></textarea>
                        <p v-if="form.errors.address" class="text-sm text-rose-300">{{ form.errors.address }}</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label class="text-slate-200">New Password</Label>
                            <Input v-model="form.password" type="password" class="border-white/10 bg-white/5 text-white" />
                            <p v-if="form.errors.password" class="text-sm text-rose-300">{{ form.errors.password }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label class="text-slate-200">Confirm Password</Label>
                            <Input v-model="form.password_confirmation" type="password" class="border-white/10 bg-white/5 text-white" />
                        </div>
                    </div>

                    <Button type="submit" class="w-full bg-emerald-400 text-slate-950 hover:bg-emerald-300" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Profile' }}
                    </Button>
                </form>
            </section>
        </div>
    </MemberLayout>
</template>
