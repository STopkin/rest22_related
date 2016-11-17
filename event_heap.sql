-- phpMyAdmin SQL Dump
-- version 4.1.14.7
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Окт 18 2016 г., 10:56
-- Версия сервера: 5.1.73-0ubuntu0.10.04.1
-- Версия PHP: 5.3.10-1~lucid+2uwsgi2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `rest22`
--

-- --------------------------------------------------------

--
-- Структура таблицы `event_heap`
--

CREATE TABLE IF NOT EXISTS `event_heap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `shedules` text COLLATE utf8_unicode_ci,
  `external_id` int(11) DEFAULT NULL,
  `done` tinyint(1) NOT NULL DEFAULT '0',
  `site` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'KINOMIR',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
