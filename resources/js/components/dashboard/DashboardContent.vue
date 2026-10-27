<script lang="ts" setup>
import {computed} from 'vue';

import type {DashboardPayload, TodayShiftSummary} from '@/types/dashboard';
import {RefreshCw} from "@lucide/vue";

const props = defineProps<{
    dashboardData?: DashboardPayload;
    loading: boolean;
    refreshing: boolean;
    roleLabel: string;
}>();

defineEmits<{
    refresh: [];
    refreshSection: [section: string];
}>();

const STANDARD_SHIFT_SECONDS = 12 * 60 * 60;
const STANDARD_SHIFT_LABEL = '12:00:00';

const todayShift = computed(() => props.dashboardData?.todayShift);
const stats = computed(() => props.dashboardData?.stats ?? []);
const sections = computed(() => props.dashboardData?.sections ?? []);

function formatDuration(seconds: number | null | undefined): string {
    const safeSeconds = Math.max(0, Math.floor(seconds ?? 0));
    const hours = Math.floor(safeSeconds / 3600);
    const minutes = Math.floor((safeSeconds % 3600) / 60);
    const remainingSeconds = safeSeconds % 60;

    return [
        String(hours).padStart(2, '0'),
        String(minutes).padStart(2, '0'),
        String(remainingSeconds).padStart(2, '0'),
    ].join(':');
}

function shiftProgressPercent(totalSeconds: number): number {
    if (totalSeconds <= 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.round((totalSeconds / STANDARD_SHIFT_SECONDS) * 100),
    );
}

function statusClass(status: TodayShiftSummary['status']): string {
    switch (status) {
        case 'on_shift':
            return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
        case 'on_break':
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-400';
        case 'completed':
            return 'bg-blue-500/10 text-blue-700 dark:text-blue-400';
        default:
            return 'bg-muted text-muted-foreground';
    }
}

