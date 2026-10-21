<script lang="ts" setup>
import type {InertiaForm} from '@inertiajs/vue3';
import {CalendarDays, Hash, Layers3, MessageSquare, Ruler, User, Wrench} from '@lucide/vue';
import {nextTick, ref, watch} from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import AppSectionSizeSelect from '@/components/selects/AppSectionSizeSelect.vue';
import {bandsawWorkLogPage} from '@/config/bandsawWorkLogs';
import {bandsawWorkLogSchema} from '@/schemas/bandsawWorkLogSchema';
import type {BandsawWorkLogFormData, BandsawWorkLogLastReport,} from '@/types/bandsawWorkLog';

type SelectedPart = {
    value: number;
    label: string;
    section_id: number | null;
    cutting_size: string | null;
};

const props = defineProps<{
    form: InertiaForm<BandsawWorkLogFormData>;
    processing: boolean;
    submitLabel: string;
    canSelectBandsaw: boolean;
    lastReports: Record<string, BandsawWorkLogLastReport>;
    isCreate: boolean;
}>();

const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

const validationFields = [
    'user_id',
    'work_date',
    'part_id',
    'section_size_id',
    'quantity',
    'remarks',
] as const;

type ValidationField = (typeof validationFields)[number];

const handleSubmit = (): void => {
    props.form.clearErrors(...validationFields);

    const result = bandsawWorkLogSchema.safeParse({
        user_id: props.form.user_id,
        work_date: props.form.work_date,
        part_id: props.form.part_id,
        section_size_id: props.form.section_size_id,
        quantity: props.form.quantity,
        remarks: props.form.remarks,
    });

    if (!result.success) {
        for (const issue of result.error.issues) {
            const field = issue.path[0] as ValidationField;

            if (validationFields.includes(field)) {
                // Preserve the first validation message for each field.
                if (!props.form.errors[field]) {
                    props.form.setError(field, issue.message);
                }
            }
        }

        return;
    }

    /*
     * A manager must select a Bandsaw operator.
     * The logged-in Bandsaw operator does not need
     * to select their own employee record.
     */
    if (props.canSelectBandsaw && !props.form.user_id) {
        props.form.setError(
            'user_id',
            'Please select a Bandsaw operator.',
        );

        return;
    }

    emit('submit');
};

/*
|--------------------------------------------------------------------------
| Local state
|--------------------------------------------------------------------------
*/

const cuttingSize = ref<string>('');

const restoringLastReport = ref(false);

/*
|--------------------------------------------------------------------------
| Part
|--------------------------------------------------------------------------
*/

const handlePartChange = (part: SelectedPart | null): void => {
    /*
     * Cutting Size always comes directly from
     * the selected Part.
     */
    cuttingSize.value = part?.cutting_size ?? '';

    /*
     * Never change dependent fields while editing.
     */
    if (!props.isCreate) {
        return;
    }

    /*
     * When restoring a last report, the last report
     * already contains the correct Section and
     * Section Size. Do not overwrite/clear them.
     */
    if (restoringLastReport.value) {
        return;
    }

    /*
     * Manual Part change:
     * Section comes from the Part.
     * Section Size must be selected again.
     */
    props.form.section_id = part?.section_id ?? null;

    props.form.section_size_id = null;
};

/*
|--------------------------------------------------------------------------
| Section
|--------------------------------------------------------------------------
*/

const handleSectionChange = (value: number | null): void => {
    if (!props.isCreate) {
        return;
    }

    props.form.section_id = value;

    /*
     * Section Size depends on Section.
     * A manual Section change must clear it.
     */
    props.form.section_size_id = null;
};

/*
|--------------------------------------------------------------------------
| Last Report
|--------------------------------------------------------------------------
*/

const applyLastReport = async (userId: number | null): Promise<void> => {
    if (!userId) {
        return;
    }

    const lastReport = props.lastReports[String(userId)];

    if (!lastReport) {
        return;
    }

    /*
     * Tell handlePartChange that this Part change
     * is programmatic and must not clear the
     * saved Section Size.
     */
    restoringLastReport.value = true;

    /*
     * Set dependent values first.
     */
    props.form.section_id = lastReport.section_id;

    props.form.section_size_id = lastReport.section_size_id;

    /*
     * Set Part last.
     *
     * AppPartSelect will resolve this ID from its
     * lookup options and emit the complete Part,
     * which populates Cutting Size.
     */
    props.form.part_id = lastReport.part_id;

    /*
     * Give AppPartSelect's watcher time to resolve
     * the selected Part and emit it.
     */
    await nextTick();

    /*
     * Keep this flag active through the current
     * reactive cycle so handlePartChange cannot
     * clear section_size_id.
     */
    await nextTick();

    restoringLastReport.value = false;
};

/*
|--------------------------------------------------------------------------
| Employee watcher
|--------------------------------------------------------------------------
*/

