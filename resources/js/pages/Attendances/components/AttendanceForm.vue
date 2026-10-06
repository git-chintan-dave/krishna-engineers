<script setup lang="ts">
import { computed, reactive } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';
import {
    CalendarDays,
    Clock3,
    Plus,
    Trash2,
    User,
} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import { Button } from '@/components/ui/button';
import { attendancePage } from '@/config/attendances';
import type {
    AttendanceFormBreak,
    AttendanceFormData,
} from '@/types/attendance';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    form: InertiaForm<AttendanceFormData>;
    employees: {
        value: string;
        label: string;
    }[];
    errors: Record<string, string>;
    processing: boolean;
    submitLabel: string;
}>();

const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

/*
|--------------------------------------------------------------------------
| Local Break Time State
|--------------------------------------------------------------------------
|
| The time inputs intentionally use their own local HH:mm values.
|
| We do NOT bind the native time picker directly to the full datetime
| stored in the Inertia form.
|
| This is especially important for overnight attendance:
|
| Attendance:
| 5 Oct 22:00 -> 6 Oct 08:00
|
| Break:
| 6 Oct 01:00 -> 6 Oct 02:00
|
*/

type BreakTimeState = {
    start_at: string;
    end_at: string;
};

const breakTimes = reactive<
    Record<string, BreakTimeState>
>({});

/*
|--------------------------------------------------------------------------
| Date / Time Helpers
|--------------------------------------------------------------------------
*/

const getDatePart = (
    value: string | null | undefined,
): string => {
    if (!value) {
        return '';
    }

    return value
        .replace(' ', 'T')
        .slice(0, 10);
};

const getTimePart = (
    value: string | null | undefined,
): string => {
    if (!value) {
        return '';
    }

    return value
        .replace(' ', 'T')
        .slice(11, 16);
};

const timeToMinutes = (
    value: string,
): number => {
    if (!value) {
        return -1;
    }

    const [hours, minutes] =
        value.split(':').map(Number);

    if (
        Number.isNaN(hours)
        || Number.isNaN(minutes)
    ) {
        return -1;
    }

    return (hours * 60) + minutes;
};

const addOneDay = (
    date: string,
): string => {
    const [year, month, day] =
        date.split('-').map(Number);

    const value = new Date(
        Date.UTC(
            year,
            month - 1,
            day,
        ),
    );

    value.setUTCDate(
        value.getUTCDate() + 1,
    );

    return [
        value.getUTCFullYear(),
        String(
            value.getUTCMonth() + 1,
        ).padStart(2, '0'),
        String(
            value.getUTCDate(),
        ).padStart(2, '0'),
    ].join('-');
};

const combineDateTime = (
    date: string,
    time: string,
): string => {
    if (!date || !time) {
        return '';
    }

    return `${date}T${time}:00`;
};

/*
|--------------------------------------------------------------------------
| Attendance Date
|--------------------------------------------------------------------------
*/

const attendanceDate = computed({
    get: (): string => {
        return props.form.attendance_date;
    },

    set: (value: string): void => {
        setAttendanceDate(value);
    },
});

/*
|--------------------------------------------------------------------------
| Attendance Overnight
|--------------------------------------------------------------------------
*/

const isOvernightAttendance =
    computed((): boolean => {
        if (
            !props.form.start_at
            || !props.form.end_at
        ) {
            return false;
        }

        return (
            getDatePart(
                props.form.start_at,
            )
            !== getDatePart(
                props.form.end_at,
            )
        );
    });

/*
|--------------------------------------------------------------------------
| Resolve Attendance End Date
|--------------------------------------------------------------------------
*/

const resolveAttendanceEndDate = (
    startDate: string,
    startTime: string,
    endTime: string,
): string => {
    const startMinutes =
        timeToMinutes(startTime);

    const endMinutes =
        timeToMinutes(endTime);

    if (
        startMinutes < 0
        || endMinutes < 0
    ) {
        return startDate;
    }

    return endMinutes < startMinutes
        ? addOneDay(startDate)
        : startDate;
};

