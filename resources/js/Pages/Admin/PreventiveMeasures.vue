<template>
    <v-app class="bg-grey-lighten-4">
        <!-- App Bar -->
        <v-app-bar color="primary" elevation="2">
            <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
            <v-app-bar-title>
                <v-icon class="mr-2">mdi-shield-check</v-icon>
                PinPointMe Admin
            </v-app-bar-title>
            <v-spacer />
            <!-- Profile Avatar Menu -->
            <v-menu offset-y>
                <template v-slot:activator="{ props }">
                    <v-btn icon v-bind="props">
                        <v-avatar color="white" size="36">
                            <span class="text-primary font-weight-bold">{{ adminInitials }}</span>
                        </v-avatar>
                    </v-btn>
                </template>
                <v-list>
                    <v-list-item @click="goToProfile" prepend-icon="mdi-account">
                        <v-list-item-title>Profile</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="toggleDarkMode" prepend-icon="mdi-theme-light-dark">
                        <v-list-item-title>{{ isDark ? 'Light Mode' : 'Dark Mode' }}</v-list-item-title>
                    </v-list-item>
                    <v-list-item @click="logout" prepend-icon="mdi-logout">
                        <v-list-item-title>Logout</v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-menu>
        </v-app-bar>

        <!-- Navigation Drawer -->
        <v-navigation-drawer
            v-model="drawer"
            :permanent="!isMobile"
            :temporary="isMobile"
            app
        >
            <v-list>
                <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" href="/admin/dashboard" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-account-group" title="Users" href="/admin/users" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-lifebuoy" title="Rescuers" href="/admin/rescuers" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-office-building" title="Buildings" href="/admin/buildings" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-file-chart" title="Reports" href="/admin/reports" @click="closeDrawerOnMobile"></v-list-item>
                <v-list-item prepend-icon="mdi-shield-alert" title="Preventive Measures" href="/admin/preventive-measures" active @click="closeDrawerOnMobile"></v-list-item>
            </v-list>
        </v-navigation-drawer>

        <!-- Main Content -->
        <v-main>
            <v-container fluid :class="isMobile ? 'pa-3' : 'pa-6'">
                <!-- Page Header -->
                <div class="page-header mb-4 mb-md-6">
                    <div class="page-header-content">
                        <h1 :class="isMobile ? 'text-h5' : 'text-h4'" class="font-weight-bold">Preventive Measures</h1>
                        <p class="text-grey mt-1 text-body-2">Manage educational content and safety videos</p>
                    </div>
                    <v-btn 
                        color="primary" 
                        @click="openAddDialog"
                        :size="isMobile ? 'small' : 'default'"
                    >
                        <v-icon start>mdi-plus</v-icon>
                        <span v-if="!isMobile">Add Video</span>
                        <span v-else>Add</span>
                    </v-btn>
                </div>

                <!-- Category Tabs -->
                <v-card rounded="lg" class="mb-6">
                    <v-tabs v-model="selectedCategory" color="primary" show-arrows>
                        <v-tab value="all">All</v-tab>
                        <v-tab v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</v-tab>
                    </v-tabs>
                </v-card>

                <!-- Stats -->
                <v-row class="mb-6">
                    <v-col cols="12" sm="4">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="primary" size="48">
                                    <v-icon color="white">mdi-video</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Total Videos</p>
                                    <h4 class="text-h5 font-weight-bold">{{ measuresList.length }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="4">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="info" size="48">
                                    <v-icon color="white">mdi-folder</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Categories</p>
                                    <h4 class="text-h5 font-weight-bold">{{ categories.length }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="4">
                        <v-card class="pa-4" rounded="lg">
                            <div class="d-flex align-center">
                                <v-avatar color="success" size="48">
                                    <v-icon color="white">mdi-check-circle</v-icon>
                                </v-avatar>
                                <div class="ml-3">
                                    <p class="text-grey text-caption mb-0">Published</p>
                                    <h4 class="text-h5 font-weight-bold">{{ measuresList.filter(m => m.is_published).length }}</h4>
                                </div>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Videos Grid -->
                <v-row>
                    <v-col v-for="measure in filteredMeasures" :key="measure.id" cols="12" sm="6" md="4" lg="3">
                        <v-card rounded="lg" class="h-100 d-flex flex-column">
                            <!-- Thumbnail -->
                            <div class="position-relative">
                                <v-img
                                    :src="getThumbnail(measure)"
                                    height="160"
                                    cover
                                    class="bg-grey-lighten-3"
                                >
                                    <template v-slot:placeholder>
                                        <div class="d-flex align-center justify-center fill-height">
                                            <v-icon size="48" color="grey">mdi-video</v-icon>
                                        </div>
                                    </template>
                                    <div class="position-absolute" style="top: 8px; right: 8px;">
                                        <v-chip :color="measure.is_published ? 'success' : 'grey'" size="x-small">
                                            {{ measure.is_published ? 'Published' : 'Draft' }}
                                        </v-chip>
                                    </div>
                                </v-img>
                                <v-btn
                                    icon
                                    size="small"
                                    color="primary"
                                    class="position-absolute"
                                    style="bottom: -16px; right: 16px;"
                                    @click="previewVideo(measure)"
                                >
                                    <v-icon>mdi-play</v-icon>
                                </v-btn>
                            </div>

                            <v-card-text class="flex-grow-1 pt-6">
                                <v-chip :color="getCategoryColor(measure.category)" size="x-small" class="mb-2">
                                    {{ measure.category }}
                                </v-chip>
                                <h3 class="text-subtitle-1 font-weight-medium mb-1 line-clamp-2">{{ measure.title }}</h3>
                                <p class="text-caption text-grey line-clamp-2">{{ measure.description }}</p>
                            </v-card-text>

                            <v-divider />

                            <v-card-actions>
                                <v-btn variant="text" size="small" @click="openEditDialog(measure)">
                                    <v-icon start size="small">mdi-pencil</v-icon>
                                    Edit
                                </v-btn>
                                <v-spacer />
                                <v-btn variant="text" size="small" :color="measure.is_published ? 'grey' : 'success'" @click="togglePublish(measure)">
                                    <v-icon start size="small">{{ measure.is_published ? 'mdi-eye-off' : 'mdi-eye' }}</v-icon>
                                    {{ measure.is_published ? 'Unpublish' : 'Publish' }}
                                </v-btn>
                                <v-btn icon size="small" color="error" @click="confirmDelete(measure)">
                                    <v-icon size="small">mdi-delete</v-icon>
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-col>
                </v-row>

                <v-alert v-if="filteredMeasures.length === 0" type="info" variant="tonal" class="mt-4">
                    No preventive measures found. Click "Add Video" to create one.
                </v-alert>
            </v-container>
        </v-main>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" max-width="600">
            <v-card>
                <v-card-title>{{ isEditing ? 'Edit Video' : 'Add New Video' }}</v-card-title>
                <v-card-text>
                    <v-form ref="form">
                        <v-text-field
                            v-model="formData.title"
                            label="Title"
                            variant="outlined"
                            density="compact"
                            :rules="[v => !!v || 'Title is required']"
                            class="mb-3"
                        />
                        <v-textarea
                            v-model="formData.description"
                            label="Description"
                            variant="outlined"
                            density="compact"
                            rows="3"
                            class="mb-3"
                        />
                        <v-select
                            v-model="formData.category"
                            :items="categoryOptions"
                            label="Category"
                            variant="outlined"
                            density="compact"
                            :rules="[v => !!v || 'Category is required']"
                            class="mb-3"
                        />
                        <!-- Video Source Type Selection -->
                        <v-radio-group v-model="videoSourceType" inline class="mb-3">
                            <v-radio label="Upload Video File" value="upload"></v-radio>
                            <v-radio label="YouTube Link" value="youtube"></v-radio>
                        </v-radio-group>

                        <!-- Video File Upload -->
                        <v-file-input
                            v-if="videoSourceType === 'upload'"
                            v-model="videoFile"
                            label="Video File"
                            variant="outlined"
                            density="compact"
                            accept="video/mp4,video/webm,video/mov,video/avi"
                            prepend-icon="mdi-video"
                            :rules="[v => (!!v || !!formData.video_url || isEditing) || 'Video file is required']"
                            :hint="videoFile ? `Selected: ${videoFile.name}` : 'Max 100MB (MP4, WebM, MOV, AVI)'"
                            persistent-hint
                            show-size
                            class="mb-3"
                            @update:model-value="onVideoFileChange"
                        />

                        <!-- Video URL for YouTube -->
                        <v-text-field
                            v-if="videoSourceType === 'youtube'"
                            v-model="formData.video_url"
                            label="YouTube Video URL"
                            variant="outlined"
                            density="compact"
                            placeholder="https://youtube.com/watch?v=..."
                            prepend-inner-icon="mdi-youtube"
                            :rules="[v => (!!v || videoSourceType !== 'youtube') || 'YouTube URL is required']"
                            class="mb-3"
                        />

                        <!-- Video Preview for Upload -->
                        <v-alert 
                            v-if="videoSourceType === 'upload' && (videoPreviewUrl || formData.video_path)"
                            type="info" 
                            variant="tonal" 
                            class="mb-3"
                        >
                            <div class="d-flex align-center">
                                <v-icon class="mr-2">mdi-check-circle</v-icon>
                                <span v-if="videoPreviewUrl">New video selected</span>
                                <span v-else>Current video: {{ formData.video_path }}</span>
                            </div>
                        </v-alert>

                        <!-- Thumbnail Upload -->
                        <v-file-input
                            v-model="thumbnailFile"
                            label="Thumbnail Image (optional)"
                            variant="outlined"
                            density="compact"
                            accept="image/*"
                            prepend-icon="mdi-image"
                            hint="Leave empty for auto-generated thumbnail. Max 5MB"
                            persistent-hint
                            show-size
                            class="mb-3"
                            @update:model-value="onThumbnailFileChange"
                        />

                        <!-- Thumbnail Preview -->
                        <v-img
                            v-if="thumbnailPreviewUrl"
                            :src="thumbnailPreviewUrl"
                            max-height="120"
                            class="mb-3 rounded"
                            cover
                        />
                        <v-text-field
                            v-model="formData.duration"
                            label="Duration"
                            variant="outlined"
                            density="compact"
                            placeholder="e.g., 5:30"
                            class="mb-3"
                        />
                        <v-switch
                            v-model="formData.is_published"
                            label="Published"
                            color="success"
                            hide-details
                        />
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="saving" @click="saveMeasure">
                        {{ isEditing ? 'Update' : 'Create' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Confirmation -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title class="text-error">
                    <v-icon start color="error">mdi-alert</v-icon>
                    Delete Video
                </v-card-title>
                <v-card-text>
                    Are you sure you want to delete "<strong>{{ selectedMeasure?.title }}</strong>"? This action cannot be undone.
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" :loading="deleting" @click="deleteMeasure">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Video Preview Dialog -->
        <v-dialog v-model="previewDialog" max-width="800">
            <v-card v-if="previewMeasure">
                <v-card-title class="d-flex align-center">
                    {{ previewMeasure.title }}
                    <v-spacer />
                    <v-btn icon @click="previewDialog = false">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text class="pa-0">
                    <video
                        v-if="isLocalVideo(previewMeasure.video_url)"
                        :src="previewMeasure.video_url"
                        controls
                        style="width: 100%; max-height: 450px;"
                    />
                    <iframe
                        v-else
                        :src="getEmbedUrl(previewMeasure.video_url)"
                        width="100%"
                        height="450"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    />
                </v-card-text>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000">
            {{ snackbarText }}
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDisplay } from 'vuetify';
import { setUserActiveStatus } from '@/Utilities/firebase';

const { mobile } = useDisplay();
const isMobile = computed(() => mobile.value);

const isDark = ref(false);
const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('v-theme--dark', isDark.value);
};
const goToProfile = () => {
    window.location.href = '/admin/profile';
};
const closeDrawerOnMobile = () => {
    if (isMobile.value) {
        drawer.value = false;
    }
};

// Admin initials for profile
const adminInitials = computed(() => {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    if (userData.first_name && userData.last_name) {
        return `${userData.first_name[0]}${userData.last_name[0]}`.toUpperCase();
    }
    return 'AD';
});

const props = defineProps({
    measures: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] }
});

