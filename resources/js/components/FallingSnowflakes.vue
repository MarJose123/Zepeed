<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from "vue";
import { useAppearance } from "@/composables/useAppearance";

const { seasonalTheme } = useAppearance();

interface Snowflake {
    id: number;
    x: number;
    size: number;
    speed: number;
    opacity: number;
    sway: number;
    swaySpeed: number;
    y: number;
}

const snowflakes = ref<Snowflake[]>([]);
let animationFrameId: number | null = null;
let lastTime = 0;
const MAX_SNOWFLAKES = 50;

function createSnowflake(): Snowflake {
    return {
        id: Math.random(),
        x: Math.random() * 100,
        size: 2 + Math.random() * 6,
        speed: 0.3 + Math.random() * 0.8,
        opacity: 0.3 + Math.random() * 0.7,
        sway: Math.random() * 40 - 20,
        swaySpeed: 0.5 + Math.random() * 1.5,
        y: -2,
    };
}

function initSnowflakes() {
    snowflakes.value = [];

    for (let i = 0; i < MAX_SNOWFLAKES; i++) {
        const sf = createSnowflake();
        sf.y = Math.random() * 100;
        snowflakes.value.push(sf);
    }
}

function animate(timestamp: number) {
    if (lastTime === 0) {
        lastTime = timestamp;
    }

    const delta = timestamp - lastTime;
    lastTime = timestamp;

    for (const sf of snowflakes.value) {
        sf.y += sf.speed * (delta / 16);
        sf.x += Math.sin(timestamp * 0.001 * sf.swaySpeed) * 0.05 * sf.sway;

        if (sf.y > 102) {
            Object.assign(sf, createSnowflake());
        }
    }

    animationFrameId = requestAnimationFrame(animate);
}

function start() {
    initSnowflakes();
    lastTime = 0;
    animationFrameId = requestAnimationFrame(animate);
}

function stop() {
    if (animationFrameId !== null) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
    }

    snowflakes.value = [];
}

onMounted(() => {
    if (seasonalTheme.value === "christmas") {
        start();
    }
});

watch(seasonalTheme, (theme) => {
    if (theme === "christmas") {
        start();
    } else {
        stop();
    }
});

onUnmounted(() => {
    stop();
});
</script>

<template>
    <div
        v-if="seasonalTheme === 'christmas'"
        class="falling-snowflakes-container"
    >
        <div
            v-for="sf in snowflakes"
            :key="sf.id"
            class="falling-snowflake"
            :style="{
                left: sf.x + '%',
                top: sf.y + '%',
                width: sf.size + 'px',
                height: sf.size + 'px',
                opacity: sf.opacity,
            }"
        />
    </div>
</template>

<style scoped>
.falling-snowflakes-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 9998;
}

.falling-snowflake {
    position: absolute;
    background: radial-gradient(
        circle,
        rgba(255, 255, 255, 0.95) 0%,
        rgba(200, 225, 255, 0.6) 100%
    );
    border-radius: 50%;
    box-shadow: 0 0 6px rgba(200, 225, 255, 0.8);
}

:deep(.dark) .falling-snowflake {
    background: radial-gradient(
        circle,
        rgba(255, 255, 255, 1) 0%,
        rgba(200, 225, 255, 0.8) 100%
    );
    box-shadow: 0 0 8px rgba(200, 225, 255, 1);
}
</style>
