<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from "vue";
import { useAppearance } from "@/composables/useAppearance";

const { seasonalTheme } = useAppearance();

interface DroppingSpider {
    id: number;
    x: number;
    threadLength: number;
    maxDrop: number;
    phase: "hiding" | "dropping" | "waiting" | "climbing" | "gone";
    dropSpeed: number;
    climbSpeed: number;
    waitTime: number;
    hideTime: number;
    size: number;
}

const spiders = ref<DroppingSpider[]>([]);
let nextId = 0;
let intervalId: number | null = null;
let animationFrameId: number | null = null;

const MAX_SPIDERS = 3;

function createSpider(): DroppingSpider {
    return {
        id: nextId++,
        x: 10 + Math.random() * 80,
        threadLength: 0,
        maxDrop: 25 + Math.random() * 35,
        phase: "dropping",
        dropSpeed: 1.5 + Math.random() * 2.5,
        climbSpeed: 3 + Math.random() * 4,
        waitTime: 2000 + Math.random() * 3000,
        hideTime: 5000 + Math.random() * 10000,
        size: 0.7 + Math.random() * 0.5,
    };
}

function spawnSpider() {
    if (spiders.value.length < MAX_SPIDERS) {
        spiders.value.push(createSpider());
    }
}

let lastTime = 0;
function animate(timestamp: number) {
    if (lastTime === 0) {
        lastTime = timestamp;
    }

    const delta = timestamp - lastTime;
    lastTime = timestamp;

    for (let i = spiders.value.length - 1; i >= 0; i--) {
        const spider = spiders.value[i];

        switch (spider.phase) {
            case "dropping":
                spider.threadLength += spider.dropSpeed * (delta / 16);

                if (spider.threadLength >= spider.maxDrop) {
                    spider.threadLength = spider.maxDrop;
                    spider.phase = "waiting";
                    spider.waitTime = 1000 + Math.random() * 2000;
                }

                break;

            case "waiting":
                spider.waitTime -= delta;

                if (spider.waitTime <= 0) {
                    spider.phase = "climbing";
                }

                break;

            case "climbing":
                spider.threadLength -= spider.climbSpeed * (delta / 16);

                if (spider.threadLength <= 0) {
                    spider.threadLength = 0;
                    spider.phase = "gone";
                    spiders.value.splice(i, 1);
                }

                break;
        }
    }

    animationFrameId = requestAnimationFrame(animate);
}

function startSpawning() {
    spawnSpider();
    intervalId = window.setInterval(
        () => {
            if (spiders.value.length < MAX_SPIDERS) {
                spawnSpider();
            }
        },
        4000 + Math.random() * 6000,
    );
}

function startAnimation() {
    startSpawning();
    animationFrameId = requestAnimationFrame(animate);
}

function stopAnimation() {
    if (intervalId !== null) {
        clearInterval(intervalId);
        intervalId = null;
    }

    if (animationFrameId !== null) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
    }

    spiders.value = [];
}

onMounted(() => {
    if (seasonalTheme.value === "halloween") {
        startAnimation();
    }
});

watch(seasonalTheme, (theme) => {
    if (theme === "halloween") {
        startAnimation();
    } else {
        stopAnimation();
    }
});

onUnmounted(() => {
    stopAnimation();
});

const spiderBodyX = (spider: DroppingSpider) => spider.x + "%";
</script>

<template>
    <TransitionGroup
        v-if="seasonalTheme === 'halloween'"
        name="dropping-spider"
        tag="div"
        class="dropping-spiders-container"
    >
        <div
            v-for="spider in spiders"
            :key="spider.id"
            class="dropping-spider-wrapper"
            :style="{ left: spiderBodyX(spider) }"
        >
            <svg
                class="dropping-spider-svg"
                :viewBox="`0 0 100 ${spider.threadLength + 50}`"
                :style="{ height: spider.threadLength + 5 + '%' }"
                xmlns="http://www.w3.org/2000/svg"
            >
                <!-- Web thread from top -->
                <line
                    x1="50"
                    y1="0"
                    :x2="50"
                    :y2="spider.threadLength"
                    stroke="var(--web-color)"
                    stroke-width="0.8"
                    opacity="0.8"
                />

                <!-- Spider body with pulse glow -->
                <g :transform="`translate(50, ${spider.threadLength})`">
                    <g
                        class="dropping-spider-body"
                        :style="{
                            transform: `scale(${spider.size})`,
                            transformOrigin: '0 0',
                        }"
                    >
                        <!-- Abdomen -->
                        <ellipse
                            cx="0"
                            cy="8"
                            rx="6"
                            ry="5"
                            fill="var(--spider-color)"
                        />
                        <!-- Head -->
                        <circle
                            cx="0"
                            cy="0"
                            r="4"
                            fill="var(--spider-color)"
                        />

                        <!-- Red eyes -->
                        <ellipse
                            cx="-1.5"
                            cy="-1"
                            rx="1.2"
                            ry="1.5"
                            fill="var(--spider-eye-color)"
                        />
                        <ellipse
                            cx="1.5"
                            cy="-1"
                            rx="1.2"
                            ry="1.5"
                            fill="var(--spider-eye-color)"
                        />

                        <!-- Left legs with crawling animation -->
                        <path
                            class="drop-leg-l1"
                            d="M-2,-2 Q-8,-8 -14,-4"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1"
                            stroke-linecap="round"
                        />
                        <path
                            class="drop-leg-l2"
                            d="M-2,-1 Q-10,-2 -16,2"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1"
                            stroke-linecap="round"
                        />
                        <path
                            class="drop-leg-l3"
                            d="M-2,0 Q-10,2 -16,6"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1"
                            stroke-linecap="round"
                        />
                        <path
                            class="drop-leg-l4"
                            d="M-2,1 Q-8,6 -12,10"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1"
                            stroke-linecap="round"
                        />

                        <!-- Right legs with crawling animation -->
                        <path
                            class="drop-leg-r1"
                            d="M2,-2 Q8,-8 14,-4"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1"
                            stroke-linecap="round"
                        />
                        <path
                            class="drop-leg-r2"
                            d="M2,-1 Q10,-2 16,2"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1"
                            stroke-linecap="round"
                        />
                        <path
                            class="drop-leg-r3"
                            d="M2,0 Q10,2 16,6"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1"
                            stroke-linecap="round"
                        />
                        <path
                            class="drop-leg-r4"
                            d="M2,1 Q8,6 12,10"
                            stroke="var(--spider-color)"
                            fill="none"
                            stroke-width="1"
                            stroke-linecap="round"
                        />
                    </g>
                </g>
            </svg>
        </div>
    </TransitionGroup>
