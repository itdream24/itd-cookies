# ITD Cookies 0.4.2 — native GitHub updater refresh

Date: 2026-10-09. Stable tag: v0.4.2. Release commit: d242f3bdaa8d787ae173eeb27507fff2d3e8631f.
Historical implementation branch: fix/itd-cookies-0.4.2-updater-refresh.
Fresh origin/main base: af3ccfd2a06696f909a0c297f77eac1ce410a7ca.
Stable: 0.4.2. Historical candidate: 0.4.2-dev.1. Published older tags/assets remain immutable.

Verdict: **ITD_COOKIES_V0.4.2_RELEASED_WITH_ASSISTED_UPDATE_ONLY** for the original cached-old-updater scenario. Publication and fixed updater PASS; automatic migration after natural expiry of that old cache was not executed. A separate native update on the clean restored snapshot with no pre-existing GitHub cache also PASS (scope below).

## Cause and native execution order

Inspected official WordPress 5.2 source and installed official WordPress 7.1.2.
Both register wp_update_plugins on load-update-core.php at the default priority
10. wp-admin/admin.php runs admin_init before the screen's load-* action.
wp-admin/update-core.php then handles force-check for wp_version_check, which
checks Core. It does not guarantee deletion of the plugin update transient or
our independent GitHub metadata cache.

Previously includes/class-itd-cookies-updater.php invalidated its GitHub cache
only through delete_site_transient_update_plugins. The native manual screen
path did not fire that deletion. A valid old GitHub result remained cached for
six hours; WordPress could also short-circuit wp_update_plugins for one minute
on the Updates screen, or rebuild no_update from stale GitHub data. The old unit
test explicitly called clear_cache and therefore did not exercise native hooks.

The fixed manual path is:

1. Core authenticated admin bootstrap/admin_init. If expired metadata is checked
   here, remember its fresh HTTP result for this request.
2. load-update-core.php priority 1: validate native hook, admin context, exact
   scalar force-check=1, update_plugins capability; exclude AJAX/REST. Invalidate
   GitHub metadata and WordPress update_plugins through native transient APIs.
3. If admin_init already fetched in this request, restore only that fresh result
   after invalidation. Do not reuse an older persistent-cache result.
4. Core wp_update_plugins at priority 10: the absent WordPress update record
   bypasses its recent-check short circuit. Its first transient write can have
   no checked versions; the adapter correctly skips this provisional record.
5. Core plugin update HTTP response produces the final response array. Modern Core also provides checked; old WP 5.2/5.2.24 can omit it after an uncached check. Only on this authenticated manual path, recover our installed version in checked when the entire checked list is absent. The provisional last_checked-only record is still skipped;
   pre_set_site_transient_update_plugins applies our existing adapter. A newer
   GitHub release moves the plugin from no_update to response.

manual_refreshed prevents repeated invalidation in one instance/request; fresh
request_release avoids a second API request following an admin_init check.
Ordinary cache TTLs remain 21600 seconds for valid releases and 900 seconds for
errors. No explicit new HTTP call was added to the early hook. The existing Core
manual link has no nonce; its authenticated screen and update_plugins permission
are used, without introducing an endpoint, changing Core or removing checks.

## Scope

Production change is restricted to includes/class-itd-cookies-updater.php and
0.4.2-dev.1 version/readme/changelog/translation metadata. package.json changed
only its version; package-lock changed only the root and root-package versions.
No dependency updates. Consent, providers, Script Adapters, UI, legal integration
and migration runtime have no diff from main. Packaging scripts are unchanged.
CI adds one controlled native-hook integration invocation within the existing
four WordPress jobs; the job count and matrix stay unchanged.

## Automated checks actually executed locally

| Check | Result |
| --- | --- |
| PHP 7.4 PHPUnit | PASS — 50 tests, 494 assertions |
| PHP 7.4 compatibility, PHPCS, PHPStan | PASS |
| JS suite, ESLint | PASS — 42 JS tests |
| npm ci and npm audit | PASS — zero vulnerabilities |
| Composer strict validation and locked audit | PASS — no advisories |
| Russian translation compilation | PASS — 96 messages |
| Production-format build / package inspector | PASS — 18 files, PHP syntax valid |
| Two builds SHA-256 equality | PASS |
| Gitleaks committed history | PASS — 45 commits scanned before final acceptance; final report commit rescan follows |

