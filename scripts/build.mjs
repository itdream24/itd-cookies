import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { ZipArchive } from "archiver";
import { inspectPackage, validateVersion } from "./inspect-package.mjs";

const root = fileURLToPath(new URL("../", import.meta.url));
const version = validateVersion(root, process.env.RELEASE_TAG);
const destination = path.join(root, "dist", `itd-cookies-${version}.zip`);
fs.mkdirSync(path.dirname(destination), { recursive: true });
const output = fs.createWriteStream(destination);
const archive = new ZipArchive({ zlib: { level: 9 }, forceLocalTime: false });
const completion = new Promise((resolve, reject) => {
	output.on("close", resolve);
	output.on("error", reject);
	archive.on("error", reject);
	archive.on("warning", reject);
});
archive.pipe(output);
const files = ["itd-cookies.php", "uninstall.php", "LICENSE", "readme.txt"];
for (const directory of ["admin", "includes", "public", "assets", "languages"]) {
	const collect = (relative) => {
		for (const entry of fs.readdirSync(path.join(root, relative), { withFileTypes: true })) {
			const name = `${relative}/${entry.name}`;
			if (entry.isSymbolicLink()) throw new Error(`Symlink forbidden: ${name}`);
			if (entry.isDirectory()) collect(name);
			else if (entry.isFile()) files.push(name);
		}
	};
	collect(directory);
}
for (const name of files.sort()) {
	archive.append(fs.readFileSync(path.join(root, name)), {
		name: `itd-cookies/${name}`, date: new Date("2000-01-01T00:00:00Z"), mode: 0o100644,
	});
}
await archive.finalize();
await completion;
inspectPackage(destination, version);
console.log(destination);
