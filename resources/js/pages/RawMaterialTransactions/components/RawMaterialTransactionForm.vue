<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    Hash,
    Layers3,
    MessageSquare,
    Ruler,
} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppRawMaterialTransactionTypeSelect from '@/components/selects/AppRawMaterialTransactionTypeSelect.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import AppSectionSizeSelect from '@/components/selects/AppSectionSizeSelect.vue';
import { rawMaterialTransactionPage } from '@/config/rawMaterialTransactions';
import type { RawMaterialTransactionFormData } from '@/types/rawMaterialTransaction';

// Props
const props = withDefaults(
    defineProps<{
        form: InertiaForm<RawMaterialTransactionFormData>;
        errors?: Partial<
            Record<
                keyof RawMaterialTransactionFormData,
                string
            >
        >;
        processing?: boolean;
        submitLabel?: string;
    }>(),
    {
        errors: () => ({}),
        processing: false,
        submitLabel: 'Save Transaction',
    },
);

// Emits
const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();
</script>

<template>
    <form
        class="min-w-0 space-y-6"
        @submit.prevent="emit('submit')"
    >
        <!-- Material -->
        <FormSection
            title="Material Information"
            description="Select the raw material section and size for this transaction."
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Section -->
                <AppField
                    label="Section"
                    input-id="raw-material-section"
                    :error="props.errors.section_id"
                    required
                >
                    <AppSectionSelect
                        id="raw-material-section"
                        v-model="props.form.section_id"
                        placeholder="Select Section"
                        :disabled="props.processing"
                        :icon="Layers3"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- Section Size -->
                <AppField
                    :label="
                        rawMaterialTransactionPage.form.sectionSizeLabel
                    "
                    input-id="raw-material-section-size"
                    :error="props.errors.section_size_id"
                    required
                >
                    <AppSectionSizeSelect
                        id="raw-material-section-size"
                        v-model="props.form.section_size_id"
                        :section-id="props.form.section_id"
                        :placeholder="
                            rawMaterialTransactionPage.form.sectionSizePlaceholder
                        "
                        :disabled="
                            props.processing ||
                            !props.form.section_id
                        "
                        :icon="Ruler"
                        searchable
                        clearable
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Transaction -->
        <FormSection
            title="Transaction Details"
            description="Configure the raw material transaction type and quantity."
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Transaction Type -->
                <AppField
                    :label="
                        rawMaterialTransactionPage.form.typeLabel
                    "
                    input-id="raw-material-transaction-type"
                    :error="props.errors.type"
                    required
                >
                    <AppRawMaterialTransactionTypeSelect
                        id="raw-material-transaction-type"
                        v-model="props.form.type"
                        :placeholder="
                            rawMaterialTransactionPage.form.typePlaceholder
                        "
                        :disabled="props.processing"
                        :icon="ArrowLeftRight"
                        searchable
                        clearable
                    />
                </AppField>

                <!-- Quantity -->
                <AppField
                    :label="
                        rawMaterialTransactionPage.form.quantityLabel
                    "
                    input-id="raw-material-quantity"
                    :error="props.errors.quantity"
                    required
                >
                    <AppInput
                        id="raw-material-quantity"
                        v-model.number="props.form.quantity"
                        type="number"
                        min="1"
                        :placeholder="
                            rawMaterialTransactionPage.form.quantityPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Hash"
                        required
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Additional Information -->
        <FormSection
            title="Additional Information"
            description="Add an optional note for this material transaction."
        >
            <div class="grid min-w-0 gap-5">
                <!-- Remarks -->
                <AppField
                    :label="
                        rawMaterialTransactionPage.form.remarksLabel
                    "
                    input-id="raw-material-remarks"
                    :error="props.errors.remarks"
                >
                    <AppInput
                        id="raw-material-remarks"
                        v-model="props.form.remarks"
                        :placeholder="
                            rawMaterialTransactionPage.form.remarksPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="MessageSquare"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Actions -->
        <FormActions
            :submit-label="submitLabel"
            :processing="processing"
            :cancel-label="
                rawMaterialTransactionPage.form.cancelLabel
            "
            @cancel="emit('cancel')"
        />
    </form>
</template>
