# Поддержка высокопроизводительного хранилища заказов (HPOS)

## Обзор

Начиная с версии 1.5.0, плагин WP ApiShip полностью поддерживает новую систему высокопроизводительного хранилища заказов (High-Performance Order Storage, HPOS) WooCommerce, также известную как Custom Order Tables.

## Что такое HPOS?

HPOS — это новая архитектура хранения заказов в WooCommerce 8.0+, которая:

- Хранит данные заказов в отдельных таблицах базы данных вместо системы постов WordPress
- Значительно улучшает производительность для магазинов с большим количеством заказов
- Обеспечивает более эффективные запросы к базе данных
- Упрощает масштабирование и оптимизацию

## Автоматическое определение системы

Плагин автоматически определяет, какая система хранения заказов используется:

```php
// Проверка включения HPOS
if (\WP_ApiShip\WP_ApiShip_HPOS_Compatibility::is_hpos_enabled()) {
    // Используется HPOS
} else {
    // Используется стандартная система постов
}
```

## Совместимость методов

Все методы работы с мета-данными заказов адаптированы для работы с обеими системами:

### Работа с мета-данными

```php
// Получение мета-данных
$value = \WP_ApiShip\Options\WP_ApiShip_Options::get_order_meta($order_id, $meta_key, $default_value);

// Обновление мета-данных  
\WP_ApiShip\Options\WP_ApiShip_Options::update_order_meta($order_id, $meta_key, $meta_value);
```

### Определение экранов админ-панели

```php
// Проверка экрана редактирования заказа
if (\WP_ApiShip\Options\WP_ApiShip_Options::is_order_edit_screen()) {
    // Код для экрана редактирования заказа
}

// Проверка экрана списка заказов
if (\WP_ApiShip\Options\WP_ApiShip_Options::is_orders_list_screen()) {
    // Код для экрана списка заказов
}
```

## Миграция данных

При первом обнаружении HPOS плагин автоматически предложит выполнить миграцию существующих данных.

### Автоматическая миграция

1. После обновления до версии 1.5.0+ появится уведомление в админ-панели
2. Нажмите кнопку "Начать миграцию" 
3. Процесс миграции выполняется пошагово для предотвращения таймаутов
4. Все существующие данные ApiShip будут перенесены в новую систему

### Ручная миграция

```php
// Принудительный запуск миграции
\WP_ApiShip\WP_ApiShip_HPOS_Migration::force_migration();

// Проверка статуса миграции
$status = \WP_ApiShip\WP_ApiShip_HPOS_Migration::get_migration_status();
```

## Технические детали

### Обновленные хуки

Плагин автоматически использует правильные хуки в зависимости от системы:

**Для HPOS:**
- `bulk_actions-woocommerce_page_wc-orders`
- `handle_bulk_actions-woocommerce_page_wc-orders`
- `manage_woocommerce_page_wc-orders_columns`
- `woocommerce_process_shop_order_meta`

**Для стандартной системы:**
- `bulk_actions-edit-shop_order`
- `handle_bulk_actions-edit-shop_order`
- `manage_edit-shop_order_columns`
- `save_post`

### Мета-боксы

Мета-боксы автоматически адаптируются для работы с новой системой заказов:

```php
// Автоматическое определение правильного экрана
$screen = \WP_ApiShip\Options\WP_ApiShip_Options::get_order_screen_id();
add_meta_box('wpapiship-order-metabox', 'ApiShip', $callback, $screen);
```

## Объявление совместимости

Плагин автоматически объявляет совместимость с HPOS:

```php
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
```

## Тестирование

Для проверки корректности работы HPOS используйте встроенные тесты:

```php
// Запуск всех тестов
$results = \WP_ApiShip\WP_ApiShip_HPOS_Test::run_tests();

// Генерация отчета
$report = \WP_ApiShip\WP_ApiShip_HPOS_Test::generate_report();
```

## Обратная совместимость

Плагин полностью сохраняет обратную совместимость:

- Продолжает работать со стандартной системой постов
- Автоматически переключается между системами
- Не требует изменений в настройках
- Сохраняет все существующие данные

## Устранение неполадок

### Проблемы с миграцией

1. **Миграция не запускается:**
   - Убедитесь, что HPOS включен в WooCommerce
   - Проверьте права доступа текущего пользователя

2. **Ошибки при миграции:**
   - Проверьте логи WordPress
   - Убедитесь в наличии достаточного времени выполнения PHP
   - Попробуйте принудительную миграцию

3. **Данные не отображаются:**
   - Проверьте статус миграции
   - Убедитесь, что мета-данные перенесены корректно

### Логирование

Включите логирование для отладки:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Требования

- WooCommerce 8.0+
- WordPress 6.0+
- PHP 7.4+

## Дополнительные ресурсы

- [Документация WooCommerce HPOS](https://woocommerce.com/document/high-performance-order-storage/)
- [Руководство по миграции WooCommerce](https://woocommerce.com/document/migrating-to-custom-order-tables/)