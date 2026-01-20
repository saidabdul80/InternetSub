<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import InputError from '@/components/InputError.vue';
import PortalLayout from '@/layouts/PortalLayout.vue';

interface Plan {
    id: number;
    name: string;
    price_kobo: number;
    speed_mbps: number;
    duration_minutes: number;
    mikrotik_profile: string;
}

interface Subscription {
    id: number;
    status: string;
    expires_at: string | null;
    plan: Plan;
}

interface Props {
    plans: Plan[];
    activeSubscription?: Subscription | null;
}

defineProps<Props>();

const page = usePage();
const user = page.props.auth?.user;

const subscribingPlanId = ref<number | null>(null);
const showGuestDialog = ref(false);
const selectedPlanId = ref<number | null>(null);
const contactForm = useForm({
    login: '',
    password: '',
});
const suggestedCodes = ref<string[]>([]);
const existingUser = ref<boolean | null>(null);
const checkingPhone = ref(false);
let phoneCheckTimeout: number | undefined;

const formatPrice = (priceKobo: number) =>
    new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(priceKobo / 100);

const formatDuration = (minutes: number) => {
    if (minutes >= 1440) {
        return `${Math.round(minutes / 1440)} day(s)`;
    }

    if (minutes >= 60) {
        return `${Math.round(minutes / 60)} hour(s)`;
    }

    return `${minutes} min(s)`;
};

const subscribe = (planId: number, payload?: Record<string, string>) => {
    subscribingPlanId.value = planId;
    if (payload) {
        router.post(`/plans/${planId}/checkout`, payload, {
            onFinish: () => {
                subscribingPlanId.value = null;
            },
        });

        return;
    }

    contactForm.post(`/plans/${planId}/checkout`, {
        onFinish: () => {
            subscribingPlanId.value = null;
        },
    });
};

const handleSubscribe = (planId: number) => {
    if (user) {
        subscribe(planId, {});
        return;
    }

    selectedPlanId.value = planId;
    showGuestDialog.value = true;
};

const confirmGuestSubscribe = () => {
    if (selectedPlanId.value === null) {
        return;
    }

    subscribingPlanId.value = selectedPlanId.value;
    contactForm.post(`/plans/${selectedPlanId.value}/checkout`, {
        onSuccess: () => {
            showGuestDialog.value = false;
        },
        onFinish: () => {
            subscribingPlanId.value = null;
        },
    });
};

const phoneRegex = /^(?:\+234|0)(7|8|9)\d{9}$/;

const generateSuggestedCodes = (phone: string): string[] => {
    const raw = phone.replace(/\D/g, '');
    const parts = [raw.slice(0, 4), raw.slice(4, 8), raw.slice(8, 11)];

    return parts.map((part) => {
        const digits = part.slice(-3);
        const letter = String.fromCharCode(65 + Math.floor(Math.random() * 26));
        const prepend = Math.random() > 0.5;
        return prepend ? `${letter}${digits}` : `${digits}${letter}`;
    });
};

watch(
    () => contactForm.login,
    (value) => {
        contactForm.password = '';

        if (phoneRegex.test(value)) {
            window.clearTimeout(phoneCheckTimeout);
            checkingPhone.value = true;
            phoneCheckTimeout = window.setTimeout(async () => {
                try {
                    const response = await fetch(`/plans/phone-check?phone=${encodeURIComponent(value)}`);
                    const data = await response.json();
                    existingUser.value = !!data.exists;
                } catch {
                    existingUser.value = null;
                } finally {
                    checkingPhone.value = false;
                }

                if (!existingUser.value) {
                    suggestedCodes.value = generateSuggestedCodes(value);
                } else {
                    suggestedCodes.value = [];
                }
            }, 300);
        } else {
            suggestedCodes.value = [];
            existingUser.value = null;
            window.clearTimeout(phoneCheckTimeout);
        }
    },
);
</script>

