<template>
    <v-app class="app-container">
        <!-- Header - matches Dashboard style -->
        <div class="page-header">
            <div class="header-content">
                <v-btn icon variant="text" @click="drawer = !drawer" class="menu-btn desktop-only">
                    <v-icon>mdi-menu</v-icon>
                </v-btn>
                <div class="header-title">
                    <h1>Preventive Measures</h1>
                    <p>Stay Safe</p>
                </div>
                <v-btn icon variant="text" @click="fetchMeasures" class="refresh-btn">
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </div>
        </div>

        <!-- Navigation Drawer -->
        <UserMenu v-model="drawer" />

        <v-main class="main-content">
            <v-container fluid class="pa-4">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-h5 font-weight-bold text-primary mb-2">
                        How to Stay Safe During Emergencies
                    </h1>
                    <p class="text-body-2 text-grey">
                        Learn essential safety tips and preventive measures
                    </p>
                </div>

                <!-- Category Filter -->
                <v-chip-group
                    v-model="selectedCategory"
                    selected-class="bg-primary text-white"
                    class="mb-4"
                >
                    <v-chip
                        value=""
                        variant="outlined"
                        filter
                    >
                        All
                    </v-chip>
                    <v-chip
                        v-for="cat in categories"
                        :key="cat.value"
                        :value="cat.value"
                        variant="outlined"
                        filter
                    >
                        <v-icon start size="small">{{ cat.icon }}</v-icon>
                        {{ cat.label }}
                    </v-chip>
                </v-chip-group>

                <!-- Loading State -->
                <div v-if="loading" class="d-flex justify-center align-center py-10">
                    <v-progress-circular indeterminate color="primary" size="50" />
                    <span class="ml-3 text-grey">Loading preventive measures...</span>
                </div>

                <!-- Error State -->
                <v-alert
                    v-else-if="error"
                    type="error"
                    variant="tonal"
                    class="mb-4"
                >
                    {{ error }}
                    <template v-slot:append>
                        <v-btn variant="text" @click="fetchMeasures">Retry</v-btn>
                    </template>
                </v-alert>

                <!-- Measures Grid -->
                <v-row v-else-if="filteredMeasures.length > 0">
                    <v-col
                        v-for="measure in filteredMeasures"
                        :key="measure.id"
                        cols="12"
                        sm="6"
                        md="4"
                    >
                        <v-card
                            class="h-100 cursor-pointer"
                            elevation="3"
                            rounded="lg"
                            @click="openVideo(measure)"
                        >
                            <!-- Video Thumbnail -->
                            <div class="position-relative">
                                <v-img
                                    :src="getThumbnailUrl(measure)"
                                    height="180"
                                    cover
                                    class="bg-grey-lighten-3"
                                >
                                    <template v-slot:placeholder>
                                        <div class="d-flex align-center justify-center fill-height">
                                            <v-progress-circular indeterminate color="primary" />
                                        </div>
                                    </template>
                                    <template v-slot:error>
                                        <div class="d-flex align-center justify-center fill-height bg-grey-lighten-2">
                                            <v-icon size="64" color="grey">mdi-video-off</v-icon>
                                        </div>
                                    </template>
                                </v-img>
                                
                                <!-- Play Button Overlay -->
                                <div class="position-absolute d-flex align-center justify-center" style="top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.2);">
                                    <v-btn
                                        icon
                                        size="large"
                                        color="white"
                                        variant="flat"
                                        class="play-btn"
                                    >
                                        <v-icon size="32">mdi-play</v-icon>
                                    </v-btn>
                                </div>

                                <!-- Category Badge -->
                                <v-chip
                                    v-if="measure.category"
                                    :color="getCategoryColor(measure.category)"
                                    size="small"
                                    class="position-absolute ma-2"
                                    style="top: 0; left: 0;"
                                >
                                    {{ formatCategory(measure.category) }}
                                </v-chip>
                            </div>

                            <v-card-title class="text-subtitle-1 font-weight-bold pb-1">
                                {{ measure.title }}
                            </v-card-title>

                            <v-card-text class="pt-0">
                                <p class="text-body-2 text-grey-darken-1 mb-2 description-text">
                                    {{ measure.description }}
                                </p>
                                <div class="d-flex align-center text-caption text-grey">
                                    <v-icon size="14" class="mr-1">mdi-account</v-icon>
                                    {{ measure.author || 'Unknown' }}
                                    <span class="mx-2">•</span>
                                    <v-icon size="14" class="mr-1">mdi-calendar</v-icon>
                                    {{ formatDate(measure.created_at) }}
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Empty State -->
                <v-alert
                    v-else
                    type="info"
                    variant="tonal"
                    class="mb-4"
                >
                    <v-alert-title>No preventive measures found</v-alert-title>
                    <p class="mt-2">
                        {{ selectedCategory ? 'No measures available for this category.' : 'No preventive measures have been added yet.' }}
                    </p>
                </v-alert>
            </v-container>

            <!-- Video Modal -->
            <v-dialog
                v-model="showVideoModal"
                max-width="800"
                :fullscreen="$vuetify.display.smAndDown"
            >
                <v-card>
                    <v-toolbar color="primary" density="compact">
                        <v-btn icon @click="closeVideo">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                        <v-toolbar-title class="text-body-1">
                            {{ selectedMeasure?.title }}
                        </v-toolbar-title>
                    </v-toolbar>

                    <v-card-text class="pa-0">
                        <!-- Video Loading -->
                        <div v-if="videoLoading" class="d-flex align-center justify-center py-10">
                            <v-progress-circular indeterminate color="primary" />
                        </div>

                        <!-- Video Player -->
                        <div v-if="selectedMeasure" class="video-container">
                            <!-- Local Video -->
                            <video
                                v-if="isLocalVideo(selectedMeasure)"
                                :src="getVideoUrl(selectedMeasure)"
                                controls
                                autoplay
                                class="video-player"
                                @loadeddata="videoLoading = false"
                                @error="handleVideoError"
                            ></video>

                            <!-- YouTube Embed -->
                            <iframe
                                v-else-if="isYouTubeVideo(selectedMeasure)"
                                :src="getYouTubeEmbedUrl(selectedMeasure)"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                class="video-iframe"
                                @load="videoLoading = false"
                                @error="handleVideoError"
                            ></iframe>

                            <!-- Direct Video -->
                            <video
                                v-else
                                :src="getVideoUrl(selectedMeasure)"
                                :poster="getThumbnailUrl(selectedMeasure)"
                                controls
                                autoplay
                                class="video-player"
                                @loadeddata="videoLoading = false"
                                @error="handleVideoError"
                            ></video>
                        </div>
                    </v-card-text>

                    <!-- Video Details -->
                    <v-card-text v-if="selectedMeasure" class="pt-4">
                        <p class="text-body-2 text-grey-darken-1 mb-3">
                            {{ selectedMeasure.description }}
                        </p>
                        <div class="d-flex align-center text-caption text-grey">
                            <v-icon size="14" class="mr-1">mdi-account</v-icon>
                            By: {{ selectedMeasure.author || 'Unknown' }}
                            <span class="mx-2">•</span>
                            <v-icon size="14" class="mr-1">mdi-calendar</v-icon>
                            {{ formatDate(selectedMeasure.created_at) }}
                        </div>
                    </v-card-text>
                </v-card>
            </v-dialog>

            <!-- Toast -->
            <v-snackbar v-model="showToast" :color="toastColor" location="top">
                {{ toastMessage }}
                <template v-slot:actions>
                    <v-btn variant="text" @click="showToast = false">Close</v-btn>
                </template>
            </v-snackbar>
        </v-main>
        
        <!-- Bottom Navigation for Mobile -->
        <UserBottomNav :notification-count="0" :message-count="unreadCount" />
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useDisplay } from 'vuetify';
import { useUnreadMessages } from '@/Composables/useUnreadMessages';
import UserMenu from '@/Components/Pages/User/Menu/UserMenu.vue';
import UserBottomNav from '@/Components/Pages/User/Menu/UserBottomNav.vue';

