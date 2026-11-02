<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from "vue";
import { useAppearance } from "@/composables/useAppearance";

const { seasonalTheme } = useAppearance();

interface Particle {
    id: number;
    x: number;
    y: number;
    vx: number;
    vy: number;
    life: number;
    maxLife: number;
    color: string;
    size: number;
    twinkle: number;
}

interface Firework {
    x: number;
    y: number;
    targetY: number;
    speed: number;
    color: string;
}

const particles = ref<Particle[]>([]);
const fireworks = ref<Firework[]>([]);
let animationFrameId: number | null = null;
let lastTime = 0;
let spawnTimer = 0;
const SPAWN_INTERVAL = 800;

const COLORS = [
    "#FFD700",
    "#FF6B6B",
    "#4ECDC4",
    "#A78BFA",
    "#F472B6",
    "#34D399",
    "#60A5FA",
    "#FBBF24",
];

function randomColor() {
    return COLORS[Math.floor(Math.random() * COLORS.length)];
}

function explode(x: number, y: number, color: string) {
    const count = 60 + Math.floor(Math.random() * 40);

    for (let i = 0; i < count; i++) {
        const angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.3;
        const speed = 2 + Math.random() * 4;
        particles.value.push({
            id: Math.random(),
            x,
            y,
            vx: Math.cos(angle) * speed,
            vy: Math.sin(angle) * speed,
            life: 0,
            maxLife: 80 + Math.random() * 60,
            color,
            size: 2 + Math.random() * 3,
            twinkle: Math.random() * Math.PI * 2,
        });
    }
}

function spawnFirework() {
    fireworks.value.push({
        x: 70 + Math.random() * 25,
        y: 100,
        targetY: 5 + Math.random() * 20,
        speed: 0.5 + Math.random() * 0.5,
        color: randomColor(),
    });
}

function animate(timestamp: number) {
    if (lastTime === 0) {
        lastTime = timestamp;
    }

    const delta = timestamp - lastTime;
    lastTime = timestamp;

    spawnTimer += delta;

    if (spawnTimer >= SPAWN_INTERVAL) {
        spawnTimer = 0;
        spawnFirework();
    }

    for (let i = fireworks.value.length - 1; i >= 0; i--) {
        const fw = fireworks.value[i];
        fw.y -= fw.speed * (delta / 16);

        if (fw.y <= fw.targetY) {
            explode(fw.x, fw.y, fw.color);
            fireworks.value.splice(i, 1);
        }
    }

    for (let i = particles.value.length - 1; i >= 0; i--) {
        const p = particles.value[i];
        p.life += delta / 16;
        p.x += p.vx * (delta / 16);
        p.y += p.vy * (delta / 16);
        p.vy += 0.02 * (delta / 16);
        p.vx *= 0.98;
        p.twinkle += 0.2 * (delta / 16);

        if (p.life >= p.maxLife) {
            particles.value.splice(i, 1);
        }
    }

    animationFrameId = requestAnimationFrame(animate);
}

function getParticleOpacity(p: Particle) {
    const fadeOut = Math.max(0, 1 - p.life / p.maxLife);
    const twinkle = 0.5 + 0.5 * Math.sin(p.twinkle);

    return fadeOut * twinkle;
}

function start() {
    lastTime = 0;
    spawnTimer = 0;
    animationFrameId = requestAnimationFrame(animate);
}

function stop() {
    if (animationFrameId !== null) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
    }

    particles.value = [];
    fireworks.value = [];
}

onMounted(() => {
    if (seasonalTheme.value === "newyear") {
        start();
    }
});

watch(seasonalTheme, (theme) => {
    if (theme === "newyear") {
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
    <div v-if="seasonalTheme === 'newyear'" class="fireworks-container">
        <div
            v-for="fw in fireworks"
            :key="fw.x + fw.y"
            class="firework-rocket"
            :style="{
                left: fw.x + '%',
                top: fw.y + '%',
                backgroundColor: fw.color,
                boxShadow:
                    '0 0 8px 2px ' + fw.color + ', 0 0 15px 5px ' + fw.color,
            }"
        />

        <div
            v-for="p in particles"
            :key="p.id"
            class="firework-particle"
            :style="{
                left: p.x + '%',
                top: p.y + '%',
                width: p.size + 'px',
                height: p.size + 'px',
                backgroundColor: p.color,
                boxShadow: '0 0 6px 2px ' + p.color,
                opacity: getParticleOpacity(p),
            }"
        />
    </div>
</template>

<style scoped>
.fireworks-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 9998;
}

.firework-rocket {
    position: absolute;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    animation: rocket-flicker 0.1s ease-in-out infinite alternate;
}

@keyframes rocket-flicker {
    0% {
        opacity: 0.8;
        transform: translate(-50%, -50%) scale(0.9);
    }
    100% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1.1);
    }
}

.firework-particle {
    position: absolute;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    filter: blur(0.5px);
}
</style>
