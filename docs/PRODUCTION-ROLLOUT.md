# Production rollout — черновик gate

Документ планирует будущую установку; production не является QA средой.
Выполненные проверки — [research report](ITD-COOKIES-WP50-FEASIBILITY.md),
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
