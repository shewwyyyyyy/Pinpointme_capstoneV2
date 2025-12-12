<template>
    <v-app class="bg-user-gradient-light">
        <!-- App Bar -->
        <v-app-bar color="primary" density="comfortable">
            <v-btn icon @click="goBack">
                <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            
            <!-- Participant Avatar with Photo -->
            <v-avatar 
                size="40" 
                :color="!otherParticipantPicture ? avatarColor : undefined" 
                class="mr-3 cursor-pointer"
                @click="showPhotoViewer = true"
            >
                <v-img
                    v-if="otherParticipantPicture"
                    :src="otherParticipantPicture"
                    cover
                />
                <span v-else class="text-white text-body-2 font-weight-bold">{{ initials }}</span>
            </v-avatar>
            
            <div class="flex-grow-1">
                <v-app-bar-title class="text-body-1 font-weight-medium">{{ chatTitle }}</v-app-bar-title>
                <div v-if="rescueRequest" class="text-caption" style="opacity: 0.8;">
                    {{ rescueRequest.room?.room_name || rescueRequest.room?.name }} • {{ formatStatus(rescueRequest.status) }}
                </div>
            </div>
            
            <v-btn v-if="rescueRequest" icon @click="viewRescue">
                <v-icon>mdi-information-outline</v-icon>
            </v-btn>
        </v-app-bar>

        <!-- Main Content -->
        <v-main>
            <!-- Loading State -->
            <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 60vh;">
                <v-progress-circular indeterminate color="primary" size="64" />
            </div>

            <!-- No Rescuer Assigned State -->
            <div v-else-if="!hasRescuerAssigned && !conversation" class="d-flex flex-column justify-center align-center pa-6" style="min-height: 60vh;">
                <v-icon size="80" color="grey-lighten-1" class="mb-4">mdi-chat-processing-outline</v-icon>
                <h3 class="text-h6 text-grey-darken-1 mb-2">Chat Not Available</h3>
                <p class="text-body-2 text-grey text-center" style="max-width: 280px;">
                    Chat will be available once a rescuer is assigned to your rescue request.
                </p>
            </div>

            <!-- Chat Container -->
            <div v-else class="chat-container d-flex flex-column" style="height: calc(100vh - 64px);">
                <!-- Messages Area -->
                <div 
                    ref="messagesContainer"
                    class="messages-area flex-grow-1 pa-4 overflow-y-auto"
                >
                    <!-- Empty State -->
                    <div v-if="messages.length === 0" class="d-flex flex-column justify-center align-center py-8" style="min-height: 200px;">
                        <v-icon size="56" color="grey-lighten-1" class="mb-3">mdi-chat-outline</v-icon>
                        <p class="text-body-2 text-grey text-center">No messages yet.<br>Start the conversation!</p>
                    </div>

                    <!-- Date Groups -->
                    <div v-for="(group, date) in groupedMessages" :key="date">
                        <!-- Date Separator -->
                        <div class="date-separator text-center my-4">
                            <v-chip size="small" variant="tonal" color="grey">
                                {{ formatDateSeparator(date) }}
                            </v-chip>
                        </div>

                        <!-- Messages -->
                        <div
                            v-for="message in group"
                            :key="message.id"
                            :class="['message-wrapper mb-2', isOwnMessage(message) ? 'own-message' : 'other-message']"
                        >
                            <div :class="['message-bubble pa-3', isOwnMessage(message) ? 'bg-primary' : 'bg-white']">
                                <!-- Sender Name for other messages -->
                                <div v-if="!isOwnMessage(message)" class="text-caption text-primary font-weight-medium mb-1">
                                    {{ message.sender_name || getMessageSenderName(message) }}
                                </div>

                                <!-- Text Message -->
                                <div v-if="!message.attachment_url" class="message-content">
                                    {{ message.content }}
                                </div>

                                <!-- Audio Message -->
                                <div v-else-if="isAudioAttachment(message)" class="audio-message">
                                    <v-btn
                                        icon
                                        size="small"
                                        :color="isOwnMessage(message) ? 'white' : 'primary'"
                                        variant="text"
                                        @click="toggleAudio(message)"
                                    >
                                        <v-icon>{{ playingAudioId === message.id ? 'mdi-pause' : 'mdi-play' }}</v-icon>
                                    </v-btn>
                                    <div class="audio-waveform flex-grow-1 mx-2">
                                        <div class="waveform-bar" v-for="n in 20" :key="n" :style="{ height: `${Math.random() * 100}%` }"></div>
                                    </div>
                                    <span class="text-caption">{{ formatDuration(audioElements[message.id]?.duration) }}</span>
                                </div>

                                <!-- Image Message -->
                                <div v-else-if="isImageAttachment(message)" class="image-message">
                                    <v-img
                                        :src="getAttachmentUrl(message.attachment_url)"
                                        max-width="200"
                                        max-height="200"
                                        rounded="lg"
                                        @click="showImagePreview(message.attachment_url)"
                                        class="cursor-pointer"
                                    />
                                    <div v-if="message.content && message.content !== '[Attachment]'" class="mt-2">
                                        {{ message.content }}
                                    </div>
                                </div>

                                <!-- Other Attachments -->
                                <div v-else-if="message.attachment_url" class="attachment-message">
                                    <v-btn
                                        variant="tonal"
                                        :color="isOwnMessage(message) ? 'white' : 'primary'"
                                        :href="getAttachmentUrl(message.attachment_url)"
                                        target="_blank"
                                        size="small"
                                    >
                                        <v-icon start>mdi-file-download</v-icon>
                                        {{ message.attachment_name || 'Download' }}
                                    </v-btn>
                                </div>

                                <!-- Message Meta -->
                                <div :class="['message-meta text-caption mt-1', isOwnMessage(message) ? 'text-right' : '']">
                                    {{ formatTime(message.sent_at || message.created_at) }}
                                    <span v-if="isOwnMessage(message)" class="ml-1 message-status">
                                        <!-- Sending -->
                                        <v-icon v-if="message.status === 'sending'" size="14" color="rgba(255,255,255,0.5)">
                                            mdi-clock-outline
                                        </v-icon>
                                        <!-- Sent/Delivered (one check) -->
                                        <v-icon v-else-if="message.status === 'sent' || message.status === 'delivered'" size="14" color="rgba(255,255,255,0.7)">
                                            mdi-check
                                        </v-icon>
                                        <!-- Read (two checks, blue) -->
                                        <v-icon v-else-if="message.status === 'read'" size="14" color="#34B7F1">
                                            mdi-check-all
                                        </v-icon>
                                        <!-- Default: one check -->
                                        <v-icon v-else size="14" color="rgba(255,255,255,0.7)">
                                            mdi-check
                                        </v-icon>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div v-if="isTyping" class="message-wrapper other-message mb-2">
                        <div class="message-bubble bg-white pa-3">
                            <div class="typing-indicator">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="input-area pa-3 bg-white">
                    <!-- Recording UI -->
                    <div v-if="isRecording" class="recording-ui d-flex align-center pa-3">
                        <v-btn icon color="error" @click="cancelRecording">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                        <div class="flex-grow-1 mx-3">
                            <div class="d-flex align-center">
                                <div class="recording-dot"></div>
                                <span class="ml-2 text-error">Recording... {{ recordingDuration }}</span>
                            </div>
                        </div>
                        <v-btn icon color="primary" @click="stopRecording">
                            <v-icon>mdi-send</v-icon>
                        </v-btn>
                    </div>

                    <!-- Normal Input -->
                    <div v-else class="d-flex align-center ga-2">
                        <!-- Attachment Button -->
                        <v-menu offset-y>
                            <template v-slot:activator="{ props }">
                                <v-btn icon variant="text" color="grey" v-bind="props">
                                    <v-icon>mdi-plus</v-icon>
                                </v-btn>
                            </template>
                            <v-list density="compact">
                                <v-list-item @click="triggerCameraCapture">
                                    <template v-slot:prepend>
                                        <v-icon>mdi-camera</v-icon>
                                    </template>
                                    <v-list-item-title>Camera</v-list-item-title>
                                </v-list-item>
                                <v-list-item @click="triggerImageUpload">
                                    <template v-slot:prepend>
                                        <v-icon>mdi-image</v-icon>
                                    </template>
                                    <v-list-item-title>Gallery</v-list-item-title>
                                </v-list-item>
                                <v-list-item @click="triggerFileUpload">
                                    <template v-slot:prepend>
                                        <v-icon>mdi-file</v-icon>
                                    </template>
                                    <v-list-item-title>File</v-list-item-title>
                                </v-list-item>
                            </v-list>
                        </v-menu>
                        
                        <v-btn icon variant="text" color="primary" @click="startRecording">
                            <v-icon>mdi-microphone</v-icon>
                        </v-btn>
                        
                        <v-text-field
                            v-model="messageInput"
                            placeholder="Type a message..."
                            variant="outlined"
                            density="compact"
                            hide-details
                            rounded
                            class="flex-grow-1"
                            @keyup.enter="sendTextMessage"
                        />
                        
                        <v-btn
                            icon
                            color="primary"
                            :disabled="!messageInput.trim()"
                            :loading="isSending"
                            @click="sendTextMessage"
                        >
                            <v-icon>mdi-send</v-icon>
                        </v-btn>
                    </div>
                </div>
            </div>
        </v-main>

        <!-- Hidden File Inputs -->
        <input
            ref="cameraInput"
            type="file"
            accept="image/*"
            capture="environment"
            style="display: none"
            @change="handleCameraCapture"
        />
        <input
            ref="imageInput"
            type="file"
            accept="image/*"
            style="display: none"
            @change="handleImageUpload"
        />
        <input
            ref="fileInput"
            type="file"
            style="display: none"
            @change="handleFileUpload"
        />

        <!-- Image Preview Dialog -->
        <v-dialog v-model="showImageDialog" max-width="90%">
            <v-img :src="previewImageUrl" max-height="80vh" contain @click="showImageDialog = false" />
        </v-dialog>

        <!-- Camera Capture Dialog -->
        <v-dialog v-model="showCameraDialog" max-width="500" persistent>
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>Take Photo</span>
                    <v-btn icon variant="text" @click="closeCameraDialog">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text class="pa-0">
                    <video 
                        ref="cameraVideo" 
                        autoplay 
                        playsinline 
                        class="camera-video"
                    ></video>
                    <canvas ref="cameraCanvas" style="display: none;"></canvas>
                </v-card-text>
                <v-card-actions class="justify-center pa-4">
                    <v-btn
                        icon
                        size="x-large"
                        color="primary"
                        @click="capturePhoto"
                    >
                        <v-icon size="32">mdi-camera</v-icon>
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Profile Photo Viewer Dialog -->
        <v-dialog v-model="showPhotoViewer" max-width="350">
            <v-card rounded="xl" class="pa-4 text-center">
                <v-avatar size="200" :color="!otherParticipantPicture ? avatarColor : undefined" class="mb-4">
                    <v-img
                        v-if="otherParticipantPicture"
                        :src="otherParticipantPicture"
                        cover
                    />
                    <span v-else class="text-h2 text-white font-weight-bold">{{ initials }}</span>
                </v-avatar>
                <h3 class="text-h6 mb-1">{{ chatTitle }}</h3>
                <p v-if="otherParticipantRole" class="text-caption text-grey mb-3">
                    {{ otherParticipantRole === 'rescuer' ? 'Emergency Rescuer' : 'User' }}
                </p>
                <v-btn variant="text" color="primary" @click="showPhotoViewer = false">Close</v-btn>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
            {{ snackbar.message }}
        </v-snackbar>
    </v-app>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    getOrCreateConversation,
    getConversation,
    getConversationMessages,
    sendMessage,
    sendMessageWithFile,
    markConversationRead,
    getProfilePictureUrl,
} from '@/Composables/useApi';

