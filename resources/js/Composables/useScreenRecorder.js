import { ref, onBeforeUnmount } from 'vue';

export function useScreenRecorder() {
    const isRecording = ref(false);
    const recordingDuration = ref(0);
    const videoBlob = ref(null);
    const videoUrl = ref(null);
    const error = ref(null);

    let mediaRecorder = null;
    let recordedChunks = [];
    let combinedStream = null;
    let displayStream = null;
    let micStream = null;
    let timerInterval = null;

    const isSupported =
        typeof navigator !== 'undefined' &&
        !!navigator.mediaDevices &&
        typeof navigator.mediaDevices.getDisplayMedia === 'function';

    const getSupportedMimeType = () => {
        if (typeof MediaRecorder === 'undefined') return '';
        const types = [
            'video/webm;codecs=vp9,opus',
            'video/webm;codecs=vp8,opus',
            'video/webm',
            'video/mp4;codecs=avc1,mp4a.40.2',
            'video/mp4',
        ];
        return types.find((t) => MediaRecorder.isTypeSupported(t)) || '';
    };

    const stopAllTracks = () => {
        if (displayStream) {
            displayStream.getTracks().forEach((track) => track.stop());
            displayStream = null;
        }
        if (micStream) {
            micStream.getTracks().forEach((track) => track.stop());
            micStream = null;
        }
        if (combinedStream) {
            combinedStream.getTracks().forEach((track) => track.stop());
            combinedStream = null;
        }
    };

    const stopRecording = () => {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }

        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            try {
                mediaRecorder.stop();
            } catch (e) {
                console.warn('Error stopping mediaRecorder:', e);
            }
        }

        isRecording.value = false;
        stopAllTracks();
    };

    const startRecording = async ({ withAudio = true, maxDurationSeconds = 120 } = {}) => {
        error.value = null;
        resetRecording();

        if (!isSupported) {
            error.value = 'متصفحك لا يدعم خاصية تسجيل الشاشة المباشر.';
            return false;
        }

        try {
            // 1. Request microphone if requested
            if (withAudio) {
                try {
                    micStream = await navigator.mediaDevices.getUserMedia({
                        audio: { echoCancellation: true, noiseSuppression: true },
                    });
                } catch (micErr) {
                    console.warn('Microphone permission denied or unavailable:', micErr);
                    micStream = null;
                }
            }

            // 2. Request screen capture
            displayStream = await navigator.mediaDevices.getDisplayMedia({
                video: {
                    frameRate: { ideal: 24, max: 30 },
                    width: { max: 1920 },
                    height: { max: 1080 },
                },
                audio: true, // system audio if supported by browser/OS
            });

            // If user clicks "Stop sharing" from the browser banner
            displayStream.getVideoTracks()[0].onended = () => {
                stopRecording();
            };

            // 3. Combine tracks into single stream
            const audioTracks = [];
            if (displayStream.getAudioTracks().length > 0) {
                audioTracks.push(...displayStream.getAudioTracks());
            }
            if (micStream && micStream.getAudioTracks().length > 0) {
                audioTracks.push(...micStream.getAudioTracks());
            }

            combinedStream = new MediaStream([...displayStream.getVideoTracks(), ...audioTracks]);

            const mimeType = getSupportedMimeType();
            const options = mimeType ? { mimeType } : {};

            mediaRecorder = new MediaRecorder(combinedStream, options);
            recordedChunks = [];

            mediaRecorder.ondataavailable = (event) => {
                if (event.data && event.data.size > 0) {
                    recordedChunks.push(event.data);
                }
            };

            mediaRecorder.onstop = () => {
                if (recordedChunks.length > 0) {
                    const blobType = mimeType || 'video/webm';
                    const blob = new Blob(recordedChunks, { type: blobType });
                    videoBlob.value = blob;
                    videoUrl.value = URL.createObjectURL(blob);
                }
                stopAllTracks();
            };

            mediaRecorder.start(1000); // 1s timeslices
            isRecording.value = true;
            recordingDuration.value = 0;

            // Start duration countdown/countup
            timerInterval = setInterval(() => {
                recordingDuration.value += 1;
                if (recordingDuration.value >= maxDurationSeconds) {
                    stopRecording();
                }
            }, 1000);

            return true;
        } catch (err) {
            console.error('Error starting screen recording:', err);
            stopAllTracks();
            isRecording.value = false;

            if (err.name === 'NotAllowedError') {
                error.value = 'تم إلغاء أو رفض إذن مشاركة الشاشة.';
            } else {
                error.value = 'تعذر بدء تسجيل الشاشة: ' + (err.message || 'خطأ غير متوقع');
            }
            return false;
        }
    };

    const resetRecording = () => {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        if (videoUrl.value) {
            URL.revokeObjectURL(videoUrl.value);
            videoUrl.value = null;
        }
        videoBlob.value = null;
        recordingDuration.value = 0;
        error.value = null;
    };

    onBeforeUnmount(() => {
        stopRecording();
        resetRecording();
    });

    return {
        isSupported,
        isRecording,
        recordingDuration,
        videoBlob,
        videoUrl,
        error,
        startRecording,
        stopRecording,
        resetRecording,
    };
}
