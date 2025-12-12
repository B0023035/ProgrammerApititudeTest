<script setup lang="ts">
import { defineProps, ref } from "vue";

const props = defineProps<{
    section: number;
    questions: any[];
}>();

const answers = ref<{ [key: number]: string }>({});
</script>

<template>
    <div>
        <h2>練習問題 第{{ props.section }}部</h2>

        <div v-for="(q, index) in props.questions" :key="q.id" class="question-block">
            <p>{{ index + 1 }}. {{ q.question }}</p>

            <div v-for="choice in q.choices" :key="choice.id">
                <label>
                    <input
                        type="radio"
                        :name="'q' + q.id"
                        :value="choice.label"
                        v-model="answers[q.id]"
                    />
                    {{ choice.label }}: {{ choice.text }}
                </label>
            </div>
        </div>

        <pre>{{ answers }}</pre>
    </div>
</template>