const props = defineProps({
    // Can pass either conversationId directly or rescueRequestId
    conversationId: {
        type: [String, Number],
        default: null,
    },
    rescueRequestId: {
        type: [String, Number],
        default: null,
    },
    // Role of the current user viewing the chat
    userRole: {
        type: String,
        default: 'user', // 'user' or 'rescuer'
    },
});

// Refs
const messagesContainer = ref(null);
const cameraInput = ref(null);
const imageInput = ref(null);
const fileInput = ref(null);
const cameraVideo = ref(null);
const cameraCanvas = ref(null);
const cameraStream = ref(null);

// State
const loading = ref(true);
const conversation = ref(null);
const rescueRequest = ref(null);
const messages = ref([]);
const messageInput = ref('');
const currentUserId = ref(null);
const isTyping = ref(false);
const isSending = ref(false);
const playingAudioId = ref(null);
const showImageDialog = ref(false);
const previewImageUrl = ref('');
const showPhotoViewer = ref(false);
const showCameraDialog = ref(false);
const pollingInterval = ref(null);
const audioElements = ref({});

// Recording state
const isRecording = ref(false);
const recordingDuration = ref('0:00');
const mediaRecorder = ref(null);
const recordingChunks = ref([]);
const recordingTimer = ref(null);
const recordingSeconds = ref(0);