</template>

<style scoped>
.dropping-spiders-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.dropping-spider-wrapper {
    position: absolute;
    top: 0;
    transform: translateX(-50%);
}

.dropping-spider-svg {
    width: 100px;
    display: block;
}

.dropping-spider-enter-active {
    transition: opacity 0.5s ease;
}

.dropping-spider-leave-active {
    transition: opacity 0.3s ease;
}

.dropping-spider-enter-from,
.dropping-spider-leave-to {
    opacity: 0;
}

/* Crawling leg animations for dropping spiders */
.drop-leg-l1 {
    animation: drop-crawl-l1 1.2s ease-in-out infinite;
    transform-origin: -2px -2px;
}

.drop-leg-l2 {
    animation: drop-crawl-l2 1.2s ease-in-out infinite;
    transform-origin: -2px -1px;
}

.drop-leg-l3 {
    animation: drop-crawl-l3 1.2s ease-in-out infinite;
    transform-origin: -2px 0px;
}

.drop-leg-l4 {
    animation: drop-crawl-l4 1.2s ease-in-out infinite;
    transform-origin: -2px 1px;
}

.drop-leg-r1 {
    animation: drop-crawl-r1 1.2s ease-in-out infinite;
    transform-origin: 2px -2px;
}

.drop-leg-r2 {
    animation: drop-crawl-r2 1.2s ease-in-out infinite;
    transform-origin: 2px -1px;
}

.drop-leg-r3 {
    animation: drop-crawl-r3 1.2s ease-in-out infinite;
    transform-origin: 2px 0px;
}

.drop-leg-r4 {
    animation: drop-crawl-r4 1.2s ease-in-out infinite;
    transform-origin: 2px 1px;
}

@keyframes drop-crawl-l1 {
    0%,
    100% {
        d: path("M-2,-2 Q-8,-8 -14,-4");
    }
    50% {
        d: path("M-2,-2 Q-6,-10 -12,-7");
    }
}

@keyframes drop-crawl-l2 {
    0%,
    100% {
        d: path("M-2,-1 Q-10,-2 -16,2");
    }
    50% {
        d: path("M-2,-1 Q-8,-4 -14,0");
    }
}

@keyframes drop-crawl-l3 {
    0%,
    100% {
        d: path("M-2,0 Q-10,2 -16,6");
    }
    50% {
        d: path("M-2,0 Q-8,4 -14,8");
    }
}

@keyframes drop-crawl-l4 {
    0%,
    100% {
        d: path("M-2,1 Q-8,6 -12,10");
    }
    50% {
        d: path("M-2,1 Q-6,8 -10,12");
    }
}

@keyframes drop-crawl-r1 {
    0%,
    100% {
        d: path("M2,-2 Q8,-8 14,-4");
    }
    50% {
        d: path("M2,-2 Q6,-10 12,-7");
    }
}

@keyframes drop-crawl-r2 {
    0%,
    100% {
        d: path("M2,-1 Q10,-2 16,2");
    }
    50% {
        d: path("M2,-1 Q8,-4 14,0");
    }
}

@keyframes drop-crawl-r3 {
    0%,
    100% {
        d: path("M2,0 Q10,2 16,6");
    }
    50% {
        d: path("M2,0 Q8,4 14,8");
    }
}

@keyframes drop-crawl-r4 {
    0%,
    100% {
        d: path("M2,1 Q8,6 12,10");
    }
    50% {
        d: path("M2,1 Q6,8 10,12");
    }
}
</style>
