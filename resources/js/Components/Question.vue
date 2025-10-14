<template>
    <div class="question-card">
        <p>問題 {{ question.number }}: {{ question.text }}</p>

        <div class="choices">
            <button
                v-for="choice in question.choices"
                :key="choice.label"
                @click="selectChoice(choice.label)"
                class="choice-btn"
            >
                {{ choice.label }}. {{ choice.text || choice.image }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from "vue";

// Choice と Question の型定義
interface Choice {
    id: number;
    label: string;
    text?: string;
    image?: string | null;
    is_correct: boolean;
}

interface QuestionType {
    id: number;
    number: number;
    part: number;
    text: string;
    image?: string | null;
    choices: Choice[];
}

// Props
const props = defineProps<{
    question: QuestionType;
}>();

// Emit イベント
const emit = defineEmits<{
    (e: "answered", label: string): void;
}>();

function selectChoice(label: string) {
    emit("answered", label);
}
</script>

<style scoped>
.question-card {
    border: 1px solid #ccc;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 6px;
}
.choices {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.choice-btn {
    padding: 8px 12px;
    border-radius: 4px;
    background-color: #f0f0f0;
    border: 1px solid #ccc;
    cursor: pointer;
}
.choice-btn:hover {
    background-color: #e0e0ff;
}
</style>
