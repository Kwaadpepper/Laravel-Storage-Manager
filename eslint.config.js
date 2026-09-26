import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';
import { configs } from 'typescript-eslint';
import vueParser from 'vue-eslint-parser';
import tsParser from '@typescript-eslint/parser';

/** @type {import('eslint').Linter.Config[]} */
export default [
    { ignores: ['src/resources', 'resources', 'vendor', 'node_modules'] },
    {
        files: ['src/ts/**/*.{ts,vue}'],
        ...js.configs.recommended,
    },
    ...configs.recommended.map((c) => ({ ...c, files: ['src/ts/**/*.{ts,vue}'] })),
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['src/ts/**/*.vue'],
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                parser: tsParser,
                extraFileExtensions: ['.vue'],
                sourceType: 'module',
            },
        },
    },
    {
        files: ['src/ts/**/*.{ts,vue}'],
        languageOptions: {
            ecmaVersion: 2020,
            globals: globals.browser,
        },
        rules: {
            '@typescript-eslint/no-unused-vars': [
                'error',
                {
                    argsIgnorePattern: '^_',
                    varsIgnorePattern: '^_',
                    destructuredArrayIgnorePattern: '^_',
                },
            ],
            'vue/multi-word-component-names': 'off',
        },
    },
]
