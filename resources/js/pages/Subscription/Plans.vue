<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    CircleHelp,
    Clock3,
    Smartphone,
    WalletCards,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Plan = {
    id: number;
    plan_type: number;
    name: string;
    amount: number;
    currency: string;
};

type PageProps = {
    flash?: {
        success?: string | null;
        error?: string | null;
    };
};

type GatewayMeta = {
    label: string;
    badge: string;
    methods: string[];
};

type Stage = 1 | 2 | 3;

const props = defineProps<{
    plans: Plan[];
    gateways: string[];
    hotspot: {
        return_url: string;
        dst: string;
        phone: string;
    };
}>();

const page = usePage<PageProps>();

const gatewayDetails: Record<string, GatewayMeta> = {
    paystack: {
        label: 'Paystack',
        badge: 'More options',
        methods: ['Card', 'Transfer', 'USSD', 'QR'],
    },
    monnify: {
        label: 'Monnify',
        badge: 'Bank checkout',
        methods: ['Card', 'Transfer', 'USSD'],
    },
};

const stages: Array<{ id: Stage; label: string }> = [
    { id: 1, label: 'Plan' },
    { id: 2, label: 'Phone' },
    { id: 3, label: 'Gateway' },
];

const currentStage = ref<Stage>(1);
const selectedPlanType = ref<number | null>(null);
const selectedGateway = ref<string>('');
const showPhoneHelp = ref(false);

const form = useForm({
    phone_number: props.hotspot.phone || '',
    plan_type: 0,
    gateway: '',
    hotspot_return: props.hotspot.return_url || '',
    hotspot_dst: props.hotspot.dst || '',
});

const selectedPlan = computed<Plan | null>(() => {
    return (
        props.plans.find((plan) => plan.plan_type === selectedPlanType.value) ??
        null
    );
});

const selectedGatewayMeta = computed<GatewayMeta | null>(() => {
    return gatewayDetails[selectedGateway.value] ?? null;
});

const hasHotspotReturn = computed<boolean>(
    () => form.hotspot_return.trim().length > 0,
);

const hasValidPhone = computed<boolean>(() => {
    const cleaned = form.phone_number.replace(/\s+/g, '');
    return /^\+?\d{8,20}$/.test(cleaned);
});

const canSubmit = computed<boolean>(() => {
    return (
        hasHotspotReturn.value &&
        hasValidPhone.value &&
        selectedPlan.value !== null &&
        selectedGateway.value.trim().length > 0 &&
        !form.processing
    );
});

const phonePreview = computed<string>(() => {
    if (!form.phone_number.trim()) {
        return 'Not entered';
    }

    return form.phone_number.replace(/\s+/g, '');
});

const formattedAmount = (plan: Plan): string => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: plan.currency || 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(plan.amount / 100);
};

const planDuration = (plan: Plan): string => {
    const name = plan.name.toLowerCase();

    if (name.includes('hourly')) {
        return '4 hours';
    }

    if (name.includes('24')) {
        return '24 hours';
    }

    if (name.includes('week')) {
        return '7 days';
    }

    if (name.includes('month')) {
        return '30 days';
    }

    return 'Timed access';
};

const selectPlan = (plan: Plan): void => {
    selectedPlanType.value = plan.plan_type;
    form.plan_type = plan.plan_type;
    currentStage.value = hasValidPhone.value ? 3 : 2;
};

const continueFromPhone = (): void => {
    if (!hasValidPhone.value) {
        return;
    }

    currentStage.value = 3;
};

const selectGateway = (gateway: string): void => {
    selectedGateway.value = gateway;
    form.gateway = gateway;
};

const goToStage = (stage: Stage): void => {
    currentStage.value = stage;
};

