import fs from "node:fs";
import path from "node:path";
import zlib from "node:zlib";
import crypto from "node:crypto";
import { fileURLToPath } from "node:url";
import { spawnSync } from "node:child_process";

export function validateVersion(root, tag) {
	const version = JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8")).version;
	const main = fs.readFileSync(path.join(root, "itd-cookies.php"), "utf8");
	const lock = JSON.parse(fs.readFileSync(path.join(root, "package-lock.json"), "utf8"));
	const readme = fs.readFileSync(path.join(root, "readme.txt"), "utf8");
	if (!/^\d+\.\d+\.\d+(?:-(?:dev|beta|rc)\.\d+)?$/.test(version) ||
		lock.version !== version || lock.packages?.[""].version !== version ||
		!readme.includes(`Stable tag: ${version}\n`) ||
		!main.includes(` * Version: ${version}\n`) ||
		!main.includes(`define( 'ITD_COOKIES_VERSION', '${version}' );`)) {
		throw new Error("Plugin header, constant, package, lock and readme version must agree");
	}
	if (tag && (!/^v(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/.test(tag) || tag !== `v${version}`)) {
		throw new Error("Stable tag must equal the source version");
	}
	return version;
}

export function inspectPackage(file, version, php = process.env.PHP_BINARY || "php") {
	const zip = fs.readFileSync(file);
	let end = zip.length - 22;
	while (end >= Math.max(0, zip.length - 65557) && zip.readUInt32LE(end) !== 0x06054b50) end--;
	if (end < 0) throw new Error("Missing ZIP central directory");
	const count = zip.readUInt16LE(end + 10);
	let cursor = zip.readUInt32LE(end + 16);
	const entries = new Map();
	for (let i = 0; i < count; i++) {
		if (zip.readUInt32LE(cursor) !== 0x02014b50) throw new Error("Invalid ZIP entry");
		const method = zip.readUInt16LE(cursor + 10);
		const size = zip.readUInt32LE(cursor + 20);
		const nameSize = zip.readUInt16LE(cursor + 28);
		const name = zip.subarray(cursor + 46, cursor + 46 + nameSize).toString("utf8");
		const local = zip.readUInt32LE(cursor + 42);
		if (!/^itd-cookies\/(itd-cookies\.php|uninstall\.php|LICENSE|readme\.txt|(?:admin|includes|public|assets|languages)\/[^\\]+)$/.test(name) ||
			name.split("/").some((part) => part.startsWith(".") || ["tests", "scripts", "node_modules", "vendor", "docs", "reports", "coverage"].includes(part)) ||
			entries.has(name) || !/\.(?:php|js|css|po|mo)$|\/(?:LICENSE|readme\.txt)$/.test(name)) {
			throw new Error(`Unexpected package path: ${name}`);
		}
		const offset = local + 30 + zip.readUInt16LE(local + 26) + zip.readUInt16LE(local + 28);
		const compressed = zip.subarray(offset, offset + size);
		const content = method === 0 ? compressed : method === 8 ? zlib.inflateRawSync(compressed) : null;
		if (!content) throw new Error(`Unsupported ZIP method: ${method}`);
		entries.set(name, content);
		if (name.endsWith(".php")) {
			const lint = spawnSync(php, ["-l"], { input: content, encoding: "utf8" });
			if (lint.status !== 0) throw new Error(`PHP lint failed: ${name}: ${lint.stderr || lint.error}`);
		}
		cursor += 46 + nameSize + zip.readUInt16LE(cursor + 30) + zip.readUInt16LE(cursor + 32);
	}
	const main = entries.get("itd-cookies/itd-cookies.php")?.toString("utf8");
	if (!main?.includes(` * Version: ${version}\n`) || !main.includes(`define( 'ITD_COOKIES_VERSION', '${version}' );`)) {
		throw new Error("ZIP main file version mismatch");
	}
	if (!entries.get("itd-cookies/readme.txt")?.toString("utf8").includes(`Stable tag: ${version}\n`)) {
		throw new Error("ZIP readme version mismatch");
	}
	for (const required of ["LICENSE", "readme.txt", "includes/class-itd-cookies-updater.php", "includes/class-itd-cookies-script-adapters.php", "includes/functions-script-adapters.php", "assets/js/script-adapters.js", "languages/itd-cookies-ru_RU.mo"]) {
		if (!entries.has(`itd-cookies/${required}`)) throw new Error(`Missing ${required}`);
	}
	return { count, sha256: crypto.createHash("sha256").update(zip).digest("hex") };
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
	const root = fileURLToPath(new URL("../", import.meta.url));
	const version = validateVersion(root, process.env.RELEASE_TAG);
	console.log(inspectPackage(process.argv[2] || path.join(root, "dist", `itd-cookies-${version}.zip`), version));
}