Unit regression models installed 0.4.0, cached GitHub 0.4.0 and stale no_update;
controlled latest 0.4.1 becomes response and removes no_update. Negative cases:
missing/invalid/array flag, no capability, ordinary visit, frontend, unrelated
hook, AJAX, REST, GitHub failure/negative TTL and repeated calls. Both successful
and failed HTTP checks before the screen hook are reused once with the proper TTL.

Controlled integration uses real Core do_action/load hook, wp_update_plugins,
transient storage and final filtering, with HTTP mocks and isolated old-version
plugin-header cache. It asserts both caches absent at priority 9, update notice
0.4.1 on modeled 0.4.0, no stale no_update, exact hook/HTTP order and one GitHub
request. A local-host guarded private copy ran successfully on installed WP
7.1.2 after the public Browser E2E; this is not a public stable upgrade. CI will
run the committed disposable-only test on WP 5.2, 5.2.24 and latest PHP 7.4/8.5.

## Earlier f42 candidate Browser E2E — PASS (historical evidence)

Only itd-cookies.local was used. baseline-v0.4.0 was actually restored and its
immutable hashes verified before testing. Synthetic settings and consent were
created through the real admin/banner. Native WordPress Upload/Replace updated
official 0.4.0 to 0.4.2-dev.1. All 18 installed hashes equal the ZIP, canonical
folder remains itd-cookies, plugin ACTIVE. All raw settings/legal links/provider
fields, marker and prior activity are equal before/after; saved rejection remains
effective after reload. No consent/provider/Script Adapter changes were made.

Seeded controlled stale metadata for the known public v0.4.0 without any HTTP
mock. A temporary local-only observer counted actual http_api_debug responses
and recorded early/final native hooks; it did not filter HTTP or replace metadata.

- Ordinary Updates visit: both cached records retained at priority 2; GitHub
  requests=0; cached version stays 0.4.0.
- Real «Проверить снова» click: both cached records absent at priority 2; native
  provisional write has checked=false; exactly one actual GitHub request returns
  HTTP 200; final checked record and metadata contain published v0.4.1.
- response has no ITD Cookies entry; no_update has version 0.4.1. This is correct
  because installed 0.4.2-dev.1 is newer. No downgrade is offered.
- Console warnings/errors: none on the manual-check page.

This proves the candidate refreshes real public metadata, NOT a future real
stable-to-stable upgrade. Update notice on an older modeled version is proven
separately by the controlled integration test.

Private screenshots and observer/cache evidence: QA_STATE/042-updater, including
native-zip-upgrade.jpg, ordinary-admin-cached.jpg, public-api-manual-refresh.jpg
and its JSON record. Credentials, dumps, raw cookies and QA tools stay outside
Git/package. Candidate ZIP: itd-cookies-0.4.2-dev.1.zip; SHA-256:

f42c1788ff087b48e5e9c25184661be1be1cba88ad96646a7def2763e8008654

## Cleanup — PASS

Cleared local consent/legacy marker QA cookies and verified an empty cleanup
result before removing instrumentation. Actually restored baseline-v0.4.0.
Only official v0.4.0 ACTIVE remains; all 18 official runtime hashes match its ZIP.
All 504 wp-content paths/hashes equal the unchanged snapshot. All 12 database
schemas and every INSERT row match baseline. Original settings/marker/activity,
absent policy option, default banner and theme state restored. Temporary MU
observer, upload/test records, options/caches and synthetic settings are absent.
No WordPress request was made after the final exact database comparison.

Immutable SQL SHA-256:
c247f4c0c96f71159a0dd806e08302f5b39def68415cd73a0c6f08b38bf47d93

Canonical all-table comparison SHA-256:
5ef5e08a66edc59ce7f0cb546ce2e5fb286b70a78c93ae1682ce4241e496a234

## Future release limitation