const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
});

// Computed
const hasRescuerAssigned = computed(() => {
    return rescueRequest.value?.rescuer_id || rescueRequest.value?.rescuer;
});

const otherParticipant = computed(() => {
    if (!conversation.value?.participants) return null;
    return conversation.value.participants.find(p => p.user_id !== currentUserId.value);
});

const otherParticipantPicture = computed(() => {
    const user = otherParticipant.value?.user;
    if (user?.profile_picture) {
        return getProfilePictureUrl(user.profile_picture);
    }
    return null;
});

const otherParticipantRole = computed(() => {
    return otherParticipant.value?.user?.role;
});

const chatTitle = computed(() => {
    // If we have rescue request, show the other party's name
    if (rescueRequest.value) {
        if (props.userRole === 'rescuer') {
            // Rescuer viewing - show requester name
            const requester = rescueRequest.value.requester || rescueRequest.value.user;
            if (requester) {
                return `${requester.first_name || ''} ${requester.last_name || ''}`.trim() || 'User';
            }
        } else {
            // User viewing - show rescuer name
            const rescuer = rescueRequest.value.rescuer;
            if (rescuer) {
                return `${rescuer.first_name || ''} ${rescuer.last_name || ''}`.trim() || 'Rescuer';
            }
        }
    }
    
    // Fallback to other participant from conversation
    const other = otherParticipant.value?.user;
    if (other) {
        return `${other.first_name || ''} ${other.last_name || ''}`.trim() || 'Chat';
    }
    
    return conversation.value?.title || 'Chat';
});

