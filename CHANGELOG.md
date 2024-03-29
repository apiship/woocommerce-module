# Changelog #

## 2024-01-19 - version 1.6.0

* Добавлена возможность изменить стоимость доставки с получателя
* Добавлена возможность изменить способ доставки (СД, тариф, ПВЗ)
* На карту добавлены кнопки масштабирования + и -
* Улучшена скорость работы модуля
* Исправлена ошибка отображения большого кол-ва ПВЗ на карте
* Исправлен расчет общих габаритов заказа, если не указаны габариты грузоместа по умолчанию

## 2023-09-23 - version 1.5.0

* Добавлена возможность отображения всех ПВЗ на одной карте
* Добавлена возможность отображения ПВЗ каждой из СД на отдельной карте
* Добавлена возможность удаления заказа в ApiShip
* Исправлено формирование строки адреса получателя и отправителя
* Улучшен процесс удаления модуля

## 2023-07-31 - version 1.4.0

* Добавлена возможность пройти авторизацию в ApiShip по токену
* Добавлена возможность массовой генерации этикеток и актов приема-передачи
* Добавлена возможность валидации заказа до его передачи в СД
* Добавлена возможность отменять заказ в СД
* Добавлена возможность указать шаблон отображения способа доставки в корзине
* Добавлена возможность указать способ и пункт приема для каждой СД
* Добавлена возможность указать вес и габариты грузоместа по умолчанию
* Добавлена возможность пройти авторизацию в ApiShip по токену
* Добавлено отображение истории статусов доставки на странице работы с заказом
* Добавлено отображение трек номера на странице работы с заказом
* Добавлен функционал синхронизации статусов и настройка из сопоставления
* Изменено расположение кнопки выбора ПВЗ в корзине
* Убрана возможность пройти авторизацию в ApiShip по логину и паролю
* Исправлена ошибка передачи стоимости доставки с получателя

## 2022-06-29 - version 1.3.0
* Добавлено использование `ceil` для предотвращения передачи в запросе дробных размеров.
* Добавлено логирование в случае пустого `$response_body`.
* Добавлены переносы строк в карточку Транспортной Компании на странице заказа.
* Исправлены синтаксические ошибки.

## 2022-06-03 - version 1.2.0
* Удалена инициализация константы `WP_APISHIP_SHIPPING_CACHE`.

## 2022-05-27 - version 1.1.0
* Добавлена опция выбора Транспортных Компаний которые будут выводится на странице checkout.
* Обход кэша тарифов доставки в соответствии с опцией Woocommerce `Режим отладки`.
* Добавлен перевод кнопки `Select` для Яндекс карт на фронт-енд.
* Центрирование Яндекс карты в соответствии с выбранным ПВЗ.
* Исправлено PHP Notice: Undefined variable: response_body in includes/class-wp-apiship-shipping-method.php

## 2022-05-13 - version 1.0.0
* Первая версия.

## 2022-05-11 - version 1.0.0-RC5
* Промежуточный RC.

## 2022-05-11 - version 1.0.0-RC4
* Установлено значение `pickupType` в классе `WP_ApiShip_Order` по умолчанию для случая переданного значения в запросе `1,2`.
* Добавлена возможность удаления данных Пункта приёма заказа.
* Добавлен фильтр `cod` в запрос `lists/points` для Яндекс карт на фронт-енд.
* Добавлены переводы на странице настроек плагина.

## 2022-05-09 - version 1.0.0-RC3
* Добавлено сообщение если Yandex карты не загружены (админ).
* Исправлена ошибка в метабоксе если не выбрана Point In по умолчанию.
* Добавлено поле адреса Point Out для пользователя на странице checkout. 
* Добавлена кнопка закрытия карты Points Out для пользователя на странице checkout. 

