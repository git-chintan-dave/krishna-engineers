import {ref, watch} from 'vue';
import {z} from 'zod';

export function useZodFormValidation<TSchema extends z.ZodType>(
    schema: TSchema,
    getData: () => unknown,
) {
    type FormData = z.input<TSchema>;

    const errors = ref<
        Partial<Record<keyof FormData, string>>
    >({});

    const validate = () => {
        const result = schema.safeParse(getData());

        errors.value = {};

        if (result.success) {
            return true;
        }

        for (const issue of result.error.issues) {
            const field = issue.path[0] as keyof FormData;

            if (field !== undefined) {
                errors.value[field] ??= issue.message;
            }
        }

        return false;
    };

    watch(
        getData,
        () => {
            if (Object.keys(errors.value).length > 0) {
                validate();
            }
        },
        {deep: true},
    );

    return {
        errors,
        validate,
    };
}
