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
import { nextTick, ref, watch } from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppEmployeeSelect from '@/components/selects/AppEmployeeSelect.vue';
import AppPartSelect from '@/components/selects/AppPartSelect.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import AppSectionSizeSelect from '@/components/selects/AppSectionSizeSelect.vue';
import { bandsawWorkLogPage } from '@/config/bandsawWorkLogs';
import type {
    BandsawWorkLogFormData,
    BandsawWorkLogLastReport,
} from '@/types/bandsawWorkLog';

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
    <form class="min-w-0 space-y-6" @submit.prevent="emit('submit')">
        <FormSection
            :title="bandsawWorkLogPage.form.sectionTitle"
            :description="bandsawWorkLogPage.form.sectionDescription"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Bandsaw Operator -->
                <AppField
                    v-if="canSelectBandsaw"
                    :label="bandsawWorkLogPage.form.bandsawLabel"
                    input-id="bandsaw-work-log-employee"
                    :error="form.errors.user_id"
                    required
                >
                    <AppEmployeeSelect
                        v-model="form.user_id"
                        :placeholder="
                            bandsawWorkLogPage.form.bandsawPlaceholder
                        "
                        :disabled="processing || !isCreate"
                        :error="form.errors.user_id"
                        :icon="User"
                        searchable
                        role="bandsaw"
                    />
                </AppField>

                <!-- Work Date -->
                <AppField
                    :label="bandsawWorkLogPage.form.workDateLabel"
                    input-id="bandsaw-work-log-date"
                    :error="form.errors.work_date"
                    required
                >
                    <AppInput
                        id="bandsaw-work-log-date"
                        v-model="form.work_date"
                        type="date"
                        :disabled="processing || !isCreate"
                        :error="form.errors.work_date"
                        :icon="CalendarDays"
                        required
                    />
                </AppField>

                <!-- Part -->
                <AppField
                    :label="bandsawWorkLogPage.form.partLabel"
                    input-id="bandsaw-work-log-part"
                    :error="form.errors.part_id"
                    required
                >
                    <AppPartSelect
                        v-model="form.part_id"
                        :placeholder="bandsawWorkLogPage.form.partPlaceholder"
                        :disabled="processing || !isCreate"
                        :error="form.errors.part_id"
                        :icon="Wrench"
                        searchable
                        @part="handlePartChange"
                    />
                </AppField>

                <!-- Cutting Size -->
                <AppField
                    label="Cutting Size"
                    input-id="bandsaw-work-log-cutting-size"
                >
                    <AppInput
                        id="bandsaw-work-log-cutting-size"
                        :model-value="cuttingSize"
                        placeholder="—"
                        disabled
                        :icon="Ruler"
                    />
                </AppField>

                <!-- Section -->
                <AppField
                    :label="bandsawWorkLogPage.form.sectionLabel"
                    input-id="bandsaw-work-log-section"
                    :error="form.errors.section_id"
                    required
                >
                    <AppSectionSelect
                        :model-value="form.section_id"
                        :placeholder="
                            bandsawWorkLogPage.form.sectionPlaceholder
                        "
                        :disabled="processing || !isCreate"
                        :error="form.errors.section_id"
                        :icon="Layers3"
                        searchable
                        @update:model-value="handleSectionChange"
                    />
                </AppField>

                <!-- Section Size -->
                <AppField
                    :label="bandsawWorkLogPage.form.sectionSizeLabel"
                    input-id="bandsaw-work-log-section-size"
                    :error="form.errors.section_size_id"
                    required
                >
                    <AppSectionSizeSelect
                        v-model="form.section_size_id"
                        :section-id="form.section_id"
                        :placeholder="
                            bandsawWorkLogPage.form.sectionSizePlaceholder
                        "
                        :disabled="processing || !isCreate"
                        :error="form.errors.section_size_id"
                        :icon="Ruler"
                        searchable
                    />
                </AppField>

                <!-- Quantity -->
                <AppField
                    :label="bandsawWorkLogPage.form.quantityLabel"
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
                        :placeholder="
                            bandsawWorkLogPage.form.quantityPlaceholder
                        "
                        :disabled="processing"
                        :error="form.errors.quantity"
                        :icon="Hash"
                        required
                    />
                </AppField>
            </div>
        </FormSection>

        <FormSection
            title="Additional Information"
            description="Add any relevant remarks about this work."
        >
            <AppField
                :label="bandsawWorkLogPage.form.remarksLabel"
                input-id="bandsaw-work-log-remarks"
                :error="form.errors.remarks"
            >
                <AppInput
                    id="bandsaw-work-log-remarks"
                    v-model="form.remarks"
                    :placeholder="bandsawWorkLogPage.form.remarksPlaceholder"
                    :disabled="processing"
                    :error="form.errors.remarks"
                    :icon="MessageSquare"
                />
            </AppField>
        </FormSection>

        <FormActions
            :submit-label="submitLabel"
            :processing="processing"
            :cancel-label="bandsawWorkLogPage.form.cancelLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
