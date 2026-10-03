<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import {
    CalendarDays,
    Clock3,
    Plus,
    Trash2,
    User,
} from '@lucide/vue';
import { computed } from 'vue';

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

// Props
const props = defineProps<{
    form: InertiaForm<AttendanceFormData>;
    errors: Record<string, string>;
    processing: boolean;
    submitLabel: string;
}>();

// Emits
const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

// Computed
const startAt = computed({
    get: () => formatDateTimeLocal(props.form.start_at),
    set: (value: string) => {
        props.form.start_at = value;
    },
});

const endAt = computed({
    get: () => formatDateTimeLocal(props.form.end_at),
    set: (value: string) => {
        props.form.end_at = value || null;
    },
});

// Breaks
const addBreak = (): void => {
    props.form.breaks.push({
        start_at: '',
        end_at: '',
    });
};

const removeBreak = (index: number): void => {
    props.form.breaks.splice(index, 1);
};

// Helpers
const formatDateTimeLocal = (
    value: string | null | undefined,
): string => {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value.slice(0, 16);
    }

    const pad = (number: number): string =>
        String(number).padStart(2, '0');

    return [
        `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`,
        `${pad(date.getHours())}:${pad(date.getMinutes())}`,
    ].join('T');
};

const formatBreakValue = (
    attendanceBreak: AttendanceFormBreak,
    field: 'start_at' | 'end_at',
): string => {
    return formatDateTimeLocal(
        attendanceBreak[field],
    );
};

const setBreakValue = (
    attendanceBreak: AttendanceFormBreak,
    field: 'start_at' | 'end_at',
    value: string | number | null,
): void => {
    attendanceBreak[field] =
        typeof value === 'string'
            ? value
            : '';
};

const breakError = (
    index: number,
    field: 'start_at' | 'end_at',
): string | undefined => {
    return props.errors[
        `breaks.${index}.${field}`
        ];
};
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
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
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
                        attendancePage.form.attendanceDateLabel
                    "
                    input-id="attendance-date"
                    required
                >
                    <AppInput
                        id="attendance-date"
                        v-model="props.form.attendance_date"
                        type="date"
                        :disabled="processing"
                        :error="
                            props.errors.attendance_date
                        "
                        :icon="CalendarDays"
                        required
                    />
                </AppField>

                <!-- Start At -->
                <AppField
                    :label="attendancePage.form.startAtLabel"
                    input-id="attendance-start-at"
                    required
                >
                    <AppInput
                        id="attendance-start-at"
                        v-model="startAt"
                        type="datetime-local"
                        :disabled="processing"
                        :error="props.errors.start_at"
                        :icon="Clock3"
                        required
                    />
                </AppField>

                <!-- End At -->
                <AppField
                    :label="attendancePage.form.endAtLabel"
                    input-id="attendance-end-at"
                >
                    <AppInput
                        id="attendance-end-at"
                        v-model="endAt"
                        type="datetime-local"
                        :disabled="processing"
                        :error="props.errors.end_at"
                        :icon="Clock3"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Attendance Breaks -->
        <FormSection
            title="Attendance Breaks"
            description="Manage the breaks taken during the attendance period."
        >
            <div class="space-y-4">
                <!-- Empty State -->
                <div
                    v-if="!form.breaks.length"
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
                        Add a break if the employee left work
                        during this attendance period.
                    </p>
                </div>

                <!-- Break List -->
                <div
                    v-for="(
                        attendanceBreak, index
                    ) in form.breaks"
                    :key="attendanceBreak.id ?? index"
                    class="rounded-xl border bg-card p-4"
                >
                    <div
                        class="mb-4 flex items-center justify-between gap-4"
                    >
                        <div class="flex items-center gap-2">
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
                                    Break {{ index + 1 }}
                                </h3>

                                <p
                                    class="text-xs text-muted-foreground"
                                >
                                    Break start and end time
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
                            @click="removeBreak(index)"
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
                                    formatBreakValue(
                                        attendanceBreak,
                                        'start_at',
                                    )
                                "
                                type="datetime-local"
                                :disabled="processing"
                                :error="
                                    breakError(
                                        index,
                                        'start_at',
                                    )
                                "
                                :icon="Clock3"
                                @update:model-value="
                                    setBreakValue(
                                        attendanceBreak,
                                        'start_at',
                                        $event,
                                    )
                                "
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
                                    formatBreakValue(
                                        attendanceBreak,
                                        'end_at',
                                    )
                                "
                                type="datetime-local"
                                :disabled="processing"
                                :error="
                                    breakError(
                                        index,
                                        'end_at',
                                    )
                                "
                                :icon="Clock3"
                                @update:model-value="
                                    setBreakValue(
                                        attendanceBreak,
                                        'end_at',
                                        $event,
                                    )
                                "
                            />
                        </AppField>
                    </div>
                </div>

                <!-- Add Break -->
                <Button
                    type="button"
                    variant="outline"
                    class="w-full cursor-pointer sm:w-auto"
                    :disabled="processing"
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