const initials = computed(() => {
    const parts = chatTitle.value.split(' ');
    if (parts.length >= 2) {
        return `${parts[0][0] || ''}${parts[1][0] || ''}`.toUpperCase();
    }
    return chatTitle.value.substring(0, 2).toUpperCase();
});

const avatarColor = computed(() => {
    if (rescueRequest.value?.emergency_type) {
        return getEmergencyColor(rescueRequest.value.emergency_type);
    }
    return 'primary';
});

const groupedMessages = computed(() => {
    const groups = {};
    messages.value.forEach(message => {
        const dateStr = message.sent_at || message.created_at;
        const date = new Date(dateStr).toDateString();
        if (!groups[date]) {
            groups[date] = [];
        }
        groups[date].push(message);
    });
    return groups;
});

// Methods
const initializeChat = async () => {
    try {
        // Get current user from localStorage
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        currentUserId.value = userData.id;

        if (props.conversationId) {
            // Direct conversation ID provided
            await fetchConversation(props.conversationId);
        } else if (props.rescueRequestId) {
            // Rescue request ID provided - get or create conversation
            const response = await getOrCreateConversation(props.rescueRequestId);
            const data = response.data || response;
            
            if (data.id) {
                conversation.value = data;
                rescueRequest.value = data.rescue_request;
                await fetchMessages(data.id);
            } else {
                // No conversation yet (no rescuer assigned)
                loading.value = false;
            }
        }
    } catch (error) {
        console.error('Error initializing chat:', error);
        showSnackbar('Failed to load chat', 'error');
        loading.value = false;
    }
};

const fetchConversation = async (convId) => {
    try {
        const response = await getConversation(convId);
        const data = response.data || response;
        
        conversation.value = data;
        rescueRequest.value = data.rescue_request;
        
        await fetchMessages(convId);
    } catch (error) {
        console.error('Error fetching conversation:', error);
        showSnackbar('Failed to load conversation', 'error');
    }
};