const drawer = ref(!mobile.value);
const dialog = ref(false);
const deleteDialog = ref(false);
const previewDialog = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const deleting = ref(false);
const selectedCategory = ref('all');
const selectedMeasure = ref(null);
const previewMeasure = ref(null);
const snackbar = ref(false);
const snackbarText = ref('');
const snackbarColor = ref('success');

// File upload refs
const videoSourceType = ref('upload');
const videoFile = ref(null);
const thumbnailFile = ref(null);
const videoPreviewUrl = ref('');
const thumbnailPreviewUrl = ref('');

const measuresList = ref(props.measures || []);
const categories = ref(props.categories || ['Fire Safety', 'Earthquake', 'First Aid', 'Evacuation', 'General Safety']);

const categoryOptions = [
    'Fire Safety',
    'Earthquake',
    'First Aid',
    'Evacuation',
    'General Safety',
    'Flood',
    'Medical Emergency'
];

const formData = ref({
    title: '',
    description: '',
    category: '',
    video_url: '',
    video_path: '',
    thumbnail_url: '',
    duration: '',
    is_published: true
});

// File change handlers
const onVideoFileChange = (file) => {
    if (file) {
        videoPreviewUrl.value = URL.createObjectURL(file);
    } else {
        videoPreviewUrl.value = '';
    }
};

