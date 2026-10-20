<script lang="ts" setup>
import {Badge, CalendarDays, CreditCard, IndianRupee, LockKeyhole, Mail, Phone, User,} from '@lucide/vue';
import {ref, watch} from 'vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';
import AppUserRoleSelect from '@/components/selects/AppUserRoleSelect.vue';
import AppUserShiftSelect from '@/components/selects/AppUserShiftSelect.vue';
import {employeePage} from '@/config/employees';
import {createEmployeeSchema, editEmployeeSchema,} from '@/schemas/employeeSchema';
import type {EmployeeFormData} from '@/types/employee';

const props = withDefaults(
    defineProps<{
        form: EmployeeFormData;
        errors?: Partial<Record<keyof EmployeeFormData, string>>;
        processing?: boolean;
        submitLabel?: string;
        isEdit?: boolean;
    }>(),
    {
        errors: () => ({}),
        processing: false,
        submitLabel: 'Save Employee',
        isEdit: false,
    },
);

const emit = defineEmits<{
    submit: [];
    cancel: [];
}>();

const frontendErrors = ref<Record<string, string>>({});
const hasSubmitted = ref(false);

const schema = () =>
    props.isEdit ? editEmployeeSchema : createEmployeeSchema;

const validateForm = () => {
    const result = schema().safeParse(props.form);
    const errors: Record<string, string> = {};

    if (!result.success) {
        for (const issue of result.error.issues) {
            const field = issue.path.join('.');

            if (field && !errors[field]) {
                errors[field] = issue.message;
            }
        }
    }

    frontendErrors.value = errors;

    return result.success;
};

const getError = (field: keyof EmployeeFormData) =>
    frontendErrors.value[field] ?? props.errors[field];

const handleSubmit = () => {
    hasSubmitted.value = true;

    if (!validateForm()) {
        return;
    }

    emit('submit');
};

watch(
    () => [
        props.form.name,
        props.form.employee_code,
        props.form.phone_number,
        props.form.alternate_phone_number,
        props.form.email,
        props.form.joining_date,
        props.form.aadhar_number,
        props.form.password,
        props.form.password_confirmation,
        props.form.role,
        props.form.shift,
        props.form.salary,
        props.form.incentive_per_day,
        props.form.deduction_per_day,
        props.form.status,
    ],
    () => {
        if (hasSubmitted.value) {
            validateForm();
        }
    },
);
</script>

