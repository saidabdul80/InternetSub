<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
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

const selectedPlanType = ref<number | null>(props.plans[0]?.plan_type ?? null);
const selectedGateway = ref<string>(props.gateways.includes('paystack') ? 'paystack' : (props.gateways[0] ?? 'paystack'));

const form = useForm({
    phone_number: props.hotspot.phone || '',
    plan_type: selectedPlanType.value ?? 0,
    gateway: selectedGateway.value,
    hotspot_return: props.hotspot.return_url || '',
    hotspot_dst: props.hotspot.dst || '',
});

const selectedPlan = computed<Plan | null>(() => {
    return props.plans.find((plan) => plan.plan_type === selectedPlanType.value) ?? null;
});

const hasHotspotReturn = computed<boolean>(() => form.hotspot_return.trim().length > 0);

const hasValidPhone = computed<boolean>(() => {
    const cleaned = form.phone_number.replace(/\s+/g, '');
    return /^\+?\d{8,20}$/.test(cleaned);
});

const canSubmit = computed<boolean>(() => {
    return hasHotspotReturn.value && hasValidPhone.value && selectedPlan.value !== null && !form.processing;
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

const submit = (): void => {
    if (!selectedPlan.value) {
        return;
    }

    form.phone_number = form.phone_number.replace(/\s+/g, '');
    form.plan_type = selectedPlan.value.plan_type;
    form.gateway = selectedGateway.value;

    form.post('/app/start');
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
        class="min-h-screen px-4 py-8 text-slate-100 sm:px-6"
        style="font-family: 'Sora', sans-serif; background: radial-gradient(circle at top right, #f97316 0%, transparent 45%), radial-gradient(circle at bottom left, #0f766e 0%, transparent 40%), #0a1118;">
        <div class="mx-auto w-full max-w-5xl">
            <header class="mb-8">
                <p class="text-xs font-semibold uppercase tracking-[0.32em] text-amber-300">
                    GoodNews Wi-Fi
                </p>
                <h1
                    class="mt-3 text-3xl font-bold leading-tight text-white sm:text-4xl"
                    style="font-family: 'Bricolage Grotesque', sans-serif;"
                >
                    Choose your subscription plan
                </h1>
                <p class="mt-3 max-w-2xl text-sm text-slate-300 sm:text-base">
                    Pick a plan, choose a payment gateway, and continue. Your hotspot account will be created automatically after successful payment.
                </p>
            </header>

            <section class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="space-y-4">
                    <button
                        v-for="plan in props.plans"
                        :key="plan.id"
                        type="button"
                        @click="selectedPlanType = plan.plan_type"
                        class="w-full rounded-2xl border p-5 text-left transition"
                        :class="
                            selectedPlanType === plan.plan_type
                                ? 'border-cyan-300 bg-cyan-500/20 shadow-[0_0_0_1px_rgba(103,232,249,0.45)]'
                                : 'border-slate-700/70 bg-slate-900/55 hover:border-cyan-500/50 hover:bg-slate-900/75'
                        "
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-white">{{ plan.name }}</h2>
                                <p class="mt-1 text-sm text-slate-300">{{ planDuration(plan) }} high-speed access</p>
                            </div>
                            <span class="rounded-full bg-slate-950/50 px-3 py-1 text-xs font-medium text-cyan-200">
                                Plan {{ plan.plan_type }}
                            </span>
                        </div>
                        <div class="mt-4 text-2xl font-bold text-amber-300">
                            {{ formattedAmount(plan) }}
                        </div>
                    </button>
                </div>

                <div class="rounded-2xl border border-slate-700/70 bg-slate-950/65 p-5 sm:p-6">
                    <h3 class="text-lg font-semibold text-white">Complete subscription</h3>
                    <p class="mt-1 text-sm text-slate-300">Use your phone number as both username and password.</p>

                    <div v-if="!hasHotspotReturn" class="mt-4 rounded-xl border border-rose-500/60 bg-rose-500/15 p-3 text-sm text-rose-100">
                        Hotspot return URL is missing. Open this page from the captive portal “Subscribe” button.
                    </div>

                    <div
                        v-if="page.props.flash?.error"
                        class="mt-4 rounded-xl border border-rose-500/60 bg-rose-500/15 p-3 text-sm text-rose-100"
                    >
                        {{ page.props.flash.error }}
                    </div>

                    <div
                        v-if="page.props.flash?.success"
                        class="mt-4 rounded-xl border border-emerald-500/60 bg-emerald-500/15 p-3 text-sm text-emerald-100"
                    >
                        {{ page.props.flash.success }}
                    </div>

                    <form class="mt-5 space-y-5" @submit.prevent="submit">
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-300">
                                Phone Number
                            </label>
                            <input
                                v-model="form.phone_number"
                                type="tel"
                                autocomplete="tel"
                                placeholder="+2348012345678"
                                class="h-11 w-full rounded-xl border border-slate-700 bg-slate-900/65 px-3 text-sm text-white outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-500/40"
                            />
                            <p v-if="form.phone_number && !hasValidPhone" class="mt-2 text-xs text-rose-300">
                                Enter a valid phone number.
                            </p>
                        </div>

                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-300">Gateway</p>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="gateway in props.gateways"
                                    :key="gateway"
                                    type="button"
                                    @click="selectedGateway = gateway"
                                    class="rounded-xl border px-3 py-2 text-sm font-semibold capitalize transition"
                                    :class="
                                        selectedGateway === gateway
                                            ? 'border-amber-300 bg-amber-400/20 text-amber-100'
                                            : 'border-slate-700 bg-slate-900/55 text-slate-200 hover:border-amber-300/45'
                                    "
                                >
                                    {{ gateway }}
                                </button>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-700 bg-slate-900/45 p-3 text-sm text-slate-200">
                            <p class="font-semibold text-white">Selected Plan</p>
                            <p class="mt-1">
                                {{ selectedPlan?.name ?? 'No plan selected' }}
                                <span class="text-amber-300">· {{ selectedPlan ? formattedAmount(selectedPlan) : '' }}</span>
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="!canSubmit"
                            class="h-12 w-full rounded-xl bg-gradient-to-r from-cyan-400 to-emerald-400 text-sm font-bold uppercase tracking-wider text-slate-950 transition enabled:hover:from-cyan-300 enabled:hover:to-emerald-300 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ form.processing ? 'Redirecting...' : 'Proceed to payment' }}
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</template>