Installed official 0.4.0 and 0.4.1 still contain the old manual-refresh defect.
Publishing 0.4.2 will not repair code before it is installed. Immediate discovery
through Check again on an old cached installation is not guaranteed. A future
real stable E2E must wait for natural cache expiry or explicitly document assisted
recovery; manual transient deletion is never evidence that the old release has
fixed native refresh behavior. No such stable-upgrade claim is made here.

No merge/main push, tag or Release in this task. v0.4.1 and older published assets
remain unchanged. No Timeweb, testwp or production access; LOCAL-FIRST maintained.

## CI execution history

Initial run https://github.com/itdream24/itd-cookies/actions/runs/37902096673: PHP, quality and node PASS; four WordPress jobs FAIL and build skipped. The new test included an ordinary-view WordPress.org HTTP event in the expected manual-only trace. Its checks for one GitHub request and refreshed update metadata passed before the trace assertion. Resetting only test trace between the ordinary and manual phases fixes that unrelated-event assumption. Production source and accepted ZIP SHA-256 are unchanged. Full CI on the corrected test remains required.

Run https://github.com/itdream24/itd-cookies/actions/runs/37902472907: six jobs PASS (including both latest WordPress jobs); WP 5.2/5.2.24 FAIL and build skipped. Official old Core omits checked in its final result after an uncached check. A bounded compatibility path now adds only this active plugin’s installed version to that final manual-check record, identified by its response array, never to the provisional record or ordinary requests. New unit regression covers these distinctions. Updated ZIP SHA-256: 6c20b96becb1f1d00374bb0d291d45efd24a07692bb4a6c8946b8318c5b050ed. Earlier f42 ZIP browser evidence does not cover this additional path; repeat acceptance and full CI are required.

Run https://github.com/itdream24/itd-cookies/actions/runs/37903587848 passed metadata refresh on old Core but failed the synthetic repeated-hook trace: old Core legitimately contacted WordPress.org again. The test now asserts first-call hook order separately from repeated-call GitHub deduplication, without suppressing Core HTTP or reducing the one-GitHub-call gate. No additional production diff or ZIP change.

Current implementation commit: 27c8e1e85b31259c4e72993953cb5ee08b5acbee. Full CI https://github.com/itdream24/itd-cookies/actions/runs/37903786635 completed SUCCESS: php (7.4), php (8.5), quality, node, wordpress (5.2, 7.4), wordpress (5.2.24, 7.4), wordpress (latest, 7.4), wordpress (latest, 8.5), build — all nine PASS. Final production-format ZIP SHA-256: 6c20b96becb1f1d00374bb0d291d45efd24a07692bb4a6c8946b8318c5b050ed; inspector 18 files PASS and two builds equal. Repeat local Browser E2E on exactly this artifact remains pending; previous browser evidence cannot be used to claim final-candidate acceptance.

## Final 6c20 artifact acceptance — PASS (2026-10-09)

This section supersedes earlier pending status without removing earlier test
results or failure history. The pre-E2E uncommitted report was copied to private
QA_STATE/042-updater/final-acceptance/report-before-e2e.md before any changes.
Runtime source stayed exactly at accepted commit
27c8e1e85b31259c4e72993953cb5ee08b5acbee. Candidate ZIP remains:

itd-cookies-0.4.2-dev.1.zip

SHA-256: 6c20b96becb1f1d00374bb0d291d45efd24a07692bb4a6c8946b8318c5b050ed

### Baseline, native upgrade and settings

Actually restored immutable baseline-v0.4.0 before testing; verified 504 files
and all 12 schemas/INSERT rows. Synthetic settings were saved through Settings
API on official 0.4.0: custom title/description, large 105% text, HTTPS privacy
link, relative local link, synthetic Metrika/GA4 IDs with both providers OFF.
Created temporary Cookie Policy page ID 7 through the real plugin admin button.
Saved Reject and reloaded before installation.

The real WordPress Upload/Replace flow upgraded official 0.4.0 to the exact final
0.4.2-dev.1 ZIP. Canonical folder itd-cookies, ACTIVE, all 18 runtime file hashes
equal ZIP. All raw settings, migration marker fresh-v1, policy ID and active
plugins equal before/after. Consent schema/version/expiry/categories equal
before/after; rejected banner stays hidden. Candidate Settings API save round
trip was tested through real clicks; restored synthetic values equal pre-upgrade.

