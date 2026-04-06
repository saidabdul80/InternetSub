<script setup lang="ts">
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
    stats: {
        total: number;
        unread: number;
        from_system: number;
        from_admin: number;
    };
    messages: Array<{
        id: number;
        subject: string;
        body: string | null;
        from_type: string;
        read_at: string | null;
        created_at: string;
    }>;
}>();

const search = ref('');
const status = ref<'all' | 'unread' | 'read'>('all');

const filteredMessages = computed(() =>
    props.messages.filter((message) => {
        const matchesSearch =
            search.value.trim() === '' ||
            `${message.subject} ${message.body ?? ''} ${message.from_type}`.toLowerCase().includes(search.value.toLowerCase());

        const matchesStatus =
            status.value === 'all' ||
            (status.value === 'unread' && !message.read_at) ||
            (status.value === 'read' && !!message.read_at);

        return matchesSearch && matchesStatus;
    }),
);

const fmt = (value: string | null) => (value ? new Date(value).toLocaleString() : 'Unread');
</script>

<template>
    <MemberLayout title="Inbox" :customer="customer">
        <div class="space-y-6">
            <section class="rounded-3xl border border-white/10 bg-[linear-gradient(135deg,rgba(56,189,248,0.16),rgba(16,185,129,0.10),transparent)] p-6 backdrop-blur">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-200">Member Inbox</p>
                <h1 class="mt-2 text-3xl font-semibold text-white">Messages, notices, and account follow-ups in one timeline.</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200">
                    This covers the message-center side of the old member panel. System notices and admin follow-ups are grouped with clearer read-state visibility.
                </p>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                    <div class="text-sm text-slate-300">Total Messages</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ stats.total }}</div>
                </div>
                <div class="rounded-3xl border border-amber-400/20 bg-amber-400/10 p-5 backdrop-blur">
                    <div class="text-sm text-amber-100">Unread</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ stats.unread }}</div>
                </div>
                <div class="rounded-3xl border border-cyan-400/20 bg-cyan-400/10 p-5 backdrop-blur">
                    <div class="text-sm text-cyan-100">System</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ stats.from_system }}</div>
                </div>
                <div class="rounded-3xl border border-emerald-400/20 bg-emerald-400/10 p-5 backdrop-blur">
                    <div class="text-sm text-emerald-100">Admin</div>
                    <div class="mt-2 text-3xl font-semibold text-white">{{ stats.from_admin }}</div>
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-white">Inbox Timeline</h2>
                        <p class="mt-1 text-sm text-slate-300">Search subjects or switch between read and unread messages.</p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Input v-model="search" placeholder="Search subject or message" class="border-white/10 bg-white/5 text-white sm:min-w-72" />
                        <select v-model="status" class="h-10 rounded-md border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                            <option value="all" class="text-slate-900">All messages</option>
                            <option value="unread" class="text-slate-900">Unread only</option>
                            <option value="read" class="text-slate-900">Read only</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    <div v-for="message in filteredMessages" :key="message.id" class="rounded-2xl border border-white/10 bg-slate-950/30 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="text-lg font-semibold text-white">{{ message.subject }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ message.from_type }} · {{ fmt(message.created_at) }}</div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-medium" :class="message.read_at ? 'bg-white/10 text-slate-200' : 'bg-amber-500/15 text-amber-200'">
                                {{ message.read_at ? 'Read' : 'Unread' }}
                            </span>
                        </div>
                        <p v-if="message.body" class="mt-4 text-sm leading-6 text-slate-200">{{ message.body }}</p>
                        <p v-else class="mt-4 text-sm text-slate-400">No message body attached.</p>
                    </div>
                    <div v-if="filteredMessages.length === 0" class="rounded-2xl border border-dashed border-white/10 px-4 py-12 text-center text-sm text-slate-400">
                        No messages match the current filter.
                    </div>
                </div>
            </section>
        </div>
    </MemberLayout>
</template>