const onThumbnailFileChange = (file) => {
    if (file) {
        thumbnailPreviewUrl.value = URL.createObjectURL(file);
    } else {
        thumbnailPreviewUrl.value = '';
    }
};

const filteredMeasures = computed(() => {
    if (selectedCategory.value === 'all') {
        return measuresList.value;
    }
    return measuresList.value.filter(m => m.category === selectedCategory.value);
});

const fetchMeasures = async () => {
    try {
        const response = await fetch('/api/preventive-measures', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success || Array.isArray(data)) {
            measuresList.value = data.data || data;
        }
    } catch (error) {
        console.error('Error fetching measures:', error);
    }
};

const openAddDialog = () => {
    isEditing.value = false;
    formData.value = {
        title: '',
        description: '',
        category: '',
        video_url: '',
        video_path: '',
        thumbnail_url: '',
        duration: '',
        is_published: true
    };
    videoSourceType.value = 'upload';
    videoFile.value = null;
    thumbnailFile.value = null;
    videoPreviewUrl.value = '';
    thumbnailPreviewUrl.value = '';
    dialog.value = true;
};

const openEditDialog = (measure) => {
    isEditing.value = true;
    selectedMeasure.value = measure;
    formData.value = { ...measure };
    // Determine video source type based on existing data
    if (measure.video_url && measure.video_url.includes('youtube')) {
        videoSourceType.value = 'youtube';
    } else {
        videoSourceType.value = 'upload';
    }
    videoFile.value = null;
    thumbnailFile.value = null;
    videoPreviewUrl.value = '';
    thumbnailPreviewUrl.value = '';
    dialog.value = true;
};