### Public metadata refresh and capability — PASS

Temporary local observer instrumented the entire request, including admin_init,
via http_api_debug and native hooks. No pre_http_request, response mock, synthetic
release feed or plugin runtime alteration. Controlled stale cache was populated
with metadata for the known published v0.4.0; only the starting cache is synthetic.

| Real browser scenario | Observed result |
| --- | --- |
| Ordinary Updates visit with stale metadata | GitHub calls 0; caches present at priority 2; metadata 0.4.0 and original timeout retained |
| Native Check again click | Both caches absent at priority 2; provisional checked=false; exactly one GitHub HTTP 200 response across full request; final metadata and no_update 0.4.1 |
| Ordinary view after refresh | GitHub calls 0; metadata 0.4.1; timeout unchanged, roughly six hours remaining |
| Subscriber force-check request | Native access-denied page; calls 0; metadata, timeout and no_update remain 0.4.0; no early invalidation |

Subscriber test temporarily reduced only local synthetic QA user rights, then
restored administrator role. Final baseline DB comparison includes user roles.
No downgrade is offered: candidate 0.4.2-dev.1 is newer than published v0.4.1.
The actual HTTP response supplied published download URL/changelog; this proves
public metadata retrieval, not future stable-to-stable upgrade. The separate
controlled Core CI regression proves the older installed-version update notice.

### Regression smoke — PASS

- Fresh, saved Reject/reload, Customize, Analytics-only, Accept all, reopen,
  Back/Cancel, Save and revoke with native page reload exercised by real clicks.
- Escape closes panel and returns focus to Settings shortcode button; Tab from
  Save moves to Accept all. Necessary stays enabled.
- Existing public-API reference fixture: no owned SDK calls before consent;
  Analytics-only library/dependent each called once, marketing absent. Trace:
  before/localized data, library, after, dependent, dependent-init.
- Repeated consent event keeps SDK counters/trace unchanged. Revoke preserves
  false categories and reloads without owned SDK execution (trace empty).
- Accept all activates analytics and marketing. Cumulative counters reflect
  the earlier analytics scenario; each current-page initializer occurs once.
- Deliberate local SDK 404: LOAD_ERROR, SKIPPED_DEPENDENCY, FAILED for analytics;
  dependent not requested; independent marketing activates. Expected injected
  failure is not an unexpected plugin error.
- Zero registered groups: empty diagnostics/counters; native application,
  neighbor, jQuery and theme still run once. No native providers enabled.
- All three shortcodes rendered. Real Cookie Policy page and legal links work;
  HTTPS/relative/generated policy URLs retained. No external client IDs used.
- Screenshots inspected at desktop 1440x900 and mobile 390x844. Mobile document
  scrollWidth equals clientWidth 375 (390 viewport minus scrollbar); panel width
  351, scrollWidth=clientWidth=349. No horizontal overflow, all action buttons
  visible, category area scrolls. Functional label remains whole.
- Observed PHP warnings/errors empty, shutdown last_error null in capability
  probe; browser Console warnings/errors empty in checked states, fixture CSP
  violations empty. This was a targeted regression smoke, not a full new audit
  of every third-party provider or all CSP policies.