<template>
    <form class="min-w-0 space-y-6" @submit.prevent="handleSubmit">
        <!-- Employee Information -->
        <FormSection
            :description="employeePage.form.employeeSectionDescription"
            :title="employeePage.form.employeeSectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Name -->
                <AppField
                    :label="employeePage.form.nameLabel"
                    input-id="employee-name"
                    required
                >
                    <AppInput
                        id="employee-name"
                        v-model="props.form.name"
                        :disabled="processing"
                        :error="getError('name')"
                        :icon="User"
                        :placeholder="employeePage.form.namePlaceholder"
                    />
                </AppField>

                <!-- Employee Code -->
                <AppField
                    :label="employeePage.form.employeeCodeLabel"
                    input-id="employee-code"
                    required
                >
                    <AppInput
                        id="employee-code"
                        v-model="props.form.employee_code"
                        :disabled="processing"
                        :error="getError('employee_code')"
                        :icon="Badge"
                        :placeholder="
                            employeePage.form.employeeCodePlaceholder
                        "
                    />
                </AppField>

                <!-- Joining Date -->
                <AppField
                    :label="employeePage.form.joiningDateLabel"
                    input-id="employee-joining-date"
                >
                    <AppInput
                        id="employee-joining-date"
                        v-model="props.form.joining_date"
                        :disabled="processing"
                        :error="getError('joining_date')"
                        :icon="CalendarDays"
                        type="date"
                    />
                </AppField>

                <!-- Phone Number -->
                <AppField
                    :label="employeePage.form.phoneNumberLabel"
                    input-id="employee-phone-number"
                >
                    <AppInput
                        id="employee-phone-number"
                        v-model="props.form.phone_number"
                        :disabled="processing"
                        :error="getError('phone_number')"
                        :icon="Phone"
                        :placeholder="
                            employeePage.form.phoneNumberPlaceholder
                        "
                        inputmode="numeric"
                        maxlength="10"
                        type="tel"
                    />
                </AppField>

                <!-- Alternate Phone Number -->
                <AppField
                    :label="employeePage.form.alternatePhoneNumberLabel"
                    input-id="employee-alternate-phone-number"
                >
                    <AppInput
                        id="employee-alternate-phone-number"
                        v-model="props.form.alternate_phone_number"
                        :disabled="processing"
                        :error="getError('alternate_phone_number')"
                        :icon="Phone"
                        :placeholder="
                            employeePage.form.alternatePhoneNumberPlaceholder
                        "
                        inputmode="numeric"
                        maxlength="10"
                        type="tel"
                    />
                </AppField>

                <!-- Email -->
                <AppField
                    :label="employeePage.form.emailLabel"
                    input-id="employee-email"
                >
                    <AppInput
                        id="employee-email"
                        v-model="props.form.email"
                        :disabled="processing"
                        :error="getError('email')"
                        :icon="Mail"
                        :placeholder="employeePage.form.emailPlaceholder"
                        type="email"
                    />
                </AppField>

                <!-- Aadhaar Number -->
                <AppField
                    :label="employeePage.form.aadharNumberLabel"
                    input-id="employee-aadhar-number"
                >
                    <AppInput
                        id="employee-aadhar-number"
                        v-model="props.form.aadhar_number"
                        :disabled="processing"
                        :error="getError('aadhar_number')"
                        :icon="CreditCard"
                        :placeholder="employeePage.form.aadharNumberPlaceholder"
                        inputmode="numeric"
                        maxlength="12"
                        type="text"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Authentication -->
        <FormSection
            v-if="!isEdit"
            :description="employeePage.form.authenticationSectionDescription"
            :title="employeePage.form.authenticationSectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Password -->
                <AppField
                    :label="employeePage.form.passwordLabel"
                    input-id="employee-password"
                    required
                >
                    <AppInput
                        id="employee-password"
                        v-model="props.form.password"
                        :disabled="processing"
                        :error="getError('password')"
                        :icon="LockKeyhole"
                        :placeholder="employeePage.form.passwordPlaceholder"
                        type="password"
                    />
                </AppField>

                <!-- Confirm Password -->
                <AppField
                    :label="employeePage.form.passwordConfirmationLabel"
                    input-id="employee-password-confirmation"
                    required
                >
                    <AppInput
                        id="employee-password-confirmation"
                        v-model="props.form.password_confirmation"
                        :disabled="processing"
                        :error="getError('password_confirmation')"
                        :icon="LockKeyhole"
                        :placeholder="
                            employeePage.form.passwordConfirmationPlaceholder
                        "
                        type="password"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Employee Configuration -->
        <FormSection
            :description="employeePage.form.configurationSectionDescription"
            :title="employeePage.form.configurationSectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
                <!-- Role -->
                <AppField
                    :label="employeePage.form.roleLabel"
                    input-id="employee-role"
                    required
                >
                    <AppUserRoleSelect
                        v-model="props.form.role"
                        :disabled="processing"
                        :error="getError('role')"
                        :placeholder="employeePage.form.rolePlaceholder"
                        searchable
                    />
                </AppField>

                <!-- Shift -->
                <AppField
                    :label="employeePage.form.shiftLabel"
                    input-id="employee-shift"
                >
                    <AppUserShiftSelect
                        v-model="props.form.shift"
                        :disabled="processing"
                        :error="getError('shift')"
                        :placeholder="employeePage.form.shiftPlaceholder"
                        searchable
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Salary Configuration -->
        <FormSection
            :description="employeePage.form.salarySectionDescription"
            :title="employeePage.form.salarySectionTitle"
        >
            <div class="grid min-w-0 gap-5 md:grid-cols-2 lg:grid-cols-3">
                <!-- Salary -->
                <AppField
                    :label="employeePage.form.salaryLabel"
                    input-id="employee-salary"
                    required
                >
                    <AppInput
                        id="employee-salary"
                        v-model="props.form.salary"
                        :disabled="processing"
                        :error="getError('salary')"
                        :icon="IndianRupee"
                        :placeholder="employeePage.form.salaryPlaceholder"
                        min="0"
                        step="0.01"
                        type="number"
                    />
                </AppField>

                <!-- Incentive Per Day -->
                <AppField
                    :label="employeePage.form.incentivePerDayLabel"
                    input-id="employee-incentive-per-day"
                    required
                >
                    <AppInput
                        id="employee-incentive-per-day"
                        v-model="props.form.incentive_per_day"
                        :disabled="processing"
                        :error="getError('incentive_per_day')"
                        :icon="IndianRupee"
                        :placeholder="
                            employeePage.form.incentivePerDayPlaceholder
                        "
                        min="0"
                        step="0.01"
                        type="number"
                    />
                </AppField>

                <!-- Deduction Per Day -->
                <AppField
                    :label="employeePage.form.deductionPerDayLabel"
                    input-id="employee-deduction-per-day"
                    required
                >
                    <AppInput
                        id="employee-deduction-per-day"
                        v-model="props.form.deduction_per_day"
                        :disabled="processing"
                        :error="getError('deduction_per_day')"
                        :icon="IndianRupee"
                        :placeholder="
                            employeePage.form.deductionPerDayPlaceholder
                        "
                        min="0"
                        step="0.01"
                        type="number"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Account Status -->
        <FormSection
            :description="employeePage.form.accountSectionDescription"
            :title="employeePage.form.accountSectionTitle"
        >
            <ToggleCardInput
                v-model="props.form.status"
                :disabled="processing"
                :error="getError('status')"
                active-text="Employee account is currently active."
                inactive-text="Employee account is currently inactive."
                label="Status"
                title="Employee Status"
            />
        </FormSection>

        <!-- Form Actions -->
        <FormActions
            :cancel-label="employeePage.form.cancelLabel"
            :processing="processing"
            :submit-label="submitLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