const submit = (): void => {
    if (!selectedPlan.value) {
        return;
    }

    form.phone_number = form.phone_number.replace(/\s+/g, '');
    form.plan_type = selectedPlan.value.plan_type;
    form.gateway = selectedGateway.value;

    form.post('/app/start', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Subscription Plans">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Bricolage+Grotesque:wght@500;700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="min-h-screen px-4 py-5 text-slate-100 sm:px-6 sm:py-8"
        style="
            font-family: 'Sora', sans-serif;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(34, 197, 94, 0.16) 0%,
                    transparent 28%
                ),
                radial-gradient(
                    circle at top right,
                    rgba(249, 115, 22, 0.18) 0%,
                    transparent 32%
                ),
                linear-gradient(160deg, #07131a 0%, #0d1820 45%, #111f27 100%);
        "
    >
        <div class="mx-auto w-full max-w-2xl">
            <header class="mb-4 text-center sm:mb-6">
                <p
                    class="text-[11px] font-semibold tracking-[0.28em] text-emerald-200 uppercase"
                >
                    GoodNews Wi-Fi
                </p>
                <h1
                    class="mt-2 text-2xl font-bold text-white sm:text-4xl"
                    style="font-family: 'Bricolage Grotesque', sans-serif"
                >
                    Fast mobile checkout
                </h1>
                <p class="mt-2 text-sm text-slate-300">
                    Pick a plan and continue.
                </p>
            </header>

            <div class="mb-4 flex flex-wrap gap-2 sm:mb-5">
                <!-- <span
                    class="rounded-full border px-3 py-1 text-[11px] font-semibold tracking-[0.16em] uppercase"
                    :class="
                        hasHotspotReturn
                            ? 'border-emerald-300/30 bg-emerald-400/10 text-emerald-100'
                            : 'border-rose-300/30 bg-rose-400/10 text-rose-100'
                    "
                >
                    {{ hasHotspotReturn ? 'Portal ready' : 'Portal missing' }}
                </span> -->
                <span
                    v-if="selectedPlan"
                    class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold tracking-[0.16em] text-white uppercase"
                >
                    {{ selectedPlan.name }}
                </span>
                <span
                    v-if="hasValidPhone"
                    class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold tracking-[0.16em] text-white uppercase"
                >
                    {{ phonePreview }}
                </span>
                <span
                    v-if="selectedGatewayMeta"
                    class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-semibold tracking-[0.16em] text-white uppercase"
                >
                    {{ selectedGatewayMeta.label }}
                </span>
            </div>

            <div
                v-if="!hasHotspotReturn"
                class="mb-4 rounded-2xl border border-rose-400/35 bg-rose-400/10 px-4 py-3 text-sm text-rose-50"
            >
                Open this page from the hotspot subscribe button.
            </div>

            <div
                v-if="page.props.flash?.error"
                class="mb-4 rounded-2xl border border-rose-400/35 bg-rose-400/10 px-4 py-3 text-sm text-rose-50"
            >
                {{ page.props.flash.error }}
            </div>

            <div
                v-if="page.props.flash?.success"
                class="mb-4 rounded-2xl border border-emerald-400/35 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-50"
            >
                {{ page.props.flash.success }}
            </div>

            <div class="mb-4 grid grid-cols-3 gap-2 sm:mb-5">
                <button
                    v-for="stage in stages"
                    :key="stage.id"
                    type="button"
                    class="rounded-2xl border px-3 py-3 text-center text-xs font-semibold tracking-[0.16em] uppercase transition"
                    :class="
                        currentStage === stage.id
                            ? 'border-cyan-300/60 bg-cyan-400/12 text-white'
                            : currentStage > stage.id
                              ? 'border-emerald-300/40 bg-emerald-400/10 text-emerald-100'
                              : 'border-white/10 bg-white/5 text-slate-400'
                    "
                    :disabled="
                        (stage.id === 2 && !selectedPlan) ||
                        (stage.id === 3 && (!selectedPlan || !hasValidPhone))
                    "
                    @click="goToStage(stage.id)"
                >
                    {{ stage.label }}
                </button>
            </div>

            <section
                class="rounded-[28px] border border-white/10 bg-slate-950/60 p-4 shadow-[0_22px_70px_rgba(2,6,23,0.45)] backdrop-blur sm:p-5"
            >
                <div v-if="currentStage === 1" class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p
                                class="text-[11px] font-semibold tracking-[0.22em] text-slate-400 uppercase"
                            >
                                Step 1
                            </p>
                            <h2 class="mt-1 text-lg font-semibold text-white">
                                Choose plan
                            </h2>
                        </div>
                    </div>

                    <div
                        v-if="props.plans.length === 0"
                        class="rounded-2xl border border-amber-300/20 bg-amber-400/10 px-4 py-3 text-sm text-amber-50"
                    >
                        No plans available.
                    </div>

                    <button
                        v-for="plan in props.plans"
                        :key="plan.id"
                        type="button"
                        class="w-full rounded-[24px] border p-4 text-left transition"
                        :class="
                            selectedPlanType === plan.plan_type
                                ? 'border-cyan-300/70 bg-cyan-400/12'
                                : 'border-white/10 bg-white/5 hover:border-cyan-300/40'
                        "
                        @click="selectPlan(plan)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-white">
                                    {{ plan.name }}
                                </p>
                                <p
                                    class="mt-2 flex items-center gap-2 text-sm text-slate-300"
                                >
                                    <Clock3 class="h-4 w-4 text-cyan-200" />
                                    {{ planDuration(plan) }}
                                </p>
                            </div>
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full border"
                                :class="
                                    selectedPlanType === plan.plan_type
                                        ? 'border-cyan-300 bg-cyan-300 text-slate-950'
                                        : 'border-white/15 text-transparent'
                                "
                            >
                                <Check class="h-4 w-4" />
                            </div>
                        </div>
                        <p class="mt-3 text-2xl font-bold text-amber-300">
                            {{ formattedAmount(plan) }}
                        </p>
                    </button>
                </div>

                <div v-else-if="currentStage === 2" class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-200 transition hover:border-white/25"
                                @click="goToStage(1)"
                            >
                                <ArrowLeft class="h-4 w-4" />
                            </button>
                            <div>
                                <p
                                    class="text-[11px] font-semibold tracking-[0.22em] text-slate-400 uppercase"
                                >
                                    Step 2
                                </p>
                                <h2
                                    class="mt-1 text-lg font-semibold text-white"
                                >
                                    Enter phone
                                </h2>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-200 transition hover:border-white/25"
                            :aria-expanded="showPhoneHelp"
                            aria-label="Show phone help"
                            @click="showPhoneHelp = !showPhoneHelp"
                        >
                            <CircleHelp class="h-4 w-4" />
                        </button>
                    </div>

                    <div
                        v-if="showPhoneHelp"
                        class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300"
                    >
                        Use the same number you want to use for hotspot login.
                    </div>

                    <div
                        class="rounded-[24px] border border-white/10 bg-white/5 p-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p
                                    class="text-[11px] font-semibold tracking-[0.22em] text-slate-400 uppercase"
                                >
                                    Phone number
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    Hotspot login number
                                </p>
                            </div>
                            <Smartphone class="h-5 w-5 text-cyan-200" />
                        </div>

                        <input
                            v-model="form.phone_number"
                            type="tel"
                            autocomplete="tel"
                            placeholder="+2348012345678"
                            class="mt-4 h-12 w-full rounded-2xl border border-white/10 bg-slate-950/60 px-4 text-sm text-white transition outline-none placeholder:text-slate-500 focus:border-cyan-300 focus:ring-2 focus:ring-cyan-500/30"
                        />

                        <p
                            v-if="form.errors.phone_number"
                            class="mt-2 text-xs text-rose-300"
                        >
                            {{ form.errors.phone_number }}
                        </p>
                        <p
                            v-else-if="form.phone_number && !hasValidPhone"
                            class="mt-2 text-xs text-rose-300"
                        >
                            Enter a valid phone number.
                        </p>

                        <p class="mt-3 text-xs text-slate-400">
                            Login:
                            <span class="font-semibold text-slate-200">{{
                                phonePreview
                            }}</span>
                        </p>
                    </div>

                    <button
                        type="button"
                        :disabled="!hasValidPhone"
                        class="flex h-[52px] w-full items-center justify-center gap-2 rounded-[22px] bg-gradient-to-r from-cyan-300 via-emerald-300 to-amber-300 px-5 text-sm font-bold tracking-[0.2em] text-slate-950 uppercase transition enabled:hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="continueFromPhone"
                    >
                        <span>Continue</span>
                        <ArrowRight class="h-4 w-4" />
                    </button>
                </div>

                <form v-else class="space-y-4" @submit.prevent="submit">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-200 transition hover:border-white/25"
                                @click="goToStage(2)"
                            >
                                <ArrowLeft class="h-4 w-4" />
                            </button>
                            <div>
                                <p
                                    class="text-[11px] font-semibold tracking-[0.22em] text-slate-400 uppercase"
                                >
                                    Step 3
                                </p>
                                <h2
                                    class="mt-1 text-lg font-semibold text-white"
                                >
                                    Choose gateway
                                </h2>
                            </div>
                        </div>
                        <WalletCards class="h-5 w-5 text-amber-200" />
                    </div>

                    <button
                        v-for="gateway in props.gateways"
                        :key="gateway"
                        type="button"
                        class="w-full rounded-[24px] border p-4 text-left transition"
                        :class="
                            selectedGateway === gateway
                                ? 'border-amber-300/60 bg-amber-400/10'
                                : 'border-white/10 bg-white/5 hover:border-amber-300/35'
                        "
                        @click="selectGateway(gateway)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p
                                        class="text-base font-semibold text-white"
                                    >
                                        {{
                                            gatewayDetails[gateway]?.label ??
                                            gateway
                                        }}
                                    </p>
                                    <span
                                        class="rounded-full border border-white/10 bg-slate-950/35 px-2.5 py-1 text-[11px] font-semibold tracking-[0.16em] text-slate-300 uppercase"
                                    >
                                        {{
                                            gatewayDetails[gateway]?.badge ??
                                            'Checkout'
                                        }}
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="method in gatewayDetails[gateway]
                                            ?.methods ?? ['Secure pay']"
                                        :key="method"
                                        class="rounded-full border border-white/10 bg-slate-950/40 px-3 py-1 text-[11px] font-medium tracking-[0.16em] text-slate-300 uppercase"
                                    >
                                        {{ method }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full border"
                                :class="
                                    selectedGateway === gateway
                                        ? 'border-amber-300 bg-amber-300 text-slate-950'
                                        : 'border-white/15 text-transparent'
                                "
                            >
                                <Check class="h-4 w-4" />
                            </div>
                        </div>
                    </button>

                    <p v-if="form.errors.gateway" class="text-xs text-rose-300">
                        {{ form.errors.gateway }}
                    </p>

                    <div
                        class="rounded-[24px] border border-white/10 bg-white/5 p-4"
                    >
                        <div class="grid gap-2 text-sm text-slate-200">
                            <div
                                class="flex items-center justify-between rounded-2xl bg-slate-950/45 px-4 py-3"
                            >
                                <span>Plan</span>
                                <span class="pl-4 font-semibold text-white">
                                    {{ selectedPlan?.name ?? 'Not selected' }}
                                </span>
                            </div>
                            <div
                                class="flex items-center justify-between rounded-2xl bg-slate-950/45 px-4 py-3"
                            >
                                <span>Amount</span>
                                <span class="pl-4 font-semibold text-white">
                                    {{
                                        selectedPlan
                                            ? formattedAmount(selectedPlan)
                                            : 'Not selected'
                                    }}
                                </span>
                            </div>
                            <div
                                class="flex items-center justify-between rounded-2xl bg-slate-950/45 px-4 py-3"
                            >
                                <span>Phone</span>
                                <span
                                    class="truncate pl-4 font-semibold text-white"
                                >
                                    {{ phonePreview }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="!canSubmit"
                        class="flex h-[52px] w-full items-center justify-center gap-2 rounded-[22px] bg-gradient-to-r from-cyan-300 via-emerald-300 to-amber-300 px-5 text-sm font-bold tracking-[0.2em] text-slate-950 uppercase transition enabled:hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span>
                            {{
                                form.processing
                                    ? 'Redirecting...'
                                    : `Proceed to ${selectedGatewayMeta?.label ?? 'payment'}`
                            }}
                        </span>
                        <ArrowRight class="h-4 w-4" />
                    </button>
                </form>
            </section>
        </div>
    </div>
</template>
