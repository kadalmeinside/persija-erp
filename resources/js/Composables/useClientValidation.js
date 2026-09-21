/**
 * useClientValidation.js
 * 
 * Composable untuk validasi client-side terpusat.
 * Digunakan bersama Inertia's `useForm` untuk memberikan 
 * feedback instan ke user sebelum request dikirim ke server.
 * 
 * Cara pakai:
 *   const { clientErrors, validate, clearClientError, hasClientErrors } = useClientValidation();
 *   
 *   const submit = () => {
 *     const errors = validate(form, rules);
 *     if (hasClientErrors(errors)) return;
 *     form.post(...);
 *   };
 */

import { ref } from 'vue';

export function useClientValidation() {
    const clientErrors = ref({});

    const clearClientError = (field) => {
        if (clientErrors.value[field]) {
            delete clientErrors.value[field];
        }
    };

    const clearAllClientErrors = () => {
        clientErrors.value = {};
    };

    const hasClientErrors = (errorsObj = null) => {
        const target = errorsObj ?? clientErrors.value;
        return Object.keys(target).length > 0;
    };

    /**
     * Validate a plain object against a rules map.
     * 
     * Rules example:
     * {
     *   nama: ['required', 'string', 'max:100'],
     *   email: ['required', 'email'],
     *   nominal: ['required', 'numeric', 'min:1'],
     *   tgl_mulai: ['required', 'date'],
     *   tgl_selesai: ['required', 'date', 'after_or_equal:tgl_mulai'],
     * }
     *
     * @param {Object} data - The form data object (form or plain ref)
     * @param {Object} rules - A map of field -> array of rule strings
     * @param {Object} messages - Optional custom error messages per field
     * @returns {Object} errors - An error map { field: 'error message' }
     */
    const validate = (data, rules, messages = {}) => {
        const errors = {};

        for (const field in rules) {
            const fieldRules = rules[field];
            const value = data[field];
            const label = messages[`${field}.label`] || fieldLabel(field);

            for (const rule of fieldRules) {
                const [ruleName, ruleParam] = rule.split(':');
                let errorMsg = null;

                if (ruleName === 'required') {
                    if (value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0)) {
                        errorMsg = messages[`${field}.required`] || `${label} wajib diisi.`;
                    }
                }

                if (ruleName === 'required_if') {
                    // required_if:other_field,value
                    const [otherField, otherValue] = ruleParam.split(',');
                    if (String(data[otherField]) === otherValue) {
                        if (value === null || value === undefined || value === '') {
                            errorMsg = messages[`${field}.required_if`] || `${label} wajib diisi.`;
                        }
                    }
                }

                if (ruleName === 'email' && value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        errorMsg = messages[`${field}.email`] || `${label} harus berupa alamat email yang valid.`;
                    }
                }

                if (ruleName === 'numeric' && value !== '' && value !== null && value !== undefined) {
                    if (isNaN(Number(value))) {
                        errorMsg = messages[`${field}.numeric`] || `${label} harus berupa angka.`;
                    }
                }

                if (ruleName === 'integer' && value !== '' && value !== null && value !== undefined) {
                    if (!Number.isInteger(Number(value))) {
                        errorMsg = messages[`${field}.integer`] || `${label} harus berupa bilangan bulat.`;
                    }
                }

                if (ruleName === 'min' && value !== '' && value !== null && value !== undefined) {
                    const isNumeric = fieldRules.includes('numeric') || fieldRules.includes('integer');
                    if (isNumeric) {
                        if (!isNaN(Number(value)) && Number(value) < Number(ruleParam)) {
                            errorMsg = messages[`${field}.min`] || `${label} minimal ${Number(ruleParam).toLocaleString('id-ID')}.`;
                        }
                    } else {
                        if (String(value).length < Number(ruleParam)) {
                            errorMsg = messages[`${field}.min`] || `${label} minimal ${ruleParam} karakter.`;
                        }
                    }
                }

                if (ruleName === 'max' && value !== '' && value !== null && value !== undefined) {
                    const isNumeric = fieldRules.includes('numeric') || fieldRules.includes('integer');
                    if (isNumeric) {
                        if (!isNaN(Number(value)) && Number(value) > Number(ruleParam)) {
                            errorMsg = messages[`${field}.max`] || `${label} maksimal ${Number(ruleParam).toLocaleString('id-ID')}.`;
                        }
                    } else {
                        if (String(value).length > Number(ruleParam)) {
                            errorMsg = messages[`${field}.max`] || `${label} maksimal ${ruleParam} karakter.`;
                        }
                    }
                }

                if (ruleName === 'string' && value !== null && value !== undefined && value !== '') {
                    if (typeof value !== 'string') {
                        errorMsg = messages[`${field}.string`] || `${label} harus berupa teks.`;
                    }
                }

                if (ruleName === 'date' && value) {
                    const d = new Date(value);
                    if (isNaN(d.getTime())) {
                        errorMsg = messages[`${field}.date`] || `${label} harus berupa tanggal yang valid.`;
                    }
                }

                if (ruleName === 'after_or_equal' && value && ruleParam) {
                    // ruleParam can be a field name or a date string
                    const compareValue = data[ruleParam] ?? ruleParam;
                    if (compareValue && new Date(value) < new Date(compareValue)) {
                        errorMsg = messages[`${field}.after_or_equal`] || `${label} tidak boleh sebelum tanggal yang dibandingkan.`;
                    }
                }

                if (ruleName === 'after' && value && ruleParam) {
                    const compareValue = data[ruleParam] ?? ruleParam;
                    if (compareValue && new Date(value) <= new Date(compareValue)) {
                        errorMsg = messages[`${field}.after`] || `${label} harus setelah tanggal yang dibandingkan.`;
                    }
                }

                if (ruleName === 'in' && value) {
                    const allowed = ruleParam.split(',');
                    if (!allowed.includes(String(value))) {
                        errorMsg = messages[`${field}.in`] || `${label} berisi pilihan yang tidak valid.`;
                    }
                }

                if (ruleName === 'array' && value !== null && value !== undefined) {
                    if (!Array.isArray(value)) {
                        errorMsg = messages[`${field}.array`] || `${label} harus berupa daftar.`;
                    }
                }

                if (ruleName === 'min_items' && Array.isArray(value)) {
                    if (value.length < Number(ruleParam)) {
                        errorMsg = messages[`${field}.min_items`] || `${label} minimal ${ruleParam} item.`;
                    }
                }

                // Stop at first error for this field
                if (errorMsg) {
                    errors[field] = errorMsg;
                    break;
                }
            }
        }

        clientErrors.value = errors;
        return errors;
    };

    /**
     * Convert camelCase/snake_case field name to human readable label.
     */
    const fieldLabel = (field) => {
        return field
            .replace(/_/g, ' ')
            .replace(/([A-Z])/g, ' $1')
            .replace(/^./, str => str.toUpperCase())
            .trim();
    };

    return {
        clientErrors,
        validate,
        clearClientError,
        clearAllClientErrors,
        hasClientErrors,
    };
}