const fetchMessages = async (convId = null) => {
    const conversationId = convId || conversation.value?.id;
    if (!conversationId) return;

    try {
        const response = await getConversationMessages(conversationId);
        const data = response.data || response;
        const newMessages = Array.isArray(data) ? data : data.data || [];
        
        messages.value = newMessages;
        
        await nextTick();
        scrollToBottom();
        
        // Mark messages as read
        markMessagesAsRead();
    } catch (error) {
        console.error('Error fetching messages:', error);
    } finally {
        loading.value = false;
    }
};

const sendTextMessage = async () => {
    const content = messageInput.value.trim();
    if (!content || isSending.value) return;

    isSending.value = true;
    const tempContent = content;
    messageInput.value = '';

    // Optimistic update
    const tempMessage = {
        id: `temp-${Date.now()}`,
        content: tempContent,
        sender_id: currentUserId.value,
        sent_at: new Date().toISOString(),
        status: 'sending',
    };
    messages.value.push(tempMessage);
    await nextTick();
    scrollToBottom();

    try {
        const response = await sendMessage(
            conversation.value.id,
            tempContent,
            currentUserId.value
        );

        const data = response.data || response;
        
        // Replace temp message with real one
        const index = messages.value.findIndex(m => m.id === tempMessage.id);
        if (index !== -1) {
            messages.value[index] = data;
        }
    } catch (error) {
        console.error('Error sending message:', error);
        // Remove temp message on error
        messages.value = messages.value.filter(m => m.id !== tempMessage.id);
        messageInput.value = tempContent; // Restore the message
        showSnackbar('Failed to send message', 'error');
    } finally {
        isSending.value = false;
    }
};

// Recording methods
const startRecording = async () => {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder.value = new MediaRecorder(stream);
        recordingChunks.value = [];
        
        mediaRecorder.value.ondataavailable = (e) => {
            if (e.data.size > 0) {
                recordingChunks.value.push(e.data);
            }
        };
        
        mediaRecorder.value.start();
        isRecording.value = true;
        recordingSeconds.value = 0;
        updateRecordingDuration();
        
        recordingTimer.value = setInterval(() => {
            recordingSeconds.value++;
            updateRecordingDuration();
        }, 1000);
    } catch (error) {
        console.error('Error starting recording:', error);
        showSnackbar('Could not access microphone', 'error');
    }
};

const stopRecording = async () => {
    if (!mediaRecorder.value) return;
    
    return new Promise((resolve) => {
        mediaRecorder.value.onstop = async () => {
            const audioBlob = new Blob(recordingChunks.value, { type: 'audio/webm' });
            cleanupRecording();
            await sendAudioMessage(audioBlob);
            resolve();
        };
        
        mediaRecorder.value.stop();
        mediaRecorder.value.stream.getTracks().forEach(track => track.stop());
    });
};

const cancelRecording = () => {
    if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
        mediaRecorder.value.stop();
        mediaRecorder.value.stream.getTracks().forEach(track => track.stop());
    }
    cleanupRecording();
};

const cleanupRecording = () => {
    if (recordingTimer.value) {
        clearInterval(recordingTimer.value);
        recordingTimer.value = null;
    }
    isRecording.value = false;
    recordingSeconds.value = 0;
    recordingDuration.value = '0:00';
    recordingChunks.value = [];
    mediaRecorder.value = null;
};

const updateRecordingDuration = () => {
    const mins = Math.floor(recordingSeconds.value / 60);
    const secs = recordingSeconds.value % 60;
    recordingDuration.value = `${mins}:${secs.toString().padStart(2, '0')}`;
};

const sendAudioMessage = async (audioBlob) => {
    isSending.value = true;
    
    try {
        const response = await sendMessageWithFile(
            conversation.value.id,
            '🎤 Voice message',
            currentUserId.value,
            new File([audioBlob], 'voice-message.webm', { type: 'audio/webm' })
        );

        const data = response.data || response;
        messages.value.push(data);
        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error('Error sending audio:', error);
        showSnackbar('Failed to send voice message', 'error');
    } finally {
        isSending.value = false;
    }
};

