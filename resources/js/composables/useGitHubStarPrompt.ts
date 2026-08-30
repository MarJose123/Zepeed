import { usePage } from "@inertiajs/vue3";
import type { Ref } from "vue";
import { onMounted, onUnmounted, ref } from "vue";
import type { Auth } from "@/types/auth";

export type UseGitHubStarPromptReturn = {
    isOpen: Ref<boolean>;
    githubStarUrl: Ref<string | null>;
    dismiss: () => void;
    star: () => void;
};

/**
 * Browser storage keys for the GitHub star prompt.
 *
 * - localStorage `zepeed.github_star_prompt`: records the last date the
 *   dialog was shown, so it appears at most once per calendar day.
 * - localStorage `zepeed.github_star_prompt.scheduled_at`: when the dialog
 *   becomes eligible — a randomized 4–15 minutes after the user logged in.
 * - sessionStorage `zepeed.github_star_prompt.dismissed`: records that the
 *   user dismissed it, keeping it hidden for the rest of the browser session.
 */
const DAILY_STATE_KEY = "zepeed.github_star_prompt";
const SCHEDULED_AT_KEY = "zepeed.github_star_prompt.scheduled_at";
const SESSION_DISMISS_KEY = "zepeed.github_star_prompt.dismissed";

/**
 * The prompt is never shown earlier than 4 minutes after login, and the
 * exact moment is randomized within 4–15 minutes so it never feels fixed.
 */
const MIN_DELAY_MS = 4 * 60 * 1000;
const MAX_DELAY_MS = 15 * 60 * 1000;

/**
 * Small buffer before showing an already-eligible dialog so the page paints
 * and the user can interact before it appears.
 */
const SHOW_DELAY_MS = 1500;

/**
 * Dev-only delay bounds so the prompt can be tested quickly. In development
 * the toast appears 2–10 seconds after load instead of the 4–15 minute
 * production countdown. These values are never used outside `import.meta.env.DEV`.
 */
const DEV_MIN_DELAY_MS = 2 * 1000;
const DEV_MAX_DELAY_MS = 10 * 1000;

/**
 * Referral tag appended to the repository URL as a `ref=` query parameter
 * (the same convention the sidebar's GitHub links use). Browsers cannot set
 * the HTTP Referer header to an arbitrary string, so this tag is what the
 * repository owner sees as the source of the star click in referral analytics.
 */
const REFERRAL_TAG = "zepeed-app";

type DailyState = {
    lastShownDate: string | null;
};

function todayKey(): string {
    return new Date().toISOString().slice(0, 10);
}

function readDailyState(): DailyState {
    try {
        const raw = localStorage.getItem(DAILY_STATE_KEY);

        return raw ? (JSON.parse(raw) as DailyState) : { lastShownDate: null };
    } catch {
        return { lastShownDate: null };
    }
}

function hasShownToday(): boolean {
    return readDailyState().lastShownDate === todayKey();
}

function markShownToday(): void {
    localStorage.setItem(
        DAILY_STATE_KEY,
        JSON.stringify({ lastShownDate: todayKey() }),
    );
}

function isDismissedForSession(): boolean {
    try {
        return sessionStorage.getItem(SESSION_DISMISS_KEY) === "1";
    } catch {
        return false;
    }
}

function markDismissedForSession(): void {
    sessionStorage.setItem(SESSION_DISMISS_KEY, "1");
}

function randomDelayMs(): number {
    return (
        Math.floor(Math.random() * (MAX_DELAY_MS - MIN_DELAY_MS + 1)) +
        MIN_DELAY_MS
    );
}

function randomDevDelayMs(): number {
    return (
        Math.floor(Math.random() * (DEV_MAX_DELAY_MS - DEV_MIN_DELAY_MS + 1)) +
        DEV_MIN_DELAY_MS
    );
}

function readScheduledAt(): number | null {
    try {
        const value = Number(localStorage.getItem(SCHEDULED_AT_KEY));

        return Number.isFinite(value) && value > 0 ? value : null;
    } catch {
        return null;
    }
}

function persistScheduledAt(timestamp: number): void {
    localStorage.setItem(SCHEDULED_AT_KEY, String(timestamp));
}

/**
 * Low-key diagnostic output explaining why the dialog is hidden or when it
 * is scheduled — visible in the browser console under the debug level.
 */