/*
|--------------------------------------------------------------------------
| Clock In
|--------------------------------------------------------------------------
*/

const startTime = computed({
    get: (): string => {
        return getTimePart(
            props.form.start_at,
        );
    },

    set: (value: string): void => {
        if (!value) {
            props.form.start_at = '';
            props.form.end_at = null;

            clearBreakTimes();

            return;
        }

        if (!attendanceDate.value) {
            return;
        }

        const existingEndTime =
            getTimePart(
                props.form.end_at,
            );

        props.form.start_at =
            combineDateTime(
                attendanceDate.value,
                value,
            );

        if (existingEndTime) {
            const endDate =
                resolveAttendanceEndDate(
                    attendanceDate.value,
                    value,
                    existingEndTime,
                );

            props.form.end_at =
                combineDateTime(
                    endDate,
                    existingEndTime,
                );
        }

        rebuildAllBreaks();
    },
});

/*
|--------------------------------------------------------------------------
| Clock Out
|--------------------------------------------------------------------------
*/

const endTime = computed({
    get: (): string => {
        return getTimePart(
            props.form.end_at,
        );
    },

    set: (value: string): void => {
        if (!value) {
            props.form.end_at = null;

            clearBreakDateTimes();

            return;
        }

        if (!props.form.start_at) {
            return;
        }

        const startDate =
            getDatePart(
                props.form.start_at,
            );

        const startTimeValue =
            getTimePart(
                props.form.start_at,
            );

        if (
            !startDate
            || !startTimeValue
        ) {
            return;
        }

        const endDate =
            resolveAttendanceEndDate(
                startDate,
                startTimeValue,
                value,
            );

        props.form.end_at =
            combineDateTime(
                endDate,
                value,
            );

        rebuildAllBreaks();
    },
});

/*
|--------------------------------------------------------------------------
| Attendance Date Change
|--------------------------------------------------------------------------
*/

const setAttendanceDate = (
    value: string,
): void => {
    if (!value) {
        props.form.attendance_date = '';
        props.form.start_at = '';
        props.form.end_at = null;

        clearBreakTimes();

        return;
    }

    const oldStartTime =
        getTimePart(
            props.form.start_at,
        );

    const oldEndTime =
        getTimePart(
            props.form.end_at,
        );

    const wasOvernight =
        isOvernightAttendance.value;

    props.form.attendance_date =
        value;

    /*
     * Clock In
     */

    props.form.start_at =
        oldStartTime
            ? combineDateTime(
                value,
                oldStartTime,
            )
            : '';

    /*
     * Clock Out
     */

    props.form.end_at =
        oldEndTime
            ? combineDateTime(
                wasOvernight
                    ? addOneDay(value)
                    : value,
                oldEndTime,
            )
            : null;

    /*
     * Rebuild all break datetimes using the
     * preserved HH:mm UI values.
     */

    rebuildAllBreaks();
};

/*
|--------------------------------------------------------------------------
| Break State Key
|--------------------------------------------------------------------------
*/

const getBreakKey = (
    attendanceBreak: AttendanceFormBreak,
    index: number,
): string => {
    return attendanceBreak.uuid
        ?? `new-${index}`;
};

/*
|--------------------------------------------------------------------------
| Initialize Break UI State
|--------------------------------------------------------------------------
*/

const ensureBreakTimeState = (
    attendanceBreak: AttendanceFormBreak,
    index: number,
): BreakTimeState => {
    const key =
        getBreakKey(
            attendanceBreak,
            index,
        );

    if (!breakTimes[key]) {
        breakTimes[key] = {
            start_at:
                getTimePart(
                    attendanceBreak.start_at,
                ),
            end_at:
                getTimePart(
                    attendanceBreak.end_at,
                ),
        };
    }

    return breakTimes[key];
};

/*
|--------------------------------------------------------------------------
| Break Date Resolution
|--------------------------------------------------------------------------
*/

