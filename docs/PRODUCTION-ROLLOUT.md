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

Для stable 0.4.4 отдельно проверить пять размеров на root font-size
темы: новая локальная база 16 px может увеличить или уменьшить прежний
«Стандартный». Проверить минимум 320/390/768/1440 px, шрифт темы, прокрутку
категорий и доступность кнопок. Выравнивание применяется только к optional
автоматическому legal footer; центр — default, слева/справа — явный выбор.
При manual footer shortcode автоматический вывод отключить, чтобы не создавать
дубликат. Никакого JS-переноса разметки темы или output buffering не требуется.
Dev-сборки не устанавливать на production. Rollout 0.4.4 — только после публикации, реального native updater acceptance и отдельного разрешения владельца.

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

## Независимые встроенные карты — обязательная проверка проектов

- [Принятое правило](MAPS-INTEGRATION-POLICY.md): iframe/constructor Яндекс.Карт
  автоматически работают при Fresh, Reject, Analytics, All и после Revoke.
  Сохранить одну карту, её размеры/расположение/параметры; если уже работает — NO-OP.
- Не переносить сайтовый consent-gate карты или регистрировать map handles в
  gated Script Adapters. Исправлять только подтверждённую несовместимую интеграцию
  сайта, без глобального iframe/script interception и без новой миграции плагина.
- Проверить отдельно native Metrika: до Analytics0 loader; после permission1
  loader/init на документ; после revoke в новом документе0. Map iframe tracking
  не считать дублем native counter и не выдавать за нарушение его gating.
- Изучить карту/SDK/telemetry/cookies в Network по frame/initiator/counter ID.
  Reject не отключает собственную аналитику внешней карты. Данные, уже переданные
  поставщику, не возвращаются удалением cookie. Не классифицировать карту как
  необходимый cookie только ради обхода consent.
- Владелец отдельно согласует privacy-описание карты и актуальные юридические
  сведения; автозагрузка не гарантирует соответствие. Тексты и consent_version
  автоматически не менять, map-gate не вводить без нового решения.
- Local desktop/mobile E2E: iframe + constructor, нулевые группы, unrelated
  adapters/dependencies/errors/dedup, сохранение consent/marker, cleanup/Restore.
  Production: только отдельно разрешённый guest smoke, без QA fixtures/observers.

План UI0.4.4 включает переводимую информационную подсказку в настройках;
это сообщение о внешних сервисах, не управляющий переключатель. Вместе с
typography/footer работами пройти обычный полный CI/release gate. Отдельный
документационный Release не создаётся.

## Выполнение согласованного production rollout

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

## Изменения 0.4.3

Опубликованная и локально принятая stable — 0.4.3. Production rollout требует отдельного разрешения владельца.
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
На WP ниже 5.8 версия 0.4.3 проверяет собственные update records при сохранении
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
устаревшей production БД с потерей новых данных. [Приёмка релиза](ITD-COOKIES-0.4.3.md).


Stable v0.4.3: [официальный Release](https://github.com/itdream24/itd-cookies/releases/tag/v0.4.3), built asset `itd-cookies-0.4.3.zip`, SHA-256 `09d9ddb4a6b03f13d83875cff1c1cf657f5842a5d9d97683ef09dc8dc654c06d`. Реальное native обновление с 0.4.2 и новый immutable baseline проверены локально; production не изменялся. При обнаружении дефекта после публикации готовится отдельный patch release, опубликованные assets не перезаписываются.