// Props from Inertia
const props = defineProps({
    measures: {
        type: Array,
        default: () => [],
    },
});

const display = useDisplay();

// Unread messages count for bottom nav
const { unreadCount } = useUnreadMessages();

// Navigation
const drawer = ref(false);

// State
const measures = ref(props.measures || []);
const loading = ref(false);
const error = ref(null);
const selectedCategory = ref('');

// Video Modal
const showVideoModal = ref(false);
const selectedMeasure = ref(null);
const videoLoading = ref(false);
const videoError = ref(false);

// Toast
const showToast = ref(false);
const toastMessage = ref('');
const toastColor = ref('success');

// Category icons and colors mapping
const categoryConfig = {
    fire: { label: 'Fire Safety', icon: 'mdi-fire', color: 'deep-orange' },
    earthquake: { label: 'Earthquake', icon: 'mdi-earth', color: 'brown' },
    flood: { label: 'Flood', icon: 'mdi-waves', color: 'blue' },
    medical: { label: 'Medical', icon: 'mdi-medical-bag', color: 'red' },
    general: { label: 'General', icon: 'mdi-shield-check', color: 'teal' },
    safety: { label: 'Safety', icon: 'mdi-shield-account', color: 'green' },
    evacuation: { label: 'Evacuation', icon: 'mdi-exit-run', color: 'orange' },
    first_aid: { label: 'First Aid', icon: 'mdi-heart-pulse', color: 'pink' },
};

