<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';
import { ArrowLeftRight, Hash, MessageSquare } from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import AppRawMaterialTransactionTypeSelect from '@/components/selects/AppRawMaterialTransactionTypeSelect.vue';
import AppSectionSelect from '@/components/selects/AppSectionSelect.vue';
import AppSectionSizeSelect from '@/components/selects/AppSectionSizeSelect.vue';
import { rawMaterialTransactionPage } from '@/config/rawMaterialTransactions';
import type {
    RawMaterialTransaction,
    RawMaterialTransactionFormData,
} from '@/types/rawMaterialTransaction';

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
    if (isEdit.value || newSectionId === oldSectionId) {
        return;
    }

    props.form.section_size_id = null;
});
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="emit('submit')">
        <FormSection
            :title="rawMaterialTransactionPage.form.sectionTitle"
            :description="rawMaterialTransactionPage.form.sectionDescription"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Section -->

                <AppField
                    label="Section"
                    input-id="raw-material-section"
                    required
                >
                    <AppSectionSelect
                        id="raw-material-section"
                        v-model="selectedSectionId"
                        placeholder="Select section"
                        :disabled="props.processing || isEdit"
                        searchable
                        clearable
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
                        :section-id="selectedSectionId"
                        :placeholder="
                            rawMaterialTransactionPage.form
                                .sectionSizePlaceholder
                        "
                        :disabled="
                            props.processing || isEdit || !selectedSectionId
                        "
                        :error="props.errors.section_size_id"
                        searchable
                        clearable
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
                        :placeholder="
                            rawMaterialTransactionPage.form.typePlaceholder
                        "
                        :disabled="props.processing || isEdit"
                        :icon="ArrowLeftRight"
                        searchable
                        clearable
                        :error="props.errors.type"
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
                        type="number"
                        min="1"
                        max="4294967295"
                        step="1"
                        :placeholder="
                            rawMaterialTransactionPage.form.quantityPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="Hash"
                        :error="props.errors.quantity"
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
                <AppField
                    :label="rawMaterialTransactionPage.form.remarksLabel"
                    input-id="raw-material-remarks"
                >
                    <AppInput
                        id="raw-material-remarks"
                        v-model="props.form.remarks"
                        :placeholder="
                            rawMaterialTransactionPage.form.remarksPlaceholder
                        "
                        :disabled="props.processing"
                        :icon="MessageSquare"
                        :error="props.errors.remarks"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Form Actions -->

        <FormActions
            :submit-label="props.submitLabel"
            :processing="props.processing"
            :cancel-label="rawMaterialTransactionPage.form.cancelLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
