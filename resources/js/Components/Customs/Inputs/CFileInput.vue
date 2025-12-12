<template>
    <v-file-input
        v-model="uploadedAttachments"
        :show-size="1000"
        :label="label"
        variant="outlined"
        density="compact"
        counter
        multiple
        prepend-icon="mdi-image-plus"
        accept="image/png, image/jpeg, image/jpg"
        :disabled="inputReadOnly"
        :hint="`Maximum attachment is: ${maxAttachment}; Must NOT exceed 8MB per attachment; ${
            hintType ? `File must be type of: ${hintType}` : ''
        }`"
        :error-messages="errorMessages"
        persistent-hint
    >
        {{ props.errors.attachments }}
        <template v-slot:selection="{ fileNames }">
            <template v-for="(fileName, index) in fileNames" :key="fileName">
                <v-chip
                    v-if="index < 2"
                    class="me-2"
                    color="primary"
                    size="small"
                    label
                >
                    {{ fileName }}
                </v-chip>

                <span
                    v-else-if="index === 2"
                    class="text-overline text-grey-darken-3 mx-2"
                >
                    +{{ uploadedAttachments.length - 2 }} File(s)
                </span>
            </template>
        </template>
    </v-file-input>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";

const props = defineProps({
    attachments: {
        default: [],
        type: Array,
    },
    currentAttachments: {
        default: [],
        type: Array,
    },
    maxAttachment: {
        default: 5,
        type: Number,
    },
    label: {
        default: "Upload Attachments",
        type: String,
    },
    hintType: String,
    errorMessages: String,
});

const uploadedAttachments = ref([]);
const emits = defineEmits(["uploadedAttachments"]);
const inputReadOnly = ref(false);

watch(uploadedAttachments, (newValue) => {
    // if attachments has value or in update form
    if (props.attachments) {
        // compute total images uploaded including previous upload
        newValue.splice(props.maxAttachment); // 2 attach

        const computeVal =
            props.currentAttachments.length - props.maxAttachment; //

        newValue.splice(Math.abs(computeVal)); // remove from current
    } else {
        if (newValue.length > props.maxAttachment) {
            newValue.splice(props.maxAttachment);
        }
    }
    emits("uploadedAttachments", newValue);
});

onMounted(() => {
    if (props.attachments) {
        inputReadOnly.value = props.attachments.length >= props.maxAttachment;
    }
});

watch(
    () => props.errorMessages,
    (newValue) => {
        if (newValue && newValue.length > 0) {
            uploadedAttachments.value = []; //empty if has error
        }
    }
);
</script>
