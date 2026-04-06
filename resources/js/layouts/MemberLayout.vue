<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { CreditCard, LayoutDashboard, LogOut, Mail, Package2, UserCircle2 } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';

const props = defineProps<{
    title: string;
    customer: {
        id: number;
        username: string;
        full_name: string;
        email?: string | null;
        phone_number?: string | null;
        balance?: string;
        status?: string;
    };
}>();

const logoutForm = useForm({});
const page = usePage();
const flash = page.props.flash as { success?: string; error?: string };

const signOut = () => {
    logoutForm.post('/member/logout');
};
</script>

<template>
    <Head :title="title" />

    <div
        class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.18),_transparent_30%),linear-gradient(180deg,_#08111a,_#0f1a24_55%,_#111827)] text-slate-100"
    >
        <header class="border-b border-white/10 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
                <div>
                    <Link href="/member" class="text-lg font-semibold tracking-tight text-white">
                        GoodNews Member
                    </Link>
                    <p class="text-xs text-slate-300">
                        {{ customer.full_name }} · {{ customer.username }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full bg-white/10 px-2.5 py-1 text-slate-200">{{ customer.status || 'Unknown' }}</span>
                        <span class="rounded-full bg-emerald-400/15 px-2.5 py-1 text-emerald-100">NGN {{ Number(customer.balance ?? 0).toFixed(2) }}</span>
                    </div>
                </div>

                <nav class="hidden items-center gap-2 md:flex">
                    <a href="/member/connect">
                        <Button class="bg-cyan-400 text-slate-950 hover:bg-cyan-300">
                            Connect Internet
                        </Button>
                    </a>
                    <Link href="/member">
                        <Button variant="ghost" class="text-slate-200">
                            <LayoutDashboard class="mr-2 h-4 w-4" />
                            Dashboard
                        </Button>
                    </Link>
                    <Link href="/member/profile">
                        <Button variant="ghost" class="text-slate-200">
                            <UserCircle2 class="mr-2 h-4 w-4" />
                            Profile
                        </Button>
                    </Link>
                    <Link href="/member/packages">
                        <Button variant="ghost" class="text-slate-200">
                            <Package2 class="mr-2 h-4 w-4" />
                            Renew or Buy Plans
                        </Button>
                    </Link>
                    <Link href="/member/orders">
                        <Button variant="ghost" class="text-slate-200">
                            <CreditCard class="mr-2 h-4 w-4" />
                            Orders
                        </Button>
                    </Link>
                    <Link href="/member/messages">
                        <Button variant="ghost" class="text-slate-200">
                            <Mail class="mr-2 h-4 w-4" />
                            Inbox
                        </Button>
                    </Link>
                    <Button variant="outline" class="border-white/15 bg-white/5 text-slate-100" @click="signOut">
                        <LogOut class="mr-2 h-4 w-4" />
                        Sign Out
                    </Button>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            <div class="mb-5 flex flex-wrap gap-2 md:hidden">
                <a href="/member/connect">
                    <Button class="bg-cyan-400 text-slate-950 hover:bg-cyan-300">
                        Connect Internet
                    </Button>
                </a>
                <Link href="/member">
                    <Button variant="outline" class="border-white/15 bg-white/5 text-slate-100">
                        <LayoutDashboard class="mr-2 h-4 w-4" />
                        Dashboard
                    </Button>
                </Link>
                <Link href="/member/packages">
                    <Button variant="outline" class="border-white/15 bg-white/5 text-slate-100">
                        <Package2 class="mr-2 h-4 w-4" />
                        Packages
                    </Button>
                </Link>
                <Link href="/member/orders">
                    <Button variant="outline" class="border-white/15 bg-white/5 text-slate-100">
                        <CreditCard class="mr-2 h-4 w-4" />
                        Orders
                    </Button>
                </Link>
                <Link href="/member/messages">
                    <Button variant="outline" class="border-white/15 bg-white/5 text-slate-100">
                        <Mail class="mr-2 h-4 w-4" />
                        Inbox
                    </Button>
                </Link>
                <Link href="/member/profile">
                    <Button variant="outline" class="border-white/15 bg-white/5 text-slate-100">
                        <UserCircle2 class="mr-2 h-4 w-4" />
                        Profile
                    </Button>
                </Link>
            </div>

            <div v-if="flash?.error" class="mb-4 rounded-2xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
                {{ flash.error }}
            </div>
            <div v-if="flash?.success" class="mb-4 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                {{ flash.success }}
            </div>

            <div class="rounded-[2rem] border border-white/10 bg-black/10 p-3 backdrop-blur-sm sm:p-4">
                <slot />
            </div>
        </main>
    </div>
</template>
