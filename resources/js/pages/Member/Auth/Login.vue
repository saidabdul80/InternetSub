<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const page = usePage();
const flash = page.props.flash as { success?: string; error?: string };

const form = useForm({
    login: '',
    password: '',
});

const buyPlanHref = computed(() => {
    const login = form.login.trim();

    if (!login) {
        return '/app';
    }

    return `/app?phone=${encodeURIComponent(login)}`;
});

const submit = () => {
    form.post('/member/login');
};
</script>

<template>
    <Head title="Member Login" />

    <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(245,158,11,0.18),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(56,189,248,0.18),_transparent_30%),linear-gradient(160deg,_#07131a,_#0f172a,_#111827)] px-4 py-10 text-slate-100">
        <div class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-md items-center">
            <div class="w-full rounded-[2rem] border border-white/10 bg-slate-950/55 p-6 shadow-2xl backdrop-blur sm:p-8">
                <div class="mb-8">
                    <p class="text-sm font-medium uppercase tracking-[0.25em] text-cyan-200">GoodNews Wi-Fi</p>
                    <h1 class="mt-3 text-3xl font-semibold text-white">Member Login</h1>
                    <p class="mt-2 text-sm text-slate-300">Use your phone number with your customer password.</p>
                </div>

                <div v-if="flash?.error" class="mb-5 rounded-2xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                    {{ flash.error }}
                </div>
                <div v-if="flash?.success" class="mb-5 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                    {{ flash.success }}
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label class="text-slate-200">Phone Number</Label>
                        <Input v-model="form.login" class="border-white/10 bg-white/5 text-white" />
                        <p v-if="form.errors.login" class="text-sm text-rose-300">{{ form.errors.login }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label class="text-slate-200">Password</Label>
                        <Input v-model="form.password" type="password" class="border-white/10 bg-white/5 text-white" />
                        <p v-if="form.errors.password" class="text-sm text-rose-300">{{ form.errors.password }}</p>
                    </div>
                    <Button type="submit" class="w-full bg-cyan-500 text-slate-950 hover:bg-cyan-400" :disabled="form.processing">
                        {{ form.processing ? 'Signing In...' : 'Sign In' }}
                    </Button>

                    <div class="text-center text-sm text-slate-300">
                        Don&apos;t have an account?
                        <Link :href="buyPlanHref" class="font-semibold text-cyan-200 underline underline-offset-4 hover:text-cyan-100">
                            Buy a plan
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
