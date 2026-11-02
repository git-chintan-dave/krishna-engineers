
<script setup lang="ts">
import {computed, ref, watch} from 'vue';
import {getLocalTimeZone, parseDate, today,} from '@internationalized/date';
import {CalendarDays, X} from '@lucide/vue';

import {Button} from '@/components/ui/button';
import {Popover, PopoverContent, PopoverTrigger,} from '@/components/ui/popover';
import {RangeCalendar} from '@/components/ui/range-calendar';

interface Props {
    startDate?: string;
    endDate?: string;
    placeholder?: string;
    clearable?: boolean;
    disabled?: boolean;
    numberOfMonths?: number;
}

interface DateRangeValue {
    startDate: string;
    endDate: string;
}

type CalendarProps = InstanceType<typeof RangeCalendar>['$props'];
type CalendarRange = NonNullable<CalendarProps['modelValue']>;
type CalendarDateValue = NonNullable<CalendarRange['start']>;

const props = withDefaults(defineProps<Props>(), {
    startDate: '',
    endDate: '',
    placeholder: 'Select date range',
    clearable: true,
    disabled: false,
    numberOfMonths: 2,
});

const emit = defineEmits<{
    'update:startDate': [value: string];
    'update:endDate': [value: string];
    change: [value: DateRangeValue];
    clear: [];
}>();

const open = ref(false);

const parseDateValue = (
    value: string,
): CalendarDateValue | undefined => {
    if (!value) {
        return undefined;
    }

    try {
        return parseDate(value) as unknown as CalendarDateValue;
    } catch {
        return undefined;
    }
};

const getDateRangeFromProps = (): CalendarRange | undefined => {
    const start = parseDateValue(props.startDate);
    const end = parseDateValue(props.endDate);

    if (!start) {
        return undefined;
    }

    return {
        start,
        end: end ?? start,
    };
};

const dateRange = ref<CalendarRange | undefined>(
    getDateRangeFromProps(),
);

watch(
    () => [props.startDate, props.endDate],
    () => {
        dateRange.value = getDateRangeFromProps();
    },
);

const hasValue = computed(() =>
    Boolean(props.startDate || props.endDate),
);

const formatDate = (value: string): string => {
    const [year, month, day] = value.split('-').map(Number);

    return new Intl.DateTimeFormat('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(year, month - 1, day));
};

const displayValue = computed(() => {
    const range = dateRange.value;

    if (!range?.start) {
        return props.placeholder;
    }

    const startDate = range.start.toString();
    const endDate = range.end?.toString();

    if (!endDate || startDate === endDate) {
        return formatDate(startDate);
    }

    return `${formatDate(startDate)} – ${formatDate(endDate)}`;
});

const handleRangeChange = (
    value: CalendarRange | undefined,
): void => {
    dateRange.value = value;

    // Wait until both dates have been selected.
    if (!value?.start || !value.end) {
        return;
    }

    const startDate = value.start.toString();
    const endDate = value.end.toString();

    emit('update:startDate', startDate);
    emit('update:endDate', endDate);

    emit('change', {
        startDate,
        endDate,
    });

    open.value = false;
};

const clear = (): void => {
    dateRange.value = undefined;

    emit('update:startDate', '');
    emit('update:endDate', '');

    emit('change', {
        startDate: '',
        endDate: '',
    });

    emit('clear');

    open.value = false;
};
</script>

<template>
    <div class="relative w-full">
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    :disabled="disabled"
                    :class="[
                        'h-9 w-full justify-start px-3 pr-9 text-left font-normal',
                        !hasValue && 'text-muted-foreground',
                    ]"
                    type="button"
                    variant="outline"
                >
                    <CalendarDays
                        aria-hidden="true"
                        class="mr-2 size-4 shrink-0"
                    />

                    <span class="min-w-0 flex-1 truncate">
                        {{ displayValue }}
                    </span>
                </Button>
            </PopoverTrigger>

            <PopoverContent
                align="start"
                :side-offset="6"
                class="w-[calc(100vw-2rem)] max-w-fit overflow-hidden p-2 sm:w-auto sm:p-3"
            >
                <RangeCalendar
                    v-model="dateRange as any"
                    :default-placeholder="today(getLocalTimeZone()).subtract({
                        months: 1,
                    })"
                    :max-value="today(getLocalTimeZone())"
                    :number-of-months="numberOfMonths"
                    initial-focus
                    @update:model-value="handleRangeChange"
                />
            </PopoverContent>
        </Popover>

        <button
            v-if="clearable && hasValue && !disabled"
            aria-label="Clear date range"
            class="absolute right-2 top-1/2 z-10 inline-flex size-5 -translate-y-1/2 cursor-pointer items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            type="button"
            @click.stop="clear"
        >
            <X
                aria-hidden="true"
                class="size-3.5"
            />
        </button>
    </div>
</template>
