<template>
  <v-card
    class="mb-1"
    rounded="lg"
    elevation="1"
    variant="outlined"
  >
    <v-card-text class="pa-0">
      <v-container no-gutters>
        <v-row align="center" class="px-0" no-gutters>
          <v-col class="pa-0">
            <v-card-title class="text-body-1 font-weight-bold pa-0 mb-1">
              {{ maskedName }}
            </v-card-title>
            <v-card-text class="text-body-2 pa-0 mb-1">
              {{ item.mealType }}
            </v-card-text>
            <v-card-text class="text-caption text-grey-darken-1 pa-0">
              {{ item.time }}
            </v-card-text>
          </v-col>
          <v-col cols="auto">
            <v-container
              class="d-flex justify-center align-center pa-0"
              style="cursor: pointer"
              @click="handleQRClick(item, index)"
            >
              <v-icon
                size="70"
                color="primary"
              >
                mdi-qrcode
              </v-icon>
            </v-container>
          </v-col>
        </v-row>
      </v-container>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  item: Object,
  index: Number
})

const emit = defineEmits(['handleQRClick'])

const maskedName = computed(() => {
  if (!props.item.name) return ''
  
  return props.item.name
    .split(' ')
    .map(word => {
      if (word.length <= 2) {
        return word
      }
      return word.substring(0, 2) + '*'.repeat(word.length - 2)
    })
    .join(' ')
})

const handleQRClick = (item, index) => {
  emit('handleQRClick', item, index)
}
</script>
