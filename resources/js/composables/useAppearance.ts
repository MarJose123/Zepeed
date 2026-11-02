import { usePage } from "@inertiajs/vue3";
import type { ComputedRef, Ref } from "vue";
import { computed, onMounted, ref } from "vue";
import type { Appearance, ResolvedAppearance, SeasonalTheme } from "@/types";

export type { Appearance, ResolvedAppearance, SeasonalTheme };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
    seasonalTheme: Ref<SeasonalTheme>;
};

export function updateTheme(value: Appearance): void {
    if (typeof window === "undefined") {
        return;
    }

    if (value === "system") {
        const mediaQueryList = window.matchMedia(
            "(prefers-color-scheme: dark)",
        );
        const systemTheme = mediaQueryList.matches ? "dark" : "light";

        document.documentElement.classList.toggle(
            "dark",
            systemTheme === "dark",
        );
    } else {
        document.documentElement.classList.toggle("dark", value === "dark");
    }
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === "undefined") {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === "undefined") {
        return null;
    }

    return window.matchMedia("(prefers-color-scheme: dark)");
};

const getStoredAppearance = () => {
    if (typeof window === "undefined") {
        return null;
    }

    return localStorage.getItem("appearance") as Appearance | null;
};

const prefersDark = (): boolean => {
    if (typeof window === "undefined") {
        return false;
    }

    return window.matchMedia("(prefers-color-scheme: dark)").matches;
};

const handleSystemThemeChange = () => {
    const currentAppearance = getStoredAppearance();

    updateTheme(currentAppearance || "system");
};

export function initializeTheme(): void {
    if (typeof window === "undefined") {
        return;
    }

    // Initialize theme from saved preference or default to system...
    const savedAppearance = getStoredAppearance();
    updateTheme(savedAppearance || "system");

    // Set up system theme change listener...
    mediaQuery()?.addEventListener("change", handleSystemThemeChange);
}

const appearance = ref<Appearance>("system");
const seasonalTheme = ref<SeasonalTheme>("none");

export function updateSeasonalThemeClass(value: SeasonalTheme): void {
    if (typeof window === "undefined") {
        return;
    }

    // Remove any existing seasonal theme classes
    document.documentElement.classList.remove(
        "theme-halloween",
        "theme-christmas",
        "theme-newyear",
    );

    // Add the new seasonal theme class if not "none"
    if (value !== "none") {
        document.documentElement.classList.add(`theme-${value}`);
    }
}

export function useAppearance(): UseAppearanceReturn {
    const page = usePage();

    onMounted(() => {
        const savedAppearance = localStorage.getItem(
            "appearance",
        ) as Appearance | null;

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }

        // Read seasonal theme from Inertia shared data (without applying class)
        const serverSeasonalTheme = page.props.appearance?.seasonalTheme;

        if (serverSeasonalTheme) {
            seasonalTheme.value = serverSeasonalTheme as SeasonalTheme;
        }
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        if (appearance.value === "system") {
            return prefersDark() ? "dark" : "light";
        }

        return appearance.value;
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem("appearance", value);

        // Store in cookie for SSR...
        setCookie("appearance", value);

        updateTheme(value);
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
        seasonalTheme,
    };
}
