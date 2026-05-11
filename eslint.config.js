import js from '@eslint/js';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';
import globals from 'globals';
import { configs } from 'typescript-eslint';

/** @type {import('eslint').Linter.Config[]} */
export default [
    { ignores: ['src/resources', 'vendor', 'node_modules'] },
    {
        files: ['src/ts/**/*.{ts,tsx}'],
        ...js.configs.recommended,
    },
    ...configs.recommended.map((c) => ({ ...c, files: ['src/ts/**/*.{ts,tsx}'] })),
    {
        files: ['src/ts/**/*.{ts,tsx}'],
        plugins: { 'react-hooks': reactHooks },
        rules: reactHooks.configs['recommended-latest'].rules,
    },
    {
        files: ['src/ts/**/*.{ts,tsx}'],
        languageOptions: {
            ecmaVersion: 2020,
            globals: globals.browser,
        },
        plugins: {
            'react-refresh': reactRefresh,
        },
        rules: {
            'react-refresh/only-export-components': [
                'warn',
                { allowConstantExport: true },
            ],
        },
    },
]
