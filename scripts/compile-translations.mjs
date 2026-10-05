import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(fileURLToPath(new URL("../", import.meta.url)));
const source = fs.readFileSync(path.join(root, "languages/itd-cookies-ru_RU.po"), "utf8");
const entries = new Map();
let id = null;

for (const line of source.split(/\r?\n/)) {
	if (line.startsWith("msgid ")) {
		id = JSON.parse(line.slice(6));
	} else if (line.startsWith("msgstr ")) {
		if (id === null) throw new Error("msgstr without msgid");
		if (entries.has(id)) throw new Error(`Duplicate msgid: ${id}`);
		entries.set(id, JSON.parse(line.slice(7)));
		id = null;
	}
}

if (!entries.has("") || entries.size < 40) {
	throw new Error("Incomplete translation catalog");
}

const phpFiles = [
	"itd-cookies.php",
	"admin/class-itd-cookies-settings.php",
	"public/class-itd-cookies-plugin.php",
];
for (const file of phpFiles) {
	const code = fs.readFileSync(path.join(root, file), "utf8");
	const pattern = /(?:__|esc_html__|esc_attr__)\(\s*'([^']+)'\s*,\s*'itd-cookies'/g;
	for (const match of code.matchAll(pattern)) {
		if (!entries.has(match[1])) {
			throw new Error(`Missing Russian translation in ${file}: ${match[1]}`);
		}
	}
}

const pairs = Array.from(entries, ([original, translated]) => ({
	original: Buffer.from(original, "utf8"),
	translated: Buffer.from(translated, "utf8"),
})).sort((a, b) => Buffer.compare(a.original, b.original));
const count = pairs.length;
const originalsOffset = 28;
const translationsOffset = originalsOffset + count * 8;
let dataOffset = translationsOffset + count * 8;
const stringsOffset = dataOffset;
const originals = [];
const translations = [];
for (const pair of pairs) {
	originals.push({ length: pair.original.length, offset: dataOffset });
	dataOffset += pair.original.length + 1;
}
for (const pair of pairs) {
	translations.push({ length: pair.translated.length, offset: dataOffset });
	dataOffset += pair.translated.length + 1;
}
const output = Buffer.alloc(dataOffset);
output.writeUInt32LE(0x950412de, 0);
output.writeUInt32LE(0, 4);
output.writeUInt32LE(count, 8);
output.writeUInt32LE(originalsOffset, 12);
output.writeUInt32LE(translationsOffset, 16);
output.writeUInt32LE(0, 20);
// WordPress 5.2's POMO reader uses hash_addr as the start of the string table
// even when hash_length is zero.
output.writeUInt32LE(stringsOffset, 24);
for (let i = 0; i < count; i += 1) {
	output.writeUInt32LE(originals[i].length, originalsOffset + i * 8);
	output.writeUInt32LE(originals[i].offset, originalsOffset + i * 8 + 4);
	output.writeUInt32LE(translations[i].length, translationsOffset + i * 8);
	output.writeUInt32LE(translations[i].offset, translationsOffset + i * 8 + 4);
	pairs[i].original.copy(output, originals[i].offset);
	pairs[i].translated.copy(output, translations[i].offset);
}
const destination = path.join(root, "languages/itd-cookies-ru_RU.mo");
fs.writeFileSync(destination, output);
process.stdout.write(`Compiled ${count} Russian messages to ${destination}\n`);
