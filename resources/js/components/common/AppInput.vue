<script lang="ts" setup>
import type { DateValue } from '@internationalized/date';
import { parseDate } from '@internationalized/date';
import {
    CalendarDays,
    Clock3,
    Eye,
    EyeOff,
    LoaderCircle,
    X,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, nextTick, ref, useAttrs } from 'vue';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Textarea } from '@/components/ui/textarea';

defineOptions({
    inheritAttrs: false,
});

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type InputType =
    | 'text'
    | 'password'
    | 'email'
    | 'number'
    | 'url'
    | 'tel'
    | 'search'
    | 'date'
    | 'time'
    | 'datetime-local'
    | 'month'
    | 'week'
    | 'color'
    | 'file';

type ModelValue = string | number | null;

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        type?: InputType;
        placeholder?: string;
        disabled?: boolean;
        readonly?: boolean;
        required?: boolean;
        loading?: boolean;
        clearable?: boolean;

        icon?: Component | null;
        prefix?: string;
        suffix?: string;

        textarea?: boolean;
        rows?: number;

        helper?: string;
        error?: string;

        datePlaceholder?: string;
        timePlaceholder?: string;
        datetimePlaceholder?: string;
        searchPlaceholder?: string;
    }>(),
    {
        type: 'text',
        placeholder: '',
        disabled: false,
        readonly: false,
        required: false,
        loading: false,
        clearable: false,

        icon: null,
        prefix: '',
        suffix: '',

        textarea: false,
        rows: 4,

        helper: '',
        error: '',

        datePlaceholder: 'Select date',
        timePlaceholder: 'Select time',
        datetimePlaceholder: 'Select date and time',
        searchPlaceholder: 'Search...',
    },
);

/*
|--------------------------------------------------------------------------
| Model
|--------------------------------------------------------------------------
*/

const model = defineModel<ModelValue>({
    default: null,
});

/*
|--------------------------------------------------------------------------
| Attributes
|--------------------------------------------------------------------------
*/

const attrs = useAttrs();

/*
|--------------------------------------------------------------------------
| Refs
|--------------------------------------------------------------------------
*/

const inputRef = ref<HTMLInputElement | null>(null);

const textareaRef = ref<HTMLTextAreaElement | null>(null);

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const datePickerOpen = ref(false);
const timePickerOpen = ref(false);
const datetimePickerOpen = ref(false);
const showPassword = ref(false);

/*
|--------------------------------------------------------------------------
| Time Options
|--------------------------------------------------------------------------
*/

const hours = Array.from({ length: 12 }, (_, index) =>
    String(index + 1).padStart(2, '0'),
);

const minutes = Array.from({ length: 60 }, (_, index) =>
    String(index).padStart(2, '0'),
);

const periods = ['AM', 'PM'] as const;

/*
|--------------------------------------------------------------------------
| Input State
|--------------------------------------------------------------------------
*/

const isDateInput = computed(() => props.type === 'date');

const isTimeInput = computed(() => props.type === 'time');

const isDatetimeInput = computed(() => props.type === 'datetime-local');

const isNativePickerInput = computed(() =>
    ['month', 'week'].includes(props.type),
);

const isPasswordInput = computed(() => props.type === 'password');

const resolvedInputType = computed(() => {
    if (!isPasswordInput.value) {
        return props.type;
    }

    return showPassword.value ? 'text' : 'password';
});

/*
|--------------------------------------------------------------------------
| Picker Icon
|--------------------------------------------------------------------------
|
| All special picker inputs use a leading icon.
|
| Explicit icon wins over the default icon.
|
*/

const pickerIcon = computed<Component | null>(() => {
    if (props.icon) {
        return props.icon;
    }

    if (
        isDateInput.value ||
        isDatetimeInput.value ||
        isNativePickerInput.value
    ) {
        return CalendarDays;
    }

    if (isTimeInput.value) {
        return Clock3;
    }

    return null;
});

/*
|--------------------------------------------------------------------------
| Layout State
|--------------------------------------------------------------------------
*/

const hasLeadingContent = computed(() => !!pickerIcon.value || !!props.prefix);