function debug(message: string): void {
    console.debug(`[github-star-prompt] ${message}`);
}

function formatRemaining(ms: number): string {
    const seconds = Math.ceil(ms / 1000);

    return seconds < 60 ? `${seconds} sec` : `${Math.round(ms / 60_000)} min`;
}

/**
 * Start the GitHub star prompt countdown after a successful login.
 *
 * Call this from every login path (login form, registration auto-login,
 * two-factor challenge). The dialog becomes eligible at a randomized
 * `MIN_DELAY_MS`–`MAX_DELAY_MS` after this moment.
 */
export function recordLoginTime(): void {
    persistScheduledAt(Date.now() + randomDelayMs());
}

/**
 * Drives the subtle "star the GitHub repository" dialog.
 *
 * The dialog is only ever considered for authenticated users with a
 * configured repository URL. It becomes eligible a randomized delay
 * after login (`recordLoginTime`, see `MIN_DELAY_MS`/`MAX_DELAY_MS`),
 * appears at most once per day, and stays hidden for the rest of the
 * session once dismissed.
 *
 * Users who were already logged in when the countdown was first introduced
 * have it anchored to their first authenticated page load instead.
 */
export function useGitHubStarPrompt(): UseGitHubStarPromptReturn {
    const page = usePage();
    const isOpen = ref(false);
    const githubStarUrl = ref<string | null>(
        (page.props.github_star_url as string | null | undefined) ?? null,
    );

    let timer: ReturnType<typeof setTimeout> | undefined;

    onMounted(() => {
        const auth = page.props.auth as Auth | undefined;

        if (!auth?.user) {
            debug("skipped: not authenticated");

            return;
        }

        if (!githubStarUrl.value) {
            debug(
                "skipped: no repository URL configured (github_star_url prop is null — check GITHUB_REPOSITORY_URL and that config is not cached)",
            );

            return;
        }

        // Dev-only fast path for testing: show the prompt 2–10 seconds after
        // load, ignoring the per-session dismissal and once-per-day limits so
        // it re-triggers on every dev reload. Never used in production.
        if (import.meta.env.DEV) {
            const devDelayMs = randomDevDelayMs();

            debug(
                `[dev] showing prompt in ${Math.round(devDelayMs / 1000)} sec for testing`,
            );
            timer = setTimeout(() => {
                isOpen.value = true;
                debug("[dev] dialog shown");
            }, devDelayMs);

            return;
        }

        if (isDismissedForSession()) {
            debug("skipped: dismissed earlier in this browser session");

            return;
        }

        if (hasShownToday()) {
            debug("skipped: already shown today (once-per-day limit)");

            return;
        }

        let scheduledAt = readScheduledAt();

        if (scheduledAt === null) {
            scheduledAt = Date.now() + randomDelayMs();
            persistScheduledAt(scheduledAt);
            debug(
                `no login recorded — scheduled for ${new Date(scheduledAt).toLocaleTimeString()} (${formatRemaining(scheduledAt - Date.now())} from now)`,
            );
        } else {
            const waitMs = scheduledAt - Date.now();

            debug(
                waitMs > 0
                    ? `scheduled for ${new Date(scheduledAt).toLocaleTimeString()} (${formatRemaining(waitMs)} from now)`
                    : `eligible now (scheduled for ${new Date(scheduledAt).toLocaleTimeString()}) — showing shortly`,
            );
        }

        const remainingMs = scheduledAt - Date.now();

        timer = setTimeout(
            () => {
                markShownToday();
                isOpen.value = true;
                debug("dialog shown");
            },
            remainingMs > 0 ? remainingMs : SHOW_DELAY_MS,
        );
    });

    onUnmounted(() => {
        if (timer !== undefined) {
            clearTimeout(timer);
        }
    });

    function dismiss(): void {
        markDismissedForSession();
        isOpen.value = false;
    }

    function star(): void {
        if (githubStarUrl.value) {
            const url = new URL(githubStarUrl.value);

            // noopener keeps the new tab from accessing window.opener, while
            // omitting noreferrer lets the request carry a Referer header;
            // the ref= query parameter identifies this app as the source.
            url.searchParams.set("ref", REFERRAL_TAG);

            window.open(url.toString(), "_blank", "noopener");
        }

        isOpen.value = false;
    }

    return { isOpen, githubStarUrl, dismiss, star };
}
