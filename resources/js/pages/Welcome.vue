<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import { dashboard, login } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="GoodNews Wi-Fi">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap"
            rel="stylesheet"
        />
    </Head>
    <div class="min-h-screen bg-[#0f1a1c] text-[#f6f3ee]">
        <div class="relative overflow-hidden">
            <div
                class="pointer-events-none absolute -top-32 left-1/2 h-[520px] w-[520px] -translate-x-1/2 rounded-full bg-[radial-gradient(circle_at_center,_#1f6f6b_0%,_transparent_60%)] opacity-60"
            />
            <div
                class="pointer-events-none absolute right-[-120px] top-[120px] h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle_at_center,_#efb14f_0%,_transparent_60%)] opacity-40"
            />
            <header class="relative z-10 mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#f6f3ee] text-[#0f1a1c]">
                        <span class="text-sm font-semibold">GN</span>
                    </div>
                    <div class="text-sm uppercase tracking-[0.3em] text-[#b6e2da]">
                        GoodNews Wi-Fi
                    </div>
                </div>
                <nav class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-full border border-[#b6e2da] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[#b6e2da] transition hover:bg-[#b6e2da] hover:text-[#0f1a1c]"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="login()"
                        class="rounded-full border border-transparent bg-[#f6f3ee] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[#0f1a1c] transition hover:bg-[#efe7dd]"
                    >
                        Log in
                    </Link>
                </nav>
            </header>
            <main class="relative z-10 mx-auto grid w-full max-w-6xl gap-10 px-6 pb-16 pt-6 lg:grid-cols-[1.2fr_0.8fr] lg:gap-12 lg:px-8 lg:pt-14">
                <section class="space-y-8">
                    <div class="space-y-4">
                        <p class="text-xs uppercase tracking-[0.4em] text-[#efb14f]">
                            captive portal · vouchers · sms delivery
                        </p>
                        <h1
                            class="text-4xl font-semibold leading-tight text-[#f6f3ee] lg:text-6xl"
                            style="font-family: 'Fraunces', serif;"
                        >
                            Fast Wi-Fi access with instant voucher delivery.
                        </h1>
                        <p class="max-w-xl text-base text-[#cbe6e0] lg:text-lg">
                            Sell time-based internet plans, accept Paystack payments,
                            and auto-deliver access codes by SMS. Built for cafes,
                            churches, events, and community hotspots.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="rounded-full bg-[#efb14f] px-6 py-3 text-xs font-semibold uppercase tracking-widest text-[#1b1406] transition hover:bg-[#f4c26a]"
                        >
                            Go to Dashboard
                        </Link>
                        <Link
                            v-else
                            :href="login()"
                            class="rounded-full border border-[#b6e2da] px-6 py-3 text-xs font-semibold uppercase tracking-widest text-[#b6e2da] transition hover:bg-[#b6e2da] hover:text-[#0f1a1c]"
                        >
                            Admin Login
                        </Link>
                        <a
                            href="tel:07035398873"
                            class="rounded-full border border-[#2b3b3d] px-6 py-3 text-xs font-semibold uppercase tracking-widest text-[#f6f3ee] transition hover:border-[#f6f3ee]"
                        >
                            Call Support
                        </a>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-xl border border-[#2b3b3d] bg-[#122225] p-4">
                            <p class="text-xs uppercase tracking-widest text-[#b6e2da]">
                                Instant
                            </p>
                            <p class="mt-2 text-sm text-[#f6f3ee]">
                                Voucher codes delivered right after payment.
                            </p>
                        </div>
                        <div class="rounded-xl border border-[#2b3b3d] bg-[#122225] p-4">
                            <p class="text-xs uppercase tracking-widest text-[#b6e2da]">
                                Reliable
                            </p>
                            <p class="mt-2 text-sm text-[#f6f3ee]">
                                Paystack verification + safe code inventory.
                            </p>
                        </div>
                        <div class="rounded-xl border border-[#2b3b3d] bg-[#122225] p-4">
                            <p class="text-xs uppercase tracking-widest text-[#b6e2da]">
                                Simple
                            </p>
                            <p class="mt-2 text-sm text-[#f6f3ee]">
                                Upload vouchers, track revenue, stay in control.
                            </p>
                        </div>
                    </div>
                </section>
                <aside class="space-y-6">
                    <div class="rounded-2xl border border-[#2b3b3d] bg-[#122225] p-6">
                        <p class="text-xs uppercase tracking-widest text-[#efb14f]">
                            How it works
                        </p>
                        <ol class="mt-4 space-y-4 text-sm text-[#f6f3ee]">
                            <li class="flex gap-3">
                                <span class="mt-1 h-5 w-5 rounded-full bg-[#efb14f] text-center text-[11px] font-semibold text-[#1b1406]">1</span>
                                <span>Guests pick a plan and pay via Paystack.</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-1 h-5 w-5 rounded-full bg-[#efb14f] text-center text-[11px] font-semibold text-[#1b1406]">2</span>
                                <span>Payment is verified instantly on callback.</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-1 h-5 w-5 rounded-full bg-[#efb14f] text-center text-[11px] font-semibold text-[#1b1406]">3</span>
                                <span>Voucher is delivered by SMS and redeemed.</span>
                            </li>
                        </ol>
                    </div>
                    <div class="rounded-2xl border border-[#2b3b3d] bg-[#0f1a1c] p-6">
                        <div class="flex items-center justify-between">
                            <p class="text-xs uppercase tracking-widest text-[#b6e2da]">
                                Contact
                            </p>
                            <span class="text-xs text-[#8aa9a4]">24/7</span>
                        </div>
                        <p class="mt-3 text-sm text-[#f6f3ee]">
                            No. 25 Mandela Road, Industrial Layout, Minna, Niger State.
                        </p>
                        <a
                            href="tel:+2347035398873"
                            class="mt-4 inline-flex rounded-full border border-[#b6e2da] px-4 py-2 text-xs font-semibold uppercase tracking-widest text-[#b6e2da] transition hover:bg-[#b6e2da] hover:text-[#0f1a1c]"
                        >
                            +234 703 539 8873
                        </a>
                    </div>
                </aside>
            </main>
        </div>
        <footer class="border-t border-[#1e2c2e] px-6 py-6 text-xs text-[#8aa9a4] lg:px-8">
            <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-3">
                <p>GoodNews Wi-Fi · Powered by voucher access</p>
                <p>Secure payments via Paystack</p>
            </div>
        </footer>
    </div>
</template>
