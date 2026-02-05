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
            <div v-else class="chat-container d-flex flex-column">
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
                                        <div class="waveform-bar" v-for="n in 15" :key="n" :style="{ height: `${Math.random() * 16 + 4}px` }"></div>
                                    </div>
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

                                <!-- Other Attachments (non-audio, non-image) -->
                                <div v-else-if="message.attachment_url && !isAudioAttachment(message) && !isImageAttachment(message)" class="attachment-message">
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
import { useNotificationAlert } from '@/Composables/useNotificationAlert';

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

// Notification alert
const { playNotificationSound, vibrate, notify } = useNotificationAlert();
const lastKnownMessageIds = ref(new Set());
const isInitialLoad = ref(true);

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
            } else if (data.has_rescuer === false) {
                // No rescuer assigned yet
                showSnackbar('Please accept or start the rescue first before chatting', 'warning');
                loading.value = false;
            } else {
                // No conversation yet
                loading.value = false;
            }
        }
    } catch (error) {
        console.error('Error initializing chat:', error);
        // Check if error is due to no rescuer assigned
        if (error.status === 400 || error.data?.has_rescuer === false) {
            showSnackbar('Please accept or start the rescue first before chatting', 'warning');
        } else {
            showSnackbar('Failed to load chat', 'error');
        }
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
        
        // Check for new messages from the other user (not initial load)
        if (!isInitialLoad.value && newMessages.length > 0) {
            const currentIds = new Set(lastKnownMessageIds.value);
            let hasNewMessageFromOther = false;
            
            for (const msg of newMessages) {
                // Check if this is a new message from the other user
                if (!currentIds.has(msg.id) && msg.sender_id !== currentUserId.value) {
                    hasNewMessageFromOther = true;
                    break;
                }
            }
            
            // Play notification sound for new messages from other user
            if (hasNewMessageFromOther) {
                playNotificationSound('message');
                vibrate('message');
            }
        }
        
        // Update the known message IDs
        lastKnownMessageIds.value = new Set(newMessages.map(m => m.id));
        
        // Mark initial load as complete after first successful fetch
        if (isInitialLoad.value) {
            isInitialLoad.value = false;
        }
        
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
        if (cameraInput.value) {
            cameraInput.value.click();
        }
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
                try {
                    await cameraVideo.value.play();
                } catch (playError) {
                    // Video may auto-play, ignore error
                    console.log('Video autoplay handled');
                }
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
    if (imageInput.value) {
        imageInput.value.click();
    }
};

const triggerFileUpload = () => {
    if (fileInput.value) {
        fileInput.value.click();
    }
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
        // Pause current audio
        if (audioElements.value[message.id]) {
            audioElements.value[message.id].pause();
        }
        playingAudioId.value = null;
    } else {
        // Stop any currently playing audio
        Object.entries(audioElements.value).forEach(([id, audio]) => {
            if (audio) {
                audio.pause();
                audio.currentTime = 0;
            }
        });
        
        // Play new audio
        if (!audioElements.value[message.id]) {
            const audio = new Audio();
            audio.src = audioUrl;
            audio.preload = 'metadata';
            
            audio.addEventListener('loadedmetadata', () => {
                // Audio loaded successfully
            });
            
            audio.addEventListener('ended', () => {
                playingAudioId.value = null;
            });
            
            audio.addEventListener('error', (e) => {
                console.error('Audio playback error:', e);
                showSnackbar('Failed to play audio', 'error');
                playingAudioId.value = null;
            });
            
            audioElements.value[message.id] = audio;
        }
        
        const audio = audioElements.value[message.id];
        audio.play().then(() => {
            playingAudioId.value = message.id;
        }).catch((error) => {
            console.error('Audio play error:', error);
            showSnackbar('Failed to play audio', 'error');
            playingAudioId.value = null;
        });
    }
};

const showImagePreview = (url) => {
    previewImageUrl.value = getAttachmentUrl(url);
    showImageDialog.value = true;
};

