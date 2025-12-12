import { ref } from 'vue';

/**
 * Composable for audio recording functionality
 */
export function useAudioRecording() {
    const isRecording = ref(false);
    const recordingTime = ref(0);
    const error = ref(null);

    let mediaRecorder = null;
    let recordingChunks = [];
    let timerInterval = null;

    const startRecording = async () => {
        if (mediaRecorder && mediaRecorder.state === 'recording') return;

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            recordingChunks = [];
            mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) recordingChunks.push(e.data);
            };

            mediaRecorder.start();
            isRecording.value = true;
            recordingTime.value = 0;
            error.value = null;

            // Start timer
            timerInterval = setInterval(() => {
                recordingTime.value++;
            }, 1000);
        } catch (e) {
            error.value = e.message || 'Failed to start recording';
            throw e;
        }
    };

    const stopRecording = () => {
        return new Promise((resolve, reject) => {
            if (!mediaRecorder) {
                reject(new Error('No active recording'));
                return;
            }

            const mr = mediaRecorder;
            mr.onstop = () => {
                try {
                    const blob = new Blob(recordingChunks, { type: 'audio/webm' });
                    // Stop all tracks
                    mr.stream.getTracks().forEach((t) => t.stop());
                    mediaRecorder = null;
                    recordingChunks = [];
                    isRecording.value = false;

                    // Clear timer
                    if (timerInterval) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                    }

                    resolve(blob);
                } catch (err) {
                    reject(err);
                }
            };
            mr.stop();
        });
    };

    const cancelRecording = () => {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stream.getTracks().forEach((t) => t.stop());
            mediaRecorder = null;
            recordingChunks = [];
        }
        isRecording.value = false;
        recordingTime.value = 0;

        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    };

    const formatTime = (seconds) => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    return {
        isRecording,
        recordingTime,
        error,
        startRecording,
        stopRecording,
        cancelRecording,
        formatTime,
    };
}