const hasTrailingContent = computed(
    () =>
        !!props.suffix ||
        props.clearable ||
        isPasswordInput.value ||
        props.loading,
);

const hasError = computed(() => !!props.error);

const hasValue = computed(
    () =>
        model.value !== null &&
        model.value !== undefined &&
        String(model.value).length > 0,
);

/*
|--------------------------------------------------------------------------
| Input Classes
|--------------------------------------------------------------------------
*/

const inputClasses = computed(() => [
    'flex w-full min-w-0 rounded-md border bg-background text-sm transition-colors',
    'placeholder:text-muted-foreground',
    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/20',
    'disabled:cursor-not-allowed disabled:opacity-50',
    'read-only:cursor-default',

    props.textarea ? 'min-h-24 py-2' : 'h-9',

    hasLeadingContent.value ? 'pl-10' : 'pl-3',

    hasTrailingContent.value ? 'pr-10' : 'pr-3',

    hasError.value
        ? [
              'border-destructive',
              'focus-visible:border-destructive',
              'focus-visible:ring-destructive/20',
          ]
        : 'border-input',
]);

/*
|--------------------------------------------------------------------------
| Native Model
|--------------------------------------------------------------------------
*/

const nativeModel = computed<string | number | undefined>({
    get: () => model.value ?? undefined,

    set: (value) => {
        model.value = value ?? null;
    },
});

/*
|--------------------------------------------------------------------------
| Date Helpers
|--------------------------------------------------------------------------
*/

const getDatePart = (value: string): string => {
    if (!value) {
        return '';
    }

    if (value.includes('T')) {
        return value.split('T')[0];
    }

    return value.slice(0, 10);
};

const getTimePart = (value: string): string => {
    if (!value) {
        return '';
    }

    if (value.includes('T')) {
        return value.split('T')[1]?.slice(0, 5) ?? '';
    }

    return value.slice(0, 5);
};

/*
|--------------------------------------------------------------------------
| Date Picker
|--------------------------------------------------------------------------
*/

const selectedDate = computed<DateValue | undefined>({
    get() {
        const value =
            typeof model.value === 'string' ? getDatePart(model.value) : '';

        if (!value) {
            return undefined;
        }

        try {
            return parseDate(value);
        } catch {
            return undefined;
        }
    },

    set(value) {
        if (!value) {
            model.value = null;

            return;
        }

        model.value = value.toString();
    },
});

const formattedDate = computed(() => {
    const value =
        typeof model.value === 'string' ? getDatePart(model.value) : '';

    if (!value) {
        return '';
    }

    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return '';
    }

    const date = new Date(year, month - 1, day);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date);
});

const selectDate = (value: DateValue | undefined): void => {
    if (!value) {
        model.value = null;
        datePickerOpen.value = false;

        return;
    }

    model.value = value.toString();

    datePickerOpen.value = false;
};

/*
|--------------------------------------------------------------------------
| DateTime Picker
|--------------------------------------------------------------------------
*/

const selectedDatetimeDate = computed<DateValue | undefined>({
    get() {
        const value =
            typeof model.value === 'string' ? getDatePart(model.value) : '';

        if (!value) {
            return undefined;
        }

        try {
            return parseDate(value);
        } catch {
            return undefined;
        }
    },

    set(value) {
        if (!value) {
            model.value = null;

            return;
        }

        const currentTime =
            typeof model.value === 'string' ? getTimePart(model.value) : '';

        model.value = `${value.toString()}T${currentTime || '00:00'}`;
    },
});

const formattedDatetime = computed(() => {
    if (typeof model.value !== 'string' || !model.value) {
        return '';
    }

    const dateValue = getDatePart(model.value);

    const timeValue = getTimePart(model.value);

    if (!dateValue) {
        return '';
    }

    const [year, month, day] = dateValue.split('-').map(Number);

    if (!year || !month || !day) {
        return '';
    }

    const date = new Date(year, month - 1, day);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const formattedDateValue = new Intl.DateTimeFormat(undefined, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date);

    if (!timeValue) {
        return formattedDateValue;
    }

    const [hour = '00', minute = '00'] = timeValue.split(':');

    const time = new Date(2000, 0, 1, Number(hour), Number(minute));

    const formattedTimeValue = new Intl.DateTimeFormat(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    }).format(time);

    return `${formattedDateValue}, ${formattedTimeValue}`;
});