const markMessagesAsRead = async () => {
    if (!conversation.value?.id || !currentUserId.value) return;
    
    // Only mark as read if the document is visible (user is actively viewing)
    if (document.hidden) return;
    
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
    // Check if there's a 'from' query parameter to determine where to go back
    const urlParams = new URLSearchParams(window.location.search);
    const from = urlParams.get('from');
    
    // Handle specific 'from' parameters first
    if (from === 'dashboard-inprogress') {
        // Go back to dashboard with inProgress tab selected
        router.visit('/rescuer/dashboard?tab=inProgress');
        return;
    } 
    
    if (from === 'dashboard-pending') {
        // Go back to dashboard with pending tab selected
        router.visit('/rescuer/dashboard?tab=pending');
        return;
    }
    
    if (from === 'dashboard-rescued') {
        // Go back to dashboard with rescued tab selected
        router.visit('/rescuer/dashboard?tab=rescued');
        return;
    }
    
    if (from === 'rescuer-chats') {
        // Go back to rescuer chats page
        router.visit('/rescuer/chats');
        return;
    }
    
    if (from === 'user-inbox') {
        // Go back to user inbox page
        router.visit('/user/inbox');
        return;
    }
    
    if (from === 'active-rescue' && rescueRequest.value?.id) {
        // Go back to active rescue page
        router.visit(`/rescuer/active/${rescueRequest.value.id}`);
        return;
    }
    
    // Try to use browser history first (if user navigated within the app)
    if (window.history.length > 1) {
        const referrer = document.referrer;
        const currentOrigin = window.location.origin;
        
        // Only go back if the referrer is from the same origin (same app)
        if (referrer && referrer.startsWith(currentOrigin)) {
            window.history.back();
            return;
        }
    }
    
    // Fallback to role-specific default pages
    if (props.userRole === 'rescuer') {
        router.visit('/rescuer/chats');
    } else {
        // For users, go back to messages/inbox first, then help-coming if available
        if (rescueRequest.value?.rescue_code) {
            router.visit(`/user/help-coming/${rescueRequest.value.rescue_code}`);
        } else {
            router.visit('/user/inbox');
        }
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
    // Check attachment_type first
    if (message.attachment_type?.includes('audio')) {
        return true;
    }
    
    // Fallback: check file extension or name for audio files
    const attachmentName = message.attachment_name || message.attachment_url || '';
    const audioExtensions = ['.webm', '.mp3', '.wav', '.ogg', '.m4a', '.aac'];
    const isAudioFile = audioExtensions.some(ext => attachmentName.toLowerCase().includes(ext));
    
    // Also check if content mentions voice message
    const isVoiceMessage = message.content && message.content.includes('Voice message');
    
    return isAudioFile || isVoiceMessage;
};

const isImageAttachment = (message) => {
    return message.attachment_type?.includes('image');
};

const getAttachmentUrl = (url) => {
    if (!url) return '';
    if (url.startsWith('http')) return url;
    
    // Get base URL from environment or use current origin
    const baseUrl = import.meta.env.VITE_API_BASE_URL || window.location.origin;
    
    // Ensure proper URL construction
    const cleanUrl = url.startsWith('/') ? url : `/${url}`;
    return `${baseUrl}${cleanUrl}`;
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
    
    // Add visibility change listener to mark messages as read when user returns to tab
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

// Handle visibility change - mark messages as read when user returns to tab
const handleVisibilityChange = () => {
    if (!document.hidden && conversation.value?.id) {
        markMessagesAsRead();
    }
};

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
    // Remove visibility change listener
    document.removeEventListener('visibilitychange', handleVisibilityChange);
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
    height: calc(100vh - 56px);
    height: calc(100dvh - 56px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.messages-area {
    background: linear-gradient(to bottom, #f8f0f0 0%, #ffffff 40%, #f8f8f8 50%, #d1f8ef 80%, #a1e3f9 100%);
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    padding-bottom: env(safe-area-inset-bottom, 0);
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
    word-break: break-word;
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
    font-size: 0.9rem;
    line-height: 1.4;
}

.message-meta {
    opacity: 0.7;
    font-size: 10px;
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
    flex-shrink: 0;
    padding-bottom: env(safe-area-inset-bottom, 8px);
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

/* Mobile Small (< 360px) */
@media (max-width: 359px) {
    .chat-container {
        height: calc(100vh - 48px);
        height: calc(100dvh - 48px);
    }
    
    .messages-area {
        padding: 8px 10px;
    }
    
    .message-bubble {
        max-width: 85%;
        padding: 8px 10px !important;
    }
    
    .message-content {
        font-size: 0.8rem;
    }
    
    .message-meta {
        font-size: 9px;
    }
    
    .input-area {
        padding: 8px !important;
    }
    
    .input-area .v-text-field {
        font-size: 0.85rem;
    }
}

/* Mobile (360px - 599px) */
@media (min-width: 360px) and (max-width: 599px) {
    .chat-container {
        height: calc(100vh - 52px);
        height: calc(100dvh - 52px);
    }
    
    .messages-area {
        padding: 12px;
    }
    
    .message-bubble {
        max-width: 80%;
    }
    
    .message-content {
        font-size: 0.85rem;
    }
    
    .message-meta {
        font-size: 10px;
    }
    
    .input-area {
        padding: 10px !important;
    }
}

/* Tablet (600px - 1023px) */
@media (min-width: 600px) and (max-width: 1023px) {
    .chat-container {
        height: calc(100vh - 56px);
        height: calc(100dvh - 56px);
    }
    
    .messages-area {
        padding: 16px 20px;
    }
    
    .message-bubble {
        max-width: 70%;
    }
    
    .message-content {
        font-size: 0.9rem;
    }
    
    .message-meta {
        font-size: 11px;
    }
}

/* Desktop (1024px+) */
@media (min-width: 1024px) {
    .chat-container {
        height: calc(100vh - 64px);
        max-width: 800px;
        margin: 0 auto;
    }
    
    .messages-area {
        padding: 20px 24px;
    }
    
    .message-bubble {
        max-width: 60%;
    }
    
    .message-content {
        font-size: 0.95rem;
    }
    
    .message-meta {
        font-size: 11px;
    }
    
    .input-area {
        padding: 16px !important;
    }
}

/* Fix for keyboards on mobile */
@supports (height: 100dvh) {
    .chat-container {
        height: calc(100dvh - 56px);
    }
    
    @media (max-width: 359px) {
        .chat-container {
            height: calc(100dvh - 48px);
        }
    }
    
    @media (min-width: 360px) and (max-width: 599px) {
        .chat-container {
            height: calc(100dvh - 52px);
        }
    }
}
</style>
