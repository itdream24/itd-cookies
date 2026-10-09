# Legacy compatibility — черновик решения

Verdict исследования: **WP50_COMPATIBILITY_NOT_RECOMMENDED**.
Полная [матрица и результаты](ITD-COOKIES-WP50-FEASIBILITY.md).

| Окружение | Практическое решение |
| --- | --- |
| WP 5.0–5.2 / PHP 7.3 | Core compatible, но ниже floor ITD Cookies; только safe refusal проверен. Не устанавливать как поддержанный runtime |
| WP 5.0–5.2 / PHP 7.4 | Core unsupported, даже если отдельные tests зелёные; не заявлять официальную поддержку |
| WP 5.3.26 / PHP 7.4 | Реальные integration/browser PASS; PHP EOL остаётся риском |
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
  update collision. Требуется отдельное hardening собственного updater.
- WP <5.5 не имеет native Upload/Replace для существующей папки plugin.
  Native GitHub updater на 5.3 работает; ручное восстановление описано отдельно.
- Modern inline API имеет существующий fallback print_inline_script.
  Полный legacy strict CSP nonce/hash Browser E2E в этом исследовании NOT_RUN.
- Низкий контраст secondary buttons на Twenty Twenty и public API после
  guard — отдельные найденные P2. Stable runtime автоматически не исправлялся.

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
