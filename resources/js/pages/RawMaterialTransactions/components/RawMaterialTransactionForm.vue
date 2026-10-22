<script lang="ts" setup>
import {computed, ref, watch} from 'vue';
import type {InertiaForm} from '@inertiajs/vue3';
import {ArrowLeftRight, Hash, MessageSquare} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppRawMaterialTransactionTypeSelect from '@/components/selects/AppRawMaterialTransactionTypeSelect.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import AppSectionSizeSelect from '@/components/selects/AppSectionSizeSelect.vue';
import {useZodFormValidation} from '@/composables/useZodFormValidation';
import {rawMaterialTransactionPage} from '@/config/rawMaterialTransactions';
import type {RawMaterialTransaction, RawMaterialTransactionFormData} from '@/types/rawMaterialTransaction';
import type {RawMaterialTransactionValidationField} from '@/schemas/rawMaterialTransactionSchema';
import {rawMaterialTransactionSchema} from '@/schemas/rawMaterialTransactionSchema';

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = withDefaults(
    defineProps<{
        form: InertiaForm<RawMaterialTransactionFormData>;
        transaction?: RawMaterialTransaction;
        errors?: Partial<Record<keyof RawMaterialTransactionFormData, string>>;
        processing?: boolean;
        submitLabel?: string;
    }>(),
    {
        transaction: undefined,
        errors: () => ({}),
        processing: false,
        submitLabel: 'Save Transaction',
    },
);

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
| Mode
|--------------------------------------------------------------------------
*/

const isEdit = computed(() => !!props.transaction);

/*
|--------------------------------------------------------------------------
| Section
|--------------------------------------------------------------------------
*/

const selectedSectionId = ref<number | null>(
    props.transaction?.section_size.section_id ?? null,
);

watch(selectedSectionId, (newSectionId, oldSectionId) => {
    if (!isEdit.value && newSectionId !== oldSectionId) {
        props.form.section_size_id = null;
    }
});

/*
|--------------------------------------------------------------------------
| Frontend Validation
|--------------------------------------------------------------------------
*/

const {
    errors: frontendErrors,
    validate,
} = useZodFormValidation(
    rawMaterialTransactionSchema,
    () => ({
        section_id: selectedSectionId.value,
        section_size_id: props.form.section_size_id,
        type: props.form.type,
        quantity: props.form.quantity,
        remarks: props.form.remarks,
    }),
);

/*
|--------------------------------------------------------------------------
| Error Handling
|--------------------------------------------------------------------------
*/

const getError = (
    field: RawMaterialTransactionValidationField,
): string | undefined => {
    const frontendError = frontendErrors.value[field];

    if (frontendError) {
        return frontendError;
    }

    if (field === 'section_id') {
        return undefined;
    }

    return props.errors[
        field as keyof RawMaterialTransactionFormData
        ];
};

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const handleSubmit = (): void => {
    if (validate()) {
        emit('submit');
    }
};

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const isAdjustment = computed(() => props.form.type === 'adjustment');

const quantityMin = computed(() =>
    isAdjustment.value ? -2147483648 : 1,
);

const quantityMax = 4294967295;
</script>

<template>
    <form
        class="min-w-0 space-y-6"
        novalidate
        @submit.prevent="handleSubmit"
    >
        <FormSection
            :description="rawMaterialTransactionPage.form.sectionDescription"
            :title="rawMaterialTransactionPage.form.sectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Section -->

                <AppField
                    input-id="raw-material-section"
                    label="Section"
                    required
                >
                    <AppSectionSelect
                        id="raw-material-section"
                        v-model="selectedSectionId"
                        :disabled="props.processing || isEdit"
                        :error="getError('section_id')"
                        clearable
                        placeholder="Select section"
                        searchable
                    />
                </AppField>

                <!-- Section Size -->

                <AppField
                    :label="rawMaterialTransactionPage.form.sectionSizeLabel"
                    input-id="raw-material-section-size"
                    required
                >
                    <AppSectionSizeSelect
                        id="raw-material-section-size"
                        v-model="props.form.section_size_id"
                        :disabled="
                            props.processing || isEdit || !selectedSectionId
                        "
                        :error="getError('section_size_id')"
                        :placeholder="
                            rawMaterialTransactionPage.form
                                .sectionSizePlaceholder
                        "
                        :section-id="selectedSectionId"
                        clearable
                        searchable
                    />
                </AppField>

                <!-- Transaction Type -->

                <AppField
                    :label="rawMaterialTransactionPage.form.typeLabel"
                    input-id="raw-material-transaction-type"
                    required
                >
                    <AppRawMaterialTransactionTypeSelect
                        id="raw-material-transaction-type"
                        v-model="props.form.type"
                        :disabled="props.processing || isEdit"
                        :error="getError('type')"
                        :icon="ArrowLeftRight"
                        :placeholder="
                            rawMaterialTransactionPage.form.typePlaceholder
                        "
                        clearable
                        searchable
                    />
                </AppField>

                <!-- Quantity -->

                <AppField
                    :label="rawMaterialTransactionPage.form.quantityLabel"
                    input-id="raw-material-quantity"
                    required
                >
                    <AppInput
                        id="raw-material-quantity"
                        v-model.number="props.form.quantity"
                        :disabled="props.processing"
                        :error="getError('quantity')"
                        :icon="Hash"
                        :placeholder="
                            rawMaterialTransactionPage.form.quantityPlaceholder
                        "
                        :max="quantityMax"
                        :min="quantityMin"
                        step="1"
                        type="number"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Additional Information -->

        <FormSection
            description="Add an optional note for this material transaction."
            title="Additional Information"
        >
            <div class="grid min-w-0 gap-5">
                <AppField
                    :label="rawMaterialTransactionPage.form.remarksLabel"
                    input-id="raw-material-remarks"
                >
                    <AppInput
                        id="raw-material-remarks"
                        v-model="props.form.remarks"
                        :disabled="props.processing"
                        :error="getError('remarks')"
                        :icon="MessageSquare"
                        :placeholder="
                            rawMaterialTransactionPage.form.remarksPlaceholder
                        "
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Form Actions -->

        <FormActions
            :cancel-label="rawMaterialTransactionPage.form.cancelLabel"
            :processing="props.processing"
            :submit-label="props.submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