const selectDatetimeDate = (value: DateValue | undefined): void => {
    if (!value) {
        model.value = null;

        return;
    }

    const currentTime =
        typeof model.value === 'string' ? getTimePart(model.value) : '';

    model.value = `${value.toString()}T${currentTime || '00:00'}`;
};

/*
|--------------------------------------------------------------------------
| Time Picker
|--------------------------------------------------------------------------
*/

const timeParts = computed(() => {
    const value =
        typeof model.value === 'string' ? getTimePart(model.value) : '';

    const [hour = '', minute = ''] = value.split(':');

    const hourNumber = Number(hour);

    if (!hour || Number.isNaN(hourNumber)) {
        return {
            hour: '12',
            minute: '00',
            period: 'AM' as const,
        };
    }

    return {
        hour: String(hourNumber % 12 || 12).padStart(2, '0'),

        minute: minute.padStart(2, '0').slice(0, 2),

        period: hourNumber >= 12 ? ('PM' as const) : ('AM' as const),
    };
});

const selectedHour = computed(() => timeParts.value.hour);

const selectedMinute = computed(() => timeParts.value.minute);

const selectedPeriod = computed(() => timeParts.value.period);

const formattedTime = computed(() => {
    if (!model.value) {
        return '';
    }

    return `${selectedHour.value}:${selectedMinute.value} ${selectedPeriod.value}`;
});

const updateTime = (
    hour: string,
    minute: string,
    period: 'AM' | 'PM',
): void => {
    let hourNumber = Number(hour);

    if (period === 'AM' && hourNumber === 12) {
        hourNumber = 0;
    }

    if (period === 'PM' && hourNumber !== 12) {
        hourNumber += 12;
    }

    model.value = `${String(hourNumber).padStart(2, '0')}:${minute}`;
};

const updateDatetimeTime = (
    hour: string,
    minute: string,
    period: 'AM' | 'PM',
): void => {
    let hourNumber = Number(hour);

    if (period === 'AM' && hourNumber === 12) {
        hourNumber = 0;
    }

    if (period === 'PM' && hourNumber !== 12) {
        hourNumber += 12;
    }

    const dateValue =
        typeof model.value === 'string' ? getDatePart(model.value) : '';

    const fallbackDate = new Date().toISOString().slice(0, 10);

    model.value = `${
        dateValue || fallbackDate
    }T${String(hourNumber).padStart(2, '0')}:${minute}`;
};

const selectHour = (hour: string): void => {
    updateTime(hour, selectedMinute.value, selectedPeriod.value);
};

const selectMinute = (minute: string): void => {
    updateTime(selectedHour.value, minute, selectedPeriod.value);
};

const selectPeriod = (period: 'AM' | 'PM'): void => {
    updateTime(selectedHour.value, selectedMinute.value, period);
};

const selectDatetimeHour = (hour: string): void => {
    updateDatetimeTime(hour, selectedMinute.value, selectedPeriod.value);
};

const selectDatetimeMinute = (minute: string): void => {
    updateDatetimeTime(selectedHour.value, minute, selectedPeriod.value);
};

const selectDatetimePeriod = (period: 'AM' | 'PM'): void => {
    updateDatetimeTime(selectedHour.value, selectedMinute.value, period);
};

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const clear = (): void => {
    model.value = null;

    nextTick(() => {
        focus();
    });
};

const togglePassword = (): void => {
    showPassword.value = !showPassword.value;

    nextTick(() => {
        focus();
    });
};

const focus = (): void => {
    if (props.textarea) {
        textareaRef.value?.focus();

        return;
    }

    inputRef.value?.focus();
};

defineExpose({
    focus,
    clear,
});
</script>

