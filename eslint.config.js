import js from "@eslint/js";
import globals from "globals";

export default [
	js.configs.recommended,
	{
		ignores: [
			"build/**",
			"coverage/**",
			"dist/**",
			"node_modules/**",
			"vendor/**",
		],
	},
	{
		files: [ "assets/js/**/*.js" ],
		languageOptions: {
			ecmaVersion: 2020,
			sourceType: "script",
			globals: {
				...globals.browser,
			},
		},
		rules: {
			"eqeqeq": "error",
		},
	},
	{
		files: [ "scripts/**/*.mjs", "tests/js/**/*.mjs" ],
		languageOptions: {
			ecmaVersion: "latest",
			sourceType: "module",
			globals: {
				...globals.node,
			},
		},
		rules: {
			"eqeqeq": "error",
		},
	},
];
