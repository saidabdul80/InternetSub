<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { Input } from '@/components/ui/input';
import MemberLayout from '@/layouts/MemberLayout.vue';

const props = defineProps<{
    customer: {
        id: number;
        username: string;
        full_name: string;
        email: string | null;
        phone_number: string | null;
        balance: string;
        status: string;
    };
    packages: Array<{
        id: number;
        plan_name: string;
        router_name: string;
        method: string;
        service_type: string;
        status: string;
        recharged_at: string;
        expires_at: string;
    }>;
}>();

const search = ref('');
const status = ref<'all' | 'active' | 'expired'>('all');

const filteredPackages = computed(() =>
    props.packages.filter((item) => {
        const matchesSearch =
            search.value.trim() === '' ||
            `${item.plan_name} ${item.router_name ?? ''} ${item.method} ${item.service_type}`.toLowerCase().includes(search.value.toLowerCase());

        const isActive = item.status === 'on';
        const matchesStatus =
            status.value === 'all' ||
            (status.value === 'active' && isActive) ||
            (status.value === 'expired' && !isActive);

        return matchesSearch && matchesStatus;
    }),
);

const activeCount = computed(() => props.packages.filter((item) => item.status === 'on').length);
const expiredCount = computed(() => props.packages.length - activeCount.value);
const fmt = (value: string) => new Date(value).toLocaleString();
</script>

<template>
    <MemberLayout title="My Packages" :customer="customer">
        <div class="space-y-6">
            <section class="rounded-3xl border border-white/10 bg-[linear-gradient(135deg,rgba(56,189,248,0.16),rgba(16,185,129,0.10),transparent)] p-6 backdrop-blur">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-200">Packages</p>
                        <h1 class="mt-2 text-3xl font-semibold text-white">See what is active, what has expired, and what to renew next.</h1>
                        <p class="mt-3 text-sm leading-6 text-slate-200">
                            Package history is now easier to scan. Filter active and expired records quickly, then jump to the public purchase flow when you need another plan.
                        </p>
                    </div>
                    <Link :href="`/app?phone=${encodeURIComponent(customer.phone_number ?? customer.username)}`" class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white transition hover:-translate-y-0.5 hover:bg-white/15">
                        Buy or Renew Plan
                    </Link>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                    <div class="text-sm text-slate-300">All Records</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ packages.length }}</div>
                </div>
                <div class="rounded-3xl border border-emerald-400/20 bg-emerald-400/10 p-5 backdrop-blur">
                    <div class="text-sm text-emerald-100">Active</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ activeCount }}</div>
                </div>
                <div class="rounded-3xl border border-slate-400/20 bg-slate-400/10 p-5 backdrop-blur">
                    <div class="text-sm text-slate-100">Expired or Off</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ expiredCount }}</div>
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-white">Your Packages</h2>
                        <p class="mt-1 text-sm text-slate-300">Active and historical service records tied to your account.</p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Input v-model="search" placeholder="Search plan, router, method" class="border-white/10 bg-white/5 text-white sm:min-w-72" />
                        <select v-model="status" class="h-10 rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                            <option value="all" class="text-slate-900">All packages</option>
                            <option value="active" class="text-slate-900">Active only</option>
                            <option value="expired" class="text-slate-900">Expired or off</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 xl:grid-cols-2">
                    <div v-for="item in filteredPackages" :key="item.id" class="rounded-2xl border border-white/10 bg-slate-950/30 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="text-lg font-semibold text-white">{{ item.plan_name }}</div>
                                <div class="mt-1 text-sm text-slate-300">{{ item.router_name || 'Unassigned router' }} · {{ item.service_type }}</div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-medium" :class="item.status === 'on' ? 'bg-emerald-500/15 text-emerald-200' : 'bg-white/10 text-slate-200'">
                                {{ item.status === 'on' ? 'Active' : item.status }}
                            </span>
                        </div>
                        <div class="mt-4 grid gap-3 text-sm text-slate-300 sm:grid-cols-2">
                            <div>Method: {{ item.method }}</div>
                            <div>Recharged: {{ fmt(item.recharged_at) }}</div>
                            <div class="sm:col-span-2">Expires: {{ fmt(item.expires_at) }}</div>
                        </div>
                    </div>
                    <div v-if="filteredPackages.length === 0" class="rounded-2xl border border-dashed border-white/10 px-4 py-12 text-center text-sm text-slate-400 xl:col-span-2">
                        No package records match the current filter.
                    </div>
                </div>
            </section>
        </div>
    </MemberLayout>
</template>
