<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { useAppearance } from "@/composables/useAppearance";

const { seasonalTheme } = useAppearance();

const spiderX = ref(80);
const spiderY = ref(110);
let intervalId: number | null = null;

const spiderBodyX = computed(() => spiderX.value - 8);
const spiderBodyY = computed(() => spiderY.value + 2);

function randomMove() {
    const angle = (Math.random() * Math.PI) / 2;
    const distance = 15 + Math.random() * 40;

    let newX =
        spiderX.value +
        Math.cos(angle) * distance * (Math.random() > 0.5 ? 1 : -1);
    let newY =
        spiderY.value +
        Math.sin(angle) * distance * (Math.random() > 0.5 ? 1 : -1);

    newX = Math.max(20, Math.min(250, newX));
    newY = Math.max(30, Math.min(260, newY));

    spiderX.value = Math.round(newX);
    spiderY.value = Math.round(newY);
}

function startRandomMovement() {
    intervalId = window.setInterval(randomMove, 2000 + Math.random() * 3000);
}

function stopRandomMovement() {
    if (intervalId !== null) {
        clearInterval(intervalId);
        intervalId = null;
    }
}

onMounted(() => {
    if (seasonalTheme.value === "halloween") {
        startRandomMovement();
    }
});

watch(seasonalTheme, (theme) => {
    if (theme === "halloween") {
        startRandomMovement();
    } else {
        stopRandomMovement();
    }
});

onUnmounted(() => {
    stopRandomMovement();
});
</script>

