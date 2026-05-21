# Kosmosafive: Командная строка PHP

Инструмент для разработчиков, работающих с проектами на Bitrix. 

Позволяет писать и запускать PHP-код прямо из браузера, 
не обращаясь к файловой системе сервера и не выходя из административной части сайта.

Может использоваться как замена стандартного функционала или дополнительный инструмент.

<p align="center">
    <picture>
      <img alt="img-main" src="https://raw.githubusercontent.com/kosmosafive/kosmosafive.commandline/HEAD/.github/img-main.png" style="max-width: 100%;">
    </picture>
</p>

- **Многовкладочный редактор**. Одновременно можно держать открытыми несколько независимых скриптов. Вкладки создаются, переименовываются и удаляются в пару кликов. Все скрипты сохраняются в браузере автоматически — они никуда не пропадут после перезагрузки страницы или закрытия вкладки.

<p align="center">
    <picture>
      <img alt="img-tabs" src="https://raw.githubusercontent.com/kosmosafive/kosmosafive.commandline/HEAD/.github/img-tabs.png" style="max-width: 100%;">
    </picture>
</p>

- **Полноценный редактор кода**. В основе — Monaco Editor, тот же движок, что используется в VS Code. Подсветка синтаксиса PHP, автодополнение, парные скобки, отступы — всё работает так, как ожидает разработчик.

<p align="center">
    <picture>
      <img alt="img-editor" src="https://raw.githubusercontent.com/kosmosafive/kosmosafive.commandline/HEAD/.github/img-editor.png" style="max-width: 100%;">
    </picture>
</p>

- **Автодополнение при наборе кода**. Редактор знает о ваших классах и методах. При наборе кода предлагаются подсказки с сигнатурами методов и сниппеты с подстановкой параметров.

<p align="center">
    <picture>
      <img alt="img-completion" src="https://raw.githubusercontent.com/kosmosafive/kosmosafive.commandline/HEAD/.github/img-completion.png" style="max-width: 100%;">
    </picture>
</p>

- **Запуск кода**. Написанный скрипт отправляется на сервер и выполняется в контексте текущего Bitrix-окружения. Результат отображается сразу под редактором. Для удобства работы с отладочным выводом (например, var_dump, print_r или вывод Symfony VarDumper) доступен режим отображения оригинального HTML-ответа.


- **Вывод результата**. Консоль показывает результат выполнения, время работы скрипта, потребление памяти и статус запроса. При необходимости вывод можно открыть на весь экран — удобно, когда результат объёмный.

<p align="center">
    <picture>
      <img alt="img-output" src="https://raw.githubusercontent.com/kosmosafive/kosmosafive.commandline/HEAD/.github/img-output.png" style="max-width: 100%;">
    </picture>
</p>

- **Настройка под себя**. Светлая и тёмная тема интерфейса, несколько тем оформления самого редактора, режим полного экрана для работы с большими скриптами.

<p align="center">
    <picture>
      <img alt="img-dark-theme" src="https://raw.githubusercontent.com/kosmosafive/kosmosafive.commandline/HEAD/.github/img-dark-theme.png" style="max-width: 100%;">
    </picture>
</p>

## Установка

В composer.json (пример для директории local) проекта добавьте

```json
{
  "require": {
    "wikimedia/composer-merge-plugin": "dev-master"
  },
  "config": {
    "allow-plugins": {
      "wikimedia/composer-merge-plugin": true
    }
  },
  "extra": {
    "merge-plugin": {
      "require": [
        "../bitrix/composer-bx.json",
        "modules/*/composer.json"
      ],
      "recurse": true,
      "replace": true,
      "ignore-duplicates": false,
      "merge-dev": true,
      "merge-extra": false,
      "merge-extra-deep": false,
      "merge-scripts": false
    },
    "installer-paths": {
      "modules/{$name}/": [
        "type:bitrix-d7-module"
      ]
    }
  }
}
```

Установите модуль и зависимости.

## Конфигурация модуля

Конфигурация указывается в файле /bitrix/.settings.php или /bitrix/.settings_extra.php.

* _replace_ — заменять ли ссылку на стандартную страницу выполнения кода (по умолчанию true)
* _dirs_ — список сканируемых директорий при формировании подсказок (по умолчанию директории модулей).

```php
return [
    'kosmosafive.commandline' => [
        'value' => [
            'replace' => true,
            'dirs' => [
                $_SERVER['DOCUMENT_ROOT'] . '/local/modules',
                $_SERVER['DOCUMENT_ROOT'] . '/local/vendor',
                $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules',
            ],           
        ],
    ],
];
```

### Подсказки в редакторе

Для генерации подсказок в редакторе необходимо запустить консольную команду

```bash
php bitrix.php kosmosafive.commandline:generate-hints
```

Команда создает файл конфигурации с подсказками для редактора на основе модулей ядра продукта и пользовательских модулей.