<template>
    <PortalLayout>
        <Head title="Plans" />

        <div class="flex flex-col gap-8">
            <section class="relative overflow-hidden rounded-2xl border border-border bg-gradient-to-br from-amber-50 via-white to-sky-50 p-6 md:p-8">
                <div class="absolute -right-16 -top-16 h-40 w-40 rounded-full bg-amber-200/40 blur-3xl"></div>
                <div class="absolute -left-10 bottom-0 h-28 w-28 rounded-full bg-sky-200/50 blur-3xl"></div>
                <div class="relative flex flex-col gap-4 md:max-w-2xl">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700/80">
                        Instant Hotspot Access
                    </span>
                    <h1 class="text-3xl font-semibold text-foreground md:text-4xl">
                        Pick a plan and get online in minutes.
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Choose a plan below, pay, and you are connected.
                    </p>
                    <div class="flex flex-wrap gap-2 text-xs text-muted-foreground">
                        <div class="rounded-full border border-amber-200 bg-white px-3 py-1">
                            Paystack secure
                        </div>
                        <div class="rounded-full border border-sky-200 bg-white px-3 py-1">
                            Auto-login
                        </div>
                    </div>
                </div>
            </section>

            <Card v-if="activeSubscription" class="border-primary/40">
                <CardHeader>
                    <CardTitle>Active plan</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-2 text-sm">
                    <div class="font-medium text-foreground">
                        {{ activeSubscription.plan.name }}
                    </div>
                    <div class="text-muted-foreground">
                        Expires:
                        {{
                            activeSubscription.expires_at
                                ? new Date(activeSubscription.expires_at).toLocaleString()
                                : 'Not set'
                        }}
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="plan in plans"
                    :key="plan.id"
                    class="group relative flex flex-col justify-between overflow-hidden border border-border bg-white shadow-sm transition-all hover:-translate-y-1 hover:shadow-md"
                >
                    <div class="pointer-events-none absolute inset-0 rounded-lg bg-gradient-to-br from-amber-50/70 via-white to-sky-50/70 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                    <CardHeader>
                        <CardTitle class="relative text-xl">{{ plan.name }}</CardTitle>
                    </CardHeader>
                    <CardContent class="relative flex flex-1 flex-col gap-4">
                        <div class="text-3xl font-semibold text-foreground">
                            {{ formatPrice(plan.price_kobo) }}
                        </div>
                        <div class="flex flex-col gap-2 text-sm text-muted-foreground">
                            <div class="flex items-center justify-between">
                                <span>Speed</span>
                                <span class="font-medium text-foreground">{{ plan.speed_mbps }} Mbps</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Duration</span>
                                <span class="font-medium text-foreground">{{ formatDuration(plan.duration_minutes) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Profile</span>
                                <span class="font-medium text-foreground">{{ plan.mikrotik_profile }}</span>
                            </div>
                        </div>
                        <div class="mt-auto">
                            <Button
                                class="w-full"
                                :disabled="subscribingPlanId === plan.id"
                                @click="handleSubscribe(plan.id)"
                            >
                                <Spinner v-if="subscribingPlanId === plan.id" />
                                Subscribe
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <Dialog v-model:open="showGuestDialog">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="text-sky-800">Continue without logging in</DialogTitle>
                    <DialogDescription >
                        Enter your phone number and pick a 4-character passcode.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="guest-login">Phone number</Label>
                        <Input
                            id="guest-login"
                            type="text"
                            v-model="contactForm.login"
                            placeholder="08012345678"
                        />
                        <InputError :message="contactForm.errors.login" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Suggested passcodes</Label>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="code in suggestedCodes"
                                :key="code"
                                type="button"
                                variant="secondary"
                                class="cursor-pointer hover:bg-sky-600 hover:text-white "
                                :class="code === contactForm.password ? 'bg-sky-200 text-sky-950 font-semibold ' : ''"
                                @click="contactForm.password = code"
                            >
                                {{ code }}
                            </Button>
                            <span
                                v-if="checkingPhone"
                                class="text-xs text-muted-foreground"
                            >
                                Checking phone number...
                            </span>
                            <span
                                v-else-if="existingUser"
                                class="text-xs text-muted-foreground"
                            >
                                Existing user detected. No new passcode needed.
                            </span>
                            <span
                                v-else-if="suggestedCodes.length === 0"
                                class="text-xs text-muted-foreground"
                            >
                                Enter a valid phone number to see suggestions.
                            </span>
                        </div>
                        <InputError :message="contactForm.errors.password" />
                    </div>
                </div>
                <DialogFooter class="mt-4">
                    <Button
                    class="cursor-pointer"
                        :disabled="selectedPlanId === null || checkingPhone || subscribingPlanId === selectedPlanId"
                        @click="confirmGuestSubscribe"
                    >
                        <Spinner v-if="subscribingPlanId === selectedPlanId" />
                        Continue to payment
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </PortalLayout>
</template>