const saveMeasure = async () => {
    saving.value = true;
    try {
        const url = isEditing.value ? `/preventive-measures/${selectedMeasure.value.id}` : '/preventive-measures';
        
        // Use FormData for file uploads
        const submitData = new FormData();
        submitData.append('title', formData.value.title);
        submitData.append('description', formData.value.description || '');
        submitData.append('category', formData.value.category);
        submitData.append('duration', formData.value.duration || '');
        submitData.append('is_active', formData.value.is_published ? '1' : '0');
        
        // Handle video source
        if (videoSourceType.value === 'upload' && videoFile.value) {
            submitData.append('video', videoFile.value);
        } else if (videoSourceType.value === 'youtube' && formData.value.video_url) {
            submitData.append('video_url', formData.value.video_url);
        }
        
        // Handle thumbnail
        if (thumbnailFile.value) {
            submitData.append('thumbnail', thumbnailFile.value);
        }
        
        // For PUT requests, we need to use POST with _method override
        if (isEditing.value) {
            submitData.append('_method', 'PUT');
        }
        
        const response = await fetch(url, {
            method: 'POST', // Always POST for FormData with file uploads
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: submitData
        });
        
        const data = await response.json();
        if (data.success || data.id) {
            showSnackbar(isEditing.value ? 'Video updated successfully' : 'Video created successfully', 'success');
            dialog.value = false;
            // Reset file inputs
            videoFile.value = null;
            thumbnailFile.value = null;
            videoPreviewUrl.value = '';
            thumbnailPreviewUrl.value = '';
            fetchMeasures();
        } else {
            showSnackbar(data.errors ? Object.values(data.errors).flat().join(', ') : 'Error saving video', 'error');
        }
    } catch (error) {
        console.error('Error saving measure:', error);
        showSnackbar('Error saving video', 'error');
    } finally {
        saving.value = false;
    }
};