// Dynamic categories from measures data
const categories = computed(() => {
    const uniqueCategories = [...new Set(measures.value.map(m => m.category).filter(Boolean))];
    return uniqueCategories.map(cat => ({
        value: cat,
        label: categoryConfig[cat]?.label || formatCategory(cat),
        icon: categoryConfig[cat]?.icon || 'mdi-tag',
    }));
});

// Computed
const filteredMeasures = computed(() => {
    if (!selectedCategory.value) {
        return measures.value;
    }
    return measures.value.filter(m => m.category === selectedCategory.value);
});

// Methods
const fetchMeasures = async () => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await fetch('/api/preventive-measures');
        const data = await response.json();
        
        if (data.success) {
            measures.value = data.data;
        } else {
            throw new Error(data.message || 'Failed to fetch measures');
        }
    } catch (err) {
        console.error('Error fetching preventive measures:', err);
        error.value = err.message || 'Failed to load preventive measures';
    } finally {
        loading.value = false;
    }
};

const getThumbnailUrl = (measure) => {
    if (measure.thumbnail_url) {
        // Check if it's a YouTube thumbnail
        if (measure.thumbnail_url.includes('youtube') || measure.thumbnail_url.includes('ytimg')) {
            return measure.thumbnail_url;
        }
        return measure.thumbnail_url;
    }
    
    if (measure.thumbnail) {
        if (measure.thumbnail.startsWith('http')) {
            return measure.thumbnail;
        }
        return `/storage/${measure.thumbnail}`;
    }
    
    // For local videos, use inline SVG placeholder
    if (measure.video_path && !measure.video_path.includes('youtube')) {
        return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 360"%3E%3Crect fill="%23e0e0e0" width="480" height="360"/%3E%3Ccircle cx="240" cy="180" r="40" fill="%23999"/%3E%3Cpolygon points="235,160 235,200 260,180" fill="%23e0e0e0"/%3E%3C/svg%3E';
    }
    
    // Try to extract YouTube thumbnail from video URL
    // Use hqdefault.jpg (480x360) which is always available, unlike maxresdefault.jpg
    if (measure.video_url && measure.video_url.includes('youtube')) {
        const videoId = extractYouTubeId(measure.video_url);
        if (videoId) {
            return `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
        }
    }
    
    return '/images/video-placeholder.png';
};

const getVideoUrl = (measure) => {
    if (measure.video_url) {
        return measure.video_url;
    }
    if (measure.video_path) {
        if (measure.video_path.startsWith('http')) {
            return measure.video_path;
        }
        // Properly encode the path for special characters
        const encodedPath = measure.video_path
            .split('/')
            .map(segment => encodeURIComponent(segment))
            .join('/');
        return `/storage/${encodedPath}`;
    }
    return '';
};

const isYouTubeVideo = (measure) => {
    const url = measure.video_url || measure.video_path || '';
    return url.includes('youtube.com') || url.includes('youtu.be') || url.includes('youtube.com/embed');
};

const isLocalVideo = (measure) => {
    const path = measure.video_path || '';
    return path && !path.includes('youtube');
};

const extractYouTubeId = (url) => {
    if (!url) return null;
    
    // Handle youtube.com/embed/ID format
    let match = url.match(/embed\/([a-zA-Z0-9_-]+)/);
    if (match) return match[1];
    
    // Handle youtube.com/watch?v=ID format
    match = url.match(/[?&]v=([a-zA-Z0-9_-]+)/);
    if (match) return match[1];
    
    // Handle youtu.be/ID format
    match = url.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
    if (match) return match[1];
    
    return null;
};

const getYouTubeEmbedUrl = (measure) => {
    const url = measure.video_url || measure.video_path || '';
    
    // If already an embed URL
    if (url.includes('/embed/')) {
        return url + '?autoplay=1';
    }
    
    const videoId = extractYouTubeId(url);
    if (videoId) {
        return `https://www.youtube.com/embed/${videoId}?autoplay=1`;
    }
    
    return url;
};

const openVideo = (measure) => {
    selectedMeasure.value = measure;
    videoLoading.value = true;
    videoError.value = false;
    showVideoModal.value = true;
};

const closeVideo = () => {
    showVideoModal.value = false;
    selectedMeasure.value = null;
    videoLoading.value = false;
    videoError.value = false;
};

const handleVideoError = () => {
    videoLoading.value = false;
    videoError.value = true;
    console.error('Video Error:', {
        measure: selectedMeasure.value,
        url: getVideoUrl(selectedMeasure.value),
    });
};

const getCategoryColor = (category) => {
    return categoryConfig[category]?.color || 'grey';
};

const formatCategory = (category) => {
    if (!category) return '';
    return category.charAt(0).toUpperCase() + category.slice(1);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return dateString;
    }
};

