# ITD Cookies — WordPress 5.0 feasibility

Дата исследования: 2026-10-09. Verdict: **WP50_COMPATIBILITY_NOT_RECOMMENDED**.

Исходная версия: официальный v0.4.2; main при начале исследования:
97f7930a4caeaee8bd2989ed2346a2d3293162f5. Ветка:
research/itd-cookies-wp50-feasibility. Production runtime, требования,
версия, main, stable tags и Releases в этом исследовании не менялись.

## Решение и границы доказательств

Технически обоснованный нижний Core для продукта при PHP 7.4 — **WordPress 5.3**:
реально проверен 5.3.26/PHP 7.4.29, и официальная матрица Core допускает это
сочетание. Текущие метаданные плагина продолжают заявлять WP 5.2/PHP 7.4;
их изменение требует отдельной задачи. Для production предпочтительны
актуальный WordPress и PHP с действующей security support.

Для WP 5.0–5.2 официальный Core/PHP путь использует PHP 7.3, но bootstrap
ITD Cookies требует PHP >=7.4. Мы не обходили guard и не активировали полный
runtime на PHP 7.3. Синтаксическая совместимость не доказывает совместимость
плагина в работе. Снижение PHP или отдельный legacy release не утверждены.

## Официальные источники

- [Core/PHP matrix](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/):
  для 5.0, 5.1, 5.2 PHP 7.3 = Y, PHP 7.4 = N; для 5.3 PHP 7.4 = Y.
  В старой части таблицы другой порядок колонок; N нельзя трактовать как Y.
