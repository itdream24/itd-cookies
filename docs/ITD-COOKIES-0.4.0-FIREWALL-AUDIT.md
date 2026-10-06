# ITD Cookies 0.4.0 — External Script Firewall Design Audit

Дата: 2026-10-06. Research stage, без production feature.

Repository: `itdream24/itd-cookies`.
Ветка: `research/itd-cookies-0.4.0-firewall`, создана после fetch от
`origin/main` = `3614cf7ff867e234feaf1c7ef7aeeac36d9e9b71`.
Stable: `v0.3.0`, runtime version `0.3.0`.

## 1. Executive summary

**Verdict: `FIREWALL_POC_PROMISING_BUT_NOT_READY`.**

Выбран **ограниченный opt-in hybrid**: WordPress handles/adapters → native hooks;
initial raw HTML → ограниченный byte-preserving tokenizer; документированный
набор dynamic insertion methods → синхронный ранний interceptor; observer →
только диагностика. Не рекомендовано обещать universal firewall или браузерный
sandbox. Автоматически включать этот механизм в stable нельзя.

POC доказал отсутствие запросов до consent для поддержанных script fixtures,
раздельное разрешение категорий, сохранение атрибутов и явно заданного порядка.
Одновременно воспроизведены сетевые обходы, ошибка неизвестной inline-зависимости,
проблема ожидания inline module и существенный overhead tokenizer.
Все результаты ниже относятся к локальным mock ресурсам, а не реальным SDK.

Production runtime, consent engine, provider loader, updater, release workflow,
version metadata не изменены. Main merge, tag, Release не выполнялись.
Remote QA requests = **0**; Timeweb SSH/FTP = **0**; production mutations = **0**.

## 2. Current limitation

В 0.3.0 `public/class-itd-cookies-plugin.php:48` подключает локальные
`providers.js` и `consent.js`. `assets/js/providers.js:158` загружает только
валидированные встроенные adapters через callback `allowed`; дедупликация
относится к этим SDK. `includes/class-itd-cookies-services.php:91` даёт registry
описаний external services, но не универсальное управление их выполнением.
`assets/js/consent.js:226` сообщает `itd_cookies_consent_changed`, а строка 306
экспортирует `ITDCookies.allowed/openSettings`.

Hardcoded script, чужой inline bootstrap, pixel или прямой `fetch` не становятся
управляемыми от появления записи в services registry. Consent UI не является
сетевым фильтром. Это ограничение stable сохранено без изменений.

Перед экспериментами восстановлен immutable `baseline-v0.3.0`: official ITD
Cookies 0.3.0 ACTIVE, единственный plugin, ru_RU, Twenty Twenty-Five 1.5,
WordPress 7.1.2 / PHP 8.1.5 / MariaDB 10.6. Исходные providers выключены,
migration marker `fresh-v1`, settings совпали, homepage работала,
501 wp-content file hashes совпали, helpers отсутствовали.

## 3. CookieRus architecture

