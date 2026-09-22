import js from '@eslint/js';
import vue from 'eslint-plugin-vue';
import ts from 'typescript-eslint';
export default ts.config(
  {
    ignores: [
      'vendor/**',
      'node_modules/**',
      'public/build/**',
      '.tools/**',
      'tmp/**',
      'bootstrap/cache/**',
      'storage/**',
      'worker-configuration.d.ts',
      '.wrangler/**',
      'playwright-report/**',
      'test-results/**',
    ],
  },
  js.configs.recommended,
  ...ts.configs.recommended,
  ...vue.configs['flat/recommended'],
  { files: ['**/*.ts'], rules: { 'no-undef': 'off' } },
  {
    files: ['**/*.vue'],
    languageOptions: { parserOptions: { parser: ts.parser } },
    // TypeScript checks undefined symbols, including DOM types in Vue scripts.
    rules: {
      'no-undef': 'off',
      'vue/multi-word-component-names': 'off',
      'vue/html-self-closing': 'off',
      'vue/max-attributes-per-line': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/html-indent': 'off',
      'vue/html-closing-bracket-newline': 'off',
      'vue/multiline-html-element-content-newline': 'off',
    },
  },
);