const resolveBreakStartDate = (
    time: string,
): string => {
    if (
        !time
        || !props.form.start_at
        || !props.form.end_at
    ) {
        return '';
    }

    const attendanceStartDate =
        getDatePart(
            props.form.start_at,
        );

    const attendanceEndDate =
        getDatePart(
            props.form.end_at,
        );

    const attendanceStartTime =
        getTimePart(
            props.form.start_at,
        );

    if (
        !attendanceStartDate
        || !attendanceEndDate
        || !attendanceStartTime
    ) {
        return '';
    }

    const selectedMinutes =
        timeToMinutes(time);

    const startMinutes =
        timeToMinutes(
            attendanceStartTime,
        );

    if (
        selectedMinutes < 0
        || startMinutes < 0
    ) {
        return '';
    }

    /*
     * Overnight attendance:
     *
     * 22:00 -> 08:00
     *
     * 01:00 belongs to the end date.
     */

    if (
        attendanceStartDate
        !== attendanceEndDate
        && selectedMinutes < startMinutes
    ) {
        return attendanceEndDate;
    }

    return attendanceStartDate;
};

/*
|--------------------------------------------------------------------------
| Break End Date Resolution
|--------------------------------------------------------------------------
*/

const resolveBreakEndDate = (
    breakStartDate: string,
    breakStartTime: string,
    breakEndTime: string,
): string => {
    const startMinutes =
        timeToMinutes(
            breakStartTime,
        );

    const endMinutes =
        timeToMinutes(
            breakEndTime,
        );

    if (
        startMinutes < 0
        || endMinutes < 0
    ) {
        return breakStartDate;
    }

    return endMinutes < startMinutes
        ? addOneDay(breakStartDate)
        : breakStartDate;
};

/*
|--------------------------------------------------------------------------
| Rebuild One Break
|--------------------------------------------------------------------------
*/

const rebuildBreak = (
    attendanceBreak: AttendanceFormBreak,
    index: number,
): void => {
    const state =
        ensureBreakTimeState(
            attendanceBreak,
            index,
        );

    /*
     * No break start.
     */

    if (!state.start_at) {
        attendanceBreak.start_at = '';
        attendanceBreak.end_at = '';

        return;
    }

    /*
     * Resolve break start date.
     */

    const breakStartDate =
        resolveBreakStartDate(
            state.start_at,
        );

    if (!breakStartDate) {
        attendanceBreak.start_at = '';
        attendanceBreak.end_at = '';

        return;
    }

    attendanceBreak.start_at =
        combineDateTime(
            breakStartDate,
            state.start_at,
        );

    /*
     * No break end yet.
     */

    if (!state.end_at) {
        attendanceBreak.end_at = '';

        return;
    }

    /*
     * Resolve break end date relative to
     * the break start.
     */

    const breakEndDate =
        resolveBreakEndDate(
            breakStartDate,
            state.start_at,
            state.end_at,
        );

    attendanceBreak.end_at =
        combineDateTime(
            breakEndDate,
            state.end_at,
        );
};

/*
|--------------------------------------------------------------------------
| Rebuild All Breaks
|--------------------------------------------------------------------------
*/

const rebuildAllBreaks = (): void => {
    props.form.breaks.forEach(
        (
            attendanceBreak,
            index,
        ) => {
            rebuildBreak(
                attendanceBreak,
                index,
            );
        },
    );
};

/*
|--------------------------------------------------------------------------
| Clear Break UI State
|--------------------------------------------------------------------------
*/

const clearBreakTimes = (): void => {
    props.form.breaks.forEach(
        (
            attendanceBreak,
            index,
        ) => {
            const key =
                getBreakKey(
                    attendanceBreak,
                    index,
                );

            breakTimes[key] = {
                start_at: '',
                end_at: '',
            };

            attendanceBreak.start_at = '';
            attendanceBreak.end_at = '';
        },
    );
};