Private evidence: final-acceptance/*.json and native-upgrade.jpg,
public-manual-refresh.jpg, desktop-fresh.jpg, desktop-panel.jpg,
mobile-fresh.jpg, mobile-panel.jpg, insufficient-capability.jpg,
restored-official-040.jpg. Screenshots/QA data/tools are excluded from Git/ZIP.

### Final cleanup — PASS

Real fixture reset cleared consent/legacy QA cookies; DOM evidence consent=null.
Actually restored immutable baseline-v0.4.0. Default fresh banner verified in
browser after restore, then final exact DB restore/export comparison executed.
No subsequent WordPress request after that final comparison.

Official 0.4.0 ACTIVE, canonical plugin directory, all 18 official file hashes
match published 0.4.0 ZIP. All 504 wp-content paths/hashes equal snapshot. All 12
table schemas and every INSERT row equal baseline. Settings/marker/activity/user
roles restored; temporary reference plugin, MU observer, Cookie Policy page 7,
uploads, private QA options/counters and seeded metadata removed.

Immutable SQL hash remains c247f4c0c96f71159a0dd806e08302f5b39def68415cd73a0c6f08b38bf47d93.
Canonical all-table hash remains 5ef5e08a66edc59ce7f0cb546ce2e5fb286b70a78c93ae1682ce4241e496a234.
No main merge/push, tag/Release, stable asset changes or remote WordPress access.

Implementation CI: https://github.com/itdream24/itd-cookies/actions/runs/37903786635
— all nine jobs PASS. Final documentation commit is pushed only to the current
fix branch; its separate full CI result is verified in the completion report.


## Stable release gate — 2026-10-09

Accepted fix commit: 43521b565d17b9a11bbba8cc98bef0500f614b35.
Release branch: release/itd-cookies-0.4.2. Stable/tag commit:
d242f3bdaa8d787ae173eeb27507fff2d3e8631f. main was fast-forwarded only
after release-branch CI and pre-release local acceptance; no history rewritten.
Stable preparation changed versions/changelog/readme/ru_RU metadata and rebuilt
the 96-message MO; no new runtime feature, consent/UI/adapter/API changes.

Release: https://github.com/itdream24/itd-cookies/releases/tag/v0.4.2

| Gate | Actual result |
| --- | --- |
| Release branch CI | 9/9 PASS — https://github.com/itdream24/itd-cookies/actions/runs/37912484706 |
| Separate main CI before tag | 9/9 PASS — https://github.com/itdream24/itd-cookies/actions/runs/37913645264 |
| Tag release workflow | 11/11 PASS — https://github.com/itdream24/itd-cookies/actions/runs/37913905135 |
| Public releases/latest | v0.4.2, draft=false, prerelease=false |
| Anonymous ZIP/checksum download | PASS, no authentication supplied |
| Production package | 18 files, itd-cookies/ root, main/updater/adapters/translations included; tests/docs/dev excluded |
| Reproducible build | Two local ZIPs and published asset identical |

ZIP: itd-cookies-0.4.2.zip.
SHA-256: **58b60e6295c5c831cc94a80315a17d9c20a5543b3063031fbcd8d4da8a3befb6**.
The published .zip.sha256 contains this hash and exact filename. PHP 7.4 package
syntax and inspector were run again on the anonymously downloaded archive.
No older tag/Release/asset was changed, replaced or rebuilt for publication.

Local quality/security checks actually run: PHPUnit PHP 7.4 (50 tests,
494 assertions), JS (42 tests), ESLint, PHPCS (20 files), PHPStan,
PHPCompatibilityWP (7.4+), Composer locked audit (0 advisories), npm audit
(0 vulnerabilities), Gitleaks (47 commits, no leaks), translations,
version/tag validation, package inspector and reproducibility — PASS.
The full CI also ran PHP 7.4/8.5 and all four WordPress jobs: 5.2/PHP7.4,
5.2.24/PHP7.4, latest/PHP7.4, latest/PHP8.5. No cancelled/skipped job was
counted as PASS. Final documentation main CI is checked after this commit/push.

### Stable pre-release local acceptance — PASS

Immutable baseline-v0.4.0 was actually restored first. Browser native Upload/
Replace 0.4.0 -> production-format 0.4.2 preserved raw settings, activity,
consent (including its original expiration) and migration marker. All 18 live
files matched the stable ZIP. In this first pass the policy option was absent
both before/after; actual policy preservation was tested in published pass A.

Controlled stale GitHub v0.4.0 + WordPress update_plugins were seeded only on
new 0.4.2. Ordinary Updates preserved the six-hour timeout with zero GitHub
calls. The actual Check again link invalidated BOTH records before Core,
made one real HTTP 200 GitHub request and received the then-current public
v0.4.1. No downgrade. A following ordinary check reused the same expiry with
zero calls. Subscriber capability test: access denied, zero calls, both caches
and timeout unchanged; administrator role restored.

No HTTP interception or synthetic release metadata was used for that real
public response. Separate Core integration/mock tests for a newer release
and update notice passed in the four WordPress CI jobs.

Pre-release cleanup PASS: 504 paths/hashes and all 12 schemas/every INSERT row
equal baseline-v0.4.0; temporary files/options/pages/counters removed and QA
consent/legacy cookies cleared through the reference reset button.

## Обновление с версий 0.4.0/0.4.1

### A1 — существующий кеш старого updater: assisted recovery

На официальной 0.4.0 до публикации обычный запрос WordPress получил настоящую
публичную metadata v0.4.1 (HTTP 200), TTL 6 часов. Значение не подменялось;
timeout не сокращался, кеш вручную не удалялся. Сохранены синтетические
настройки, Reject-consent, fresh-v1 marker и созданная штатной кнопкой политика
Cookie (page 7). После публикации настоящая кнопка «Проверить снова» сохранила
v0.4.1 и прежний timeout; GitHub requests=0; осталось 21326 секунд.

Естественное истечение этих шести часов **не ожидалось и не проверено**.
Это не доказательство автоматического обновления после TTL. Исправление 0.4.2
не может изменить ещё выполняющийся старый updater. Отдельный browser тест
старой 0.4.1 не выполнялся; общий механизм старых 0.4.0/0.4.1 подтверждён кодом.

Выполнен разрешённый assisted recovery: анонимно скачанный опубликованный ZIP
загружен через WordPress Plugins -> Upload -> Replace. PASS: официальная 0.4.2
ACTIVE, папка itd-cookies, все 18 файлов равны опубликованному ZIP; settings
и список активности совпадают, consent полностью совпадает (без потери срока),
marker и Cookie Policy ID/все поля страницы сохранены; юридические ссылки и
шорткоды работают. Это **не автоматическое обнаружение нового release старым
закешированным updater** и не ручная подмена файлов.

Практический путь владельцу старой версии: дождаться естественного TTL и
обычной проверки WordPress; если обновление нужно сразу — сделать backup,
скачать официальный ZIP выше, проверить SHA-256, Upload/Replace в WordPress.
После запуска 0.4.2 штатная кнопка «Проверить снова» уже сбрасывает оба кеша.
Для восстановления использовать backup сайта, не изменять старые GitHub assets.

### A2 — cache miss на чистом baseline: native updater PASS

После полного удаления QA-состояния восстановлен исходный immutable snapshot
0.4.0, в котором изначально **нет GitHub metadata/timeout**. Обычный вход в
WordPress после публикации получил настоящий v0.4.2. В Updates была видна
0.4.0 -> 0.4.2; выбрана только ITD Cookies и нажата «Обновить плагины».
Native upgrader анонимно скачал release package; iframe подтвердил успех.
Все 18 файлов совпали с опубликованным архивом; официальный плагин ACTIVE.

Не было synthetic metadata, целевого удаления кеша или вмешательства в HTTP.
Однако это **новый чистый контекст после восстановления БД**, а не продолжение
A1 и не истечение его старого TTL; результат не выдаётся за доказательство
полной автоматической миграции сайтов с сохранённым старым кешем. Поэтому
итоговый verdict для исходного A1 остаётся WITH_ASSISTED_UPDATE_ONLY.

При первом открытии Plugins после native upgrade осталось старое update notice
с той же 0.4.2: upgrader ещё исполнял bootstrap 0.4.0. Новая настоящая «Проверить
снова» на уже установленной 0.4.2 убрала его; Plugins/Updates больше не предлагают
равную версию. Кеш не исправлялся через CLI. Это наблюдение сохранено отдельно.

### B — новый официальный updater: PASS

На установленной официальной 0.4.2 отдельно создан контролируемый старый кеш
v0.4.0 и update_plugins. Настоящая «Проверить снова»: оба записи отсутствуют
до Core; один реальный GitHub HTTP 200; public metadata v0.4.2, корректный ZIP
URL/changelog; no_update=0.4.2, update notice/downgrade отсутствует. Следующая
обычная проверка: zero GitHub requests, тот же шестичасовой timeout.
Это реальный API, без подмены HTTP. Будущий update notice проверен отдельным
mock/Core regression CI; фиктивный GitHub Release не создавался.

### Published runtime browser smoke — PASS

- Сохранённый Reject/reload; Customize/Analytics-only; Accept all; Save/reopen;
  revoke двух категорий с настоящим reload; Back, Escape и возврат фокуса.
- Tab от Save переводит фокус на Accept all. Necessary checked/disabled.
- До согласия owned SDK counters/trace пусты; analytics library/dependent по
  одному запросу, порядок before/library/after/dependent/dependent-init.
- Повтор consent event не меняет counters/trace. Accept all запускает marketing
  один раз. Revoke оставляет analytics/marketing=false, новый trace пустой.
- Zero groups: diagnostics/counters пусты, application/neighbor/theme/jquery
  продолжают работать. Все три shortcode и реальная Cookie Policy работают;
  HTTPS/relative/generated legal links сохранены. Встроенные providers выключены;
  использованы только синтетические ID и локальные SDK, никаких client IDs.
- Изучены screenshots 1440x900 и 390x844: mobile document scrollWidth=clientWidth
  375 (viewport 390 с scrollbar), панель 351px, кнопки доступны, overflow нет.
- В checked published-case Console warnings/errors отсутствуют; PHP observer
  errors=[]; CSP violations=[]. Полный новый аудит внешних провайдеров/CSP не
  выполнялся, это целевой smoke поверх уже принятой implementation матрицы.
- В pre-release Console были ошибки JSON polling **самого fixture** при
  временном переводе его текущего пользователя в subscriber: endpoint вернул
  HTML access-denied. Роль восстановлена, published-case таких ошибок нет;
  это не ошибка shipped runtime. Не скрыто и не включено в утверждение zero.

Private evidence: 042-stable-release/*.json and pre-upgrade-success.jpg,
pre-refresh.jpg, A-old-updater-cache.jpg, A-assisted-official-upgrade.jpg,
B-public-042-refresh.jpg, official-desktop-panel.jpg, official-mobile-panel.jpg,
official-mobile-fresh.jpg, clean-baseline-native-offer.jpg,
clean-native-upgrade-success.jpg, final-official-042-active.jpg.
Private tools/dumps/credentials/screenshots excluded from Git and production ZIP.

### Final cleanup / immutable baselines — PASS

Reference reset verified consent=null and cleared QA consent/legacy cookies.
Restored immutable baseline-v0.4.0, then compared all 504 files and 12 complete
table schemas/INSERT rows again. That removed observer/reference, policy page 7,
upload attachments, synthetic settings/metadata/options/counters. Its SQL SHA
is unchanged: c247f4c0c96f71159a0dd806e08302f5b39def68415cd73a0c6f08b38bf47d93;
canonical DB hash 5ef5e08a66edc59ce7f0cb546ce2e5fb286b70a78c93ae1682ce4241e496a234.

After clean native update described in A2, checked official default options,
marker, no managed policy and only one installed/active plugin. Fresh homepage
verified, no QA cookies. Captured NEW baseline-v0.4.2 outside web-root/Git;
Capture refuses overwrite. Actually restored it, verified all **504 file hashes**,
settings, locale/theme/plugin/activity, then exact DB import/export proved all
**12 table schemas and every INSERT row** equal. No WordPress request after
the final DB comparison. Official **0.4.2 ACTIVE**, no temporary tools or data.

New baseline SQL SHA-256:
09c7599b3f8570444a95b27b5ce9380f1b6b3fdd096b49cc85341a2c00dc8aa4.
Canonical DB SHA-256:
b3e80e63d2c2ba256bd9153c081927785c405ce950fde176f04d66d529b13f76.
Old baselines were not overwritten. Timeweb/testwp/prod requests and mutations=0.

### Передача следующей задаче — WordPress 5.0 / PHP 7.4

Published stable v0.4.2, current local reset baseline-v0.4.2, upstream main.
Minimum supported WordPress remains **5.2**, PHP **7.4**. WordPress 5.0 was NOT
tested or declared supported here. Next task should create a separate branch,
inventory 5.0 APIs/Core updater hooks and add a real WP5.0/PHP7.4 integration
job before lowering metadata. Preserve release assets/older baselines and the
separate A1 cache limitation. No compatibility work started in this release.
