import vue from "eslint-plugin-vue";
import eslintPluginPrettier from "eslint-plugin-prettier";
import tsParser from "@typescript-eslint/parser";
import vueParser from "vue-eslint-parser";
import tseslint from "@typescript-eslint/eslint-plugin";

export default [
    {
        files: ["**/*.vue", "**/*.ts", "**/*.js"],
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                parser: tsParser,
                ecmaVersion: "latest",
                sourceType: "module",
                extraFileExtensions: [".vue"],
            },
        },
        plugins: {
            vue,
            prettier: eslintPluginPrettier,
            "@typescript-eslint": tseslint,
        },
        rules: {
            ...(vue.configs["vue3-recommended"] ? vue.configs["vue3-recommended"].rules : {}),
            "prettier/prettier": "error",
            "vue/multi-word-component-names": "off",
            "@typescript-eslint/no-unused-vars": "off",
        },
    },
];
