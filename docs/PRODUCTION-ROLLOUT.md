# Production rollout — checklist

Документ планирует будущую установку; production не является QA средой.
Выполненные проверки — [research report](https://github.com/itdream24/itd-cookies/blob/4bc5fa007a1c2bd836bf10db009a080126c1c47f/docs/ITD-COOKIES-WP50-FEASIBILITY.md),
инструкция — [installation guide](INSTALLATION-GUIDE.md).

## Inventories и решение до изменений

- Зафиксировать Core/PHP/theme/cache, согласовать поддержанное сочетание;
  PHP 7.4 compatibility floor не равен рекомендуемому безопасному хостингу.
- Инвентаризировать активные/неактивные analytics/consent plugins, MU code,
  theme snippets, GTM containers и integrations. Назначить одного владельца SDK.
- Сохранить исходный tracking code, plugin settings/activity, policies и IDs.
  Удалять дубликаты только по согласованному плану; verify в staging Network.
- Подготовить восстановимый backup файлов и всех таблиц БД, проверить Restore
  отдельно. Записать ответственного, окно изменений и условия rollback.
- Выбрать уже опубликованный принятый stable asset и проверить checksum.
  Не создавать новый пакет/тег во время rollout и не использовать dev/source ZIP.

## Staging acceptance

1. Проверить активацию, миграцию allowlist ModuBricks, сохранность marker/settings.
   Не держать два consent engine активными одновременно.
2. Настроить минимально нужные providers, категории, consent lifetime/version,
   legal links, Cookie Policy, shortcode opener и при необходимости footer.
3. Fresh/Reject/Analytics/Marketing/All/Save/Reload/Revoke; до consent собственные
   optional SDK отсутствуют. Просмотреть Network и Console, theme conflicts.
4. Проверить accounts/delivery отдельно с владельцем; GTM tags не наследуют
   автоматически корректную юридическую категорию из факта загрузки контейнера.
5. Проверить desktop/mobile, keyboard, длинные legal URLs/описания, контраст.
6. Для custom Script Adapters проверить declared ownership, закрытые зависимости,
   inline order, dedup/errors и нулевые группы. Unknown scripts не блокируются.
7. Проверить native stable upgrade, metadata/TTL/manual refresh и rollback.
   Controlled HTTP fixtures не считать настоящим GitHub release discovery.
8. Очистить staging fixtures/test cookies/IDs. Сохранить отчёт и baseline.

## Production после отдельного разрешения владельца

Применить только принятый stable ZIP и согласованные настройки; сверить SHA,
активность/версию/marker/policy. Проверить fresh consent в обычном браузере,
реальные SDK после разрешения, PHP logs/Console и отсутствие дублей. Выполнить
ограниченное наблюдение без debug helpers и синтетических fixtures на production.
При fatal/повреждении updater/некорректном consent gating — остановить rollout
и исполнить [rollback playbook](ROLLBACK-PLAYBOOK.md).

ITD Cookies не блокирует автоматически чужие trackers и не является
универсальным script firewall или гарантией юридического соответствия.
Политика и категории требуют решения владельца сайта и его специалистов.
Отдельный rollout на WP 5.0 в настоящий момент не рекомендован и не разрешён.

## Изменения кандидата 0.4.3-dev.1

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
