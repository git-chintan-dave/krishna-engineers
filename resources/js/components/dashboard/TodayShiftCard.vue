<script lang="ts" setup>
import {ArrowRight, CalendarDays, Clock3, Coffee, LogIn, LogOut, RefreshCw,} from '@lucide/vue';

import {Button} from '@/components/ui/button';

interface AttendanceBreak {
    startAt: string | null;
    endAt: string | null;
    durationSeconds: number;
    isOngoing: boolean;
}

interface TodayShift {
    status: 'present' | 'not_recorded';
    statusLabel: string;
    shiftLabel: string;
    checkIn: string | null;
    checkOut: string | null;
    totalWorkingSeconds: number;
    breakSeconds: number;
    effectiveSeconds: number;
    undertimeSeconds: number;
    targetSeconds: number;
    isComplete: boolean;
    breaks: AttendanceBreak[];
}

const props = withDefaults(
    defineProps<{
        attendance?: TodayShift;
        loading?: boolean;
    }>(),
    {
        loading: false,
    },
);

const emit = defineEmits<{
    refresh: [];
    viewAttendance: [];
}>();

function formatDuration(seconds: number): string {
    const safeSeconds = Math.max(0, seconds);
    const hours = Math.floor(safeSeconds / 3600);
    const minutes = Math.floor((safeSeconds % 3600) / 60);

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
}

function progressPercent(attendance: TodayShift): number {
    if (attendance.targetSeconds <= 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.round(
            (attendance.effectiveSeconds / attendance.targetSeconds) * 100,
        ),
    );
}
</script>

<template>
    <article class="min-w-0 rounded-xl border bg-card p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="font-semibold">Today's shift</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Attendance and time breakdown
                </p>
            </div>

            <Button
                aria-label="Refresh attendance"
                :disabled="loading"
                size="icon"
                variant="ghost"
                @click="emit('refresh')"
            >
                <RefreshCw
                    class="size-4"
                    :class="{ 'animate-spin': loading }"
                />
            </Button>
        </div>

        <div v-if="loading && !attendance" class="mt-6 space-y-4">
            <div class="h-5 w-28 animate-pulse rounded bg-muted" />
            <div class="h-16 animate-pulse rounded-lg bg-muted" />
            <div class="h-16 animate-pulse rounded-lg bg-muted" />
            <div class="h-20 animate-pulse rounded-lg bg-muted" />
        </div>

        <div
            v-else-if="!attendance"
            class="mt-6 rounded-lg bg-muted/40 p-5 text-center"
        >
            <CalendarDays
                class="mx-auto size-8 text-muted-foreground"
            />
            <p class="mt-3 font-medium">Attendance unavailable</p>
            <p class="mt-1 text-sm text-muted-foreground">
                We couldn't load today's attendance.
            </p>
            <Button
                class="mt-3"
                size="sm"
                variant="outline"
                @click="emit('refresh')"
            >
                Try again
            </Button>
        </div>

        <template v-else>
            <div class="mt-6 flex items-center justify-between gap-3">
                <span
                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                    :class="
                        attendance.status === 'present'
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                            : 'bg-muted text-muted-foreground'
                    "
                >
                    {{ attendance.statusLabel }}
                </span>

                <span class="text-sm text-muted-foreground">
                    {{ attendance.shiftLabel }}
                </span>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-lg bg-muted/50 p-3">
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <LogIn class="size-4" />
                        <span class="text-xs">Check-in</span>
                    </div>
                    <p class="mt-2 font-semibold tabular-nums">
                        {{ attendance.checkIn ?? '—' }}
                    </p>
                </div>

                <div class="rounded-lg bg-muted/50 p-3">
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <LogOut class="size-4" />
                        <span class="text-xs">Check-out</span>
                    </div>
                    <p class="mt-2 font-semibold tabular-nums">
                        {{ attendance.checkOut ?? 'In progress' }}
                    </p>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted"
                    >
                        <Clock3 class="size-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-muted-foreground">
                            Effective working time
                        </p>
                        <p class="mt-0.5 font-medium tabular-nums">
                            {{ formatDuration(attendance.effectiveSeconds) }}
                            <span class="text-xs font-normal text-muted-foreground">
                                hrs : mins
                            </span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted"
                    >
                        <Coffee class="size-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-muted-foreground">
                            Break time recorded
                        </p>
                        <p class="mt-0.5 font-medium tabular-nums">
                            {{ formatDuration(attendance.breakSeconds) }}
                            <span class="text-xs font-normal text-muted-foreground">
                                hrs : mins
                            </span>
                        </p>
                    </div>
                </div>

                <div
                    v-if="attendance.breaks.length"
                    class="rounded-lg border p-3"
                >
                    <p class="mb-3 text-sm font-medium">Break details</p>

                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in attendance.breaks"
                            :key="index"
                            class="flex items-center justify-between gap-3 text-sm"
                        >
                            <div>
                                <p class="font-medium">
                                    Break {{ index + 1 }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ item.startAt ?? '—' }}
                                    –
                                    {{ item.endAt ?? 'In progress' }}
                                </p>
                            </div>

                            <span class="shrink-0 tabular-nums">
                                {{ formatDuration(item.durationSeconds) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    v-if="attendance.isComplete"
                    class="flex items-center justify-between gap-3 text-sm"
                >
                    <span class="text-muted-foreground">
                        Undertime
                    </span>
                    <span class="font-medium tabular-nums">
                        {{ formatDuration(attendance.undertimeSeconds) }}
                    </span>
                </div>
            </div>

            <div class="mt-5 rounded-lg bg-muted/60 p-3">
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span class="text-muted-foreground">
                        Shift target progress
                    </span>
                    <span class="font-semibold tabular-nums">
                        {{ progressPercent(attendance) }}%
                    </span>
                </div>

                <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-primary transition-[width] duration-300"
                        :style="{
                            width: `${progressPercent(attendance)}%`,
                        }"
                    />
                </div>

                <p class="mt-2 text-xs text-muted-foreground">
                    {{ formatDuration(attendance.effectiveSeconds) }}
                    of
                    {{ formatDuration(attendance.targetSeconds) }}
                    effective hours
                </p>
            </div>
        </template>

        <Button
            class="mt-5 w-full"
            variant="outline"
            @click="emit('viewAttendance')"
        >
            View my attendance
            <ArrowRight class="ml-2 size-4" />
        </Button>
    </article>
</template>
