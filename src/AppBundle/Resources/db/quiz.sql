-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Wersja serwera:               10.4.10-MariaDB - mariadb.org binary distribution
-- Serwer OS:                    Win64
-- HeidiSQL Wersja:              10.2.0.5599
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Zrzut struktury tabela projekt.answers
DROP TABLE IF EXISTS `answers`;
CREATE TABLE IF NOT EXISTS `answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_correct` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_answers_questions` (`question_id`),
  KEY `FK_answers_quizes` (`quiz_id`),
  CONSTRAINT `FK_answers_questions` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  CONSTRAINT `FK_answers_quizes` FOREIGN KEY (`quiz_id`) REFERENCES `quizes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eksport danych został odznaczony.

-- Zrzut struktury tabela projekt.questions
DROP TABLE IF EXISTS `questions`;
CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL DEFAULT 0,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `points` int(11) NOT NULL DEFAULT 1,
  `multiple` tinyint(4) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sortable',
  PRIMARY KEY (`id`),
  KEY `FK_questions_quizes` (`quiz_id`),
  CONSTRAINT `FK_questions_quizes` FOREIGN KEY (`quiz_id`) REFERENCES `quizes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eksport danych został odznaczony.

-- Zrzut struktury tabela projekt.quizes
DROP TABLE IF EXISTS `quizes`;
CREATE TABLE IF NOT EXISTS `quizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `tries` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eksport danych został odznaczony.

-- Zrzut struktury tabela projekt.user_answers
DROP TABLE IF EXISTS `user_answers`;
CREATE TABLE IF NOT EXISTS `user_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `try_number` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_user_answer_answers` (`answer_id`),
  KEY `FK_user_answer_users` (`user_id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `FK_user_answers_questions` (`question_id`),
  CONSTRAINT `FK_user_answer_answers` FOREIGN KEY (`answer_id`) REFERENCES `answers` (`id`),
  CONSTRAINT `FK_user_answer_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `FK_user_answers_questions` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  CONSTRAINT `FK_user_answers_quizes` FOREIGN KEY (`quiz_id`) REFERENCES `quizes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eksport danych został odznaczony.

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

-- inserty

-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Wersja serwera:               10.4.10-MariaDB - mariadb.org binary distribution
-- Serwer OS:                    Win64
-- HeidiSQL Wersja:              10.2.0.5599
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Zrzucanie danych dla tabeli projekt.answers: ~12 rows (około)
DELETE FROM `answers`;
/*!40000 ALTER TABLE `answers` DISABLE KEYS */;
INSERT INTO `answers` (`id`, `question_id`, `quiz_id`, `text`, `is_correct`) VALUES
	(1, 1, 1, 'odp odp odp odp odp odp 1', 0),
	(7, 1, 1, 'odp odp odp odp odp odp 2', 0),
	(8, 1, 1, 'odp odp odp odp odp odp 3', 0),
	(9, 1, 1, 'odp odp odp odp odp odp 4', 0),
	(10, 2, 1, 'odp odp odp odp odp odp 5', 0),
	(11, 2, 1, 'odp odp odp odp odp odp 6', 0),
	(12, 2, 1, 'odp odp odp odp odp odp 7', 0),
	(13, 2, 1, 'odp odp odp odp odp odp 8', 0),
	(14, 3, 1, 'odp odp odp odp odp odp 9', 0),
	(15, 3, 1, 'odp odp odp odp odp odp 10', 0),
	(16, 3, 1, 'odp odp odp odp odp odp 11', 0),
	(17, 3, 1, 'odp odp odp odp odp odp 12', 0);
/*!40000 ALTER TABLE `answers` ENABLE KEYS */;

-- Zrzucanie danych dla tabeli projekt.questions: ~3 rows (około)
DELETE FROM `questions`;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
INSERT INTO `questions` (`id`, `quiz_id`, `text`, `points`, `multiple`, `status`, `type`) VALUES
	(1, 1, 'Pytanie Pytanie Pytanie Pytanie Pytanie Pytanie Pytanie Pytanie Pytanie 1', 1, 0, 1, 'sortable'),
	(2, 1, 'A WEDD DSFA WEDD DSFA WEDD DSFA WEDD DSFA WEDD DSF 2', 1, 0, 1, 'sortable'),
	(3, 1, 'fsadsafsadsafsadsafsadsa3 ', 1, 0, 1, 'sortable');
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;

-- Zrzucanie danych dla tabeli projekt.quizes: ~4 rows (około)
DELETE FROM `quizes`;
/*!40000 ALTER TABLE `quizes` DISABLE KEYS */;
INSERT INTO `quizes` (`id`, `name`, `description`, `status`, `date_start`, `date_end`, `tries`) VALUES
	(1, 'Quiz wiedzy pilkarskiej', 'There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don\'t look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn\'t anything embarrassing hidden in the middle of text.', 1, '2019-12-07 14:15:53', '2020-01-07 14:15:55', 1),
	(2, 'Testowy', 'Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia.', 1, '2019-09-07 14:04:50', '2020-02-07 14:05:00', 1),
	(3, 'Quiz z nagrodami', 'Witamy w konkursie Moja chwila dla siebie, w którym możesz wygrać cenne nagrody. Dzięki nim Twoja skóra i włosy będą wyglądały zdrowo i pięknie każdego dnia.', 1, '2019-12-07 18:41:31', '2020-02-07 18:41:31', 1),
	(5, 'Quiz z nagrodami', 'Witamy w konkursie Moja chwila dla siebie, w którym możesz wygrać cenne nagrody. Dzięki nim Twoja skóra i włosy będą wyglądały zdrowo i pięknie każdego dnia.', 1, '2019-12-07 18:41:31', '2020-02-07 18:41:31', 1);
/*!40000 ALTER TABLE `quizes` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