<template>
    <div class="w-full min-w-0">
        <!--
        |--------------------------------------------------------------------------
        | Date Picker
        |--------------------------------------------------------------------------
        -->

        <Popover v-if="isDateInput" v-model:open="datePickerOpen">
            <PopoverTrigger as-child>
                <button
                    :aria-invalid="hasError"
                    :class="[
                        inputClasses,
                        'relative flex cursor-pointer items-center text-left',
                        !formattedDate && 'text-muted-foreground',
                    ]"
                    :disabled="disabled || readonly"
                    type="button"
                    v-bind="attrs"
                >
                    <component
                        :is="pickerIcon"
                        aria-hidden="true"
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />

                    <span class="block min-w-0 truncate">
                        {{ formattedDate || placeholder || datePlaceholder }}
                    </span>

                    <span
                        v-if="suffix"
                        class="pointer-events-none absolute top-1/2 right-3 max-w-[30%] -translate-y-1/2 truncate text-sm text-muted-foreground"
                    >
                        {{ suffix }}
                    </span>
                </button>
            </PopoverTrigger>

            <PopoverContent align="start" class="w-auto p-0">
                <Calendar
                    v-model="selectedDate"
                    initial-focus
                    @update:model-value="selectDate"
                />
            </PopoverContent>
        </Popover>

        <!--
        |--------------------------------------------------------------------------
        | Time Picker
        |--------------------------------------------------------------------------
        -->

        <Popover v-else-if="isTimeInput" v-model:open="timePickerOpen">
            <PopoverTrigger as-child>
                <button
                    :aria-invalid="hasError"
                    :class="[
                        inputClasses,
                        'relative flex cursor-pointer items-center text-left',
                        !formattedTime && 'text-muted-foreground',
                    ]"
                    :disabled="disabled || readonly"
                    type="button"
                    v-bind="attrs"
                >
                    <component
                        :is="pickerIcon"
                        aria-hidden="true"
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />

                    <span class="block min-w-0 truncate">
                        {{ formattedTime || placeholder || timePlaceholder }}
                    </span>

                    <span
                        v-if="suffix"
                        class="pointer-events-none absolute top-1/2 right-3 max-w-[30%] -translate-y-1/2 truncate text-sm text-muted-foreground"
                    >
                        {{ suffix }}
                    </span>
                </button>
            </PopoverTrigger>

            <PopoverContent align="start" class="w-auto min-w-64 p-3">
                <div class="grid grid-cols-3 gap-2">
                    <!-- Hours -->

                    <div class="min-w-0 space-y-1">
                        <p
                            class="px-2 text-xs font-medium text-muted-foreground"
                        >
                            Hour
                        </p>

                        <div class="max-h-56 overflow-y-auto rounded-md border">
                            <button
                                v-for="hour in hours"
                                :key="hour"
                                :class="[
                                    'flex h-9 w-full items-center justify-center text-sm transition-colors',
                                    'hover:bg-accent hover:text-accent-foreground',
                                    'focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none',
                                    selectedHour === hour &&
                                        'bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground',
                                ]"
                                type="button"
                                @click="selectHour(hour)"
                            >
                                {{ hour }}
                            </button>
                        </div>
                    </div>

                    <!-- Minutes -->

                    <div class="min-w-0 space-y-1">
                        <p
                            class="px-2 text-xs font-medium text-muted-foreground"
                        >
                            Minute
                        </p>

                        <div class="max-h-56 overflow-y-auto rounded-md border">
                            <button
                                v-for="minute in minutes"
                                :key="minute"
                                :class="[
                                    'flex h-9 w-full items-center justify-center text-sm transition-colors',
                                    'hover:bg-accent hover:text-accent-foreground',
                                    'focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none',
                                    selectedMinute === minute &&
                                        'bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground',
                                ]"
                                type="button"
                                @click="selectMinute(minute)"
                            >
                                {{ minute }}
                            </button>
                        </div>
                    </div>

                    <!-- Period -->

                    <div class="min-w-0 space-y-1">
                        <p
                            class="px-2 text-xs font-medium text-muted-foreground"
                        >
                            Period
                        </p>

                        <div class="rounded-md border">
                            <button
                                v-for="period in periods"
                                :key="period"
                                :class="[
                                    'flex h-9 w-full items-center justify-center text-sm transition-colors',
                                    'hover:bg-accent hover:text-accent-foreground',
                                    'focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none',
                                    selectedPeriod === period &&
                                        'bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground',
                                ]"
                                type="button"
                                @click="selectPeriod(period)"
                            >
                                {{ period }}
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-3 flex items-center justify-between gap-3 border-t pt-3"
                >
                    <span class="text-xs text-muted-foreground">
                        Selected time
                    </span>

                    <span class="text-sm font-medium">
                        {{ formattedTime || '—' }}
                    </span>
                </div>
            </PopoverContent>
        </Popover>

        <!--
        |--------------------------------------------------------------------------
        | Date & Time Picker
        |--------------------------------------------------------------------------
        -->

        <Popover v-else-if="isDatetimeInput" v-model:open="datetimePickerOpen">
            <PopoverTrigger as-child>
                <button
                    :aria-invalid="hasError"
                    :class="[
                        inputClasses,
                        'relative flex cursor-pointer items-center text-left',
                        !formattedDatetime && 'text-muted-foreground',
                    ]"
                    :disabled="disabled || readonly"
                    type="button"
                    v-bind="attrs"
                >
                    <component
                        :is="pickerIcon"
                        aria-hidden="true"
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />

                    <span class="block min-w-0 truncate">
                        {{
                            formattedDatetime ||
                            placeholder ||
                            datetimePlaceholder
                        }}
                    </span>

                    <span
                        v-if="suffix"
                        class="pointer-events-none absolute top-1/2 right-3 max-w-[30%] -translate-y-1/2 truncate text-sm text-muted-foreground"
                    >
                        {{ suffix }}
                    </span>
                </button>
            </PopoverTrigger>

            <PopoverContent
                align="start"
                class="w-auto max-w-[calc(100vw-2rem)] min-w-88 p-3"
            >
                <div class="space-y-4">
                    <!-- Date -->

                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <CalendarDays
                                class="size-4 text-muted-foreground"
                            />

                            <span class="text-sm font-medium"> Date </span>
                        </div>

                        <Calendar
                            v-model="selectedDatetimeDate"
                            initial-focus
                            @update:model-value="selectDatetimeDate"
                        />
                    </div>

                    <!-- Time -->

                    <div class="border-t pt-4">
                        <div class="mb-2 flex items-center gap-2">
                            <Clock3 class="size-4 text-muted-foreground" />

                            <span class="text-sm font-medium"> Time </span>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <!-- Hours -->

                            <div class="min-w-0 space-y-1">
                                <p class="px-2 text-xs text-muted-foreground">
                                    Hour
                                </p>

                                <div
                                    class="max-h-40 overflow-y-auto rounded-md border"
                                >
                                    <button
                                        v-for="hour in hours"
                                        :key="hour"
                                        :class="[
                                            'flex h-8 w-full items-center justify-center text-sm transition-colors',
                                            'hover:bg-accent hover:text-accent-foreground',
                                            'focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none',
                                            selectedHour === hour &&
                                                'bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground',
                                        ]"
                                        type="button"
                                        @click="selectDatetimeHour(hour)"
                                    >
                                        {{ hour }}
                                    </button>
                                </div>
                            </div>

                            <!-- Minutes -->

                            <div class="min-w-0 space-y-1">
                                <p class="px-2 text-xs text-muted-foreground">
                                    Minute
                                </p>

                                <div
                                    class="max-h-40 overflow-y-auto rounded-md border"
                                >
                                    <button
                                        v-for="minute in minutes"
                                        :key="minute"
                                        :class="[
                                            'flex h-8 w-full items-center justify-center text-sm transition-colors',
                                            'hover:bg-accent hover:text-accent-foreground',
                                            'focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none',
                                            selectedMinute === minute &&
                                                'bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground',
                                        ]"
                                        type="button"
                                        @click="selectDatetimeMinute(minute)"
                                    >
                                        {{ minute }}
                                    </button>
                                </div>
                            </div>

                            <!-- Period -->

                            <div class="min-w-0 space-y-1">
                                <p class="px-2 text-xs text-muted-foreground">
                                    Period
                                </p>

                                <div class="rounded-md border">
                                    <button
                                        v-for="period in periods"
                                        :key="period"
                                        :class="[
                                            'flex h-8 w-full items-center justify-center text-sm transition-colors',
                                            'hover:bg-accent hover:text-accent-foreground',
                                            'focus-visible:bg-accent focus-visible:text-accent-foreground focus-visible:outline-none',
                                            selectedPeriod === period &&
                                                'bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground',
                                        ]"
                                        type="button"
                                        @click="selectDatetimePeriod(period)"
                                    >
                                        {{ period }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Value -->

                    <div
                        class="flex items-center justify-between gap-3 border-t pt-3"
                    >
                        <span class="text-xs text-muted-foreground">
                            Selected
                        </span>

                        <span class="truncate text-sm font-medium">
                            {{ formattedDatetime || 'Select date and time' }}
                        </span>
                    </div>

                    <!-- Done -->

                    <Button
                        :disabled="!model"
                        class="w-full"
                        type="button"
                        @click="datetimePickerOpen = false"
                    >
                        Done
                    </Button>
                </div>
            </PopoverContent>
        </Popover>

        <!--
        |--------------------------------------------------------------------------
        | Native Input
        |--------------------------------------------------------------------------
        -->

        <div v-else class="relative w-full min-w-0">
            <!-- Leading Icon / Prefix -->

            <component
                :is="pickerIcon || props.icon"
                v-if="pickerIcon || props.icon"
                aria-hidden="true"
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />

            <span
                v-else-if="prefix"
                class="pointer-events-none absolute top-1/2 left-3 max-w-[35%] -translate-y-1/2 truncate text-sm text-muted-foreground"
            >
                {{ prefix }}
            </span>

            <!-- Loading -->

            <LoaderCircle
                v-if="loading"
                aria-hidden="true"
                class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 animate-spin text-muted-foreground"
            />

            <!-- Clear -->

            <button
                v-else-if="clearable && hasValue && !disabled && !readonly"
                aria-label="Clear input"
                class="absolute top-1/2 right-2 flex size-7 -translate-y-1/2 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                tabindex="-1"
                type="button"
                @click="clear"
            >
                <X aria-hidden="true" class="size-4" />
            </button>

            <!-- Password -->

            <button
                v-else-if="isPasswordInput"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                :disabled="disabled || readonly"
                class="absolute top-1/2 right-2 flex size-7 -translate-y-1/2 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none"
                tabindex="-1"
                type="button"
                @click="togglePassword"
            >
                <EyeOff v-if="showPassword" aria-hidden="true" class="size-4" />

                <Eye v-else aria-hidden="true" class="size-4" />
            </button>

            <!-- Suffix -->

            <span
                v-else-if="suffix"
                class="pointer-events-none absolute top-1/2 right-3 max-w-[30%] -translate-y-1/2 truncate text-sm text-muted-foreground"
            >
                {{ suffix }}
            </span>

            <!-- Textarea -->

            <Textarea
                v-if="textarea"
                ref="textareaRef"
                v-model="nativeModel"
                :class="inputClasses"
                :disabled="disabled"
                :placeholder="placeholder"
                :readonly="readonly"
                :required="required"
                :rows="rows"
                v-bind="attrs"
            />

            <!-- Input -->

            <Input
                v-else
                ref="inputRef"
                v-model="nativeModel"
                :class="inputClasses"
                :disabled="disabled"
                :placeholder="
                    props.type === 'search'
                        ? placeholder || searchPlaceholder
                        : placeholder
                "
                :readonly="readonly"
                :required="required"
                :type="resolvedInputType"
                v-bind="attrs"
            />
        </div>

        <!-- Helper -->

        <p v-if="helper && !error" class="mt-1 text-xs text-muted-foreground">
            {{ helper }}
        </p>

        <!-- Error -->

        <p v-if="error" class="mt-1 text-xs text-destructive" role="alert">
            {{ error }}
        </p>

        <!-- Description Slot -->

        <slot name="description" />
    </div>
</template>
