<template>
    <v-app id="inspire">
        <slot />
    </v-app>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useFirebaseFCM } from '@/Composables/useFirebaseFCM';

// Initialize Firebase FCM for authenticated users
const { initializeFCM, userId } = useFirebaseFCM();

// Prevent page scrolling/dragging - only allow scroll inside v-main
onMounted(() => {
    // Prevent wheel scroll on body
    const preventBodyScroll = (e) => {
        // Allow scroll if target is inside v-main or scrollable container
        let target = e.target;
        while (target && target !== document.body) {
            const style = window.getComputedStyle(target);
            const overflowY = style.getPropertyValue('overflow-y');
            const isScrollable = overflowY === 'auto' || overflowY === 'scroll';
            
            if (isScrollable && target.scrollHeight > target.clientHeight) {
                // Check if we're at the scroll boundaries
                const atTop = target.scrollTop === 0;
                const atBottom = target.scrollTop + target.clientHeight >= target.scrollHeight - 1;
                
                // Allow scroll if not at boundary or scrolling in valid direction
                if (e.deltaY < 0 && !atTop) return; // Scrolling up, not at top
                if (e.deltaY > 0 && !atBottom) return; // Scrolling down, not at bottom
                
                // At boundary, check if there's more content
                if (!atTop || !atBottom) return;
            }
            
            // Check for v-main or scrollable classes
            if (target.classList.contains('v-main') || 
                target.classList.contains('v-main__wrap') ||
                target.classList.contains('v-navigation-drawer__content')) {
                return; // Allow scroll in these containers
            }
            
            target = target.parentElement;
        }
        
        // Prevent scroll on body
        e.preventDefault();
    };

    // Prevent touch move on body (for mobile)
    const preventTouchMove = (e) => {
        let target = e.target;
        while (target && target !== document.body) {
            const style = window.getComputedStyle(target);
            const overflowY = style.getPropertyValue('overflow-y');
            if (overflowY === 'auto' || overflowY === 'scroll') {
                if (target.scrollHeight > target.clientHeight) {
                    return; // Allow touch scroll in scrollable container
                }
            }
            if (target.classList.contains('v-main') || 
                target.classList.contains('v-main__wrap') ||
                target.classList.contains('v-navigation-drawer__content')) {
                return;
            }
            target = target.parentElement;
        }
        e.preventDefault();
    };

    document.addEventListener('wheel', preventBodyScroll, { passive: false });
    document.addEventListener('touchmove', preventTouchMove, { passive: false });

    // Cleanup on unmount
    onUnmounted(() => {
        document.removeEventListener('wheel', preventBodyScroll);
        document.removeEventListener('touchmove', preventTouchMove);
    });
});
</script>