const clearBreakDateTimes = (): void => {
    props.form.breaks.forEach(
        (
            attendanceBreak,
            index,
        ) => {
            const state =
                ensureBreakTimeState(
                    attendanceBreak,
                    index,
                );

            if (!state.start_at) {
                attendanceBreak.start_at = '';
                attendanceBreak.end_at = '';

                return;
            }

            rebuildBreak(
                attendanceBreak,
                index,
            );
        },
    );
};

/*
|--------------------------------------------------------------------------
| Break Input Values
|--------------------------------------------------------------------------
*/

const getBreakStartTime = (
    attendanceBreak: AttendanceFormBreak,
    index: number,
): string => {
    return ensureBreakTimeState(
        attendanceBreak,
        index,
    ).start_at;
};

const getBreakEndTime = (
    attendanceBreak: AttendanceFormBreak,
    index: number,
): string => {
    return ensureBreakTimeState(
        attendanceBreak,
        index,
    ).end_at;
};

/*
|--------------------------------------------------------------------------
| Break Start Input
|--------------------------------------------------------------------------
*/

const setBreakStartTime = (
    attendanceBreak: AttendanceFormBreak,
    index: number,
    value: string,
): void => {
    const state =
        ensureBreakTimeState(
            attendanceBreak,
            index,
        );

    state.start_at = value;

    /*
     * If start is cleared, clear end too.
     */

    if (!value) {
        state.end_at = '';

        attendanceBreak.start_at = '';
        attendanceBreak.end_at = '';

        return;
    }

    /*
     * Keep the selected HH:mm in local state.
     *
     * This is the important part:
     * the native time picker is no longer fighting
     * with a full datetime value.
     */

    rebuildBreak(
        attendanceBreak,
        index,
    );
};

/*
|--------------------------------------------------------------------------
| Break End Input
|--------------------------------------------------------------------------
*/

const setBreakEndTime = (
    attendanceBreak: AttendanceFormBreak,
    index: number,
    value: string,
): void => {
    const state =
        ensureBreakTimeState(
            attendanceBreak,
            index,
        );

    state.end_at = value;

    if (!value) {
        attendanceBreak.end_at = '';

        return;
    }

    /*
     * The break start must exist first.
     */

    if (!state.start_at) {
        state.end_at = '';

        attendanceBreak.end_at = '';

        return;
    }

    /*
     * Store the selected time immediately.
     * Then resolve its actual calendar date.
     */

    rebuildBreak(
        attendanceBreak,
        index,
    );
};

/*
|--------------------------------------------------------------------------
| Break Value Helpers
|--------------------------------------------------------------------------
*/

const breakError = (
    index: number,
    field: 'start_at' | 'end_at',
): string | undefined => {
    return props.errors[
        `breaks.${index}.${field}`
        ];
};

/*
|--------------------------------------------------------------------------
| Break UI
|--------------------------------------------------------------------------
*/

const canAddBreak =
    computed((): boolean => {
        return Boolean(
            props.form.attendance_date
            && props.form.start_at
            && props.form.end_at,
        );
    });

const addBreak = (): void => {
    if (!canAddBreak.value) {
        return;
    }

    const attendanceBreak: AttendanceFormBreak = {
        start_at: '',
        end_at: '',
    };

    props.form.breaks.push(
        attendanceBreak,
    );

    const index =
        props.form.breaks.length - 1;

    ensureBreakTimeState(
        attendanceBreak,
        index,
    );
};

const removeBreak = (
    index: number,
): void => {
    const attendanceBreak =
        props.form.breaks[index];

    if (attendanceBreak) {
        const key =
            getBreakKey(
                attendanceBreak,
                index,
            );

        delete breakTimes[key];
    }

    props.form.breaks.splice(
        index,
        1,
    );
};

/*
|--------------------------------------------------------------------------
| Form Initialization
|--------------------------------------------------------------------------
|
| Existing edit records need their local HH:mm values
| initialized from the backend datetime values.
|
*/

props.form.breaks.forEach(
    (
        attendanceBreak,
        index,
    ) => {
        ensureBreakTimeState(
            attendanceBreak,
            index,
        );
    },
);
</script>

