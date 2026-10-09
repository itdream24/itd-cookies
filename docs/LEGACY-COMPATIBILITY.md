# Legacy compatibility — политика совместимости

Verdict исследования: **WP50_COMPATIBILITY_NOT_RECOMMENDED**.
Полная [матрица и результаты](https://github.com/itdream24/itd-cookies/blob/4bc5fa007a1c2bd836bf10db009a080126c1c47f/docs/ITD-COOKIES-WP50-FEASIBILITY.md).

| Окружение | Практическое решение |
| --- | --- |
| WP 5.0–5.2 / PHP 7.3 | Core compatible, но ниже floor ITD Cookies; только safe refusal проверен. Не устанавливать как поддержанный runtime |
| WP 5.0–5.2 / PHP 7.4 | Core unsupported, даже если отдельные tests зелёные; не заявлять официальную поддержку |
| WP 5.3.26 / PHP 7.4 | Research 0.4.2 integration/browser PASS; кандидат 0.4.3 CLI PASS, Browser pending; PHP EOL |
| Актуальный WP / PHP с security support | Предпочтительный rollout после staging и полного CI |

[Официальная матрица](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/)
и [PHP EOL](https://www.php.net/eol.php) проверены 2026-10-09. Текущая stable
metadata 0.4.2 не изменена: WP 5.2+, PHP 7.4+. Для основного продукта при
PHP 7.4 технически обоснованный minimum Core — 5.3; решение о повышении
заявленного floor должно быть отдельным согласованным изменением.

## Ограничения старого Core

- WP <5.2 не валидирует requirements так же, как новый Core. Возможна пометка
  ACTIVE при отказавшем bootstrap; это не означает работающий consent engine.
- WP <5.8 не использует Update URI как защиту от same-slug WordPress.org
  update collision. В кандидате 0.4.3 добавлена узкая проверка собственного updater.
- WP <5.5 не имеет native Upload/Replace для существующей папки plugin.
  Native GitHub updater на 5.3 работает; ручное восстановление описано отдельно.
- Modern inline API имеет существующий fallback print_inline_script.
  Полный legacy strict CSP nonce/hash Browser E2E в этом исследовании NOT_RUN.
- Кандидат 0.4.3 исправляет контраст и public API после guard; опубликованный
  stable 0.4.2 остаётся неизменным.

## Если владелец всё-таки выберет PHP 7.3

Нужно отдельное архитектурное утверждение EOL-риска и модели поддержки.
Синтаксических блокеров сейчас не найдено, но это не разрешает понизить floor.
Требуется синхронизация всех requirements, guard/caller regression, updater
ownership на pre5.8, полный security/quality CI и функциональный browser E2E
5.0/5.1/5.2; отдельные темы/CSP/native updater/failure/rollback проверки.
Отдельная legacy линия увеличит число поддерживаемых сборок и матриц.
В исследовании legacy variant, compatibility shims и новый Release не создавались.

Рекомендуемый путь: backup → staging → обновить Core/PHP → принять consent/
analytics acceptance → согласовать rollout. Ни один smoke на неподдержанном
Core/PHP не заменяет официальную матрицу или security support PHP.

## Изменения 0.4.3

Опубликованная stable остаётся 0.4.2. Кандидат не разрешает production rollout.
Основная проверяемая линия начинается с WP 5.3 / PHP 7.4; новые production сайты
должны использовать актуальный Core и PHP с security support. WP 5.0–5.2
не включены в официальную матрицу поддерживаемых сочетаний. Тесты WP 5.2 /
PHP 7.4 — только регрессионный контроль существующих установок.

Metadata и runtime guard WP 5.2 сохранены: немедленное повышение до 5.3
отключило бы действующий consent engine на WP 5.2. Рекомендация владельцу:
сначала мигрировать эти сайты на поддерживаемые Core/PHP и отдельно согласовать
повышение floor. PHP 7.4 — EOL минимум совместимости, не рекомендация безопасности.

На отказавшем bootstrap consent API отсутствует. Интеграция должна проверять
function_exists('itd_cookies_allowed'); отсутствие API не означает согласие.
На WP ниже 5.8 кандидат проверяет собственные update records при сохранении
и чтении: только canonical stable GitHub asset доверен. При ошибке GitHub
чужая запись того же basename удаляется, остальные плагины не изменяются.
Details modal возвращает WP_Error вместо fallback на каталог WordPress.org.
Это не защита от произвольного стороннего кода, который обходит Core updater.

Для 0.4.0/0.4.1 старый updater может ждать истечения шестичасового metadata cache.
Проверка снова в этих версиях не гарантирует немедленного сброса GitHub cache.
При необходимости используйте официальный опубликованный ZIP через штатный
Upload/Replace на WP >=5.5. На старом Core сначала предпочтительно обновить Core;
assisted file recovery выполняет администратор по проверенному backup.
Не удаляйте плагин через uninstall ради обновления: это может удалить настройки.
Assisted recovery не считать автоматическим обновлением.

Не запускайте два consent engines. До включения провайдера проверьте theme,
MU plugins, snippets и GTM на уже встроенный SDK; отключение одной интеграции
не удаляет чужой tracking code. Rollback файлов не требует слепого возврата
устаревшей production БД с потерей новых данных. [Проверки кандидата](ITD-COOKIES-0.4.3.md).
