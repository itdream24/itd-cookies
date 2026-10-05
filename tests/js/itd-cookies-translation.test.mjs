import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

test('ITD Cookies MO table offsets are readable by WordPress 5.2 POMO', () => {
	const mo = fs.readFileSync(new URL('../../languages/itd-cookies-ru_RU.mo', import.meta.url));
	const count = mo.readUInt32LE(8);
	const originals = mo.readUInt32LE(12);
	const translations = mo.readUInt32LE(16);
	const hashLength = mo.readUInt32LE(20);
	const strings = mo.readUInt32LE(24);
	assert.equal(mo.readUInt32LE(0), 0x950412de);
	assert.equal(mo.readUInt32LE(4), 0);
	assert.ok(count > 40);
	assert.equal(translations - originals, count * 8);
	assert.equal(hashLength, 0);
	assert.equal(strings - translations, count * 8);
	assert.ok(strings < mo.length);
});
