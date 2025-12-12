module.exports = {
    root: true,
    env: {
        browser: true,
        node: true,
        es2021: true,
    },
    extends: ["plugin:vue/vue3-essential", "eslint:recommended", "@vue/eslint-config-prettier"],
    parser: "vue-eslint-parser",
    parserOptions: {
        parser: "@babel/eslint-parser",
        ecmaVersion: "latest",
        sourceType: "module",
        requireConfigFile: false,
    },
    rules: {
        "vue/multi-word-component-names": "off",
    },
};
