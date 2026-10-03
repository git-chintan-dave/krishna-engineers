<script setup lang="ts">
import {
    Badge,
    CalendarDays,
    CreditCard,
    IndianRupee,
    LockKeyhole,
    Mail,
    Phone,
    User,
} from '@lucide/vue';

import AppInput from '@/components/common/AppInput.vue';
import AppField from '@/components/form/AppField.vue';
import FormActions from '@/components/form/FormActions.vue';
import FormSection from '@/components/form/FormSection.vue';
import ToggleCardInput from '@/components/form/ToggleCardInput.vue';
import AppUserRoleSelect from '@/components/selects/AppUserRoleSelect.vue';
import AppUserShiftSelect from '@/components/selects/AppUserShiftSelect.vue';
import {employeePage} from '@/config/employees';
import type {EmployeeFormData} from '@/types/employee';

// Props
const props = withDefaults(
    defineProps<{
        form: EmployeeFormData;
        errors?: Partial<
            Record<keyof EmployeeFormData, string>
        >;
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

// Events
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
        <!-- Employee Information -->
        <FormSection
            :title="employeePage.form.employeeSectionTitle"
            :description="
                employeePage.form.employeeSectionDescription
            "
        >
            <div
                class="grid min-w-0 gap-5 md:grid-cols-2"
            >
                <!-- Name -->
                <AppField
                    :label="employeePage.form.nameLabel"
                    input-id="employee-name"
                    required
                >
                    <AppInput
                        id="employee-name"
                        v-model="props.form.name"
                        :placeholder="
                            employeePage.form.namePlaceholder
                        "
                        :disabled="processing"
                        :error="errors.name"
                        :icon="User"
                        required
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
                        :placeholder="
                            employeePage.form.employeeCodePlaceholder
                        "
                        :disabled="processing"
                        :error="errors.employee_code"
                        :icon="Badge"
                        required
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
                        type="date"
                        :disabled="processing"
                        :error="errors.joining_date"
                        :icon="CalendarDays"
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
                        type="tel"
                        inputmode="numeric"
                        maxlength="10"
                        :placeholder="
                            employeePage.form.phoneNumberPlaceholder
                        "
                        :disabled="processing"
                        :error="errors.phone_number"
                        :icon="Phone"
                    />
                </AppField>

                <!-- Alternate Phone Number -->
                <AppField
                    :label="
                        employeePage.form
                            .alternatePhoneNumberLabel
                    "
                    input-id="employee-alternate-phone-number"
                >
                    <AppInput
                        id="employee-alternate-phone-number"
                        v-model="
                            props.form.alternate_phone_number
                        "
                        type="tel"
                        inputmode="numeric"
                        maxlength="10"
                        :placeholder="
                            employeePage.form
                                .alternatePhoneNumberPlaceholder
                        "
                        :disabled="processing"
                        :error="errors.alternate_phone_number"
                        :icon="Phone"
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
                        type="email"
                        :placeholder="
                            employeePage.form.emailPlaceholder
                        "
                        :disabled="processing"
                        :error="errors.email"
                        :icon="Mail"
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
                        type="text"
                        inputmode="numeric"
                        maxlength="12"
                        :placeholder="
                            employeePage.form.aadharNumberPlaceholder
                        "
                        :disabled="processing"
                        :error="errors.aadhar_number"
                        :icon="CreditCard"
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Authentication -->
        <FormSection
            v-if="!isEdit"
            :title="
                employeePage.form.authenticationSectionTitle
            "
            :description="
                employeePage.form
                    .authenticationSectionDescription
            "
        >
            <div
                class="grid min-w-0 gap-5 md:grid-cols-2"
            >
                <!-- Password -->
                <AppField
                    :label="employeePage.form.passwordLabel"
                    input-id="employee-password"
                    required
                >
                    <AppInput
                        id="employee-password"
                        v-model="props.form.password"
                        type="password"
                        :placeholder="
                            employeePage.form.passwordPlaceholder
                        "
                        :disabled="processing"
                        :error="errors.password"
                        :icon="LockKeyhole"
                        required
                    />
                </AppField>

                <!-- Confirm Password -->
                <AppField
                    :label="
                        employeePage.form
                            .passwordConfirmationLabel
                    "
                    input-id="employee-password-confirmation"
                    required
                >
                    <AppInput
                        id="employee-password-confirmation"
                        v-model="
                            props.form.password_confirmation
                        "
                        type="password"
                        :placeholder="
                            employeePage.form
                                .passwordConfirmationPlaceholder
                        "
                        :disabled="processing"
                        :error="errors.password_confirmation"
                        :icon="LockKeyhole"
                        required
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Employee Configuration -->
        <FormSection
            :title="
                employeePage.form.configurationSectionTitle
            "
            :description="
                employeePage.form
                    .configurationSectionDescription
            "
        >
            <div
                class="grid min-w-0 gap-5 md:grid-cols-2"
            >
                <!-- Role -->
                <AppField
                    :label="employeePage.form.roleLabel"
                    input-id="employee-role"
                >
                    <AppUserRoleSelect
                        v-model="props.form.role"
                        :placeholder="
                            employeePage.form.rolePlaceholder
                        "
                        :disabled="processing"
                        :error="errors.role"
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
                        :placeholder="
                            employeePage.form.shiftPlaceholder
                        "
                        :disabled="processing"
                        :error="errors.shift"
                        searchable
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Salary Configuration -->
        <FormSection
            :title="employeePage.form.salarySectionTitle"
            :description="
                employeePage.form.salarySectionDescription
            "
        >
            <div
                class="grid min-w-0 gap-5 md:grid-cols-3"
            >
                <!-- Salary -->
                <AppField
                    :label="employeePage.form.salaryLabel"
                    input-id="employee-salary"
                    required
                >
                    <AppInput
                        id="employee-salary"
                        v-model="props.form.salary"
                        type="number"
                        min="0"
                        step="0.01"
                        :placeholder="
                            employeePage.form.salaryPlaceholder
                        "
                        :disabled="processing"
                        :error="errors.salary"
                        :icon="IndianRupee"
                        required
                    />
                </AppField>

                <!-- Incentive Per Day -->
                <AppField
                    :label="
                        employeePage.form.incentivePerDayLabel
                    "
                    input-id="employee-incentive-per-day"
                    required
                >
                    <AppInput
                        id="employee-incentive-per-day"
                        v-model="
                            props.form.incentive_per_day
                        "
                        type="number"
                        min="0"
                        step="0.01"
                        :placeholder="
                            employeePage.form
                                .incentivePerDayPlaceholder
                        "
                        :disabled="processing"
                        :error="errors.incentive_per_day"
                        :icon="IndianRupee"
                        required
                    />
                </AppField>

                <!-- Deduction Per Day -->
                <AppField
                    :label="
                        employeePage.form.deductionPerDayLabel
                    "
                    input-id="employee-deduction-per-day"
                    required
                >
                    <AppInput
                        id="employee-deduction-per-day"
                        v-model="
                            props.form.deduction_per_day
                        "
                        type="number"
                        min="0"
                        step="0.01"
                        :placeholder="
                            employeePage.form
                                .deductionPerDayPlaceholder
                        "
                        :disabled="processing"
                        :error="errors.deduction_per_day"
                        :icon="IndianRupee"
                        required
                    />
                </AppField>
            </div>
        </FormSection>

        <!-- Account Status -->
        <FormSection
            :title="employeePage.form.accountSectionTitle"
            :description="
                employeePage.form.accountSectionDescription
            "
        >
            <ToggleCardInput
                v-model="props.form.status"
                title="Employee Status"
                label="Status"
                active-text="Employee account is currently active."
                inactive-text="Employee account is currently inactive."
                :disabled="processing"
                :error="errors.status"
            />
        </FormSection>

        <!-- Form Actions -->
        <FormActions
            :submit-label="submitLabel"
            :processing="processing"
            :cancel-label="employeePage.form.cancelLabel"
            @cancel="emit('cancel')"
        />
    </form>
</template>