Изучен реальный исходник **02805b2b9ce6032ce721b4e150881b56c238d135**,
header 1.2.0, [GPL v2 or later](https://github.com/RuCoder-sudo/cookierus/blob/02805b2b9ce6032ce721b4e150881b56c238d135/CookieRus.php#L9).
Код не копировался и не устанавливался.

| Механизм | Фактический код |
| --- | --- |
| Early guard | `wp_head` с priority 0, `print_consent_guard`; runtime guard внутри PHP-generated JS |
| Server buffer | `template_redirect`, `start_output_buffer`, технические исключения |
| Recognition | домены, категории/сервисы, substring/regex inline signatures |
| Representation | inert `type`, `src → data-cookierus-blocked-src`, inline/category markers |
| Activation | восстановление src, клонирование inline script |
| Dynamic | Node append/insert, setAttribute, document.write; отдельные Callibri paths |
| Network | также wrappers fetch/XHR/sendBeacon/Image/iframe |

Первичные участки: [buffer](https://github.com/RuCoder-sudo/cookierus/blob/02805b2b9ce6032ce721b4e150881b56c238d135/CookieRus.php#L79),
[domains](https://github.com/RuCoder-sudo/cookierus/blob/02805b2b9ce6032ce721b4e150881b56c238d135/CookieRus.php#L262),
[rewriter](https://github.com/RuCoder-sudo/cookierus/blob/02805b2b9ce6032ce721b4e150881b56c238d135/CookieRus.php#L433),
[release/interception](https://github.com/RuCoder-sudo/cookierus/blob/02805b2b9ce6032ce721b4e150881b56c238d135/CookieRus.php#L538).

Полезны сочетание early guard/server rewrite, registry и explicit service gates.
Риски, выведенные из кода: regex требует quoted src; исходный module type
теряется; release не ждёт external load перед inline; широкий domain match
может затронуть функциональные assets. На изученном callback path нет полноценной
MIME/status/chunk guard, а guard может добавиться перед ответом без head.
Server markup зависит от consent, что требует cache variance. Inline matching
не исключает data blocks на уровне HTML parser. Глобальные сетевые wrappers
меняют semantics; blocked beacon возвращает success без отправки.

ITD нужен более узкий registry, сохранение type/body, явные зависимости,
единый blocked HTML и строгий response gate. Эти риски — source audit,
не результаты browser QA установленного CookieRus.

## 4. Complianz/other CMP comparison

| Проект / pinned commit | License evidence | Изученное поведение |
| --- | --- | --- |
| Complianz GDPR `8bc8bb002c2ddd1a8253d4ae6a6bf7cb39287230` | [readme GPL2](https://github.com/complianz/complianz-gdpr/blob/8bc8bb002c2ddd1a8253d4ae6a6bf7cb39287230/readme.txt#L6), LICENSE.txt | buffer, registry, regex script/src rewriting, inline signatures, modules, iframe/image adapters |
| Complianz integrations `9bceb7ddcc126b6fddbe210d9aaabf0608de48b2` | отдельный license file/header не найден в просмотренном tree | набор opt-in mu-plugin snippets; использует движок Complianz, не самостоятельный firewall |
| Osano cookieconsent-wpplugin `4ceff590674954ba13071b52992d099e79f283b5` | [MIT header](https://github.com/osano/cookieconsent-wpplugin/blob/4ceff590674954ba13071b52992d099e79f283b5/osano-cookie-consent.php#L9) | enqueue banner assets + footer initialise; автоматический ресурсный firewall не найден |

У Complianz [start_buffer/replace_tags](https://github.com/complianz/complianz-gdpr/blob/8bc8bb002c2ddd1a8253d4ae6a6bf7cb39287230/class-cookie-blocker.php#L448)
переносят src в data-cmplz-src, ставят text/plain, явно исключают JSON-LD,
сохраняют module в data-script-type. Registry интеграций задаёт signatures,
категории, whitelist, images/iframes и зависимости. Подключение buffer проходит
через conditional hooks в `functions.php:1176–1232`; это configurable engine.

Client [cmplz_run_script](https://github.com/complianz/complianz-gdpr/blob/8bc8bb002c2ddd1a8253d4ae6a6bf7cb39287230/cookiebanner/js/complianz.js#L475)
использует load/waitfor/fired registry; inline дополняется callback-кодом, что
может нарушить исходный CSP hash. Attribute copier исключает async, module
восстанавливает отдельно. Postscribe — специальная compatibility ветка.
Глобальный перехват всех будущих script insertions в просмотренном blocking
path не найден; найденный MutationObserver не доказывает такое покрытие.
Media/placeholders — curated integration paths, не общая noscript гарантия.

[Google Maps snippet](https://github.com/complianz/complianz-integrations/blob/9bceb7ddcc126b6fddbe210d9aaabf0608de48b2/Google%20Maps/custom-google-maps.php)
показывает ручную зависимость initMap и map loader;
[whitelist](https://github.com/complianz/complianz-integrations/blob/9bceb7ddcc126b6fddbe210d9aaabf0608de48b2/whitelist-script.php)
и [reload](https://github.com/complianz/complianz-integrations/blob/9bceb7ddcc126b6fddbe210d9aaabf0608de48b2/reload-after-consent.php)
подтверждают необходимость адаптеров. Лицензию основного проекта нельзя молча
распространять на отдельный repository: до заимствования нужно уточнение upstream.

Osano здесь — именно указанный legacy WP wrapper/CookieConsent banner, не
современный коммерческий Osano CMP. Нет buffer, классификации inline, dependency
replay или noscript blocker; callbacks должен подключать автор интеграции.
Все четыре проекта изучались по source; чужой код в POC отсутствует.

## 5. Strategy A — WP hooks

`script_loader_tag` видит enqueued external tag; handle registry позволяет явно
назначить категорию и использовать WP dependencies. Это первый рекомендуемый
path для интеграций, которыми владелец управляет.

Browser: enqueued analytics — **0 requests**; raw head/footer — **1 request**
каждый до consent. Риск **LOW** для explicit handles, **HIGH** для заявленного
общего покрытия. `wp_add_inline_script` до/после handle не следует считать
автоматически безопасным: особенно на WP 5.2 нужен adapter, связывающий всю
группу, а не изменение только внешнего tag. Filter не видит CDN cache hit,
HTML вне WP enqueue или будущую JS injection.

## 6. Strategy B — output buffering

В POC конечный HTML проходит tokenizer до отправки; original src/type хранятся
в data attributes, body остаётся byte-identical. Full fixture initial scripts
не отправили запросов; dynamic after DCL отправил **1 request**.

Риски: весь document ждёт final buffer; дополнительный TTFB/memory, поздние
headers, разные вложенные buffers, minifiers, template/rawtext parsing.
Unsupported HTML/encoding/size возвращается без изменений с diagnostic:
availability сохраняется, privacy guarantee теряется. Это **BLOCKER** для
универсального zero-request контракта. Нельзя на parser error блокировать всю
страницу или молча сообщать «защищено».

## 7. Strategy C — runtime interception

POC запускает локальный controller раньше fixture code и синхронно перехватывает
`Node.appendChild/insertBefore`. Dynamic append denied: **0 requests**.
Parser-inserted head script: **1 request**. MutationObserver negative control:
**1 request и выполнение**, несмотря на последующую попытку блокировки.

Observer уведомляет после mutation; он не может быть основной сетевой границей.
[DOM mutation observer model](https://dom.spec.whatwg.org/#mutation-observers)
описывает доставку через microtask. Даже ранний interceptor должен оказаться
перед инициирующим кодом и учитывать preload scanner; wp_head hook сам по себе
этого не гарантирует. Saved native methods, другие realms/workers и alternative
DOM APIs остаются обходами. Риск **HIGH**, universal coverage **BLOCKER**.

## 8. Strategy D — hybrid

Наиболее полезен для **явно ограниченного** набора интеграций: A обрабатывает
handles, B охватывает raw initial tags, C закрывает некоторые late injections.
Одна representation + consent reader + replay queue, observer диагностирует
неподдержанные вставки. Full fixture Fresh/Reject — **0 optional requests**.

Но `Element.append` и connected-node `script.src` отправляют по **1 request**;
preload/pixel живут вне script path. Hybrid не складывается в универсальную
гарантию. Сложность приемлема только при opt-in, явных adapters/dependencies,
документированном scope и негативных тестах. Текущий POC не готов к переносу.

## 9. HTML parsing options

| Подход | Решение / риск |
| --- | --- |
| Regex only | Отвергнут: quoted/unquoted attrs, rawtext, comments/templates/escaped states; **BLOCKER** |
| DOMDocument | Доступен на PHP 7.4, но HTML4/libxml parsing + serialization меняют документ; **HIGH** |
| Streaming HTML tokenizer | Возможен только с сохранением состояний/границ chunks; нельзя отдать исходный chunk до решения; **HIGH** |
| WP HTML APIs | Хороший современный путь, но отсутствует на WP 5.2; **BLOCKER** как единственный backend |
| Limited byte tokenizer | Выбран для research; сохраняет поддержанные bytes, честно bypass unsupported input; **HIGH** до hardening |

[WP_HTML_Tag_Processor](https://developer.wordpress.org/reference/classes/wp_html_tag_processor/)
появился в WP 6.2; расширенный
[HTML API/Processor](https://make.wordpress.org/core/2023/10/21/updates-to-the-html-api-in-6-4/)
в WP 6.4. Минимум WordPress 5.2 / PHP 7.4 оставлен.
Compatibility layer должен иметь одну semantics и общий corpus; безопасный
backport — отдельный проект с license/maintenance review. Молчаливое повышение
минимума не принято. [DOMDocument::loadHTML](https://www.php.net/manual/en/domdocument.loadhtml.php)
не является современным браузерным HTML5 parser.

POC учитывает quoted `>`, comments, template depth, rawtext, UTF-8, duplicate
attributes; не поддерживает всю HTML5 namespace/state machine. Escaped script,
malformed/duplicate tags, incomplete template, non-UTF8 и >2 MiB вызывают bypass.
SVG scripting, base URL, MIME aliases, fragment parsing и URL canonicalization
требуют расширения corpus. Head insertion locator тоже ограничен.
PHP/browser gap `/a/../gtm.js` воспроизведён тестом; его нельзя скрывать.

## 10. Classification model

`experiments/firewall-poc/rules.json` — единый shared registry: provider,
category, exact host, path prefix. POC PHP и JS читают один файл.

| Provider | Hosts / paths | Category |
| --- | --- | --- |
| Yandex | mc.yandex.ru /metrika/ | analytics |
| GA4/legacy GA | www.googletagmanager.com /gtag/js; www.google-analytics.com /analytics.js | analytics |
| GTM | www.googletagmanager.com /gtm.js | analytics |
| Clarity | www.clarity.ms и clarity.ms /tag/ | analytics |
| Meta | connect.facebook.net /en_US/fbevents.js | marketing |

Не добавлялись десятки провайдеров. Locale-specific Meta URLs/aliases отсутствуют:
это baseline rules, не полный vendor catalog. Path-prefix overmatch, protocol,
dot segments/percent encoding/base и PHP/browser equivalence пока **HIGH**.
Host-suffix spoofing/query-only signatures/userinfo проходят negative unit tests.

Developer API design: validated server rule `host + path matcher → category`,
`handle → category`, integration group + explicit dependency IDs. Имена public
filters пока не утверждены; в эксперименте только `itd_fw_poc_handles`.
Allow unknown by default; explicit annotation
`data-itd-cookies-category="analytics|marketing"` работает и для inline.
Автор интеграции отвечает за полноту группы и category. Это не permission для
произвольного удалённого code injection или замена CSP.

## 11. Inline script problem

Нельзя блокировать весь inline JS. Explicit annotation/adapters — надёжнее
heuristic signatures. POC heuristic требует literal known SDK URL и createElement;
строки/comments могут совпасть, obfuscation может не совпасть. **HIGH**.

Annotated Clarity/Meta/GTM-style bootstraps не создавали requests до consent;
после category grant локальный SDK запросился/выполнился ровно один раз.
External A → external B → annotated inline `library.init()` даёт правильный
порядок. Но unannotated inline рядом с blocked A выполняется сразу и падает
`Cannot read properties of undefined (reading 'init')`: **BLOCKER**.
Adjacency не доказывает зависимость; нельзя автоматически задерживать соседний
unknown script без functional regression. Нужны explicit groups и adapters.

JSON-LD, importmap, text/template/data script и содержимое template не
классифицируются как JS. Regression включает JSON-LD с literal `<script`/comment
и известным URL. Inline body не дополняется callback-кодом.

## 12. Dynamic injection problem

Covered: synchronous appendChild/insertBefore после controller, включая
script nodes в DocumentFragment. Observer — диагностический.
Reproduced bypasses: Element.append; src assigned после соединения с DOM.
До consent каждый дал actual mock request и execution. **BLOCKER** для universal.

Не проверено полное множество replaceChild/replaceWith/append/prepend,
insertAdjacentHTML/innerHTML/document.write, native saved references, Shadow DOM,
workers/iframes, script text/type setters и module graph. Перехватывать только
createElement недостаточно: элемент ещё может не иметь URL/category.
Расширять глобальные prototypes нужно только после определения bounded scope,
совместимости с другими wrappers и воспроизводимых negative tests.

## 13. Noscript/pixel problem

До consent live image fixture отправил **1 request**, execution script при этом
не происходил. Noscript image в JS-enabled браузере — 0; это **не** доказательство
блокировки в JS-disabled режиме. JS-disabled browser QA не запускался.
Блокировка analytics script не выключает scripting браузера: noscript зависит
от режима scripting, согласно
[HTML noscript](https://html.spec.whatwg.org/multipage/scripting.html#the-noscript-element).

GTM noscript iframe/Meta pixel требуют отдельного server resource registry и
consent policy для JS-disabled visitors. Script-only promise не покрывает их.
Если будущий контракт — zero known optional network, noscript/img/iframe URLs
нельзя игнорировать: **HIGH/BLOCKER**. Автоматическое iframe blocking не добавлено.
YouTube/maps/embeds потребуют placeholders, explicit categories и отдельного QA;
изменение функционального embed без назначения категории не рекомендовано.

## 14. Cache/CDN considerations

Предпочтение: **один always-blocked HTML для всех consent states**, versioned
local controller читает first-party consent и активирует permitted nodes.
HTTP comparison двух синтетических cookies дал одинаковый blocked HTML после
нормализации только random fixture document ID. Stored consent/reload в браузере
активирует permitted scripts без нового выбора.

Это модель cache-friendly HTML, не полноценный cache acceptance. Cache перед PHP
может отдавать старый unblocked HTML; minifier/async optimizer может переместить
controller или удалить type/data attributes; CDN может добавить script/hints.
Потребуются purge, exclusions, controller priority, transformed-cache marker,
cookie/header review и конкретная matrix cache plugins/CDN. Эти продукты здесь
не устанавливались; integration **NOT RUN**, risk **HIGH**.

Known optional preload до consent отправил **1 request**, executions=0.
Экспериментальный fw_hints=1 устранил этот request. Остальные
[resource hints](https://html.spec.whatwg.org/multipage/links.html#link-type-preload)
нужно классифицировать отдельно: preconnect/DNS раскрывают обращение к host,
modulepreload может подгрузить module graph. Host-only hints POC не покрывает;
modulepreload/preconnect/DNS browser matrix **NOT RUN**. Нельзя объявить network
gate PASS по одному отсутствующему SDK tag.

## 15. CSP/module considerations

Изолированные CSP probes: script-src self + nonce и script-src self + exact inline
hash. До grant — 0 optional requests; после — external request/execution=1 и
inline execution=1. Nonce/body сохранены. Controller — external same-origin,
его config находится в escaped attribute, не inline code.

Это не arbitrary CSP support: [CSP3 strict-dynamic](https://www.w3.org/TR/CSP3/#strict-dynamic-usage),
nonce rotation/cache, trusted-types, controller blocking, nonce-hidden attr API,
loader integrity и hash-only policies без self требуют отдельного design. **HIGH**.
Включение unsafe-inline/ослабление CSP не предлагалось.

External module, inline module и local imports выполнились по одному разу.
Original type=module/nomodule сохранены; nomodule request=0 в этом browser.
Но inline module replay wait даёт `replay-timeout:fw-inline-module`, даже когда
сам module уже выполнился. Queue completion semantics — unresolved **HIGH**.
Attributes async/defer сохранены, однако повторное создание tag после consent
не восстанавливает исходную parser/DCL timing. General dependency graphs,
dynamic import, top-level await и importmap changes не проверены. Основание:
[HTML script processing model](https://html.spec.whatwg.org/multipage/scripting.html#script-processing-model).

## 16. Performance

Fresh PHP 7.4.33 CLI process на каждый размер/метод, 15 iterations, 20 tracked
script tags, ASCII padding, точные 100/500/1024 KiB. Значения локальные, не SLA.
Peak — PHP allocator high water, не RSS процесса/всего WordPress.

| HTML KiB | Tokenizer median/max ms | Regex median ms | DOM median ms | Tokenizer peak / delta MiB | Output delta bytes tokenizer / regex / DOM |
| --- | --- | --- | --- | --- | --- |
| 100 | 14.73 / 17.38 | 0.04 | 0.91 | 2 / 0 | 1680 / 460 / 568 |
| 500 | 75.02 / 89.94 | 0.15 | 4.78 | 4 / 2 | 1680 / 460 / 568 |
| 1024 | 160.55 / 227.34 | 0.40 | 16.90 | 8 / 4 | 1680 / 460 / 568 |

Regex/DOM comparator выполняет более простую обработку; быстрота не доказывает
эквивалентную correctness. Buffer задерживает отдачу первого document chunk;
end-to-end TTFB и peak WordPress worker здесь не измерялись.
Reverse substr_replace/временные substrings плохо масштабируются с числом tags.
Дополнительный 1024 KiB/2000 tags stress был остановлен после нескольких минут:
результат **NOT COMPLETED**, не PASS и без выдуманного median.
Нужен линейный edit assembly, cap tags/bytes и bounded latency: **HIGH**.

## 17. POC implementation

Изолированные original GPL-2.0-or-later файлы:

- `experiments/firewall-poc/`: parser.php, poc.php, controller.js, rules.json,
  tests.php, dom-tests.mjs, controller-tests.mjs, http-tests.mjs, benchmark.php,
  eslint.config.mjs, README.md.
- `experiments/firewall-fixtures/`: fixtures.php, application.js, README.md.

Два dev-only plugins устанавливались только в named local domain; отдельная
temporary page ID 7 с `[itd_firewall_fixture]`. Main plugin settings не менялись;
services descriptions добавлялись filter только для fixture requests.
Server mock counters — атомарные INSERT/UPDATE, один private non-autoload option
на doc/label; concurrent loads не теряют increments. Evidence UI различает
requests/executions, order/errors, copied attrs. Refresh имеет aria-busy, чтобы
не считывать предыдущий snapshot до завершения local HTTP.

Response gate: request kind + 2xx + UTF-8 HTML + no attachment/Location/encoding
at PHP buffer layer + final/non-flushed callback. Поздняя server gzip поверх уже
преобразованного HTML допустима; уже gzencoded body bypass. Final Content-Length
удаляется при изменении bytes. Streaming/early flush всегда bypass.
[PHP callback phases](https://www.php.net/ob-start) учитываются без nested ob calls
внутри callback. Недостаточная уверенность означает original bytes + diagnostic.

Build использует unchanged allowlist в `scripts/build.mjs`; experiments не
runtime dependency. `npm run build`/`npm run inspect` **PASS**: 15 entries,
root itd-cookies/, translations/updater включены, experiments/tests/docs/evidence
отсутствуют, packaged PHP syntax valid, version 0.3.0.

SHA-256 `itd-cookies-0.3.0.zip`:
`aa45c5a4ba2c4bc01e4dde683093504ac127db30556227f20a26666bc8c6f9f4`.
Он совпадает с official release asset; asset/tag/release не изменялись.

## 18. Browser/network results

In-app Chromium browser, official local 0.3.0, widths 1440×900 и 390×844.
Real consent UI/events использованы для основной matrix. Mock endpoints только
same-origin; known vendor matching отдельно проверен unit tests. Реальные vendor
SDK/accounts не использовались. Unknown CDN URL проверен offline jsdom;
реальный browser unknown-functional fixture — локальный mock, не внешний CDN.

| Full hybrid scenario | Analytics requests | Marketing requests | Functional/unknown | Desktop / mobile |
| --- | --- | --- | --- | --- |
| Fresh | 0 | 0 | application/unknown по 1, menu/form работают | PASS / PASS |
| Reject | 0 | 0 | работают | PASS / PASS |
| Analytics only | 12 | 0 | работают; A→B→inline-B | PASS / PASS для request/category gate |
| Marketing only, independent fresh choice | 0 | 1 | работают | PASS / PASS |
| Accept all | 12 | 1 | работают | PASS / PASS для request/category gate |
| Revoke optional, next document | 0 | 0 | работают | PASS / PASS |

Один request/execution на каждый разрешённый mock label; nomodule=0.
Repeated Accept all не увеличил counters, stored all desktop reload и stored
marketing mobile reload прошли. Module timeout из §15 сохраняется: эти строки
не означают full replay readiness. Раннее чтение mobile Analytics snapshot было
устаревшим; итог проверен после ожидания replay tag и завершённого refresh.

Сохранены async, defer, module, nomodule, crossorigin, валидный SRI,
referrerpolicy, nonce, custom data, id/class. Нет horizontal document overflow:
scrollWidth 1425≤1440 и 375≤390; banner buttons видны/доступны. Menu, required
empty-input validation, filled form и локальная jQuery-like dependency прошли.
DOM structural comparison после нормализации expected firewall attrs прошёл:
forms/SVG/JSON-LD/templates/importmap/entities/UTF-8/comments.

| Изолированная проверка | Actual pre-consent requests | Вывод |
| --- | --- | --- |
| A enqueue / raw head / raw footer | 0 / 1 / 1 | hook coverage ограничено |
| B full/dynamic | dynamic=1, initial optional scripts=0 | сервер не видит late injection |
| C raw head / dynamic appendChild | 1 / 0 | parser script не перехватывается |
| observer only | dynamic=1, execution=1 | слишком поздно |
| D Element.append / late-src | 1 / 1 | обходы interceptor |
| D preload / opt-in hint suppression | 1 / 0 | request без SDK execution |
| D live image pixel | 1 | вне script scope |
| D unknown inline dependency | request=0, TypeError | функциональная поломка |
| CSP nonce / hash probes | 0 before grant; 1 external after + inline=1 | bounded probes PASS |

HTTP fixture checks **PASS**: HTML/Content-Length transformed; JSON, XML,
download, 302, 404, 500, pre-gzipped, early-flushed stream bypass; valid final
length/no stale length. Real REST 200 JSON, feed 200 RSS, robots 200 text/plain,
admin 302 bypass. Local sitemap disabled/404: полноценный sitemap route **NOT
AVAILABLE**, XML fixture и unit sitemap exclusion прошли. AJAX/cron/CLI kind
exclusions — unit **PASS**, отдельный real AJAX/cron browser запуск **NOT RUN**.

Automated checks фактически запущены:

| Проверка | Результат |
| --- | --- |
| PHP POC tests, PHP 7.4.33 | PASS, 71 assertions |
| POC controller jsdom tests, no real HTTP | PASS, 2 tests |
| DOM regression + URL normalization counterexample | PASS; gap reproduced |
| Syntax lint всех 5 experimental PHP files | PASS, PHP 7.4.33 |
| Experimental ESLint | PASS |
| Existing stable ESLint + JS tests | PASS, 31 tests |
| Existing PHPUnit | PASS, 21 tests / 340 assertions; финальный запуск без result cache |
| Local HTTP integration | PASS, 10 fixture responses + 5 real-route exclusions + common HTML comparison |
| Stable build/package inspector | PASS, 15 files; hash unchanged |
| Whitespace/security diff checks | PASS; scoped secrets scan без находок |

Полный GitHub CI, новый WP 5.2 browser стенд, PHPCS/PHPStan/Composer/npm audit,
cross-browser, full-page cache/CDN и JS-disabled E2E в этой задаче **NOT RUN**.
Неизменённые production файлы не объявляются вновь полностью CI-certified.

**Cleanup PASS:** оба dev plugins, counters/options, page ID 7 удалены
восстановлением immutable baseline. Consent-cookie presence для трёх ITD names
= false, значения cookies не выводились. 501 file hashes/settings/activity/theme
совпали; official 0.3.0 ACTIVE, helpers отсутствуют, normal homepage подтверждена.

Первый post-restore row-hash comparison выявил четыре regenerated theme-pattern
cache options после WordPress bootstrap; это не было скрыто как PASS. После
homepage smoke выполнен final SQL import/export без WordPress bootstrap;
CREATE schemas и **все INSERT rows всех 12 tables** совпали с immutable dump.
Canonical schema+row SHA-256:
`5e4de59abce9821678a8b76d5641ae6a1af8fb28811fecd22c3e4206b0759c1f`.
Immutable SQL SHA-256:
`61eeb52f5597c8360d31a44edfc64c819d6b56e9bbe22aac5e0f816439873ab9`.
Baseline не recaptured/overwritten; private SQL, screenshots и session evidence
не входят в Git/package. Следующая загрузка WP может штатно обновить cache TTL.

## 19. Compatibility risks

| Риск | Grade | Evidence / необходимое действие |
| --- | --- | --- |
| HTML corruption/unsupported parsing | HIGH | subset DOM PASS; escaped/namespace/malformed bypass; full corpus нужен |
| Known scripts on parser bypass | BLOCKER | original HTML fail-open сохраняет request path; universal guarantee невозможна |
| External/inline execution order | BLOCKER | explicit order PASS, unknown inline TypeError; нужны adapter groups |
| Dynamic zero-request guarantee | BLOCKER | append/late-src/observer actual requests=1 |
| Hints/pixels/noscript | HIGH | preload/pixel leaks; JS-disabled route не проверен |
| Cache/CDN/optimizers | HIGH | common HTML model PASS; products не проверены |
| CSP/module lifecycle | HIGH | bounded CSP PASS, inline module completion timeout |
| Async/defer/parser timing | HIGH | атрибуты сохранены, execution model после recreation отличается |
| PHP/browser URL/inline matching | HIGH | dot-segment differential, prefix/heuristic limitations |
| Old WordPress | HIGH | baseline WP 5.2 retained; POC integration только WP 7.1.2 |
| Processing/memory/TTFB | HIGH | 1MiB ~161 ms, dense stress незавершён; нужна bounded-linear обработка |
| Unknown functional JS | MEDIUM | mocks/menu/form PASS; dependency side effects сохраняются |
| Registry maintenance | MEDIUM | пять providers, aliases/CDN меняются; нужны pinned signature updates |
| Production package isolation | LOW | immutable allowlist + 15-file inspector + identical ZIP hash |

Grades относятся к выбранному ограниченному design и отдельно отмечают blockers
для более широкого universal обещания; отсутствие теста не считается PASS.

## 20. Proposed production architecture

Один выбор: **opt-in integration-aware hybrid**, disabled by default, без
universal claim. В production сейчас не внедряется.

1. Централизованный validated registry с exact URL semantics, explicit handles,
   HTML annotations, dependency groups; unknown functional code — fail-open.
2. Enqueue path — WP-native adapters для всей external/inline dependency group,
   с поддержкой before/after inline на WP 5.2. Это основной безопасный путь.
3. Raw HTML — one always-blocked document, byte-preserving ограниченный parser
   с проверенными states/response gates и ограничением времени/памяти.
4. Ранний внешний local controller — строгий existing consent read, independent
   categories, один activation registry, graph-based explicit classic dependencies.
   CSP failure и unsupported input дают заметную diagnostics, не fake protection.
5. Dynamic component — opt-in synchronous bounded API coverage для известных
   integrations; observer только сообщает leaks. Не patch всех network APIs.
6. Known hints/noscript resources — отдельные reviewed adapters/policies, прежде
   чем заявлять network guarantee. Module replay исключить из поддержанного
   production scope, пока lifecycle/dependency semantics не доказаны.

Нельзя превращать parser fallback в скрытое обещание защиты. При unsupported
integration нужно рекомендовать отключить её собственный early loader или
добавить explicit adapter. UI должен сообщать реальные coverage gaps.

## 21. Explicit non-goals

- Браузерный sandbox, перехват всех fetch/XHR/beacon/Image/WebSocket.
- Блокировка всего unknown JS, автоматическое угадывание любых inline dependencies.
- Автоматическая iframe/YouTube/maps/media замена без отдельного дизайна.
- Перехват произвольных imports/workers/других realms или обфусцированного кода.
- Ослабление CSP, изменение consent schema/engine/providers/updater.
- Автоматическое включение firewall в stable, повышение minimum WP/PHP.
- Scan/privacy compliance certification или юридическая гарантия consent.
- Version bump 0.4.0, main merge, tag, Release, remote/production QA.

## 22. Implementation plan

Это план для отдельной задачи, не выполненные production изменения:

1. Утвердить bounded scope/opt-in contract и diagnostics unsupported pages;
   zero-request acceptance только для перечисленных adapters/resources.
2. Устранить classifier normalization/prefix gaps, выбрать строгий canonical
   URL matcher, объединить PHP/JS conformance fixtures.
3. Создать tested parser corpus и linear edit writer, время/размер/tag caps,
   bytes-preserving response exclusions. При невозможности безопасного fallback
   сузить поддержанные сайты/интеграции, а не обещать universal coverage.
4. Реализовать explicit dependency groups, исключить unproven modules/document.write,
   проверять load failure/timeout без выполнения зависимых inline.
5. Определить dynamic API coverage и negative matrix saved native/connected src/
   append/fragment/realm. Не расширять network interception автоматически.
6. Добавить known hint/noscript/pixel policies, JS-disabled tests, nonce/hash/
   strict-dynamic/trusted-types review и cache/minifier/CDN compatibility matrix.
7. Local WP 5.2/5.2.24/current + PHP 7.4/current, browser engines, performance и
   full CI, production inspector isolation; restore baseline после каждой matrix.
8. Только после нового acceptance review решать о production feature; merge,
   version/tag/Release остаются отдельным release gate.

## 23. Verdict

**`FIREWALL_POC_PROMISING_BUT_NOT_READY`**

Scope-defined hybrid полезен, но текущие parsing/performance/dependency/module и
dynamic/network gaps запрещают production перенос POC и universal обещание.
Research code воспроизводим, stable runtime не изменён, official local baseline
восстановлен точно. Следующий шаг — отдельное утверждение ограниченного design
и закрытие blockers, без автоматического выпуска 0.4.0.