const showNotification = (message, color = 'success') => {
    toastMessage.value = message;
    toastColor.value = color;
    showToast.value = true;
};

// Lifecycle
onMounted(() => {
    if (!measures.value || measures.value.length === 0) {
        fetchMeasures();
    }
});
</script>

<style scoped>
.description-text {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

.play-btn {
    opacity: 0.9;
    transition: transform 0.2s, opacity 0.2s;
}

.v-card:hover .play-btn {
    transform: scale(1.1);
    opacity: 1;
}

.video-container {
    position: relative;
    width: 100%;
    padding-top: 56.25%; /* 16:9 Aspect Ratio */
    background: #000;
}

.video-iframe,
.video-player {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.video-player {
    background: #000;
}

.cursor-pointer {
    cursor: pointer;
}

/* App Container */
.app-container {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    overflow: hidden !important;
}

/* Header - matches Dashboard style */
.page-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #3674B5;
    padding: env(safe-area-inset-top, 0) 0 0 0;
    flex-shrink: 0;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    gap: 12px;
}

.menu-btn, .refresh-btn {
    color: white;
}

.header-title {
    flex: 1;
    text-align: center;
}

.header-title h1 {
    font-size: 1.25rem;
    font-weight: 700;
    font-style: italic;
    color: white;
    margin: 0;
}

.header-title p {
    font-size: 0.65rem;
    letter-spacing: 2px;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    text-transform: uppercase;
}

/* Main Content */
.main-content {
    background: linear-gradient(180deg, #e8f5f3 0%, #f5f9f8 50%, #ffffff 100%);
    height: 100%;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

/* Desktop-only elements */
.desktop-only {
    display: flex;
}

/* Responsive visibility */
@media (max-width: 1023px) {
    .desktop-only {
        display: none !important;
    }
    
    .main-content :deep(.v-container) {
        padding-bottom: 80px !important;
    }
}

@media (min-width: 1024px) {
    .desktop-only {
        display: flex;
    }
}
</style>