<template>
    <Transition name="spiderweb-fade">
        <div v-if="seasonalTheme === 'halloween'" class="spiderweb-container">
            <svg
                class="spiderweb-svg"
                viewBox="0 0 300 300"
                xmlns="http://www.w3.org/2000/svg"
            >
                <defs>
                    <!-- Subtle texture filter for the web -->
                    <filter
                        id="webTexture"
                        x="-20%"
                        y="-20%"
                        width="140%"
                        height="140%"
                    >
                        <feTurbulence
                            type="fractalNoise"
                            baseFrequency="0.04"
                            numOctaves="3"
                            result="noise"
                        />
                        <feDisplacementMap
                            in="SourceGraphic"
                            in2="noise"
                            scale="3"
                            xChannelSelector="R"
                            yChannelSelector="G"
                        />
                    </filter>
                    <!-- Fog/mist gradient for eerie atmosphere -->
                    <radialGradient id="webFog" cx="0%" cy="0%" r="100%">
                        <stop
                            offset="0%"
                            stop-color="var(--web-color)"
                            stop-opacity="0.3"
                        />
                        <stop
                            offset="100%"
                            stop-color="var(--web-color)"
                            stop-opacity="0"
                        />
                    </radialGradient>
                </defs>

                <!-- Eerie fog/mist around the web corner -->
                <ellipse
                    class="web-fog"
                    cx="0"
                    cy="0"
                    rx="180"
                    ry="180"
                    fill="url(#webFog)"
                />

                <!-- Web strands from corner -->
                <g
                    class="web-strands"
                    stroke="var(--web-color)"
                    fill="none"
                    stroke-width="0.6"
                    filter="url(#webTexture)"
                >
                    <!-- Radial lines extending from corner -->
                    <path
                        d="M0,0 Q25,-2 50,1 T100,0 T150,2 T200,1 T250,3 T300,0"
                    />
                    <path
                        d="M0,0 Q25,3 50,8 T100,15 T150,22 T200,28 T250,32 T300,35"
                    />
                    <path
                        d="M0,0 Q25,8 50,18 T100,35 T150,52 T200,70 T250,88 T300,105"
                    />
                    <path
                        d="M0,0 Q25,15 50,32 T100,65 T150,98 T200,130 T250,162 T300,195"
                    />
                    <path
                        d="M0,0 Q25,22 50,48 T100,95 T150,142 T200,188 T250,235 T300,280"
                    />
                    <path
                        d="M0,0 Q22,25 45,55 T90,110 T135,165 T180,220 T225,275 T270,300"
                    />

                    <!-- Continuous spiral connections weaving through all radial lines -->
                    <!-- Inner spiral 1 - closest to corner -->
                    <path
                        d="M15,0 Q18,5 15,10 Q12,12 10,15 Q8,14 5,18 Q3,15 0,20"
                        stroke-width="0.5"
                    />
                    <!-- Inner spiral 2 -->
                    <path
                        d="M30,0 Q35,8 32,18 Q28,22 25,28 Q20,26 15,35 Q10,30 0,40"
                        stroke-width="0.5"
                    />
                    <!-- Spiral 3 -->
                    <path
                        d="M45,0 Q52,12 48,28 Q42,35 38,45 Q30,42 22,55 Q15,48 0,60"
                        stroke-width="0.5"
                    />
                    <!-- Spiral 4 -->
                    <path
                        d="M60,0 Q70,16 65,38 Q58,48 52,62 Q40,58 30,75 Q20,65 0,80"
                        stroke-width="0.5"
                    />
                    <!-- Spiral 5 -->
                    <path
                        d="M75,0 Q88,20 82,48 Q72,60 65,78 Q50,72 38,95 Q25,82 0,100"
                        stroke-width="0.5"
                    />
                    <!-- Spiral 6 -->
                    <path
                        d="M90,0 Q105,24 98,58 Q85,72 78,95 Q60,88 45,115 Q30,100 0,120"
                        stroke-width="0.5"
                    />
                    <!-- Spiral 7 -->
                    <path
                        d="M105,0 Q122,28 115,68 Q100,85 90,112 Q70,105 52,138 Q35,120 0,140"
                        stroke-width="0.5"
                    />
                    <!-- Spiral 8 -->
                    <path
                        d="M120,0 Q140,32 132,78 Q115,98 105,130 Q82,122 62,160 Q42,140 0,160"
                        stroke-width="0.5"
                    />
                    <!-- Spiral 9 -->
                    <path
                        d="M135,0 Q158,36 150,88 Q130,110 118,148 Q92,138 70,180 Q48,158 0,180"
                        stroke-width="0.5"
                    />
                    <!-- Spiral 10 -->
                    <path
                        d="M150,0 Q175,40 168,98 Q145,122 132,165 Q102,155 78,200 Q55,178 0,200"
                        stroke-width="0.5"
                    />
                    <!-- Spiral 11 -->
                    <path
                        d="M165,0 Q192,44 185,108 Q160,135 145,182 Q112,172 85,220 Q60,198 0,220"
                        stroke-width="0.5"
                    />
                    <!-- Spiral 12 -->
                    <path
                        d="M180,0 Q210,48 202,118 Q175,148 158,200 Q122,190 92,240 Q65,218 0,240"
                        stroke-width="0.5"
                    />
                    <!-- Spiral 13 -->
                    <path
                        d="M195,0 Q228,52 220,128 Q190,160 172,218 Q132,208 100,260 Q70,238 0,260"
                        stroke-width="0.5"
                    />
                    <!-- Spiral 14 -->
                    <path
                        d="M210,0 Q245,56 238,138 Q205,172 185,235 Q142,225 108,278 Q75,255 0,280"
                        stroke-width="0.5"
                    />
                    <!-- Outer spiral 15 -->
                    <path
                        d="M225,0 Q262,60 255,148 Q220,185 198,252 Q152,242 115,295 Q80,272 0,300"
                        stroke-width="0.5"
                    />
                </g>

                <!-- Dew drops on web with staggered shimmer -->
                <g class="dew-drops" fill="var(--web-color)">
                    <circle
                        class="dew-drop-1"
                        cx="45"
                        cy="45"
                        r="1.5"
                        opacity="0.6"
                    />
                    <circle
                        class="dew-drop-2"
                        cx="90"
                        cy="30"
                        r="1"
                        opacity="0.5"
                    />
                    <circle
                        class="dew-drop-3"
                        cx="135"
                        cy="45"
                        r="1.2"
                        opacity="0.7"
                    />
                    <circle
                        class="dew-drop-1"
                        cx="180"
                        cy="60"
                        r="0.8"
                        opacity="0.4"
                    />
                    <circle
                        class="dew-drop-2"
                        cx="225"
                        cy="75"
                        r="1.5"
                        opacity="0.6"
                    />
                    <circle
                        class="dew-drop-4"
                        cx="60"
                        cy="90"
                        r="1"
                        opacity="0.5"
                    />
                    <circle
                        class="dew-drop-3"
                        cx="120"
                        cy="90"
                        r="1.3"
                        opacity="0.7"
                    />
                    <circle
                        class="dew-drop-1"
                        cx="165"
                        cy="105"
                        r="0.9"
                        opacity="0.4"
                    />
                    <circle
                        class="dew-drop-2"
                        cx="210"
                        cy="120"
                        r="1.1"
                        opacity="0.6"
                    />
                    <circle
                        class="dew-drop-4"
                        cx="75"
                        cy="135"
                        r="1.4"
                        opacity="0.5"
                    />
                    <circle
                        class="dew-drop-3"
                        cx="150"
                        cy="135"
                        r="1"
                        opacity="0.7"
                    />
                    <circle
                        class="dew-drop-1"
                        cx="195"
                        cy="150"
                        r="1.2"
                        opacity="0.4"
                    />
                    <circle
                        class="dew-drop-2"
                        cx="90"
                        cy="180"
                        r="0.8"
                        opacity="0.6"
                    />
                    <circle
                        class="dew-drop-4"
                        cx="135"
                        cy="195"
                        r="1.5"
                        opacity="0.5"
                    />
                    <circle
                        class="dew-drop-3"
                        cx="180"
                        cy="210"
                        r="1"
                        opacity="0.7"
                    />
                    <circle
                        class="dew-drop-1"
                        cx="35"
                        cy="70"
                        r="1.1"
                        opacity="0.5"
                    />
                    <circle
                        class="dew-drop-2"
                        cx="110"
                        cy="110"
                        r="0.9"
                        opacity="0.6"
                    />
                    <circle
                        class="dew-drop-4"
                        cx="170"
                        cy="140"
                        r="1.3"
                        opacity="0.4"
                    />
                    <circle
                        class="dew-drop-3"
                        cx="200"
                        cy="100"
                        r="0.7"
                        opacity="0.5"
                    />
                    <circle
                        class="dew-drop-1"
                        cx="55"
                        cy="150"
                        r="1.2"
                        opacity="0.6"
                    />
                    <circle
                        class="dew-drop-2"
                        cx="100"
                        cy="170"
                        r="1"
                        opacity="0.5"
                    />
                    <circle
                        class="dew-drop-4"
                        cx="145"
                        cy="175"
                        r="1.4"
                        opacity="0.7"
                    />
                    <circle
                        class="dew-drop-3"
                        cx="220"
                        cy="140"
                        r="0.8"
                        opacity="0.4"
                    />
                    <circle
                        class="dew-drop-1"
                        cx="250"
                        cy="110"
                        r="1.1"
                        opacity="0.6"
                    />
                </g>

                <!-- Spider -->
                <g class="spider-group">
                    <g
                        class="spider-body"
                        :transform="`translate(${spiderBodyX}, ${spiderBodyY})`"
                    >
                        <!-- Spider abdomen (large body) -->
                        <ellipse
                            cx="8"
                            cy="2"
                            rx="7"
                            ry="5.5"
                            fill="var(--spider-color)"
                        />
                        <!-- Spider cephalothorax (head area) -->
                        <circle
                            cx="0"
                            cy="-2"
                            r="4.5"
                            fill="var(--spider-color)"
                        />

                        <!-- Spider eyes -->
                        <g class="spider-eyes">
                            <!-- Main eyes -->
                            <ellipse
                                cx="-2"
                                cy="-3.5"
                                rx="1.5"
                                ry="1.8"
                                fill="var(--spider-eye-color)"
                            />
                            <ellipse
                                cx="2"
                                cy="-3.5"
                                rx="1.5"
                                ry="1.8"
                                fill="var(--spider-eye-color)"
                            />
                            <!-- Smaller side eyes -->
                            <circle
                                cx="-3.5"
                                cy="-1.5"
                                r="0.8"
                                fill="var(--spider-eye-color)"
                            />
                            <circle
                                cx="3.5"
                                cy="-1.5"
                                r="0.8"
                                fill="var(--spider-eye-color)"
                            />
                        </g>

                        <!-- Legs - left side (L1-L4) -->
                        <path
                            class="spider-leg-l1"
                            d="M-3,-3 Q-12,-12 -18,-8"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1.2"
                            stroke-linecap="round"
                        />
                        <path
                            class="spider-leg-l2"
                            d="M-3,-2 Q-14,-6 -20,-2"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1.2"
                            stroke-linecap="round"
                        />
                        <path
                            class="spider-leg-l3"
                            d="M-3,-1 Q-14,0 -22,2"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1.2"
                            stroke-linecap="round"
                        />
                        <path
                            class="spider-leg-l4"
                            d="M-3,0 Q-12,4 -18,8"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1.2"
                            stroke-linecap="round"
                        />

                        <!-- Legs - right side (R1-R4) -->
                        <path
                            class="spider-leg-r1"
                            d="M3,-3 Q12,-12 18,-8"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1.2"
                            stroke-linecap="round"
                        />
                        <path
                            class="spider-leg-r2"
                            d="M3,-2 Q14,-6 20,-2"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1.2"
                            stroke-linecap="round"
                        />
                        <path
                            class="spider-leg-r3"
                            d="M3,-1 Q14,0 22,2"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1.2"
                            stroke-linecap="round"
                        />
                        <path
                            class="spider-leg-r4"
                            d="M3,0 Q12,4 18,8"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1.2"
                            stroke-linecap="round"
                        />
                    </g>
                </g>
            </svg>
        </div>
    </Transition>
