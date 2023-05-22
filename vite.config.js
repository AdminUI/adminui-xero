import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue2";
import { resolve } from "node:path";
import * as dotenv from "dotenv";
import { homedir } from "node:os";

export default defineConfig(({ mode }) => ({
	plugins: [
		laravel({
			input: "./resources/index.js",
			publicDirectory: "publish/js",
			hotFile: "publish/js/hot"
		}),
		vue({
			template: {
				transformAssetUrls: {
					base: ".",
					includeAbsolute: false
				}
			}
		})
	],
	build: {
		emptyOutDir: true,
		outDir: "./publish/js"
	},
	server: mode === 'development' ? {
		host: "192.168.2.42"
	} : {}
}));