function progressClass(status: TodayShiftSummary['status']): string {
    switch (status) {
        case 'on_shift':
            return 'bg-emerald-500';
        case 'on_break':
            return 'bg-amber-500';
        case 'completed':
            return 'bg-blue-500';
        default:
            return 'bg-muted-foreground';
    }
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-sm font-medium text-muted-foreground">
                    {{ roleLabel }}
                </p>

                <h1 class="mt-1 text-2xl font-semibold tracking-tight">
                    {{ dashboardData?.title ?? 'Dashboard' }}
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                    {{ dashboardData?.description ?? 'Your dashboard information and daily activity.' }}
                </p>
            </div>

            <button
                type="button"
                class="inline-flex h-9 items-center gap-2 rounded-md border border-border px-3 text-sm font-medium hover:bg-muted disabled:opacity-50"
                :disabled="refreshing"
                @click="$emit('refresh')"
            >
                <RefreshCw
                    class="size-4"
                    :class="{ 'animate-spin': refreshing }"
                    aria-hidden="true"
                />

                {{ refreshing ? 'Refreshing…' : 'Refresh' }}
            </button>
        </header>

        <!-- Initial loading -->
        <template v-if="loading && !dashboardData">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="index in 4"
                    :key="index"
                    class="h-28 animate-pulse rounded-xl border border-border bg-muted/40"
                />
            </section>

            <section class="h-72 animate-pulse rounded-xl border border-border bg-muted/40" />
        </template>

        <template v-else-if="dashboardData">
            <!-- Today's shift -->
            <section class="overflow-hidden rounded-xl border border-border bg-card">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="font-semibold tracking-tight">
                            Today's Shift
                        </h2>

                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ todayShift?.shiftLabel ?? 'Shift information' }}
                        </p>
                    </div>

                    <span
                        v-if="todayShift"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-medium"
                        :class="statusClass(todayShift.status)"
                    >
                        <span class="size-1.5 rounded-full bg-current" aria-hidden="true" />
                        {{ todayShift.statusLabel }}
                    </span>
                </header>

                <div v-if="todayShift" class="space-y-6 p-5 sm:p-6">
                    <!-- Shift progress -->
                    <div
                        v-if="todayShift.status !== 'not_recorded'"
                        class="rounded-lg bg-muted/30 p-4 sm:p-5"
                    >
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium">
                                    Shift progress
                                </p>

                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ formatDuration(todayShift.totalSeconds) }}
                                    <span class="mx-1">/</span>
                                    {{ STANDARD_SHIFT_LABEL }} target
                                </p>
                            </div>

                            <p class="text-2xl font-semibold tracking-tight tabular-nums">
                                {{ shiftProgressPercent(todayShift.totalSeconds) }}%
                            </p>
                        </div>

                        <div
                            class="mt-4 h-2.5 overflow-hidden rounded-full bg-muted"
                            role="progressbar"
                            aria-label="Shift duration progress"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            :aria-valuenow="shiftProgressPercent(todayShift.totalSeconds)"
                        >
                            <div
                                class="h-full rounded-full transition-[width] duration-500 ease-out"
                                :class="progressClass(todayShift.status)"
                                :style="{ width: `${shiftProgressPercent(todayShift.totalSeconds)}%` }"
                            />
                        </div>

                        <div class="mt-2 flex justify-between text-xs text-muted-foreground">
                            <span>0 hours</span>
                            <span>12 hours</span>
                        </div>
                    </div>

                    <!-- Attendance metrics -->
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <article class="rounded-lg border border-border p-4">
                            <p class="text-sm text-muted-foreground">
                                Clock in
                            </p>
                            <p class="mt-2 text-xl font-semibold tabular-nums">
                                {{ todayShift.checkIn ?? '—' }}
                            </p>
                        </article>

                        <article class="rounded-lg border border-border p-4">
                            <p class="text-sm text-muted-foreground">
                                Clock out
                            </p>
                            <p class="mt-2 text-xl font-semibold tabular-nums">
                                {{ todayShift.checkOut ?? '—' }}
                            </p>
                        </article>

                        <article class="rounded-lg border border-border p-4">
                            <p class="text-sm text-muted-foreground">
                                Total hours
                            </p>
                            <p class="mt-2 text-xl font-semibold tabular-nums">
                                {{ formatDuration(todayShift.totalSeconds) }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Including breaks
                            </p>
                        </article>

                        <article class="rounded-lg border border-border p-4">
                            <p class="text-sm text-muted-foreground">
                                Effective hours
                            </p>
                            <p class="mt-2 text-xl font-semibold tabular-nums">
                                {{ formatDuration(todayShift.effectiveSeconds) }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                After break deduction
                            </p>
                        </article>
                    </div>

                    <!-- Breaks -->
                    <div class="border-t border-border pt-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold">
                                    Breaks
                                </h3>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Recorded break intervals for today
                                </p>
                            </div>

                            <span class="rounded-md bg-muted px-2.5 py-1 text-sm font-medium tabular-nums">
                                {{ formatDuration(todayShift.breakSeconds) }}
                            </span>
                        </div>

                        <div
                            v-if="todayShift.breaks?.length"
                            class="mt-4 divide-y divide-border"
                        >
                            <article
                                v-for="(breakItem, index) in todayShift.breaks"
                                :key="`${breakItem.startAt}-${index}`"
                                class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-sm font-medium">
                                        {{ index + 1 }}
                                    </div>

                                    <div>
                                        <p class="text-sm font-medium">
                                            Break {{ index + 1 }}
                                        </p>

                                        <p
                                            v-if="breakItem.isOngoing"
                                            class="mt-0.5 text-xs text-amber-700 dark:text-amber-400"
                                        >
                                            In progress
                                        </p>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <p class="text-sm font-medium tabular-nums">
                                        {{ breakItem.startAt ?? '—' }}
                                        <span class="mx-1 text-muted-foreground">→</span>
                                        {{ breakItem.endAt ?? 'In progress' }}
                                    </p>

                                    <p class="mt-1 text-xs text-muted-foreground tabular-nums">
                                        {{ formatDuration(breakItem.durationSeconds) }}
                                    </p>
                                </div>
                            </article>
                        </div>

                        <p
                            v-else
                            class="mt-4 rounded-lg border border-dashed border-border px-4 py-5 text-center text-sm text-muted-foreground"
                        >
                            No breaks recorded.
                        </p>
                    </div>

                    <!-- Missing attendance -->
                    <div
                        v-if="todayShift.status === 'not_recorded'"
                        class="rounded-lg border border-border bg-muted/30 p-4"
                    >
                        <p class="text-sm font-medium">
                            No attendance recorded
                        </p>
                        <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                            No attendance record was found for today. This does not automatically mean the employee is absent.
                        </p>
                    </div>
                </div>

                <div v-else class="p-6 text-sm text-muted-foreground">
                    Attendance information is unavailable.
                </div>
            </section>

            <!-- Statistics -->
            <section
                v-if="stats.length"
                class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
            >
                <article
                    v-for="stat in stats"
                    :key="stat.label"
                    class="min-w-0 rounded-xl border border-border bg-card p-4 transition-colors hover:bg-muted/20"
                >
                    <p class="text-sm text-muted-foreground">
                        {{ stat.label }}
                    </p>

                    <p class="mt-2 break-words text-2xl font-semibold tracking-tight tabular-nums">
                        {{ stat.value }}
                    </p>

                    <p class="mt-2 text-xs leading-relaxed text-muted-foreground">
                        {{ stat.description }}
                    </p>
                </article>
            </section>

            <!-- Recent activity -->
            <section
                v-for="section in sections"
                :key="section.title"
                class="overflow-hidden rounded-xl border border-border bg-card"
            >
                <header class="border-b border-border px-5 py-4">
                    <h2 class="font-semibold tracking-tight">
                        {{ section.title }}
                    </h2>
                </header>

                <div v-if="section.items?.length" class="divide-y divide-border">
                    <article
                        v-for="(item, index) in section.items"
                        :key="`${item.title}-${index}`"
                        class="flex flex-wrap items-start justify-between gap-3 px-5 py-4 transition-colors hover:bg-muted/20"
                    >
                        <div class="min-w-0">
                            <p class="break-words text-sm font-medium">
                                {{ item.title }}
                            </p>

                            <p
                                v-if="item.subtitle"
                                class="mt-1 break-words text-sm text-muted-foreground"
                            >
                                {{ item.subtitle }}
                            </p>
                        </div>

                        <p
                            v-if="item.meta"
                            class="max-w-full break-words text-right text-xs text-muted-foreground sm:max-w-[45%]"
                        >
                            {{ item.meta }}
                        </p>
                    </article>
                </div>

                <p
                    v-else
                    class="px-5 py-8 text-center text-sm text-muted-foreground"
                >
                    {{ section.emptyMessage ?? 'No recent activity.' }}
                </p>
            </section>
        </template>

        <!-- No data / loading failure -->
        <section
            v-else
            class="rounded-xl border border-border bg-card p-6"
        >
            <h2 class="font-semibold">
                Dashboard data unavailable
            </h2>

            <p class="mt-2 text-sm text-muted-foreground">
                The dashboard could not load its data. Use Refresh to try again.
            </p>

            <button
                type="button"
                :disabled="refreshing"
                class="mt-4 inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 disabled:pointer-events-none disabled:opacity-60"
                @click="$emit('refresh')"
            >
                {{ refreshing ? 'Refreshing…' : 'Try again' }}
            </button>
        </section>
    </div>
</template>
