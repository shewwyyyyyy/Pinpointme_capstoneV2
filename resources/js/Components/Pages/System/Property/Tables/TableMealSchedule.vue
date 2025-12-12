<template>
  <v-expand-transition>
    <v-card v-if="form.name" class="mt-9" outlined>
      <v-card-title class="text-h5 font-weight-bold mb-4">
        <v-icon class="mr-2">mdi-clock-outline</v-icon>
        Meal Schedule
      </v-card-title>

      <v-card-text>
        <!-- Table Layout -->
        <v-container fluid>
          <v-row class="mb-2 d-none d-sm-flex">
            <v-col cols="1" class="font-weight-bold text-h6 border-b-lg"></v-col>
            <v-col cols="2" class="font-weight-bold text-h6 border-b-lg">Day</v-col>
            <v-col cols="3" class="font-weight-bold text-h6 text-center border-b-lg">Breakfast</v-col>
            <v-col cols="3" class="font-weight-bold text-h6 text-center border-b-lg">Lunch</v-col>
            <v-col cols="3" class="font-weight-bold text-h6 text-center border-b-lg">Dinner</v-col>
          </v-row>

          <v-row 
            v-for="day in days" 
            :key="day"
            class="border-b py-2"
          >
            <v-col cols="12" sm="1" class="d-flex align-center">
              <v-checkbox
                v-model="dayEnabled[day]"
                hide-details
                density="compact"
                color="primary"
                @update:model-value="handleDayToggle"
              />
              <span class="d-sm-none font-weight-medium ml-2">{{ day }}</span>
            </v-col>
            <v-col cols="12" sm="2" class="font-weight-medium d-none d-sm-flex align-center">
              {{ day }} 
            </v-col>
            <v-col cols="12" sm="3" class="text-center d-flex flex-column flex-sm-row align-center justify-center">
              <span class="d-sm-none font-weight-medium mb-1 text-left align-self-start">Breakfast:</span>
              <v-btn
                variant="tonal"
                color="primary"
                size="small"
                :disabled="!dayEnabled[day]"
                @click="openDialog(day, 'breakfast')"
                class="flex-grow-1"
              >
                {{ formatTime(schedule[day]?.breakfast_start || schedule[day].breakfast_start) }} - {{ formatTime(schedule[day]?.breakfast_end || schedule[day].breakfast_end) }}
              </v-btn>
            </v-col>
            <v-col cols="12" sm="3" class="text-center d-flex flex-column flex-sm-row align-center justify-center">
              <span class="d-sm-none font-weight-medium mb-1 text-left align-self-start">Lunch:</span>
              <v-btn
                variant="tonal"
                color="primary"
                size="small"
                :disabled="!dayEnabled[day]"
                @click="openDialog(day, 'lunch')"
                class="flex-grow-1"
              >
                {{ formatTime(schedule[day]?.lunch_start || schedule[day].lunch_start) }} - {{ formatTime(schedule[day]?.lunch_end || schedule[day].lunch_end) }}
              </v-btn>
            </v-col>
            <v-col cols="12" sm="3" class="text-center d-flex flex-column flex-sm-row align-center justify-center">
              <span class="d-sm-none font-weight-medium mb-1 text-left align-self-start">Dinner:</span>
              <v-btn
                variant="tonal"
                color="primary"
                size="small"
                :disabled="!dayEnabled[day]"
                @click="openDialog(day, 'dinner')"
                class="flex-grow-1"
              >
                {{ formatTime(schedule[day]?.dinner_start || schedule[day].dinner_start) }} - {{ formatTime(schedule[day]?.dinner_end || schedule[day].dinner_end) }}
              </v-btn>
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>
    </v-card>
  </v-expand-transition>

  <v-dialog v-model="dialog" max-width="500" persistent>
    <v-card
      prepend-icon="mdi-clock-edit-outline"
      :title="selectedDay"
    >
      <v-container>     
        <v-row dense>
          <v-col cols="12" class="mb-2">
            <v-label class="text-caption mb-1">Start</v-label>
            <v-text-field
              v-model="editTimes.start"
              type="time"
              variant="outlined"
              density="compact"
              hide-details="auto"
            />
          </v-col>
          <v-col cols="12">
            <v-label class="text-caption mb-1">End</v-label>
            <v-text-field
              v-model="editTimes.end"
              type="time"
              variant="outlined"
              density="compact"
              hide-details="auto"
            />
          </v-col>
        </v-row>
      </v-container>

      <template v-slot:actions>
        <v-btn :disabled="btnDisabled" @click="cancelEdit">
          close
        </v-btn>
        <v-btn
          :disabled="btnDisabled"
          :loading="btnDisabled"
          variant="elevated"
          prepend-icon="mdi-check-circle"
          @click="saveSchedule"
        >
          save
        </v-btn>
      </template>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  existingSchedule: {
    type: Object,
    default: null,
  },
})