## 2022-05-07 - version 1.0.0-RC2
* Обновлены переводы на странице настроек плагина.
* Обновлены подсказки на странице `Службы доставки`.
* Изменён алгоритм работы с мета данными метода `add_rate` класса `WP_ApiShip_Shipping_Method`.
* Добавлена пакетная печать наклеек.

## 2022-05-05 - version 1.0.0-RC1
* Добавлено предупреждение об отсутствии установки ПВЗ по умолчанию на карточке ТК.
* Добавлен вывод `Тип доставки` в информации о тарифе.
* Изменён алгоритм центрирования карт перед выбором ПВЗ.
* Изменён алгоритм редактирования данных пунктов приёма/выдачи заказов.
* Изменён алгоритм сохранения/загрузки данных контактного лица.

## 2022-05-04 - version 1.0.0-beta9
* Добавлена карта выбора ПВЗ на странице `checkout` для тарифов с доставкой в ПВЗ.
* Добавлена секция `Пункт выдачи заказа` в метабоксе ApiShip с возможностью смены ПВЗ на карте.
* Добавлена возможность перехода на страницу `Службы Доставки` со страницы `Заказ` кликом по лого транспортной компании.
* Номер заказа в метабоксе ApiShip выведен в поле с типом `text`.

## 2022-04-25 - version 1.0.0-beta8
* Добавлена возможность выбора/отмены предпочитаемых служб доставки на странице `Службы Доставки`.
* Добавлена возможность установки пунктов приёма отправлений на странице `Службы Доставки` для магазина/склада.
* Обновлена секция информации о службе доставки в метабоксе ApiShip.
* Добавлена секция `Пункт приёма заказа` с возможностью изменений адресов в метабоксе ApiShip.
* Добавлена смена места отгрузки магазин/склад (в зависимости от настроек) в секции `Отправитель` в метабоксе ApiShip.

## 2022-04-18 - version 1.0.0-beta7
* Добавлена константа `WP_APISHIP_SHIPPING_CACHE` со значением false|true для отмены|разрешения кеширования при тестировании.
* Добавлен метабокс для деталей заказа и кнопками действий.
* Добавлена иконка предупреждения о наличии заказа в системе ApiShip, но без информации в плагине.
* Исправлены ошибки в запросах API.

## 2022-04-11 - version 1.0.0-beta6
* Добавлено свойство $timeout в класс WP_ApiShip_HTTP.
* Добавлено кэширование запросов калькуляции доставки.

## 2022-04-10 - version 1.0.0-beta5
* Согласование настройки опции Country/State в адресе склада с основной опцией Woocommerce Country/State.
* Вывод сообщения об ошибке в секции Providers в случае ошибки в ответе API.
* В окно Tools добавлены запросы для удаления/отмены существующего заказа ApiShip.

## 2022-04-07 - version 1.0.0-beta4
* Добавлен расчёт общего веса в классе `WP_ApiShip_Order`.
* Добавлен перерасчёт стоимости доставки для каждого метода платежа:
  `Прямой банковский перевод`, `Оплата при доставке` на странице `checkout`.
* Добавлен JS скрипт для фронт-енд.
* Добавлена возмжность загрузки минифицированных скриптов JS,CSS.

## 2022-04-03 - version 1.0.0-beta3
* Добавлена опция `includeFees` на странице настроек.
* Добавлена опция `includeFees` в запросе на калькуляцию.
* Добавлена кнопка тестирования подключения к API на странице настроек.
* Добавлены переводы настроек ru_RU.

## 2022-03-31 - version 1.0.0-beta2
* Исправления в запросе на калькуляцию (кроме опции `includeFees`)
* Исправления в запросе на создание/редактирование заказа
* Добавлен метабокс ApiShip на странице заказа.

## 2022-03-29 - version 1.0.0-beta1
* Updates as 29.03.2022

## 2022-03-24 - version 0.2.0
* Updates as 24.03.2022

## 2022-03-11
* Added many updates.

## 2021-03-04 - version 0.1.0
* Init commits.