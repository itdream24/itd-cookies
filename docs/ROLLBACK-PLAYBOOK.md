# Rollback и assisted recovery — черновик

Исполнять только в согласованном окружении после проверенного backup.
Research не менял production. [Installation guide](INSTALLATION-GUIDE.md).

## Перед восстановлением

1. Зафиксировать ошибку, версию/active_plugins, Console/PHP logs и ожидаемое
   состояние settings/consent version/migration marker/policy pages.
2. Сохранить текущие данные, если rollback БД может потерять новые заказы/
   записи сайта. Выбрать восстановление только plugin files либо всей БД
   вместе с файлами по согласованной точке, не восстанавливать БД вслепую.
3. Использовать backup вне web-root/Git и проверенный официальный stable ZIP.
   Не править опубликованные tags/assets и не подменять updater metadata.

## Ошибка установки / сломанный сайт

- Если admin доступен: деактивировать ITD Cookies, вернуть принятые plugin
  files из backup/официального asset, проверить версию и settings перед активацией.
- Если admin недоступен: администратор хостинга восстанавливает plugin files
  штатным файловым/backup инструментом; полная БД только по согласованному плану.
- Проверить consent engine и прежнюю аналитику без повторной инициализации;
  не включать одновременно ModuBricks и ITD Cookies. Сохранить старые legal
  страницы и version согласия, если политика не менялась.
- Проверить Native Updates, shortcodes/footer, Console/Network и restore hashes.
  Для принятия restore сравнить схемы/строки всех таблиц, settings/activity и
  файловый manifest, учитывая согласованный период новых бизнес-данных.

## Старый updater 0.4.0/0.4.1

Старый установленный код может хранить GitHub metadata до ~6 часов. Новый
0.4.2 не исправляет старый код до запуска обновлённых файлов. Сначала обычная
проверка WordPress/естественное истечение TTL; отсутствие немедленного update
notice не доказывает отсутствие публичного Release. Не объявлять очистку
кеша вручную доказательством автоматической миграции.

**Assisted recovery** при необходимости: скачать официальный built ZIP с
checksum, сделать backup/settings capture и установить штатно через WordPress.
На WP >=5.5 использовать Upload/Replace. На более старом Core без такой кнопки
отдельно проверить на staging последовательность deactivate → удалить только
папку plugin через штатный uninstall → установить официальный ZIP → activate.
Текущий uninstall.php оставляет settings, consent и legacy ModuBricks options;
policy pages/marker также нужно сверить. Не выполнять эту последовательность
без backup и staging verification; именно delete/reinstall на старом Core
не проверялось в этом research. Это assisted, а не автоматическое обновление.

После запуска 0.4.2 Dashboard → Updates → Check again сбрасывает GitHub cache
и update_plugins и получает настоящий stable Release. Защита источника
обновлений на Core <5.8 требует отдельного hardening; Update URI там не работает.
Повторить native upgrade, settings/marker/consent persistence и Network gate.
Не создавать synthetic GitHub Release для recovery и не понижать minimum PHP.