// File upload methods
const triggerCameraCapture = async () => {
    // Check if we're on mobile (has native camera support)
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile) {
        // Use native file input with capture on mobile
        cameraInput.value?.click();
    } else {
        // Use webcam dialog on desktop
        try {
            showCameraDialog.value = true;
            await nextTick();
            
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: 'environment' } 
            });
            cameraStream.value = stream;
            
            if (cameraVideo.value) {
                cameraVideo.value.srcObject = stream;
            }
        } catch (error) {
            console.error('Camera access error:', error);
            showCameraDialog.value = false;
            showSnackbar('Could not access camera. Please allow camera permissions.', 'error');
        }
    }
};

const capturePhoto = async () => {
    if (!cameraVideo.value || !cameraCanvas.value) return;
    
    const video = cameraVideo.value;
    const canvas = cameraCanvas.value;
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    canvas.toBlob(async (blob) => {
        if (blob) {
            const file = new File([blob], `photo-${Date.now()}.jpg`, { type: 'image/jpeg' });
            closeCameraDialog();
            await uploadFile(file);
        }
    }, 'image/jpeg', 0.9);
};

const closeCameraDialog = () => {
    showCameraDialog.value = false;
    if (cameraStream.value) {
        cameraStream.value.getTracks().forEach(track => track.stop());
        cameraStream.value = null;
    }
    if (cameraVideo.value) {
        cameraVideo.value.srcObject = null;
    }
};

const triggerImageUpload = () => {
    imageInput.value?.click();
};

const triggerFileUpload = () => {
    fileInput.value?.click();
};

const handleCameraCapture = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    
    await uploadFile(file);
    event.target.value = '';
};

const handleImageUpload = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    
    await uploadFile(file);
    event.target.value = '';
};

const handleFileUpload = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    
    await uploadFile(file);
    event.target.value = '';
};

const uploadFile = async (file) => {
    isSending.value = true;
    
    try {
        const response = await sendMessageWithFile(
            conversation.value.id,
            '',
            currentUserId.value,
            file
        );

        const data = response.data || response;
        messages.value.push(data);
        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error('Error uploading file:', error);
        showSnackbar('Failed to send file', 'error');
    } finally {
        isSending.value = false;
    }
};

// Audio playback
const toggleAudio = (message) => {
    const audioUrl = getAttachmentUrl(message.attachment_url);
    
    if (playingAudioId.value === message.id) {
        // Pause
        audioElements.value[message.id]?.pause();
        playingAudioId.value = null;
    } else {
        // Stop any playing audio
        Object.values(audioElements.value).forEach(audio => audio?.pause());
        
        // Play new audio
        if (!audioElements.value[message.id]) {
            audioElements.value[message.id] = new Audio(audioUrl);
            audioElements.value[message.id].onended = () => {
                playingAudioId.value = null;
            };
        }
        audioElements.value[message.id].play();
        playingAudioId.value = message.id;
    }
};

const showImagePreview = (url) => {
    previewImageUrl.value = getAttachmentUrl(url);
    showImageDialog.value = true;
};

const markMessagesAsRead = async () => {
    if (!conversation.value?.id || !currentUserId.value) return;
    
    const unreadMessages = messages.value.filter(
        m => m.status !== 'read' && m.sender_id !== currentUserId.value
    );
    
    if (unreadMessages.length === 0) return;

    try {
        await markConversationRead(conversation.value.id, currentUserId.value);
        unreadMessages.forEach(m => m.status = 'read');
    } catch (error) {
        console.error('Error marking messages as read:', error);
    }
};

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const goBack = () => {
    if (props.userRole === 'rescuer') {
        router.visit('/rescuer/chats');
    } else {
        router.visit('/user/inbox');
    }
};

const viewRescue = () => {
    if (rescueRequest.value) {
        if (props.userRole === 'rescuer') {
            router.visit(`/rescuer/active/${rescueRequest.value.id}`);
        } else {
            // Use rescue_code for user's HelpComing page
            router.visit(`/user/help-coming/${rescueRequest.value.rescue_code}`);
        }
    }
};

// Helper methods
const isOwnMessage = (message) => {
    return String(message.sender_id) === String(currentUserId.value);
};

