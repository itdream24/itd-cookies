# ITD Cookies — установка (черновик для владельца сайта)

Основание: официальный v0.4.2 и [feasibility audit](ITD-COOKIES-WP50-FEASIBILITY.md).
Это инструкция для будущего согласованного rollout; установка на production
в рамках исследования не выполнялась.

## До установки

1. На отдельном staging проверьте версии Core/PHP. Header 0.4.2 заявляет
   WP 5.2+, PHP 7.4+, но официальная совместимость Core с PHP 7.4 начинается
   с WP 5.3. PHP 7.3/7.4 EOL; для новых сайтов выбирайте актуальные Core и
   PHP с security support. WP 5.0 не является принятой поддержкой плагина.
2. Составьте список consent/analytics plugins, MU plugins, theme snippets,
   GTM tags, внешних script injections и кеширования. Определите владельца
   каждого SDK. Отключите дублирующие tracking snippets на staging и убедитесь,
   что тот же сервис не установлен одновременно нативно, в GTM и через adapter.
   Перед удалением snippets сохраните исходный код/настройки для rollback.
3. Сохраните файлы сайта, БД, plugin settings/active_plugins, policy pages и
   конфигурацию theme/cache. Проверьте Restore на отдельном сайте. Резервные
   копии, HAR, cookies и credentials храните вне web-root и Git.
4. При миграции ModuBricks сохраните его настройки и деактивируйте его consent
   engine перед включением ITD Cookies. Два consent engine вместе не поддержаны.

## Установка и настройка

1. Скачайте построенный asset itd-cookies-0.4.2.zip и checksum из
   [официального Release](https://github.com/itdream24/itd-cookies/releases/tag/v0.4.2).
   Source code ZIP GitHub не является installable production package.
2. Проверьте SHA-256: 58b60e6295c5c831cc94a80315a17d9c20a5543b3063031fbcd8d4da8a3befb6.
   В архиве один root itd-cookies/, main file, local assets и ru_RU translations;
   tests/docs/vendor/node_modules отсутствуют.
3. Plugins → Add New → Upload Plugin → Install → Activate. Для уже установленной
   копии используйте native updater. Upload/Replace имеется только в WP >=5.5;
   older Core assisted procedure — [rollback playbook](ROLLBACK-PLAYBOOK.md).
4. Settings → ITD Cookies: включите баннер, задайте заголовок/описание, один из
   пяти размеров, срок consent и version политики. Не меняйте consent version
   без содержательной причины: это заставляет посетителя выбирать снова.
5. Укажите HTTPS или относительные legal URLs. Абсолютный HTTP своего сайта
   преобразуется в относительный адрес; внешний HTTP отклоняется с ошибкой,
   прежнее валидное значение сохраняется. Проверьте фактически сохранённые URL.
6. Создайте Cookie Policy явной admin кнопкой и проверьте содержание вручную.
   При наличии существующей policy не заменяйте её без решения владельца.
7. Необходимые cookies always on; Functional/Analytics/Marketing — по выбору.
   Включайте только нужные провайдеры с корректными ID. Yandex/GA4/GTM/Clarity
   относятся к Analytics, Meta — к Marketing. Настройки тегов внутри GTM
   должен согласовать владелец контейнера; ITD не переписывает их автоматически.
8. Shortcodes: [itd_cookies_settings], [itd_cookies_policy],
   [itd_cookies_legal_links]. При желании включите отдельный legal footer.

## Проверка перед rollout

- Новый посетитель/отказ: собственных optional SDK запросов нет в Network.
- Analytics/Marketing отдельно, all, save/reopen, reload/revoke: категории
  сохранены и соответствуют Network. Vendor delivery требует отдельной проверки
  с владельцем аккаунта; synthetic IDs для QA не подтверждают отчёты vendor.
- Проверяйте отсутствие повторных SDK инициализаций, исключая внутренние
  дополнительные запросы самого SDK; сверяйте Console/PHP logs.
- Desktop и 390/320 px: видимые controls, контраст на реальной теме, keyboard
  Tab/Escape/focus return, доступные действия при длинной панели.
- Updater показывает только настоящий stable GitHub asset; настройки/marker/
  consent/policy после upgrade сохранены. [Rollout checklist](PRODUCTION-ROLLOUT.md).

ITD Cookies **не универсальный script firewall**: произвольные трекеры themes/
plugins не блокируются автоматически. Script Adapters контролируют только
явно зарегистрированные закрытые группы классических WordPress handles.
Descriptive service registry entry сама по себе скрипт не блокирует.