</template>

<style scoped>
.spiderweb-container {
    position: fixed;
    top: 0;
    right: 0;
    width: 300px;
    height: 300px;
    pointer-events: none;
    z-index: 2;
    opacity: 0.85;
}

.web-fog {
    animation: fog-pulse 6s ease-in-out infinite;
}

@keyframes fog-pulse {
    0%,
    100% {
        opacity: 0.4;
    }
    50% {
        opacity: 0.8;
    }
}

/* Staggered dew drop shimmer animations */
.dew-drop-1 {
    animation: dew-shimmer-1 4s ease-in-out infinite;
}

.dew-drop-2 {
    animation: dew-shimmer-2 5s ease-in-out infinite 1s;
}

.dew-drop-3 {
    animation: dew-shimmer-3 6s ease-in-out infinite 2s;
}

.dew-drop-4 {
    animation: dew-shimmer-4 4.5s ease-in-out infinite 0.5s;
}

@keyframes dew-shimmer-1 {
    0%,
    100% {
        opacity: 0.3;
    }
    50% {
        opacity: 0.8;
    }
}

@keyframes dew-shimmer-2 {
    0%,
    100% {
        opacity: 0.4;
    }
    50% {
        opacity: 0.9;
    }
}

@keyframes dew-shimmer-3 {
    0%,
    100% {
        opacity: 0.2;
    }
    50% {
        opacity: 0.7;
    }
}

