<script lang="ts" setup>
import type {InertiaForm} from '@inertiajs/vue3';
import {CalendarDays, Hash, Layers3, MessageSquare, Ruler, User, Wrench,} from '@lucide/vue';
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
import {useZodFormValidation} from '@/composables/useZodFormValidation';
import {bandsawWorkLogSchema} from '@/schemas/bandsawWorkLogSchema';

import type {BandsawWorkLogFormData, BandsawWorkLogLastReport,} from '@/types/bandsawWorkLog';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

type SelectedPart = {
    value: number;
    label: string;
    section_id: number | null;
    cutting_size: string | null;
};

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps<{
    form: InertiaForm<BandsawWorkLogFormData>;
    processing: boolean;
    submitLabel: string;
    canSelectBandsaw: boolean;
    lastReports: Record<string, BandsawWorkLogLastReport>;
    isCreate: boolean;
}>();

/*
|--------------------------------------------------------------------------
| Emits
|--------------------------------------------------------------------------
*/

const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

const {errors: frontendErrors, validate} = useZodFormValidation(
    bandsawWorkLogSchema,
    () => props.form,
);

const handleSubmit = (): void => {
    if (validate()) {
        emit('submit');
    }
};

/*
|--------------------------------------------------------------------------
| Local State
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
     * When restoring a last report, preserve
     * the restored Section and Section Size.
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
     * Tell handlePartChange this Part change
     * is programmatic.
     */
    restoringLastReport.value = true;

    /*
     * Set dependent values first.
     */
    props.form.section_id = lastReport.section_id;
    props.form.section_size_id = lastReport.section_size_id;

    /*
     * Set Part last so AppPartSelect can resolve
     * the selected Part and emit its details.
     */
    props.form.part_id = lastReport.part_id;

    await nextTick();
    await nextTick();

    restoringLastReport.value = false;
};

/*
|--------------------------------------------------------------------------
| Employee Watcher
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
         * Edit initialization:
         * Preserve values from the existing record.
         */
        if (!props.isCreate && previousUserId === undefined) {
            return;
        }

        /*
         * Create initialization or operator change:
         * Apply that operator's last report.
         */
        if (
            props.isCreate &&
            (previousUserId === undefined || userId !== previousUserId)
        ) {
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
                    :error="frontendErrors.user_id ?? form.errors.user_id"
                    :label="bandsawWorkLogPage.form.bandsawLabel"
                    input-id="bandsaw-work-log-employee"
                    required
                >
                    <AppEmployeeSelect
                        v-model="form.user_id"
                        :disabled="processing || !isCreate"
                        :error="frontendErrors.user_id ?? form.errors.user_id"
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
                    :error="frontendErrors.work_date ?? form.errors.work_date"
                    :label="bandsawWorkLogPage.form.workDateLabel"
                    input-id="bandsaw-work-log-date"
                    required
                >
                    <AppInput
                        id="bandsaw-work-log-date"
                        v-model="form.work_date"
                        :disabled="processing || !isCreate"
                        :error="
                            frontendErrors.work_date ?? form.errors.work_date
                        "
                        :icon="CalendarDays"
                        required
                        type="date"
                    />
                </AppField>

                <!-- Part -->
                <AppField
                    :error="frontendErrors.part_id ?? form.errors.part_id"
                    :label="bandsawWorkLogPage.form.partLabel"
                    input-id="bandsaw-work-log-part"
                    required
                >
                    <AppPartSelect
                        v-model="form.part_id"
                        :disabled="processing || !isCreate"
                        :error="frontendErrors.part_id ?? form.errors.part_id"
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
                    :error="frontendErrors.section_id ?? form.errors.section_id"
                    :label="bandsawWorkLogPage.form.sectionLabel"
                    input-id="bandsaw-work-log-section"
                    required
                >
                    <AppSectionSelect
                        :disabled="processing || !isCreate"
                        :error="
                            frontendErrors.section_id ?? form.errors.section_id
                        "
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
                    :error="
                        frontendErrors.section_size_id ??
                        form.errors.section_size_id
                    "
                    :label="bandsawWorkLogPage.form.sectionSizeLabel"
                    input-id="bandsaw-work-log-section-size"
                    required
                >
                    <AppSectionSizeSelect
                        v-model="form.section_size_id"
                        :disabled="processing || !isCreate"
                        :error="
                            frontendErrors.section_size_id ??
                            form.errors.section_size_id
                        "
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
                    :error="frontendErrors.quantity ?? form.errors.quantity"
                    :label="bandsawWorkLogPage.form.quantityLabel"
                    input-id="bandsaw-work-log-quantity"
                    required
                >
                    <AppInput
                        id="bandsaw-work-log-quantity"
                        v-model.number="form.quantity"
                        :disabled="processing"
                        :error="frontendErrors.quantity ?? form.errors.quantity"
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
                :error="frontendErrors.remarks ?? form.errors.remarks"
                :label="bandsawWorkLogPage.form.remarksLabel"
                input-id="bandsaw-work-log-remarks"
            >
                <AppInput
                    id="bandsaw-work-log-remarks"
                    v-model="form.remarks"
                    :disabled="processing"
                    :error="frontendErrors.remarks ?? form.errors.remarks"
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