const emits = defineEmits(['update:schedule'])

const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

const dayEnabled = ref({
  Monday: true,
  Tuesday: true,
  Wednesday: true,
  Thursday: true,
  Friday: true,
  Saturday: true,
  Sunday: true,
})

// Initialize schedule with default values
const getDefaultSchedule = () => ({
  Monday: { breakfast_start: '07:00', breakfast_end: '10:00', lunch_start: '11:00', lunch_end: '14:00', dinner_start: '17:00', dinner_end: '20:00' },
  Tuesday: { breakfast_start: '07:00', breakfast_end: '10:00', lunch_start: '11:00', lunch_end: '14:00', dinner_start: '17:00', dinner_end: '20:00' },
  Wednesday: { breakfast_start: '07:00', breakfast_end: '10:00', lunch_start: '11:00', lunch_end: '14:00', dinner_start: '17:00', dinner_end: '20:00' },
  Thursday: { breakfast_start: '07:00', breakfast_end: '10:00', lunch_start: '11:00', lunch_end: '14:00', dinner_start: '17:00', dinner_end: '20:00' },
  Friday: { breakfast_start: '07:00', breakfast_end: '10:00', lunch_start: '11:00', lunch_end: '14:00', dinner_start: '17:00', dinner_end: '20:00' },
  Saturday: { breakfast_start: '07:00', breakfast_end: '10:00', lunch_start: '11:00', lunch_end: '14:00', dinner_start: '17:00', dinner_end: '20:00' },
  Sunday: { breakfast_start: '07:00', breakfast_end: '10:00', lunch_start: '11:00', lunch_end: '14:00', dinner_start: '17:00', dinner_end: '20:00' },
})

const schedule = reactive(getDefaultSchedule())

// Watch for existing schedule from props and update
watch(
  () => props.existingSchedule,
  (newSchedule) => {
    if (newSchedule && Object.keys(newSchedule).length > 0) {
      days.forEach(day => {
        dayEnabled.value[day] = false
      })

      days.forEach(day => {
        if (newSchedule[day]) {
          Object.assign(schedule[day], newSchedule[day])
          dayEnabled.value[day] = true
        }
      })
    }
  },
  { immediate: true, deep: true }
)

const handleDayToggle = () => {
  const enabledSchedule = {}
  days.forEach(day => {
    if (dayEnabled.value[day]) {
      enabledSchedule[day] = schedule[day]
    }
  })
  emits('update:schedule', JSON.parse(JSON.stringify(enabledSchedule)))
}

// Dialog state
const dialog = ref(false)
const selectedDay = ref('')
const selectedDayKey = ref('') // Store the actual day key for updating schedule
const selectedMealType = ref('')
const editTimes = reactive({
  start: '',
  end: ''
})
const btnDisabled = ref(false)

// Format time to 12-hour format with AM/PM
const formatTime = (time) => {
  if (!time) return ''
  const [hours, minutes] = time.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const displayHour = hour % 12 || 12
  return `${displayHour}:${minutes}${ampm}`
}

// Open dialog for editing a specific meal
const openDialog = (day, mealType) => {
  selectedDay.value = `${day} - ${mealType.charAt(0).toUpperCase() + mealType.slice(1)}`
  selectedDayKey.value = day // Store the actual day key
  selectedMealType.value = mealType
  
  // Copy current times for the selected meal
  editTimes.start = schedule[day][`${mealType}_start`]
  editTimes.end = schedule[day][`${mealType}_end`]
  
  dialog.value = true
}

// Save the edited meal times
const saveSchedule = () => {
  const day = selectedDayKey.value
  const mealType = selectedMealType.value
  

  schedule[day] = {
    ...schedule[day],
    [`${mealType}_start`]: editTimes.start,
    [`${mealType}_end`]: editTimes.end
  }
  
  dialog.value = false
  
  const enabledSchedule = {}
  days.forEach(day => {
    if (dayEnabled.value[day]) {
      enabledSchedule[day] = schedule[day]
    }
  })
  emits('update:schedule', JSON.parse(JSON.stringify(enabledSchedule)))
}

// Cancel editing
const cancelEdit = () => {
  dialog.value = false
}
 
</script>
