-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
-- Хост: localhost:3306
-- Время создания: Янв 28 2026 г., 15:30
-- Версия сервера: 5.7.24
-- Версия PHP: 8.3.1
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET SESSION sql_require_primary_key = 0;
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `auto_parts_shop`
--

-- Структура таблицы `categories`
CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `categories`
INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Фильтры'),
(2, 'Тормоза'),
(3, 'Электрика'),
(4, 'Подвеска'),
(5, 'Двигатель');

-- Структура таблицы `news`
CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `news`
INSERT INTO `news` (`id`, `title`, `content`, `created_at`) VALUES
(1, 'Новое поступление запчастей', 'Мы получили новую партию масляных фильтров и тормозных колодок!', '2025-08-13 16:03:16'),
(2, 'Акция на аккумуляторы', 'Скидка 10% на все аккумуляторы до конца месяца.', '2025-08-15 10:20:33'),
(3, 'Расширение ассортимента', 'Добавили новые детали для подвески и системы охлаждения.', '2025-09-01 09:15:42'),
(4, 'Сезонное обслуживание', 'Подготовьтесь к зиме: скидки на антифриз и щетки стеклоочистителя.', '2025-10-05 14:30:21'),
(5, 'Новый сервисный центр', 'Открылся наш новый сервисный центр в центре города.', '2025-11-12 11:45:18');

-- Структура таблицы `orders`
CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `products` text NOT NULL,
  `status` enum('pending','accepted') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `orders`
INSERT INTO `orders` (`id`, `customer_name`, `customer_address`, `phone`, `total`, `products`, `status`, `created_at`) VALUES
(1, 'Медведев Захар Алексеевич', 'Студенческая 20', '+79102794704', '9500.00', '[{"id":1,"quantity":2},{"id":6,"quantity":1},{"id":2,"quantity":4}]', 'accepted', '2025-08-15 12:42:00'),
(2, 'Медведев Захар Алексеевич', 'Студенческая 20', '+79102794704', '500.00', '[{"id":1,"quantity":1}]', 'accepted', '2025-09-14 08:58:47'),
(3, 'Медведев Захар Алексеевич', 'Студенческая 20', '89102794704', '500.00', '[{"id":1,"quantity":1}]', 'pending', '2025-09-14 09:03:31'),
(4, 'Иванова Мария Петровна', 'Ленина 45, кв. 12', '+79205551234', '7800.00', '[{"id":4,"quantity":1},{"id":5,"quantity":2},{"id":3,"quantity":3}]', 'accepted', '2025-09-20 14:22:15'),
(5, 'Сидоров Алексей Владимирович', 'Гагарина 17, кв. 56', '+79301112233', '3200.00', '[{"id":2,"quantity":2},{"id":7,"quantity":1}]', 'accepted', '2025-10-03 16:45:09'),
(6, 'Козлов Дмитрий Сергеевич', 'Пушкина 33', '+79507778899', '15000.00', '[{"id":4,"quantity":3}]', 'pending', '2025-10-15 10:12:33'),
(7, 'Петрова Анна Игоревна', 'Советская 8', '+79604445566', '4200.00', '[{"id":8,"quantity":2},{"id":9,"quantity":1}]', 'accepted', '2025-10-28 09:34:21'),
(8, 'Волков Роман Олегович', 'Кирова 12б', '+79702223344', '6700.00', '[{"id":10,"quantity":1},{"id":11,"quantity":2},{"id":12,"quantity":1}]', 'pending', '2025-11-05 17:55:42'),
(9, 'Смирнова Екатерина Андреевна', 'Мира 50, кв. 78', '+79809990011', '12500.00', '[{"id":13,"quantity":5},{"id":14,"quantity":2}]', 'accepted', '2025-11-18 13:20:17'),
(10, 'Новиков Павел Дмитриевич', 'Набережная 7', '+79903334455', '8900.00', '[{"id":15,"quantity":1},{"id":16,"quantity":3},{"id":17,"quantity":2}]', 'pending', '2025-12-01 11:05:38');