const getMessageSenderName = (message) => {
    return message.sender?.first_name 
        ? `${message.sender.first_name} ${message.sender.last_name || ''}`.trim()
        : 'Unknown';
};

const isAudioAttachment = (message) => {
    return message.attachment_type?.includes('audio');
};

const isImageAttachment = (message) => {
    return message.attachment_type?.includes('image');
};

const getAttachmentUrl = (url) => {
    if (!url) return '';
    if (url.startsWith('http')) return url;
    // Use empty string for same-origin requests to avoid CORS issues
    const baseUrl = import.meta.env.VITE_API_BASE_URL || '';
    return `${baseUrl}${url.startsWith('/') ? '' : '/'}${url}`;
};

const getEmergencyColor = (type) => {
    const colors = {
        'Fire': 'deep-orange',
        'Medical': 'red',
        'Earthquake': 'brown',
        'Flood': 'blue',
        'Violence': 'purple',
        'Other': 'grey',
    };
    return colors[type] || 'primary';
};

const formatStatus = (status) => {
    const labels = {
        'pending': 'Pending',
        'accepted': 'Active',
        'en_route': 'En Route',
        'on_scene': 'On Scene',
        'rescued': 'Completed',
        'cancelled': 'Cancelled',
    };
    return labels[status] || status;
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatDateSeparator = (dateString) => {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    }
    if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }
    return date.toLocaleDateString([], { month: 'long', day: 'numeric', year: 'numeric' });
};

const formatDuration = (seconds) => {
    if (!seconds || isNaN(seconds)) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const showSnackbar = (message, color = 'success') => {
    snackbar.value = { show: true, message, color };
};

// Lifecycle
onMounted(async () => {
    await initializeChat();
    
    // Poll for new messages every 5 seconds
    if (conversation.value?.id) {
        pollingInterval.value = setInterval(() => fetchMessages(), 5000);
    }
});

onUnmounted(() => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
    }
    cleanupRecording();
    closeCameraDialog();
    // Clean up audio elements
    Object.values(audioElements.value).forEach(audio => {
        audio?.pause();
    });
});

// Watch for conversation changes to start polling
watch(() => conversation.value?.id, (newId) => {
    if (newId && !pollingInterval.value) {
        pollingInterval.value = setInterval(() => fetchMessages(), 5000);
    }
});
</script>

<style scoped>
.chat-container {
    background-color: #f5f5f5;
}

.messages-area {
    background: linear-gradient(to bottom, #f8f0f0 0%, #ffffff 40%, #f8f8f8 50%, #d1f8ef 80%, #a1e3f9 100%);
}

.message-wrapper {
    display: flex;
}

.own-message {
    justify-content: flex-end;
}

.other-message {
    justify-content: flex-start;
}

.message-bubble {
    max-width: 75%;
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.own-message .message-bubble {
    border-bottom-right-radius: 4px;
    color: white;
}

.other-message .message-bubble {
    border-bottom-left-radius: 4px;
}

.message-content {
    word-break: break-word;
    white-space: pre-wrap;
}

.message-meta {
    opacity: 0.7;
    font-size: 11px;
}

.own-message .message-meta {
    color: rgba(255, 255, 255, 0.8);
}

.date-separator {
    position: relative;
}

.date-separator::before,
.date-separator::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 30%;
    height: 1px;
    background-color: rgba(0, 0, 0, 0.1);
}

.date-separator::before {
    left: 0;
}

.date-separator::after {
    right: 0;
}

.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 4px 0;
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #999;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
    }
    30% {
        transform: translateY(-8px);
    }
}

.recording-ui {
    background-color: #ffebee;
    border-radius: 24px;
}

.recording-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #f44336;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.audio-message {
    display: flex;
    align-items: center;
    min-width: 180px;
}

.audio-waveform {
    display: flex;
    align-items: center;
    gap: 2px;
    height: 24px;
}

.waveform-bar {
    width: 3px;
    background-color: currentColor;
    border-radius: 2px;
    opacity: 0.6;
}

.input-area {
    border-top: 1px solid rgba(0, 0, 0, 0.08);
}

.cursor-pointer {
    cursor: pointer;
}

.message-status {
    display: inline-flex;
    align-items: center;
    vertical-align: middle;
}

.camera-video {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    background-color: #000;
}
</style>