<template>
    <form
        class="min-w-0 space-y-6"
        @submit.prevent="emit('submit')"
    >
        <!-- Attendance Information -->

        <FormSection
            :title="attendancePage.form.sectionTitle"
            :description="
                attendancePage.form.sectionDescription
            "
        >
            <div
                class="grid min-w-0 gap-5 md:grid-cols-2"
            >
                <!-- Employee -->

                <AppField
                    :label="
                        attendancePage.form.employeeLabel
                    "
                    input-id="attendance-employee"
                    required
                >
                    <AppEmployeeSelect
                        v-model="props.form.user_id"
                        :placeholder="
                            attendancePage.form.employeePlaceholder
                        "
                        :disabled="processing"
                        :error="props.errors.user_id"
                        :icon="User"
                        searchable
                    />
                </AppField>

                <!-- Attendance Date -->

                <AppField
                    :label="
                        attendancePage.form
                            .attendanceDateLabel
                    "
                    input-id="attendance-date"
                    required
                >
                    <AppInput
                        id="attendance-date"
                        :model-value="
                            props.form.attendance_date
                        "
                        type="date"
                        :disabled="processing"
                        :error="
                            props.errors.attendance_date
                        "
                        :icon="CalendarDays"
                        required
                        @update:model-value="
                            setAttendanceDate(
                                String(
                                    $event ?? '',
                                ),
                            )
                        "
                        :placeholder="attendancePage.form.attendanceDatePlaceholder"
                    />
                </AppField>

                <!-- Clock In -->

                <AppField
                    :label="
                        attendancePage.form.startAtLabel
                    "
                    input-id="attendance-start-at"
                    required
                >
                    <AppInput
                        id="attendance-start-at"
                        v-model="startTime"
                        type="time"
                        :disabled="processing"
                        :error="
                            props.errors.start_at
                        "
                        :placeholder="attendancePage.form.startAtPlaceholder"
                    />

                    <p
                        v-if="attendanceDate"
                        class="mt-1.5 text-xs text-muted-foreground"
                    >
                        Date:
                        {{ attendanceDate }}
                    </p>
                </AppField>

                <!-- Clock Out -->

                <AppField
                    :label="
                        attendancePage.form.endAtLabel
                    "
                    input-id="attendance-end-at"
                >
                    <AppInput
                        id="attendance-end-at"
                        v-model="endTime"
                        type="time"
                        :disabled="
                            processing
                            || !startTime
                        "
                        :error="
                            props.errors.end_at
                        "
                        :placeholder="attendancePage.form.endAtPlaceholder"
                    />

                    <p
                        v-if="
                            attendanceDate
                            && endTime
                        "
                        class="mt-1.5 text-xs text-muted-foreground"
                    >
                        {{
                            isOvernightAttendance
                                ? `Next day • ${addOneDay(attendanceDate)}`
                                : `Same day • ${attendanceDate}`
                        }}
                    </p>
                </AppField>
            </div>
        </FormSection>

        <!-- Attendance Breaks -->

        <FormSection
            title="Attendance Breaks"
            description="Break times must stay completely within the clock-in and clock-out period."
        >
            <div class="space-y-4">
                <!-- Empty State -->

                <div
                    v-if="
                        !props.form.breaks.length
                    "
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed px-6 py-8 text-center"
                >
                    <div
                        class="mb-3 rounded-lg border bg-muted/30 p-3"
                    >
                        <Clock3
                            class="size-5 text-muted-foreground"
                            aria-hidden="true"
                        />
                    </div>

                    <p class="text-sm font-medium">
                        No breaks added
                    </p>

                    <p
                        class="mt-1 max-w-md text-xs text-muted-foreground"
                    >
                        {{
                            canAddBreak
                                ? 'Add a break if the employee left work during this attendance period.'
                                : 'Set clock-in and clock-out time before adding breaks.'
                        }}
                    </p>
                </div>

                <!-- Break List -->

                <div
                    v-for="(
                        attendanceBreak,
                        index
                    ) in props.form.breaks"
                    :key="
                        attendanceBreak.uuid
                            ?? index
                    "
                    class="rounded-xl border bg-card p-4"
                >
                    <div
                        class="mb-4 flex items-center justify-between gap-4"
                    >
                        <div
                            class="flex items-center gap-2"
                        >
                            <div
                                class="rounded-md bg-muted p-2"
                            >
                                <Clock3
                                    class="size-4 text-muted-foreground"
                                    aria-hidden="true"
                                />
                            </div>

                            <div>
                                <h3
                                    class="text-sm font-medium"
                                >
                                    Break
                                    {{ index + 1 }}
                                </h3>

                                <p
                                    class="text-xs text-muted-foreground"
                                >
                                    Select the break time only.
                                </p>
                            </div>
                        </div>

                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-8 cursor-pointer text-muted-foreground hover:text-destructive"
                            :disabled="processing"
                            :aria-label="
                                `Remove break ${index + 1}`
                            "
                            @click="
                                removeBreak(index)
                            "
                        >
                            <Trash2
                                class="size-4"
                                aria-hidden="true"
                            />
                        </Button>
                    </div>

                    <div
                        class="grid min-w-0 gap-5 md:grid-cols-2"
                    >
                        <!-- Break Start -->

                        <AppField
                            label="Start Time"
                            :input-id="
                                `break-${index}-start`
                            "
                        >
                            <AppInput
                                :id="
                                    `break-${index}-start`
                                "
                                :model-value="
                                    getBreakStartTime(
                                        attendanceBreak,
                                        index,
                                    )
                                "
                                type="time"
                                :disabled="processing"
                                :error="
                                    breakError(
                                        index,
                                        'start_at',
                                    )
                                "
                                @update:model-value="
                                    setBreakStartTime(
                                        attendanceBreak,
                                        index,
                                        String(
                                            $event ?? '',
                                        ),
                                    )
                                "
                                :placeholder="attendancePage.form.breakStartPlaceholder"
                            />
                        </AppField>

                        <!-- Break End -->

                        <AppField
                            label="End Time"
                            :input-id="
                                `break-${index}-end`
                            "
                        >
                            <AppInput
                                :id="
                                    `break-${index}-end`
                                "
                                :model-value="
                                    getBreakEndTime(
                                        attendanceBreak,
                                        index,
                                    )
                                "
                                type="time"
                                :disabled="
                                    processing
                                    || !getBreakStartTime(
                                        attendanceBreak,
                                        index,
                                    )
                                "
                                :error="
                                    breakError(
                                        index,
                                        'end_at',
                                    )
                                "
                                @update:model-value="
                                    setBreakEndTime(
                                        attendanceBreak,
                                        index,
                                        String(
                                            $event ?? '',
                                        ),
                                    )
                                "
                                :placeholder="attendancePage.form.breakEndPlaceholder"
                            />
                        </AppField>
                    </div>

                    <!-- Break Dates -->

                    <p
                        v-if="
                            attendanceBreak.start_at
                            && attendanceBreak.end_at
                        "
                        class="mt-3 text-xs text-muted-foreground"
                    >
                        {{
                            getDatePart(
                                attendanceBreak.start_at,
                            )
                        }}
                        →
                        {{
                            getDatePart(
                                attendanceBreak.end_at,
                            )
                        }}
                    </p>
                </div>

                <!-- Add Break -->

                <Button
                    type="button"
                    variant="outline"
                    class="w-full cursor-pointer sm:w-auto"
                    :disabled="
                        processing
                        || !canAddBreak
                    "
                    @click="addBreak"
                >
                    <Plus
                        class="size-4"
                        aria-hidden="true"
                    />

                    <span>Add Break</span>
                </Button>
            </div>
        </FormSection>

        <!-- Form Actions -->

        <FormActions
            :submit-label="submitLabel"
            :cancel-label="
                attendancePage.form.cancelLabel
            "
            :processing="processing"
            @cancel="emit('cancel')"
        />
    </form>
</template>
