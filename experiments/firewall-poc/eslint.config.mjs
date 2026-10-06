import js from '@eslint/js';
import globals from 'globals';
export default [
  js.configs.recommended,
  { files:['**/*.js'], languageOptions:{ecmaVersion:2020,sourceType:'script',globals:{...globals.browser,FWJQuery:'readonly'}} },
  { files:['**/*.mjs'], languageOptions:{ecmaVersion:'latest',sourceType:'module',globals:globals.node} },
];
