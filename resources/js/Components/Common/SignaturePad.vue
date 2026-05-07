<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    modelValue: String,
    label: String
});

const emit = defineEmits(['update:modelValue', 'clear']);

const canvasRef = ref(null);
const isDrawing = ref(false);
const ctx = ref(null);

let resizeObserver = null;

onMounted(() => {
    const canvas = canvasRef.value;
    ctx.value = canvas.getContext('2d');
    
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    
    // Add a ResizeObserver to handle modal transitions
    if (canvas.parentElement) {
        resizeObserver = new ResizeObserver(() => {
            // Only resize if the width actually changed and is > 0
            const rect = canvas.parentElement.getBoundingClientRect();
            if (rect.width > 0 && rect.width !== canvas.width) {
                resizeCanvas();
            }
        });
        resizeObserver.observe(canvas.parentElement);
    }

    ctx.value.lineWidth = 2;
    ctx.value.lineCap = 'round';
    ctx.value.strokeStyle = '#000';
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', resizeCanvas);
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
});

const resizeCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const rect = canvas.parentElement.getBoundingClientRect();
    
    // Save current drawing
    let tempCanvas = null;
    let tempCtx = null;
    if (canvas.width > 0 && canvas.height > 0) {
        tempCanvas = document.createElement('canvas');
        tempCanvas.width = canvas.width;
        tempCanvas.height = canvas.height;
        tempCtx = tempCanvas.getContext('2d');
        tempCtx.drawImage(canvas, 0, 0);
    }
    
    canvas.width = rect.width > 0 ? rect.width : 500; // Fallback width
    canvas.height = 200;
    
    ctx.value.lineWidth = 2;
    ctx.value.lineCap = 'round';
    ctx.value.strokeStyle = '#000';
    
    // Restore drawing after resize
    if (tempCanvas) {
        ctx.value.drawImage(tempCanvas, 0, 0);
    }
};

const startDrawing = (e) => {
    isDrawing.value = true;
    const { x, y } = getCoordinates(e);
    ctx.value.beginPath();
    ctx.value.moveTo(x, y);
};

const draw = (e) => {
    if (!isDrawing.value) return;
    const { x, y } = getCoordinates(e);
    ctx.value.lineTo(x, y);
    ctx.value.stroke();
};

const stopDrawing = () => {
    if (!isDrawing.value) return;
    isDrawing.value = false;
    ctx.value.closePath();
    saveSignature();
};

const getCoordinates = (e) => {
    const canvas = canvasRef.value;
    const rect = canvas.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return {
        x: clientX - rect.left,
        y: clientY - rect.top
    };
};

const clear = () => {
    const canvas = canvasRef.value;
    ctx.value.clearRect(0, 0, canvas.width, canvas.height);
    emit('update:modelValue', null);
    emit('clear');
};

const saveSignature = () => {
    const canvas = canvasRef.value;
    const dataURL = canvas.toDataURL('image/png');
    emit('update:modelValue', dataURL);
};
</script>

<template>
    <div class="space-y-2">
        <label v-if="label" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ label }}
        </label>
        <div class="relative border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl bg-white overflow-hidden">
            <canvas
                ref="canvasRef"
                class="w-full h-[200px] cursor-crosshair touch-none"
                @mousedown="startDrawing"
                @mousemove="draw"
                @mouseup="stopDrawing"
                @mouseleave="stopDrawing"
                @touchstart.prevent="startDrawing"
                @touchmove.prevent="draw"
                @touchend.prevent="stopDrawing"
            ></canvas>
            
            <div class="absolute bottom-2 right-2">
                <SecondaryButton type="button" @click="clear" size="xs">
                    {{ $t('common.clear') || 'تفريغ' }}
                </SecondaryButton>
            </div>
        </div>
    </div>
</template>
