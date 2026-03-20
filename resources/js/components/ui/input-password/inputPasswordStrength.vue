<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import {
    EyeIcon,
    EyeOffIcon,
    CheckCircle2Icon,
    XCircleIcon,
    AlertCircleIcon,
} from "lucide-vue-next";

import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Field, FieldGroup, FieldLabel } from "@/components/ui/field";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";

// Define props
const props = defineProps<{
    id: string;
    name: string;
    label?: string;
    placeholder?: string;
    class?: string;
    required?: boolean;
    tabindex?: number;
    autocomplete?: string;
}>();

const _typePassword = ref<string>("");

// Define emits
const emits = defineEmits<{
    (e: "update:modelValue", value: string): void;
}>();

// Create a ref for the password and bind it to modelValue
const password = computed({
    get: () => _typePassword.value || "",
    set: (value) => {
        _typePassword.value = value;
        emits("update:modelValue", value);
    },
});

// Password visibility state
const isVisible = ref(false);

// Toggle password visibility
const toggleVisibility = () => {
    isVisible.value = !isVisible.value;
};

const resetStrength = () => {
    _typePassword.value = "";
};

// Password strength checking
const checkStrength = (pass: string) => {
    const requirements = [
        { regex: /.{8,}/, text: "At least 8 characters" },
        { regex: /[0-9]/, text: "At least 1 number" },
        { regex: /[a-z]/, text: "At least 1 lowercase letter" },
        { regex: /[A-Z]/, text: "At least 1 uppercase letter" },
        {
            regex: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/,
            text: "At least 1 symbol",
        },
    ];

    return requirements.map((req) => ({
        met: req.regex.test(pass),
        text: req.text,
    }));
};

// Compute strength based on password
const strength = computed(() => checkStrength(password.value));

// Compute strength score
const strengthScore = computed(() => {
    return strength.value.filter((req) => req.met).length;
});

// Get color based on strength score
const getStrengthColor = (score: number) => {
    if (score === 0) return "bg-border";
    if (score <= 1) return "bg-red-500";
    if (score <= 2) return "bg-orange-500";
    if (score <= 3) return "bg-amber-500";
    return "bg-emerald-500";
};

// Get text description based on strength score
const getStrengthText = (score: number) => {
    if (score === 0) return "Enter a password";
    if (score <= 2) return "Weak password";
    if (score <= 3) return "Medium password";
    return "Strong password";
};

// Default export
defineExpose({
    password,
    strengthScore,
    isVisible,
    toggleVisibility,
    resetStrength,
});
</script>

<template>
    <div :class="props.class">
        <!-- Password input field with toggle visibility button -->
        <div class="relative">
            <Input
                :id="id"
                :name="name"
                class="pe-9"
                :placeholder="placeholder"
                :type="isVisible ? 'text' : 'password'"
                v-model="password"
                :required="required"
                :tabindex="tabindex"
                :autocomplete="autocomplete"
                :aria-describedby="`${id}-description`"
            />
            <button
                class="text-muted-foreground/80 hover:text-foreground focus-visible:border-ring focus-visible:ring-ring/50 absolute inset-y-0 end-0 flex h-full w-9 items-center justify-center rounded-e-md transition-[color,box-shadow] outline-none focus:z-10 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
                type="button"
                @click="toggleVisibility"
                :aria-label="isVisible ? 'Hide password' : 'Show password'"
                :aria-pressed="isVisible"
                aria-controls="password"
            >
                <EyeOffIcon v-if="isVisible" :size="16" aria-hidden="true" />
                <EyeIcon v-else :size="16" aria-hidden="true" />
            </button>
        </div>

        <!-- Password strength indicator -->
        <div
            class="bg-border mt-2 mb-2 h-1 w-full overflow-hidden rounded-full"
            role="progressbar"
            :aria-valuenow="strengthScore"
            aria-valuemin="0"
            aria-valuemax="5"
            aria-label="Password strength"
        >
            <div
                :class="`h-full ${getStrengthColor(strengthScore)} transition-all duration-500 ease-out`"
                :style="{ width: `${(strengthScore / 5) * 100}%` }"
            ></div>
        </div>

        <slot name="FieldError" />

        <!-- Password requirements list -->
        <Alert>
            <AlertTitle
                >{{ getStrengthText(strengthScore) }}. Must contain:</AlertTitle
            >
            <AlertDescription>
                <ul
                    class="mt-2 list-inside list-disc space-y-1"
                    aria-label="Password requirements"
                >
                    <li
                        v-for="(req, index) in strength"
                        :key="index"
                        class="flex items-center gap-2"
                    >
                        <CheckCircle2Icon
                            v-if="req.met"
                            :size="16"
                            class="text-emerald-500"
                            aria-hidden="true"
                        />
                        <XCircleIcon
                            v-else
                            :size="16"
                            class="text-muted-foreground/80"
                            aria-hidden="true"
                        />
                        <span
                            :class="`text-xs ${req.met ? 'text-emerald-600' : 'text-muted-foreground'}`"
                        >
                            {{ req.text }}
                            <span class="sr-only">
                                {{
                                    req.met
                                        ? " - Requirement met"
                                        : " - Requirement not met"
                                }}
                            </span>
                        </span>
                    </li>
                </ul>
            </AlertDescription>
        </Alert>
    </div>
</template>