@keyframes dew-shimmer-4 {
    0%,
    100% {
        opacity: 0.35;
    }
    50% {
        opacity: 0.85;
    }
}

.spiderweb-svg {
    width: 100%;
    height: 100%;
    transform: scaleX(-1);
}

.web-strands {
    opacity: 0.8;
    animation: web-pulse 4s ease-in-out infinite;
}

@keyframes web-pulse {
    0%,
    100% {
        opacity: 0.6;
    }
    50% {
        opacity: 1;
    }
}

.spider-group {
    transition: all 2s ease-in-out;
    transform-origin: 0 0;
}

.spider-body {
    transition: transform 2s ease-in-out;
}

.spider-eyes {
    animation: eyes-blink 3s ease-in-out infinite;
}

@keyframes eyes-blink {
    0%,
    85%,
    95%,
    100% {
        opacity: 1;
    }
    90% {
        opacity: 0.2;
    }
}

.spider-leg-l1 {
    animation: crawl-l1 1.5s ease-in-out infinite;
    transform-origin: -3px -3px;
}

.spider-leg-l2 {
    animation: crawl-l2 1.5s ease-in-out infinite;
    transform-origin: -3px -2px;
}

.spider-leg-l3 {
    animation: crawl-l3 1.5s ease-in-out infinite;
    transform-origin: -3px -1px;
}

