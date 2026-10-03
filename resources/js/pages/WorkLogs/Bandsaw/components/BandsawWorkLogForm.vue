<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import {
    CalendarDays,
    Hash,
    Layers3,
    MessageSquare,
    Ruler,
    User,
    Wrench,
} from '@lucide/vue';
import { watch } from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import AppSectionSizeSelect from '@/components/selects/AppSectionSizeSelect.vue';
import type {
    BandsawWorkLogFormData,
    BandsawWorkLogLastReport,
} from '@/types/bandsawWorkLog';

// Props
const props = defineProps<{
    form: InertiaForm<BandsawWorkLogFormData>;
    processing: boolean;
    submitLabel: string;
    canSelectBandsaw: boolean;
    lastReports: Record<
        string,
        BandsawWorkLogLastReport
    >;
    isCreate: boolean;
}>();

// Emits
const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

// Last Report
const applyLastReport = (
    userId: number | null,
): void => {
    if (!userId) {
        return;
    }

    const lastReport =
        props.lastReports[String(userId)];

    if (!lastReport) {
        return;
    }

    props.form.part_id = lastReport.part_id;
    props.form.section_id =
        lastReport.section_id;
    props.form.section_size_id =
        lastReport.section_size_id;
};

// Watchers
watch(
    () => props.form.user_id,
    (userId, previousUserId) => {
        if (!userId) {
            return;
        }

        // Preserve saved values when opening Edit.
        if (
            !props.isCreate &&
            previousUserId === undefined
        ) {
            return;
        }

        // Initialize Create from the current/selected Bandsaw.
        if (
            props.isCreate &&
            (
                previousUserId === undefined ||
                userId !== previousUserId
            )
        ) {
            applyLastReport(userId);
            return;
        }

        // React to user changes on Edit.
        if (
            props.canSelectBandsaw &&
            userId !== previousUserId
        ) {
            applyLastReport(userId);
        }
    },
    {
        immediate: true,
    },
);
</script>

<template>
    <form
        class="min-w-0 space-y-6"
        @submit.prevent="emit('submit')"
    >
        <!-- Work Details -->
        <FormSection
            title="Work Details"
            description="Enter the Bandsaw work details."
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Bandsaw -->
                <AppField
                    v-if="canSelectBandsaw"
                    label="Bandsaw"
                    input-id="bandsaw-work-log-employee"
                    :error="form.errors.user_id"
                    required
                >
                    <AppEmployeeSelect
                        v-model="form.user_id"
                        placeholder="Select Bandsaw"
                        :disabled="processing"
                        :error="form.errors.user_id"
                        :icon="User"
                        searchable
                        role="bandsaw"
                    />
                </AppField>

                <!-- Work Date -->
                <AppField
                    label="Work Date"
                    input-id="bandsaw-work-log-date"
                    :error="form.errors.work_date"
                    required
                >
                    <AppInput
                        id="bandsaw-work-log-date"
                        v-model="form.work_date"
                        type="date"
                        :disabled="processing"
                        :error="form.errors.work_date"
                        :icon="CalendarDays"
                        required
                    />
                </AppField>

                <!-- Part -->
                <AppField
                    label="Part"
                    input-id="bandsaw-work-log-part"
                    :error="form.errors.part_id"
                    required
                >
                    <AppPartSelect
                        v-model="form.part_id"
                        placeholder="Select Part"
                        :disabled="processing"
                        :error="form.errors.part_id"
                        :icon="Wrench"
                        searchable
                    />
                </AppField>

                <!-- Section -->
                <AppField
                    label="Section"
                    input-id="bandsaw-work-log-section"
                    :error="form.errors.section_id"
                    required
                >
                    <AppSectionSelect
                        v-model="form.section_id"
                        placeholder="Select Section"
                        :disabled="processing"
                        :error="form.errors.section_id"
                        :icon="Layers3"
                        searchable
                    />
                </AppField>

                <!-- Section Size -->
                <AppField
                    label="Section Size"
                    input-id="bandsaw-work-log-section-size"
                    :error="
                        form.errors.section_size_id
                    "
                    required
                >
                    <AppSectionSizeSelect
                        v-model="form.section_size_id"
                        :section-id="form.section_id"
                        placeholder="Select Section Size"
                        :disabled="processing"
                        :error="
                            form.errors.section_size_id
                        "
                        :icon="Ruler"
                        searchable
                    />
                </AppField>

                <!-- Quantity -->
                <AppField
                    label="Quantity"
                    input-id="bandsaw-work-log-quantity"
                    :error="form.errors.quantity"
                    required
                >
                    <AppInput
                        id="bandsaw-work-log-quantity"
                        v-model.number="form.quantity"
                        type="number"
                        min="1"
                        step="1"
                        placeholder="Enter quantity"
                        :disabled="processing"
                        :error="form.errors.quantity"
                        :icon="Hash"
                        required
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Additional Information -->
        <FormSection
            title="Additional Information"
            description="Add any relevant remarks about this work."
        >
            <AppField
                label="Remarks"
                input-id="bandsaw-work-log-remarks"
                :error="form.errors.remarks"
            >
                <AppInput
                    id="bandsaw-work-log-remarks"
                    v-model="form.remarks"
                    placeholder="Enter remarks"
                    :disabled="processing"
                    :error="form.errors.remarks"
                    :icon="MessageSquare"
                />
            </AppField>
        </FormSection>

        <!-- Form Actions -->
        <FormActions
            :submit-label="submitLabel"
            :processing="processing"
            cancel-label="Cancel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
