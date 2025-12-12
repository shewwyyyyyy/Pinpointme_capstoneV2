<template>
    <v-form>
        <v-container>
            <v-row>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <v-text-field
                        variant="outlined"
                        density="compact"
                        label="Name"
                        hide-details="auto"
                        v-model="form.name"
                        :error-messages="formErrors.name"
                        required
                        @keypress="filterEmoji"
                        @paste="handlePaste"
                    />
                </v-col>
                <v-col cols="12" md="6" lg="6" xl="6" xxl="6">
                    <v-text-field
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        label="Code"
                        v-model="form.code"
                        :error-messages="formErrors.code"
                        required
                        placeholder="e.g., IT, HR, FIN"
                        @keypress="filterEmojiCode"
                        @paste="handlePasteCode"
                    />
                </v-col>
                <v-col cols="12" md="12" lg="12" xl="12" xxl="12">
                    <v-textarea
                        hide-details="auto"
                        variant="outlined"
                        density="compact"
                        label="Description"
                        v-model="form.description"
                        :error-messages="formErrors.description"
                        rows="3"
                        placeholder="Enter department description..."
                        @keypress="filterEmoji"
                        @paste="handlePaste"
                    />
                </v-col>
            </v-row>
        </v-container>
    </v-form>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    department: Object,
    errors: Object,
    flash: Object,
    can: Array,
});

const form = ref({
    id: null,
    name: null,
    code: null,
    description: null,
});

watch(
    () => props.department,
    (newVal) => {
        if (newVal && Object.keys(newVal).length > 0) {
            Object.keys(form.value).forEach((key) => {
                const isNull = form.value[key] === null;
                const isEmptyArray =
                    Array.isArray(form.value[key]) &&
                    form.value[key].length === 0;

                // Only update if the current value is null or an empty array
                if (isNull || isEmptyArray) {
                    if (newVal.hasOwnProperty(key)) {
                        form.value[key] = newVal[key];
                    }
                }
            });
        }
    },
    { immediate: true, deep: true }
);

// set error start
const formErrors = ref({});
watch(
    () => props.errors,
    (newValue) => {
        formErrors.value = Object.assign({}, newValue);
    },
    { deep: true }
);
// set error end

const emits = defineEmits(["formValues"]);

// Emoji filtering functions
const isEmoji = (char) => {
    // Simple emoji detection using Unicode property
    return /\p{Emoji}/u.test(char);
};

const filterEmoji = (event) => {
    const char = event.key;
    
    // Allow special keys and numbers explicitly
    if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Enter', 'Escape'].includes(char)) {
        return;
    }
    
    // Allow numbers explicitly before emoji check
    if (/^[0-9]$/.test(char)) {
        return;
    }
    
    if (isEmoji(char)) {
        event.preventDefault();
        return false;
    }
    
    // Additional check for name and description fields
    const allowedPattern = /^[a-zA-Z\s\-_.,!?()&]$/;
    if (!allowedPattern.test(char)) {
        event.preventDefault();
        return false;
    }
};

const filterEmojiCode = (event) => {
    const char = event.key;
    
    // Allow special keys and numbers explicitly
    if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Enter', 'Escape'].includes(char)) {
        return;
    }
    
    // Allow numbers explicitly before emoji check
    if (/^[0-9]$/.test(char)) {
        return;
    }
    
    if (isEmoji(char)) {
        event.preventDefault();
        return false;
    }
    
    // Stricter pattern for code field
    const allowedPattern = /^[a-zA-Z\-_]$/;
    if (!allowedPattern.test(char)) {
        event.preventDefault();
        return false;
    }
};

const handlePaste = (event) => {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    // Remove all emojis
    const cleanText = pastedText.replace(/\p{Emoji}/gu, '');
    
    // Filter out invalid characters for name/description
    const filteredText = cleanText.replace(/[^a-zA-Z0-9\s\-_.,!?()&]/g, '');
    
    if (filteredText !== pastedText) {
        event.preventDefault();
        const input = event.target;
        const start = input.selectionStart;
        const end = input.selectionEnd;
        const currentValue = input.value;
        
        // Update the model value directly
        const fieldName = input.getAttribute('aria-label') || input.getAttribute('label') || 'name';
        if (fieldName.toLowerCase().includes('name')) {
            form.value.name = currentValue.substring(0, start) + filteredText + currentValue.substring(end);
        } else if (fieldName.toLowerCase().includes('description')) {
            form.value.description = currentValue.substring(0, start) + filteredText + currentValue.substring(end);
        }
    }
};

const handlePasteCode = (event) => {
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    // Remove all emojis
    const cleanText = pastedText.replace(/\p{Emoji}/gu, '');
    
    // Filter out invalid characters for code field (stricter)
    const filteredText = cleanText.replace(/[^a-zA-Z0-9\-_]/g, '');
    
    if (filteredText !== pastedText) {
        event.preventDefault();
        const input = event.target;
        const start = input.selectionStart;
        const end = input.selectionEnd;
        const currentValue = input.value;
        
        form.value.code = currentValue.substring(0, start) + filteredText + currentValue.substring(end);
    }
};

const emitFormData = () => {
    emits("formValues", form.value);
};

defineExpose({
    emitFormData,
});
</script>