.spider-leg-l4 {
    animation: crawl-l4 1.5s ease-in-out infinite;
    transform-origin: -3px 0px;
}

.spider-leg-r1 {
    animation: crawl-r1 1.5s ease-in-out infinite;
    transform-origin: 3px -3px;
}

.spider-leg-r2 {
    animation: crawl-r2 1.5s ease-in-out infinite;
    transform-origin: 3px -2px;
}

.spider-leg-r3 {
    animation: crawl-r3 1.5s ease-in-out infinite;
    transform-origin: 3px -1px;
}

.spider-leg-r4 {
    animation: crawl-r4 1.5s ease-in-out infinite;
    transform-origin: 3px 0px;
}

/* Left legs crawl animation - lift and reach forward */
@keyframes crawl-l1 {
    0%,
    100% {
        d: path("M-3,-3 Q-12,-12 -18,-8");
    }
    25% {
        d: path("M-3,-3 Q-10,-14 -16,-12");
    }
    50% {
        d: path("M-3,-3 Q-14,-10 -20,-6");
    }
    75% {
        d: path("M-3,-3 Q-11,-11 -17,-9");
    }
}

@keyframes crawl-l2 {
    0%,
    100% {
        d: path("M-3,-2 Q-14,-6 -20,-2");
    }
    25% {
        d: path("M-3,-2 Q-12,-8 -18,-5");
    }
    50% {
        d: path("M-3,-2 Q-15,-4 -21,0");
    }
    75% {
        d: path("M-3,-2 Q-13,-6 -19,-3");
    }
}

@keyframes crawl-l3 {
    0%,
    100% {
        d: path("M-3,-1 Q-14,0 -22,2");
    }
    25% {
        d: path("M-3,-1 Q-15,-2 -23,0");
    }
    50% {
        d: path("M-3,-1 Q-13,2 -21,4");
    }
    75% {
        d: path("M-3,-1 Q-14,1 -22,3");
    }
}

@keyframes crawl-l4 {
    0%,
    100% {
        d: path("M-3,0 Q-12,4 -18,8");
    }
    25% {
        d: path("M-3,0 Q-13,2 -19,5");
    }
    50% {
        d: path("M-3,0 Q-11,6 -17,10");
    }
    75% {
        d: path("M-3,0 Q-12,5 -18,9");
    }
}

/* Right legs crawl animation - alternating with left legs */
@keyframes crawl-r1 {
    0%,
    100% {
        d: path("M3,-3 Q12,-12 18,-8");
    }
    25% {
        d: path("M3,-3 Q11,-11 17,-9");
    }
    50% {
        d: path("M3,-3 Q14,-10 20,-6");
    }
    75% {
        d: path("M3,-3 Q10,-14 16,-12");
    }
}

@keyframes crawl-r2 {
    0%,
    100% {
        d: path("M3,-2 Q14,-6 20,-2");
    }
    25% {
        d: path("M3,-2 Q13,-6 19,-3");
    }
    50% {
        d: path("M3,-2 Q15,-4 21,0");
    }
    75% {
        d: path("M3,-2 Q12,-8 18,-5");
    }
}

@keyframes crawl-r3 {
    0%,
    100% {
        d: path("M3,-1 Q14,0 22,2");
    }
    25% {
        d: path("M3,-1 Q14,1 22,3");
    }
    50% {
        d: path("M3,-1 Q13,2 21,4");
    }
    75% {
        d: path("M3,-1 Q15,-2 23,0");
    }
}

@keyframes crawl-r4 {
    0%,
    100% {
        d: path("M3,0 Q12,4 18,8");
    }
    25% {
        d: path("M3,0 Q12,5 18,9");
    }
    50% {
        d: path("M3,0 Q11,6 17,10");
    }
    75% {
        d: path("M3,0 Q13,2 19,5");
    }
}

.spiderweb-fade-enter-active,
.spiderweb-fade-leave-active {
    transition: opacity 0.8s ease;
}

.spiderweb-fade-enter-from,
.spiderweb-fade-leave-to {
    opacity: 0;
}
</style>