watch(
    () => props.form.user_id,
    async (userId, previousUserId) => {
        if (!userId) {
            cuttingSize.value = '';

            return;
        }

        /*
         * Edit:
         *
         * Never apply another employee's last report
         * when the existing form is initialized.
         *
         * Edit fields are populated from the record itself.
         */
        if (!props.isCreate && previousUserId === undefined) {
            return;
        }

        /*
         * Create:
         *
         * Apply the selected employee's last report.
         */
        if (
            props.isCreate &&
            (previousUserId === undefined || userId !== previousUserId)
        ) {
            await applyLastReport(userId);

            return;
        }

        /*
         * Manager selecting a different Bandsaw operator
         * while creating/editing.
         */
        if (props.canSelectBandsaw && userId !== previousUserId) {
            await applyLastReport(userId);
        }
    },
    {
        immediate: true,
    },
);
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="handleSubmit">
        <FormSection
            :description="bandsawWorkLogPage.form.sectionDescription"
            :title="bandsawWorkLogPage.form.sectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Bandsaw Operator -->
                <AppField
                    v-if="canSelectBandsaw"
                    :error="form.errors.user_id"
                    :label="bandsawWorkLogPage.form.bandsawLabel"
                    input-id="bandsaw-work-log-employee"
                    required
                >
                    <AppEmployeeSelect
                        v-model="form.user_id"
                        :disabled="processing || !isCreate"
                        :error="form.errors.user_id"
                        :icon="User"
                        :placeholder="
                            bandsawWorkLogPage.form.bandsawPlaceholder
                        "
                        role="bandsaw"
                        searchable
                    />
                </AppField>

                <!-- Work Date -->
                <AppField
                    :error="form.errors.work_date"
                    :label="bandsawWorkLogPage.form.workDateLabel"
                    input-id="bandsaw-work-log-date"
                    required
                >
                    <AppInput
                        id="bandsaw-work-log-date"
                        v-model="form.work_date"
                        :disabled="processing || !isCreate"
                        :error="form.errors.work_date"
                        :icon="CalendarDays"
                        required
                        type="date"
                    />
                </AppField>

                <!-- Part -->
                <AppField
                    :error="form.errors.part_id"
                    :label="bandsawWorkLogPage.form.partLabel"
                    input-id="bandsaw-work-log-part"
                    required
                >
                    <AppPartSelect
                        v-model="form.part_id"
                        :disabled="processing || !isCreate"
                        :error="form.errors.part_id"
                        :icon="Wrench"
                        :placeholder="bandsawWorkLogPage.form.partPlaceholder"
                        searchable
                        @part="handlePartChange"
                    />
                </AppField>

                <!-- Cutting Size -->
                <AppField
                    input-id="bandsaw-work-log-cutting-size"
                    label="Cutting Size"
                >
                    <AppInput
                        id="bandsaw-work-log-cutting-size"
                        :icon="Ruler"
                        :model-value="cuttingSize"
                        disabled
                        placeholder="—"
                    />
                </AppField>

                <!-- Section -->
                <AppField
                    :error="form.errors.section_id"
                    :label="bandsawWorkLogPage.form.sectionLabel"
                    input-id="bandsaw-work-log-section"
                    required
                >
                    <AppSectionSelect
                        :disabled="processing || !isCreate"
                        :error="form.errors.section_id"
                        :icon="Layers3"
                        :model-value="form.section_id"
                        :placeholder="
                            bandsawWorkLogPage.form.sectionPlaceholder
                        "
                        searchable
                        @update:model-value="handleSectionChange"
                    />
                </AppField>

                <!-- Section Size -->
                <AppField
                    :error="form.errors.section_size_id"
                    :label="bandsawWorkLogPage.form.sectionSizeLabel"
                    input-id="bandsaw-work-log-section-size"
                    required
                >
                    <AppSectionSizeSelect
                        v-model="form.section_size_id"
                        :disabled="processing || !isCreate"
                        :error="form.errors.section_size_id"
                        :icon="Ruler"
                        :placeholder="
                            bandsawWorkLogPage.form.sectionSizePlaceholder
                        "
                        :section-id="form.section_id"
                        searchable
                    />
                </AppField>

                <!-- Quantity -->
                <AppField
                    :error="form.errors.quantity"
                    :label="bandsawWorkLogPage.form.quantityLabel"
                    input-id="bandsaw-work-log-quantity"
                    required
                >
                    <AppInput
                        id="bandsaw-work-log-quantity"
                        v-model.number="form.quantity"
                        :disabled="processing"
                        :error="form.errors.quantity"
                        :icon="Hash"
                        :placeholder="
                            bandsawWorkLogPage.form.quantityPlaceholder
                        "
                        min="1"
                        required
                        step="1"
                        type="number"
                    />
                </AppField>
            </div>
        </FormSection>

        <FormSection
            description="Add any relevant remarks about this work."
            title="Additional Information"
        >
            <AppField
                :error="form.errors.remarks"
                :label="bandsawWorkLogPage.form.remarksLabel"
                input-id="bandsaw-work-log-remarks"
            >
                <AppInput
                    id="bandsaw-work-log-remarks"
                    v-model="form.remarks"
                    :disabled="processing"
                    :error="form.errors.remarks"
                    :icon="MessageSquare"
                    :placeholder="bandsawWorkLogPage.form.remarksPlaceholder"
                />
            </AppField>
        </FormSection>

        <FormActions
            :cancel-label="bandsawWorkLogPage.form.cancelLabel"
            :processing="processing"
            :submit-label="submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
