import vue from "eslint-plugin-vue";
import prettier from "eslint-config-prettier";

export default [
    {
        files: ["resources/js/**/*.{js,vue}"],
        languageOptions: {
            ecmaVersion: 2020,
            sourceType: "module",
        },
        plugins: {
            vue,
        },
        rules: {
            "vue/html-indent": ["error", 2],
            "vue/max-attributes-per-line": ["error", { singleline: 3 }],
            "no-unused-vars": "warn",
        },
    },
    prettier,
];
