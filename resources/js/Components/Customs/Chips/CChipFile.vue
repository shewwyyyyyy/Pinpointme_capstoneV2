<template>
    <c-chip :prepend-icon="computedFileTypeIcon" :density="density" v-bind="$attrs">
        <slot />
    </c-chip>
</template>

<script setup>
import { computed } from "vue";
import CChip from "./CChip.vue";

const props = defineProps({
    fileType: String,
    density: {
        type: String,
        default: "compact", // Options: "comfortable", "compact", "default"
    },
});

const computedFileTypeIcon = computed(() => {
    if (
        props.fileType === "png" ||
        props.fileType === "jpg" ||
        props.fileType === "jpeg"
    ) {
        return "mdi-image";
    }

    if (props.fileType === "pdf") {
        return "mdi-file-pdf-box";
    }

    if (props.fileType === "doc" || props.fileType === "docx") {
        return "mdi-file-word-box";
    }

    if (props.fileType === "xls" || props.fileType === "xlsx") {
        return "mdi-file-excel-box";
    }

    if (props.fileType === "ppt" || props.fileType === "pptx") {
        return "mdi-file-powerpoint-box";
    }
    if (props.fileType === "zip" || props.fileType === "rar") {
        return "mdi-zip-box";
    }

    return "mdi-file-question";
});
</script>