const togglePublish = async (measure) => {
    try {
        const response = await fetch(`/preventive-measures/${measure.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ is_published: !measure.is_published })
        });
        
        if (response.ok) {
            showSnackbar(measure.is_published ? 'Video unpublished' : 'Video published', 'success');
            fetchMeasures();
        }
    } catch (error) {
        console.error('Error toggling publish:', error);
        showSnackbar('Error updating video', 'error');
    }
};

const confirmDelete = (measure) => {
    selectedMeasure.value = measure;
    deleteDialog.value = true;
};

const deleteMeasure = async () => {
    deleting.value = true;
    try {
        const response = await fetch(`/preventive-measures/${selectedMeasure.value.id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        if (response.ok) {
            showSnackbar('Video deleted successfully', 'success');
            deleteDialog.value = false;
            fetchMeasures();
        }
    } catch (error) {
        console.error('Error deleting measure:', error);
        showSnackbar('Error deleting video', 'error');
    } finally {
        deleting.value = false;
    }
};

const previewVideo = (measure) => {
    previewMeasure.value = measure;
    previewDialog.value = true;
};

const getThumbnail = (measure) => {
    if (measure.thumbnail_url) return measure.thumbnail_url;
    if (isLocalVideo(measure.video_url)) {
        return 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180" viewBox="0 0 320 180"><rect fill="#1976d2" width="320" height="180"/><polygon fill="white" points="130,60 130,120 200,90"/></svg>');
    }
    const videoId = getYouTubeId(measure.video_url);
    return videoId ? `https://img.youtube.com/vi/${videoId}/hqdefault.jpg` : '';
};

const isLocalVideo = (url) => {
    return url && (url.startsWith('/storage') || url.startsWith('/videos') || url.includes('.mp4') || url.includes('.webm'));
};

const getYouTubeId = (url) => {
    if (!url) return null;
    const match = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
    return match ? match[1] : null;
};

const getEmbedUrl = (url) => {
    const videoId = getYouTubeId(url);
    return videoId ? `https://www.youtube.com/embed/${videoId}` : url;
};

const getCategoryColor = (category) => {
    const colors = {
        'Fire Safety': 'red',
        'Earthquake': 'brown',
        'First Aid': 'green',
        'Evacuation': 'orange',
        'General Safety': 'blue',
        'Flood': 'cyan',
        'Medical Emergency': 'pink'
    };
    return colors[category] || 'grey';
};

const showSnackbar = (text, color) => {
    snackbarText.value = text;
    snackbarColor.value = color;
    snackbar.value = true;
};

const logout = async () => {
    // Set user as inactive in Firebase (keep FCM token for offline notifications)
    try {
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        if (userData.id) {
            await setUserActiveStatus(userData.id, false);
            console.log('[Logout] User marked as inactive in Firebase');
        }
    } catch (e) {
        console.error('[Logout] Error setting user inactive:', e);
    }

    localStorage.removeItem('userData');
    localStorage.removeItem('authToken');
    localStorage.removeItem('token');
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        await fetch('/logout', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            credentials: 'include'
        });
    } catch (e) { console.error('Logout error:', e); }
    
    window.location.href = '/login';
};

onMounted(() => {
    // Data comes from Inertia props
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Page Header Responsive Styles */
.page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.page-header-content {
    flex: 1;
    min-width: 200px;
}

/* Mobile Specific Styles */
@media (max-width: 600px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .page-header-content {
        width: 100%;
    }
}
</style>