- [PHP EOL](https://www.php.net/eol.php): PHP 7.3 — 2021-12-06,
  PHP 7.4 — 2022-11-28. Совместимость Core не возвращает security support PHP.
- [WordPress release archive](https://wordpress.org/download/releases/):
  проверенные последние patch-версии 5.0.30, 5.1.27, 5.3.26.
- [Update URI introduced in 5.8](https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/).
- [Native Upload/Replace introduced in 5.5](https://make.wordpress.org/core/2020/07/29/miscellaneous-developer-focused-changes-in-wordpress-5-5/).

## Точная матрица

PASS в столбце guard означает безопасный отказ собственного runtime,
а не поддержку функциональности. NOT_RUN означает намеренно не запускалось.
Результаты CI ниже относятся к research commit, отдельно от main. CI legacy PHP 7.3 = 7.3.33, PHP 7.4 = 7.4.33.

| Core | PHP локально | Официально Core | Bootstrap | Полный runtime | Проверка |
| --- | --- | --- | --- | --- | --- |
| 5.0.0 (архив 5.0) | 7.3.33 | Да | PASS: отказ PHP | NOT_RUN | CLI guard |
| 5.0.30 | 7.3.33 | Да | PASS: отказ PHP | NOT_RUN | CLI; native Activate/Deactivate в браузере |
| 5.0.30 | 7.4.29 | Нет, эксперимент | PASS: отказ WP | NOT_RUN | CLI guard, без обхода |
| 5.1.27 | 7.3.33 | Да | PASS: отказ PHP | NOT_RUN | CLI guard |
| 5.2.24 | 7.3.33 | Да | PASS: отказ PHP | NOT_RUN | CLI guard |
| 5.3.26 | 7.4.29 | Да | PASS | PASS | CLI integration + browser E2E |
| 5.2 / 5.2.24 | CI PHP 7.4.33 | Нет | PASS | PASS regression | Существующие 2 jobs PASS; не официальная поддержка Core |
| latest = 7.1.3 | CI PHP 7.4.33 / 8.5.11 | Да | PASS | PASS | Существующие 2 jobs PASS |

Расширение legacy-research.yml выполняет еще шесть jobs: пять guard-only и
один функциональный 5.3.26/PHP 7.4. В исходном ci.yml остаются все девять jobs:
php (7.4, 8.5), quality, node, wordpress (четыре сочетания), build.
Ни одна проверка безопасности или качества не отключена. CI не выполняет
браузерную приёмку; она проведена отдельно локально.

## Изоляция и восстановление

Созданы шесть disposable сайтов с отдельными БД и пользователями, права каждого
ограничены его БД. Browser hostname: itd-cookies-wp50.local, loopback-only
серверы PHP на 18050 (5.0.30/7.3), 18054 (5.0.30/7.4), 18053 (5.3.26/7.4).
Глобальная версия PHP/OpenServer других проектов не переключалась.
Данные синтетические; credentials, SQL, сырые сетевые URL и cookies не в Git.

После тестов все шесть БД восстановлены из исходных дампов: схемы и строки
всех 12 таблиц каждого сайта совпали. wp-content возвращён к чистому содержимому
официального Core: 218/218/218/218/224/237 файлов соответственно. Тестовый
ITD Cookies, reference plugin, MU observer, policy page, counters, transient
metadata и QA options удалены. Тестовые consent/provider cookies очищены
через локальный fixture, оба администраторских сеанса завершены. Три временных
сервера остановлены. Чистые disposable БД/пользователи/сайты сохранены для
повторного исследования; hostname назначен только новому локальному сайту.

Основной itd-cookies.local не открывался и не изменялся. Read-only проверка:
504 файла wp-content и все 12 таблиц (схемы и строки) совпадают с immutable
baseline-v0.4.2, включая настройки и active_plugins. Baseline SQL SHA-256:
09c7599b3f8570444a95b27b5ce9380f1b6b3fdd096b49cc85341a2c00dc8aa4.
Официальный 0.4.2 остался ACTIVE. Restore основного сайта в этой задаче не
выполнялся, поскольку запрещены его изменения; проверены целостность резервной
копии и точное совпадение текущего состояния. Ранее принятый baseline не перезаписан.
Remote WordPress QA, SSH/FTP Timeweb и production operations = 0.

## Аудит production API и PHP

Проверены все десять PHP файлов: bootstrap, uninstall, settings, consent,
legal, services, updater, adapters, adapter functions и frontend plugin.
Token inventory нашёл 112 различных глобальных имён функций; отсутствующих
в реально загруженном Core 5.0/PHP 7.3.33 — 0. Из них 66 внешних PHP-функций
WordPress. Собственные методы/объявления и встроенные функции отделены.
Все десять файлов прошли реальный php -l на 7.3.33; PHPCompatibilityWP
с testVersion 7.3-7.3 также PASS. PHP 7.4-only синтаксис/функции не обнаружены.
Это статический аудит, не запуск полного runtime на PHP 7.3.

| Подсистема | Проверенные API/структуры | Результат и границы |
| --- | --- | --- |
| Settings | register_setting, Settings API form, settings_fields, add_settings_error, sanitize/escape, manage_options | Есть в 5.0; реальные сохранения и ru_RU на 5.3 PASS. Settings nonce/capability не менялись |
| Script Adapters | wp_scripts, get_data, add_data, print_extra_script, print_inline_script; registered/deps/extra/done/do_concat | API/структуры присутствуют в 5.0; native WordPress rendering на 5.3 PASS |
| Inline modern fallback | includes/class-itd-cookies-script-adapters.php:353–365 | get_inline_script_tag отсутствует в 5.0; method_exists выбирает уже имеющийся print_inline_script. Core shims не нужны |
| Assets / frontend | wp_enqueue_script/style, wp_localize_script, script_loader_tag; локальные JS | Поздние обязательные WordPress API не найдены; до consent собственные SDK не грузятся |
| Consent | get_option, wp_unslash, hash_equals, строгая cookie schema 1 | API доступны; reload/revoke на 5.3 PASS; shared HTML cache не является персональным consent storage |
| Providers | services filter, закрытая JS карта пяти SDK | Поздних Core API нет; реальные запросы проверены с фиктивными IDs, доставка vendor analytics не утверждается |
| Legal / shortcodes | get_post/permalink, wp_insert_post, wp_safe_redirect, nonce; три add_shortcode | Все доступны; managed policy, footer, settings opener на 5.3 PASS |
| i18n | load_plugin_textdomain, __, esc_html__, esc_attr__; packaged MO | ru_RU в браузере и integration PASS; WP JS translations API не используется |
| Updater | HTTP API, transient API, wp_doing_ajax/cron, current_filter, update_plugins hooks | Реальный GitHub updater на 5.3 PASS; Update URI на Core <5.8 не защищает slug |
| Activation | register_activation_hook; собственные guards PHP 7.4 и WP 5.2 | Safe refusal PASS; Core 5.0/5.1 не имеет современного validate_plugin_requirements |

### Inventory: все 66 WordPress-функций

- `__` — admin/class-itd-cookies-settings.php:30
- `absint` — includes/class-itd-cookies-legal.php:34
- `add_action` — itd-cookies.php:32
- `add_filter` — includes/class-itd-cookies-script-adapters.php:72
- `add_option` — admin/class-itd-cookies-settings.php:173
- `add_options_page` — admin/class-itd-cookies-settings.php:235
- `add_settings_error` — admin/class-itd-cookies-settings.php:150
- `add_shortcode` — includes/class-itd-cookies-legal.php:22
- `admin_url` — admin/class-itd-cookies-settings.php:265
- `apply_filters` — includes/class-itd-cookies-services.php:91
- `check_admin_referer` — includes/class-itd-cookies-legal.php:148
- `checked` — admin/class-itd-cookies-settings.php:377
- `current_filter` — includes/class-itd-cookies-updater.php:82
- `current_user_can` — admin/class-itd-cookies-settings.php:250
- `delete_site_transient` — includes/class-itd-cookies-updater.php:89
- `delete_transient` — includes/class-itd-cookies-updater.php:103
- `did_action` — includes/class-itd-cookies-script-adapters.php:103
- `disabled` — public/class-itd-cookies-plugin.php:129
- `do_action` — includes/class-itd-cookies-script-adapters.php:85
- `esc_attr` — admin/class-itd-cookies-settings.php:271
- `esc_attr__` — includes/class-itd-cookies-legal.php:92
- `esc_html` — admin/class-itd-cookies-settings.php:274
- `esc_html__` — itd-cookies.php:35
- `esc_textarea` — admin/class-itd-cookies-settings.php:271
- `esc_url` — admin/class-itd-cookies-settings.php:265
- `esc_url_raw` — admin/class-itd-cookies-settings.php:419
- `get_edit_post_link` — admin/class-itd-cookies-settings.php:339
- `get_option` — admin/class-itd-cookies-settings.php:69
- `get_permalink` — includes/class-itd-cookies-legal.php:52
- `get_post` — includes/class-itd-cookies-legal.php:35
- `get_post_status` — includes/class-itd-cookies-legal.php:51
- `get_transient` — includes/class-itd-cookies-updater.php:112
- `home_url` — admin/class-itd-cookies-settings.php:438
- `is_admin` — includes/class-itd-cookies-legal.php:101
- `is_wp_error` — includes/class-itd-cookies-legal.php:150
- `load_plugin_textdomain` — itd-cookies.php:67
- `plugin_basename` — itd-cookies.php:67
- `plugin_dir_path` — itd-cookies.php:25
- `plugin_dir_url` — itd-cookies.php:26
- `register_activation_hook` — itd-cookies.php:62
- `register_setting` — admin/class-itd-cookies-settings.php:218
- `sanitize_key` — includes/class-itd-cookies-services.php:105
- `sanitize_text_field` — admin/class-itd-cookies-settings.php:93
- `sanitize_textarea_field` — admin/class-itd-cookies-settings.php:96
- `selected` — admin/class-itd-cookies-settings.php:274
- `set_transient` — includes/class-itd-cookies-updater.php:93
- `settings_fields` — admin/class-itd-cookies-settings.php:266
- `submit_button` — admin/class-itd-cookies-settings.php:333
- `update_option` — includes/class-itd-cookies-legal.php:179
- `wp_dequeue_script` — includes/class-itd-cookies-script-adapters.php:253
- `wp_die` — admin/class-itd-cookies-settings.php:251
- `wp_doing_ajax` — includes/class-itd-cookies-updater.php:81
- `wp_doing_cron` — includes/class-itd-cookies-updater.php:117
- `wp_enqueue_script` — includes/class-itd-cookies-script-adapters.php:259
- `wp_enqueue_style` — public/class-itd-cookies-plugin.php:44
- `wp_insert_post` — includes/class-itd-cookies-legal.php:166
- `wp_json_encode` — includes/class-itd-cookies-script-adapters.php:463
- `wp_localize_script` — public/class-itd-cookies-plugin.php:50
- `wp_nonce_field` — admin/class-itd-cookies-settings.php:343
- `wp_parse_url` — admin/class-itd-cookies-settings.php:421
- `wp_remote_get` — includes/class-itd-cookies-updater.php:120
- `wp_remote_retrieve_body` — includes/class-itd-cookies-updater.php:134
- `wp_remote_retrieve_response_code` — includes/class-itd-cookies-updater.php:133
- `wp_safe_redirect` — includes/class-itd-cookies-legal.php:153
- `wp_scripts` — includes/class-itd-cookies-script-adapters.php:186
- `wp_unslash` — includes/class-itd-cookies-consent.php:41

## Browser acceptance на 5.3.26/PHP 7.4.29

Официальный 0.4.0 установлен штатным Plugins → Add New → Upload, затем
активирован. Перед обновлением через Settings API сохранены синтетические
настройки, два legal URL, размер 110%, consent version и пять provider IDs;
создана Cookie Policy ID 6 и сохранён отказ пользователя.

Обновление **0.4.0 → публичный GitHub 0.4.2** выполнено штатной кнопкой WordPress.
Первое открытие Updates уже получило GitHub HTTP 200, но запись Core не показала
предложение; обычное «Проверить снова» показало 0.4.2 из настоящего кеша GitHub.
Не было synthetic release metadata, HTTP replacement или очистки старого кеша
в сценарии обнаружения/установки. Root itd-cookies/ сохранился, ACTIVE, версия
0.4.2; хеши всех 18 файлов совпали с официальным ZIP. Настройки, rejected
consent, migration marker fresh-v1 и policy ID не изменились.

| Сценарий | Результат |
| --- | --- |
| Fresh / Reject / reload | PASS: до выбора и после отказа нет пяти SDK |
| Analytics only | PASS: Yandex, GA4, GTM, Clarity разрешены; Meta не загружен |
| Marketing only | PASS: Meta разрешён; analytics SDK не загружены |
| Accept all / Save / reopen | PASS: разрешены все выбранные категории |
| Revoke / reload | PASS: новый документ не загружает отозванные собственные SDK |
| Back / Escape / Tab / focus return | PASS: несохранённый выбор не применяется; фокус возвращается к opener |
| Settings / legal / policy / three shortcodes / footer | PASS, русский интерфейс и content |
| Zero adapter groups | PASS: нет adapter manifest/assets, соседние app/jquery/CDN работают |
| Public adapter API, dependency order | PASS: before → library → after → dependent → dependent-init |
| Repeat consent event | PASS: счетчики текущего документа и native GA4 init/tag не увеличились |
| Library 404 | PASS: LOAD_ERROR, зависимые skipped, отдельная Marketing группа работает |
| Desktop 1440×900, mobile 390×844 / 320×568, 110% | Layout PASS: без horizontal overflow, целое «Функциональные», видимые switches и доступные sticky actions |
| Secondary button contrast on Twenty Twenty | FAIL: отдельный P2 ниже |
| PHP warnings/fatal, sampled browser Console | PASS: неожиданные ошибки плагина не обнаружены |
| CSP violations under default policy | PASS: наблюдалось 0; strict nonce/hash CSP на legacy Core NOT_RUN |
| Manual refresh denied capability | Browser NOT_RUN; controlled integration regression PASS |

Синтетические IDs доказывают gating запросов/инициализацию, но не работу
учётных записей провайдеров. Google SDK может сам делать дополнительные
gtag requests; один native init/tag не означает один сетевой запрос vendor.
Неизвестный fixture CDN намеренно загрузился до consent: плагин не firewall.

### Исправленный updater в официальном 0.4.2

Отдельно, уже после установки, контролируемо записаны старые кеши GitHub
0.4.0 и update_plugins. Настоящая кнопка «Проверить снова» очистила оба до
Core check, выполнила **один реальный GitHub API HTTP 200** и сохранила
публичную metadata v0.4.2; TTL наблюдался 21599 секунд (~6 часов), downgrade
не предложен. Обычное admin navigation — 0 новых GitHub requests и тот же кеш.
Это тест инвалидации, не доказательство обнаружения без контролируемых кешей.
В отдельном CLI integration используются mock HTTP/новая metadata: они
подтверждают update notice, denied capability, ошибки ZIP/сети/timeout и
сохранность установки; не выдаются за публичный browser update.

## Найденные ограничения и план отдельных исправлений

1. **P2: public API после отказа bootstrap.** itd-cookies.php:87–88 объявляет
   itd_cookies_allowed без условного блока; PHP регистрирует функцию до раннего
   return. В пяти guard cases function_exists=true, а вызов обращается к
   отсутствующим классам и бросает Error. Сам ITD runtime не стартует и admin
   notice безопасен. Но другой plugin, проверяющий только function_exists,
   может получить fatal. План: условное объявление либо явно fail-closed wrapper;
   отдельный regression на обоих guards и интеграцию caller. Исправление не внесено.
2. **P2: защита источника обновлений на Core <5.8 не гарантирована.**
   itd-cookies.php:9 Update URI игнорируется этим Core. В
   includes/class-itd-cookies-updater.php:190–195 ранние return оставляют исходную
   запись update_plugins: при отсутствии валидного GitHub release чужой ответ
   WordPress.org для того же basename не удаляется. При валидной metadata своя
   запись заменяется. Это вывод из кода, реальная чужая collision не воспроизводилась.
   План: отдельный узкий fail-closed ownership/source filter только своего
   basename + tests API timeout/malformed/unrelated plugins/provisional write.
3. **P2: контраст вторичных кнопок на Twenty Twenty 1.0.**
   assets/css/consent.css:103–105 задаёт белый background вторичной кнопки,
   но theme button:not(.toggle) выигрывает specificity. В браузере фактические
   foreground #0b57d0/background #cd2653, contrast ~1.22:1. На 320 px кнопки
   функционируют, но текст плохо различим. План: scoped specificity для нормального,
   hover/focus состояния; повторить themes/размеры/contrast. Скриншот wp53-panel-320.png.
4. **P3: обзорная документация устарела.** README.md:5 всё еще называет stable
   0.3.0, хотя metadata/header/package и GitHub Release — 0.4.2. Нужна отдельная
   синхронизация обзорного README при следующем документальном изменении.

Никакие найденные дефекты не исправлялись в stable runtime этой research-задачи.
Legacy strict CSP nonce/hash и полный PHP 7.3 runtime остаются NOT_RUN.

## Оценка PHP 7.3 / release strategy

Не обнаружено необходимости переписывать runtime из-за синтаксиса PHP 7.4.
Но снижение требует согласовать bootstrap, Composer requirement/platform,
header/readme, updater requires_php, release fixtures и отдельную полную
security/quality/browser матрицу. Не достаточно изменить одну строку guard.
Начальная оценка: 2–4 инженерных дня на первые tests/guards/source protection,
2–4 на legacy browser/theme/CSP/failure acceptance, затем постоянные двойные
регрессии. Это оценка, не выполненная работа или обещание поддержки.

PHP 7.3 EOL делает официальный Core путь WP 5.0 небезопасным для рекомендуемого
нового развёртывания; PHP 7.4 на WP 5.0 неофициален и также EOL. Отдельный legacy
release увеличивает стоимость поддержки и требует честной маркировки ограничений,
отдельной доставки/CI/rollback. Его создание сейчас не разрешено.

Рекомендация: сохранять современную основную линию, технически обосновать WP 5.3
при floor PHP 7.4 в отдельной задаче, исправить три P2 до заявления расширенной
legacy поддержки, для реальных сайтов сначала обновить Core/PHP на staging.
Запрос архитектурного выбора направлен владельцу; ожидание ответа не заменяет
разрешение снижать требования. Никакого compatibility release здесь нет.

## Фактически выполненные quality checks

- PHPUnit PHP 7.4.33: PASS, 50 tests / 494 assertions.
- JavaScript: PASS, 42 tests; ESLint PASS.
- PHPCS: PASS; PHPStan: PASS; PHPCompatibilityWP 7.3-7.3: PASS.
- Translation build: PASS, 96 messages; tracked languages diff отсутствует.
- Composer audit --locked: PASS, advisories не найдены; npm audit: 0 vulnerabilities.
- Gitleaks git history: PASS, 48 commits, no leaks; final staged working tree scan: PASS, no leaks.
- Build/inspector: PASS, 18 files. Воспроизведён неизменённый официальный ZIP SHA-256:
  58b60e6295c5c831cc94a80315a17d9c20a5543b3063031fbcd8d4da8a3befb6.
- Five bootstrap guards: PASS. WP 5.3 integration: PASS (activation/migration/
  reactivation, updater refresh/upgrade/failures, 9 adapter scenarios, benchmark).
- Native real GitHub browser upgrade: PASS; layout/consent/adapters PASS;
  secondary theme contrast FAIL отдельно от функциональных checks.
- Cleanup: PASS, original QA unchanged; all 6 disposable databases restored.

GitHub CI ссылки и результаты фиксируются для точного pushed research HEAD:
[CI branch](https://github.com/itdream24/itd-cookies/actions/workflows/ci.yml?query=branch%3Aresearch%2Fitd-cookies-wp50-feasibility),
[additional research matrix](https://github.com/itdream24/itd-cookies/actions/workflows/legacy-research.yml?query=branch%3Aresearch%2Fitd-cookies-wp50-feasibility).
До завершения run нельзя считать CI PASS; это не release gate и не разрешение merge.

## Принятые GitHub CI результаты

Research/test commit 0063b8c227f0e74f53910b3ebcc265838d6577ae:

- [CI run 37927381690](https://github.com/itdream24/itd-cookies/actions/runs/37927381690): **9/9 PASS**, completed/success, включая четыре WordPress matrix jobs и build.
- [Legacy run 37927381861](https://github.com/itdream24/itd-cookies/actions/runs/37927381861): **6/6 PASS**, completed/success; пять отказов guard и functional 5.3.26.
- CI latest фактически скачал Core **7.1.3**; PHP **7.4.33 / 8.5.11** подтверждены job logs. CI build повторил официальный SHA-256 58b60e6295c5c831cc94a80315a17d9c20a5543b3063031fbcd8d4da8a3befb6.

Итоговая правка только этого отчёта запускает оба workflow ещё раз на новом
HEAD. Финальный ответ фиксирует результат этих последних run; предыдущий PASS
не подменяет проверку итогового commit. Research PASS не означает WP 5.0
functional support или разрешение merge/release.
Приватные browser evidence: wp50-safe-refusal.png, wp53-real-update-offered.png,
wp53-native-update-complete.png, wp53-fresh-desktop.png, wp53-panel-1440.png,
wp53-panel-390.png, wp53-panel-320.png. Они сохранены локально отдельно от Git,
как и raw browser evidence/SQL/credentials. В финальном ответе приложены снимки.
