<template>
  <Head title="Subscription Plans">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap"
      rel="stylesheet"
    />
  </Head>

  <div
    class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100/40 px-4 py-6 text-slate-900 antialiased sm:px-6 sm:py-8 lg:px-8"
    style="font-family: 'Inter', sans-serif"
  >
    <div class="mx-auto w-full max-w-7xl">
      <!-- Header Section -->
      <header class="group relative mb-8 overflow-hidden rounded-2xl bg-white/80 shadow-lg shadow-slate-200/50 backdrop-blur-sm transition-all duration-300 hover:shadow-xl sm:rounded-3xl">
        <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/5 via-transparent to-emerald-500/5" />
        <div class="relative px-5 py-5 sm:px-8 sm:py-6">
          <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <p class="inline-flex items-center gap-1.5 rounded-full bg-cyan-50 px-3 py-1 text-[11px] font-semibold tracking-wider text-cyan-700">
                <span class="relative flex h-2 w-2">
                  <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-400 opacity-75" />
                  <span class="relative inline-flex h-2 w-2 rounded-full bg-cyan-500" />
                </span>
                GoodNews Wi-Fi
              </p>
              <h1
                class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl"
                style="font-family: 'Plus Jakarta Sans', sans-serif"
              >
                Get connected in
                <span class="bg-gradient-to-r from-cyan-600 to-emerald-600 bg-clip-text text-transparent">seconds</span>
              </h1>
              <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-500">
                Choose a plan, verify your number, and complete payment — your internet will be ready instantly.
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
              <Link
                href="/member/login"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 hover:shadow-md"
              >
                <UserCircle2 class="h-4 w-4" />
                <span>Login</span>
              </Link>
              <a
                href="/GoonewsApp.apk"
                download
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-cyan-600 to-cyan-500 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition-all hover:from-cyan-700 hover:to-cyan-600 hover:shadow-lg"
              >
                <Download class="h-4 w-4" />
                <span>App</span>
              </a>
              <a
                href="tel:+2347035398873"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 hover:shadow-md"
              >
                <Headset class="h-4 w-4" />
                <span class="hidden sm:inline">Support</span>
              </a>
            </div>
          </div>
        </div>
      </header>

      <!-- Flash Messages -->
      <div v-if="page.props.flash?.error" class="mb-6 animate-in slide-in-from-top-2 fade-in duration-300">
        <div class="rounded-xl border-l-4 border-rose-500 bg-rose-50 p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <CircleAlert class="h-5 w-5 text-rose-500" />
            <p class="text-sm font-medium text-rose-700">{{ page.props.flash.error }}</p>
          </div>
        </div>
      </div>

      <div v-if="page.props.flash?.success" class="mb-6 animate-in slide-in-from-top-2 fade-in duration-300">
        <div class="rounded-xl border-l-4 border-emerald-500 bg-emerald-50 p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <CheckCircle class="h-5 w-5 text-emerald-500" />
            <p class="text-sm font-medium text-emerald-700">{{ page.props.flash.success }}</p>
          </div>
        </div>
      </div>

      <!-- Main Grid -->
      <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
        <!-- Main Content Area -->
        <div class="space-y-6">
          <!-- Stage Navigation -->
          <nav class="flex items-center justify-between gap-2 rounded-2xl bg-white/60 p-1.5 shadow-sm backdrop-blur-sm">
            <button
              v-for="stage in stages"
              :key="stage.id"
              type="button"
              class="relative flex flex-1 items-center gap-2 rounded-xl px-3 py-2.5 text-left transition-all sm:px-4"
              :class="[
                currentStage === stage.id
                  ? 'bg-white shadow-md ring-1 ring-slate-200'
                  : currentStage > stage.id
                    ? 'text-emerald-700'
                    : 'text-slate-400',
              ]"
              :disabled="
                (stage.id === 2 && !selectedPlan) ||
                (stage.id === 3 && (!selectedPlan || !hasValidPhone))
              "
              @click="goToStage(stage.id)"
            >
              <div
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                :class="{
                  'bg-cyan-600 text-white': currentStage === stage.id,
                  'bg-emerald-100 text-emerald-700': currentStage > stage.id,
                  'bg-slate-100 text-slate-500': currentStage < stage.id,
                }"
              >
                <Check v-if="currentStage > stage.id" class="h-3 w-3" />
                <span v-else>{{ stage.id }}</span>
              </div>
              <div class="hidden sm:block">
                <div class="text-xs font-medium uppercase tracking-wider text-slate-400">Step {{ stage.id }}</div>
                <div class="text-sm font-semibold">{{ stage.label }}</div>
              </div>
              <div class="ml-auto sm:hidden">
                <div class="text-xs font-medium">{{ stage.label }}</div>
              </div>
            </button>
          </nav>

          <!-- Stage 1: Plan Selection -->
          <div v-if="currentStage === 1" class="animate-in slide-in-from-left-5 fade-in duration-300">
            <div class="mb-5 flex items-center justify-between">
              <div>
                <h2 class="text-xl font-bold text-slate-900">Choose your plan</h2>
                <p class="text-sm text-slate-500">Select the perfect package for your needs</p>
              </div>
            </div>

            <div v-if="props.plans.length === 0" class="rounded-xl bg-amber-50 p-6 text-center">
              <p class="text-amber-700">No plans available at the moment. Please check back later.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <button
                v-for="plan in props.plans"
                :key="plan.id"
                type="button"
                class="group relative overflow-hidden rounded-2xl border-2 p-5 text-left transition-all duration-200"
                :class="[
                  selectedPlanType === plan.plan_type
                    ? 'border-cyan-500 bg-gradient-to-br from-cyan-50/80 to-white shadow-lg shadow-cyan-100/50'
                    : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-md',
                ]"
                @click="selectPlan(plan)"
              >
                <div class="absolute right-0 top-0 -mr-8 -mt-8 h-20 w-20 rounded-full bg-gradient-to-br from-cyan-100/40 to-transparent opacity-0 transition-opacity group-hover:opacity-100" />
                <div class="flex items-start justify-between">
                  <div>
                    <h3 class="text-lg font-bold text-slate-900">{{ plan.name.replaceAll('_', ' ') }}</h3>
                    <div class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                      <Clock3 class="h-3.5 w-3.5" />
                      {{ planDuration(plan) }}
                    </div>
                  </div>
                  <div
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 transition-all"
                    :class="
                      selectedPlanType === plan.plan_type
                        ? 'border-cyan-600 bg-cyan-600 text-white'
                        : 'border-slate-300 bg-white group-hover:border-cyan-400'
                    "
                  >
                    <Check v-if="selectedPlanType === plan.plan_type" class="h-3.5 w-3.5" />
                  </div>
                </div>
                <div class="mt-5">
                  <span class="text-3xl font-black text-slate-900">{{ formattedAmount(plan) }}</span>
                  <p class="mt-1 text-xs text-slate-500">One-time payment</p>
                </div>
              </button>
            </div>
          </div>

          <!-- Stage 2: Phone Number -->
          <div v-else-if="currentStage === 2" class="animate-in slide-in-from-right-5 fade-in duration-300">
            <div class="mb-5 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <button
                  type="button"
                  class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                  @click="goToStage(1)"
                >
                  <ArrowLeft class="h-4 w-4" />
                </button>
                <div>
                  <h2 class="text-xl font-bold text-slate-900">Your phone number</h2>
                </div>
              </div>
              <button
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                @click="showPhoneHelp = !showPhoneHelp"
              >
                <CircleHelp class="h-4 w-4" />
              </button>
            </div>

            <div v-if="showPhoneHelp" class="mb-5 rounded-xl bg-cyan-50 p-4 text-sm text-cyan-800">
              <p>Use the phone number you'll use to connect to Wi-Fi. If it's new, an account will be created automatically after payment.</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-xs font-semibold uppercase tracking-wider text-slate-500">Phone Number</label>
                  <p class="text-xs text-slate-400">This will be your login credential</p>
                </div>
                <div class="rounded-xl bg-slate-100 p-2.5 text-cyan-600">
                  <Smartphone class="h-5 w-5" />
                </div>
              </div>

              <input
                v-model="form.phone_number"
                type="tel"
                autocomplete="tel"
                placeholder="08012345678"
                class="mt-4 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-base text-slate-900 outline-none transition focus:border-cyan-400 focus:bg-white focus:ring-2 focus:ring-cyan-100"
              />

              <p v-if="form.errors.phone_number" class="mt-2 text-sm text-rose-600">{{ form.errors.phone_number }}</p>
              <p v-else-if="form.phone_number && !hasValidPhone" class="mt-2 text-sm text-rose-600">Enter a valid phone number.</p>

              <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                Login preview:
                <span class="ml-2 font-semibold text-slate-900">{{ phonePreview }}</span>
              </div>
            </div>

            <button
              type="button"
              :disabled="!hasValidPhone"
              class="mt-6 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-cyan-600 to-cyan-500 text-sm font-semibold text-white shadow-md transition-all hover:from-cyan-700 hover:to-cyan-600 disabled:opacity-50"
              @click="continueFromPhone"
            >
              <span>Continue to Payment</span>
              <ArrowRight class="h-4 w-4" />
            </button>
          </div>

          <!-- Stage 3: Payment Method -->
          <form v-else class="animate-in slide-in-from-bottom-5 fade-in duration-300" @submit.prevent="submit">
            <div class="mb-5 flex items-center gap-3">
              <button
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                @click="goToStage(2)"
              >
                <ArrowLeft class="h-4 w-4" />
              </button>
              <div>
                <h2 class="text-xl font-bold text-slate-900">Payment method</h2>
                <p class="text-sm text-slate-500">Choose how you'd like to pay</p>
              </div>
            </div>

            <div class="grid gap-3">
              <button
                v-for="gateway in props.gateways"
                :key="gateway"
                type="button"
                class="flex items-center justify-between rounded-xl border-2 p-4 transition-all"
                :class="
                  selectedGateway === gateway
                    ? 'border-cyan-500 bg-cyan-50/50 shadow-md'
                    : 'border-slate-200 bg-white hover:border-slate-300'
                "
                @click="selectGateway(gateway)"
              >
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100">
                    <CreditCard v-if="gateway === 'paystack'" class="h-5 w-5 text-slate-600" />
                    <Banknote v-else class="h-5 w-5 text-slate-600" />
                  </div>
                  <div>
                    <div class="font-semibold text-slate-900">{{ gatewayDetails[gateway]?.label ?? gateway }}</div>
                    <div class="text-xs text-slate-500">{{ gatewayDetails[gateway]?.methods.join(' • ') }}</div>
                  </div>
                </div>
                <div
                  class="flex h-5 w-5 items-center justify-center rounded-full border-2"
                  :class="
                    selectedGateway === gateway
                      ? 'border-cyan-600 bg-cyan-600 text-white'
                      : 'border-slate-300'
                  "
                >
                  <Check v-if="selectedGateway === gateway" class="h-3 w-3" />
                </div>
              </button>
            </div>

            <p v-if="form.errors.gateway" class="mt-2 text-sm text-rose-600">{{ form.errors.gateway }}</p>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500">Plan</span>
                  <span class="font-semibold text-slate-900">{{ selectedPlan?.name ?? 'Not selected' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500">Amount</span>
                  <span class="font-semibold text-slate-900">{{ selectedPlan ? formattedAmount(selectedPlan) : 'Not selected' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500">Phone</span>
                  <span class="font-semibold text-slate-900">{{ phonePreview }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-500">Renewal</span>
                  <span class="font-semibold text-slate-900">{{ form.renew ? 'Enabled' : 'Disabled' }}</span>
                </div>
              </div>
            </div>

            <button
              type="submit"
              :disabled="!canSubmit"
              class="mt-6 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-slate-800 to-slate-900 text-sm font-semibold text-white shadow-md transition-all hover:from-slate-900 hover:to-slate-800 disabled:opacity-50"
            >
              <WalletCards class="h-4 w-4" />
              <span>{{ form.processing ? 'Processing...' : `Pay ${selectedPlan ? formattedAmount(selectedPlan) : ''}` }}</span>
            </button>
          </form>
        </div>

        <!-- Sidebar -->
        <aside class="space-y-6">
          <!-- Order Summary Card -->
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
              <h3 class="font-bold text-slate-900">Order summary</h3>
              <div class="rounded-xl bg-cyan-50 p-2 text-cyan-600">
                <ShieldCheck class="h-4 w-4" />
              </div>
            </div>

            <div class="mt-4 space-y-3">
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">Plan</span>
                <span class="font-medium text-slate-900">{{ selectedPlan?.name?.replaceAll('_', ' ') || '—' }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">Duration</span>
                <span class="font-medium text-slate-900">{{ selectedPlan ? planDuration(selectedPlan) : '—' }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">Phone</span>
                <span class="font-medium text-slate-900">{{ phonePreview }}</span>
              </div>
              <div class="pt-3">
                <div class="flex justify-between border-t border-slate-100 pt-3 text-base font-bold">
                  <span>Total</span>
                  <span>{{ selectedPlan ? formattedAmount(selectedPlan) : '—' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Help Card -->
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-bold text-slate-900">Need assistance?</h3>
            <p class="mt-1 text-sm text-slate-500">Our support team is ready to help you 24/7.</p>
            <div class="mt-4 space-y-2">
              <a
                href="tel:+2347035398873"
                class="flex items-center justify-center gap-2 rounded-xl bg-emerald-50 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
              >
                <Headset class="h-4 w-4" />
                Call +234 703 539 8873
              </a>
              <a
                href="/GoonewsApp.apk"
                download
                class="flex items-center justify-center gap-2 rounded-xl bg-cyan-50 py-2.5 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-100"
              >
                <Download class="h-4 w-4" />
                Download App
              </a>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Checkbox } from '@/components/ui/checkbox';
import {
  ArrowLeft,
  ArrowRight,
  Banknote,
  Check,
  CheckCircle,
  CircleAlert,
  CircleHelp,
  Clock3,
  CreditCard,
  Download,
  Headset,
  ShieldCheck,
  Smartphone,
  UserCircle2,
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
    badge: 'Popular',
    methods: ['Card', 'Transfer', 'USSD', 'QR'],
  },
  monnify: {
    label: 'Monnify',
    badge: 'Bank',
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
  renew: false,
  hotspot_return: props.hotspot.return_url || '',
  hotspot_dst: props.hotspot.dst || '',
});

const selectedPlan = computed<Plan | null>(() => {
  return props.plans.find((plan) => plan.plan_type === selectedPlanType.value) ?? null;
});

const hasHotspotReturn = computed<boolean>(() => form.hotspot_return.trim().length > 0);

const isDirectPurchase = computed<boolean>(() => !hasHotspotReturn.value);

const hasValidPhone = computed<boolean>(() => {
  const cleaned = form.phone_number.replace(/\s+/g, '');
  return /^\+?\d{8,20}$/.test(cleaned);
});

const canSubmit = computed<boolean>(() => {
  return (
    hasValidPhone.value &&
    selectedPlan.value !== null &&
    selectedGateway.value.trim().length > 0 &&
    !form.processing
  );
});

const phonePreview = computed<string>(() => {
  if (!form.phone_number.trim()) return 'Not entered';
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
  if (name.includes('hourly')) return '4 hours';
  if (name.includes('24')) return '24 hours';
  if (name.includes('week')) return '7 days';
  if (name.includes('month')) return '30 days';
  return 'Timed access';
};

const selectPlan = (plan: Plan): void => {
  selectedPlanType.value = plan.plan_type;
  form.plan_type = plan.plan_type;
  currentStage.value = hasValidPhone.value ? 3 : 2;
};

const continueFromPhone = (): void => {
  if (!hasValidPhone.value) return;
  currentStage.value = 3;
};

const selectGateway = (gateway: string): void => {
  selectedGateway.value = gateway;
  form.gateway = gateway;
};

const goToStage = (stage: Stage): void => {
  if (stage === 2 && !selectedPlan.value) return;
  if (stage === 3 && (!selectedPlan.value || !hasValidPhone.value)) return;
  currentStage.value = stage;
};

const submit = (): void => {
  if (!selectedPlan.value) return;
  form.phone_number = form.phone_number.replace(/\s+/g, '');
  form.plan_type = selectedPlan.value.plan_type;
  form.gateway = selectedGateway.value;
  form.post('/app/start', { preserveScroll: true });
};
</script>

<style>
/* Smooth transitions for stage changes */
.animate-in {
  animation-duration: 0.3s;
  animation-fill-mode: both;
}

.slide-in-from-left-5 {
  animation-name: slideInFromLeft;
}

.slide-in-from-right-5 {
  animation-name: slideInFromRight;
}

.slide-in-from-bottom-5 {
  animation-name: slideInFromBottom;
}

.fade-in {
  animation-name: fadeIn;
}

@keyframes slideInFromLeft {
  from {
    transform: translateX(-1.25rem);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slideInFromRight {
  from {
    transform: translateX(1.25rem);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slideInFromBottom {
  from {
    transform: translateY(1rem);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style>