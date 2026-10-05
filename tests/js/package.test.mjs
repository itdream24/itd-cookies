import assert from "node:assert/strict";
import fs from "node:fs";
import os from "node:os";
import path from "node:path";
import test from "node:test";
import { validateVersion } from "../../scripts/inspect-package.mjs";

test("stable tag validation rejects prerelease and version mismatches", () => {
	const root = fs.mkdtempSync(path.join(os.tmpdir(), "itd-cookies-version-"));
	try {
		fs.writeFileSync(path.join(root, "package.json"), JSON.stringify({version:"1.0.0"}));
		fs.writeFileSync(path.join(root, "itd-cookies.php"), " * Version: 1.0.0\ndefine( 'ITD_COOKIES_VERSION', '1.0.0' );");
		assert.equal(validateVersion(root, "v1.0.0"), "1.0.0");
		for (const tag of ["latest", "v1", "v01.0.0", "v1.0.0-beta.1", "v1.1.0"]) {
			assert.throws(() => validateVersion(root, tag));
		}
	} finally {
		fs.rmSync(root, {recursive:true, force:true});
	}
});
