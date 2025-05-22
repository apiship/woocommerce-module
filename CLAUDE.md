# CLAUDE.md

Этот файл предоставляет руководство для Claude Code (claude.ai/code) при работе с кодом в данном репозитории.

## Обзор проекта

WP ApiShip — это плагин для WooCommerce, интегрирующий сервис ApiShip для расчета стоимости доставки и управления заказами. Предоставляет единый интерфейс для работы с 40+ службами доставки через платформу ApiShip.

**Текущая версия:** 1.5.0 (с поддержкой HPOS)

## Архитектура

### Основные компоненты

**Структура плагина:**
- `wp-apiship.php` - главный файл плагина, управляет зависимостями WooCommerce и инициализацией
- `includes/class-wp-apiship-core.php` - центральный синглтон, управляющий жизненным циклом плагина
- `includes/class-wp-apiship-shipping-method.php` - реализация метода доставки WooCommerce (наследует `WC_Shipping_Method`)
- `includes/class-wp-apiship-options.php` - управление конфигурацией и настройками плагина

**API-слой (`includes/api/`):**
- Классы Calculator обрабатывают запросы расчета стоимости доставки к сервису ApiShip
- Классы Order управляют преобразованием данных заказов для API ApiShip
- Классы Connection управляют аутентификацией провайдеров и настройками

**Совместимость с HPOS (v1.5.0+):**
- `class-wp-apiship-hpos-compatibility.php` - слой абстракции для HPOS vs устаревшего хранения постов
- `class-wp-apiship-hpos-migration.php` - автоматическая миграция данных между системами хранения
- `class-wp-apiship-hpos-test.php` - утилиты тестирования совместимости с HPOS

**Административный интерфейс (`includes/admin/`):**
- Административные страницы, мета-боксы, инструменты маппинга и интеграция с WooCommerce
- Система шаблонов для компонентов административного интерфейса

### Ключевые паттерны проектирования

**Паттерн Singleton:** Основные классы используют паттерн синглтон (например, `WP_ApiShip_Core::get_instance()`)

**Использование пространств имен:** Все классы используют пространство имен `WP_ApiShip` с подпространствами для организации

**WordPress хуки:** Активное использование хуков действий/фильтров WordPress для расширяемости

**Абстракция HPOS:** Методы автоматически определяют и используют подходящую систему хранения (HPOS vs устаревшие посты)

## Рекомендации по разработке

### Совместимость с HPOS

Всегда используйте слой совместимости при работе с данными заказов:

```php
// Получение мета-данных заказа
$value = \WP_ApiShip\WP_ApiShip_HPOS_Compatibility::get_order_meta($order, $meta_key, true, $default);

// Обновление мета-данных заказа
\WP_ApiShip\WP_ApiShip_HPOS_Compatibility::update_order_meta($order, $meta_key, $value);

// Проверка включения HPOS
if (\WP_ApiShip\WP_ApiShip_HPOS_Compatibility::is_hpos_enabled()) {
    // Логика для HPOS
}
```

### Стандарты WordPress

- Используйте стандарты кодирования WordPress для PHP
- Экранируйте весь вывод соответствующими функциями (`esc_html__`, `esc_attr`, и т.д.)
- Интернационализация: используйте `__()`, `_e()`, `esc_html__()` с текстовым доменом `'wp-apiship'`
- Добавляйте префикс `wp_apiship` ко всем функциям, хукам и глобальным переменным или используйте пространства имен

### Интеграция с WooCommerce

- Наследуйте `WC_Shipping_Method` для реализации метода доставки
- Используйте хуки WooCommerce для обработки заказов и интеграции с админ-панелью
- Следуйте соглашениям структуры данных WooCommerce для заказов, доставки и корзины

### Интеграция с API

- Вся коммуникация с API ApiShip проходит через класс `WP_ApiShip_HTTP`
- Классы API-запросов в `includes/api/` обрабатывают преобразование данных
- Используйте правильную обработку ошибок и логирование для сбоев API

## Организация файлов

**Основные файлы плагина:**
- Основная логика плагина в `includes/class-wp-apiship-*.php`
- API-классы в `includes/api/`
- Административный интерфейс в `includes/admin/`
- Шаблоны в `includes/admin/templates/`

**Ресурсы:**
- CSS: `assets/css/` (полные и минифицированные версии)
- JavaScript: `assets/js/` (полные и минифицированные версии)
- Изображения: `assets/images/`

**Локализация:**
- Файлы переводов в директории `languages/`
- POT-шаблон: `wp-apiship.pot`
- Русский перевод: `wp-apiship-ru_RU.po/mo`

## Важные константы

```php
WP_APISHIP_VERSION // Версия плагина
WP_APISHIP_SHIPPING_CACHE // Флаг управления кэшем
```

## Тестирование

Плагин включает тесты совместимости с HPOS в `class-wp-apiship-hpos-test.php`. Запускайте их при внесении изменений в функциональность, связанную с заказами.

## Процесс сборки

Этот проект не использует систему сборки (нет package.json/composer.json). JavaScript и CSS файлы поддерживаются вручную как в полной, так и в минифицированной версиях.

## Обработка миграции

При работе с функциями HPOS учитывайте автоматическую систему миграции, которая переносит данные из устаревших мета-данных постов в HPOS при его включении. Тщательно тестируйте с обеими системами хранения.

## Ключевые методы HPOS-совместимости

```php
// Получение мета-данных заказа
WP_ApiShip_HPOS_Compatibility::get_order_meta($order, $meta_key, $single, $default)

// Обновление мета-данных заказа
WP_ApiShip_HPOS_Compatibility::update_order_meta($order, $meta_key, $meta_value)

// Добавление мета-данных заказа
WP_ApiShip_HPOS_Compatibility::add_order_meta($order, $meta_key, $meta_value, $unique)

// Удаление мета-данных заказа
WP_ApiShip_HPOS_Compatibility::delete_order_meta($order, $meta_key, $meta_value)

// Получение заказов по мета-запросу
WP_ApiShip_HPOS_Compatibility::get_orders_by_meta($meta_query, $args)

// Проверка экрана редактирования заказа
WP_ApiShip_HPOS_Compatibility::is_order_edit_screen()

// Проверка экрана списка заказов
WP_ApiShip_HPOS_Compatibility::is_orders_list_screen()
```