-- Структура таблицы `products`
CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT '0',
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `products`
INSERT INTO `products` (`id`, `name`, `short_description`, `description`, `price`, `stock`, `category_id`) VALUES
(1, 'Масляный фильтр Mann', 'Высококачественный фильтр для двигателей', 'Оригинальный масляный фильтр Mann для легковых автомобилей', '500.00', 45, 1),
(2, 'Тормозные колодки передние', 'Керамические колодки с низким износом', 'Тормозные колодки с усиленной конструкцией для городской езды', '1500.00', 32, 2),
(3, 'Свечи зажигания NGK', 'Иридиевые свечи для стабильного запуска', 'Свечи зажигания с иридиевым электродом, срок службы до 100 000 км', '300.00', 60, 3),
(4, 'Аккумулятор 60 Ач', 'Необслуживаемый аккумулятор', 'Аккумуляторная батарея 12V 60 Ач, кальциевая технология', '5000.00', 18, 3),
(5, 'Воздушный фильтр салона', 'Фильтр с угольным слоем', 'Салонный фильтр с активированным углем для очистки воздуха', '700.00', 27, 1),
(6, 'Тормозной диск передний', 'Вентилируемый диск 280мм', 'Тормозной диск для передней оси, диаметр 280мм', '2500.00', 96, 2),
(7, 'Рычаг передней подвески', 'Левый рычаг с шаровой опорой', 'Рычаг передней подвески в сборе с сайлентблоками', '1600.00', 15, 4),
(8, 'Амортизатор передний', 'Газовый амортизатор', 'Передний газовый амортизатор для легковых автомобилей', '2100.00', 22, 4),
(9, 'Стойка стабилизатора', 'Комплект 2 шт.', 'Стойки стабилизатора поперечной устойчивости', '800.00', 40, 4),
(10, 'Ремень ГРМ', 'Комплект с роликами', 'Ремень ГРМ с натяжным и ведущим роликами', '1800.00', 35, 5),
(11, 'Термостат', 'Термостат 82 градуса', 'Термостат для системы охлаждения двигателя', '900.00', 28, 5),
(12, 'Масло моторное 5W-30', 'Синтетическое масло 4л', 'Полностью синтетическое моторное масло 5W-30, 4 литра', '2800.00', 50, 5),
(13, 'Фильтр топливный', 'Топливный фильтр тонкой очистки', 'Фильтр для системы впрыска топлива', '2500.00', 24, 1),
(14, 'Тормозной цилиндр', 'Задний тормозной цилиндр', 'Ремкомплект заднего тормозного цилиндра', '3750.00', 19, 2),
(15, 'Генератор', 'Генератор 14В 90А', 'Автомобильный генератор с регулятором напряжения', '7500.00', 8, 3),
(16, 'Рулевая тяга', 'Внутренняя рулевая тяга', 'Рулевая тяга с наконечником в сборе', '1450.00', 17, 4),
(17, 'Помпа водяная', 'Помпа с прокладкой', 'Водяной насос системы охлаждения двигателя', '2200.00', 14, 5),
(18, 'Катушка зажигания', 'Катушка зажигания оригинал', 'Высоковольтная катушка зажигания', '1100.00', 31, 3),
(19, 'Ступичный подшипник', 'Передний ступичный подшипник', 'Ступичный подшипник в сборе с датчиком ABS', '1950.00', 20, 4),
(20, 'Прокладка ГБЦ', 'Металлическая прокладка', 'Прокладка головки блока цилиндров усиленная', '1650.00', 25, 5);

-- Структура таблицы `reviews`
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `message` text NOT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `reviews`
INSERT INTO `reviews` (`id`, `name`, `rating`, `message`, `is_approved`, `created_at`) VALUES
(1, 'Дима', 5, 'качественно', 1, '2025-09-11 21:54:00'),
(2, 'Анна', 5, 'Быстрая доставка и отличное качество запчастей!', 1, '2025-09-25 14:22:10'),
(3, 'Сергей', 4, 'Хорошие цены, но доставка задержалась на день', 1, '2025-10-08 09:15:33'),
(4, 'Ольга', 5, 'Заказывала тормозные колодки - все идеально подошло!', 1, '2025-10-30 18:40:22'),
(5, 'Максим', 3, 'Товар пришел с небольшой вмятиной на упаковке', 0, '2025-11-15 11:05:47'),
(6, 'Евгений', 5, 'Уже третий раз покупаю здесь - всегда доволен!', 1, '2025-12-10 16:33:09');

-- Структура таблицы `site_reviews`
CREATE TABLE `site_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `site_reviews`
INSERT INTO `site_reviews` (`id`, `name`, `rating`, `comment`, `created_at`, `approved`) VALUES
(1, 'Иван К.', 5, 'Отличный магазин! Все запчасти оригинальные.', '2025-09-20 10:15:22', 1),
(2, 'Мария С.', 4, 'Хороший выбор, но сайт иногда тормозит', '2025-10-05 14:30:18', 1),
(3, 'Алексей П.', 5, 'Быстро обрабатывают заказы, рекомендую!', '2025-11-01 09:45:33', 1);

--
-- Индексы сохранённых таблиц
--

-- Индексы таблицы `categories`
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

-- Индексы таблицы `news`
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

-- Индексы таблицы `orders`
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

-- Индексы таблицы `products`
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

-- Индексы таблицы `reviews`
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

-- Индексы таблицы `site_reviews`
ALTER TABLE `site_reviews`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

-- AUTO_INCREMENT для таблицы `categories`
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

-- AUTO_INCREMENT для таблицы `news`
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

-- AUTO_INCREMENT для таблицы `orders`
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

-- AUTO_INCREMENT для таблицы `products`
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

-- AUTO_INCREMENT для таблицы `reviews`
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

-- AUTO_INCREMENT для таблицы `site_reviews`
ALTER TABLE `site_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

-- Ограничения внешнего ключа таблицы `products`
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;