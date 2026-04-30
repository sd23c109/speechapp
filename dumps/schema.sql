/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.4-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: mka
-- ------------------------------------------------------
-- Server version	11.8.4-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Current Database: `mka`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `mka` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `mka`;

--
-- Table structure for table `admin_pricing`
--

DROP TABLE IF EXISTS `admin_pricing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_pricing` (
  `pricing_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_user_uuid` varchar(36) NOT NULL,
  `tier_name` varchar(50) NOT NULL,
  `price_monthly` decimal(10,2) NOT NULL,
  `price_yearly` decimal(10,2) NOT NULL,
  `max_sounds` int(11) DEFAULT NULL,
  `max_words` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`pricing_id`),
  UNIQUE KEY `unique_admin_tier` (`admin_user_uuid`,`tier_name`),
  KEY `idx_admin` (`admin_user_uuid`),
  CONSTRAINT `admin_pricing_ibfk_1` FOREIGN KEY (`admin_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_pricing`
--

LOCK TABLES `admin_pricing` WRITE;
/*!40000 ALTER TABLE `admin_pricing` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `admin_pricing` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `assignment_success_media`
--

DROP TABLE IF EXISTS `assignment_success_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignment_success_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_group_id` bigint(20) unsigned NOT NULL,
  `media_id` int(11) NOT NULL,
  `display_order` int(11) DEFAULT 999,
  `selection_type` enum('sequential','random','specific') DEFAULT 'sequential',
  `specific_exercise_index` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment_media` (`assignment_group_id`,`media_id`,`specific_exercise_index`),
  KEY `idx_assignment` (`assignment_group_id`),
  KEY `idx_media` (`media_id`),
  KEY `idx_order` (`display_order`),
  CONSTRAINT `assignment_success_media_ibfk_1` FOREIGN KEY (`assignment_group_id`) REFERENCES `exercise_assignment_groups` (`assignment_group_id`) ON DELETE CASCADE,
  CONSTRAINT `assignment_success_media_ibfk_2` FOREIGN KEY (`media_id`) REFERENCES `exercise_success_media` (`media_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Links assignments to success media for completion celebration';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_success_media`
--

LOCK TABLES `assignment_success_media` WRITE;
/*!40000 ALTER TABLE `assignment_success_media` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `assignment_success_media` VALUES
(1,2,8,999,'sequential',NULL,'2026-02-15 15:47:47'),
(2,1,8,999,'sequential',NULL,'2026-02-15 15:47:47'),
(3,2,1,999,'sequential',NULL,'2026-02-18 15:11:25');
/*!40000 ALTER TABLE `assignment_success_media` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_3cv_blends`
--

DROP TABLE IF EXISTS `exercise_3cv_blends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_3cv_blends` (
  `blend_3cv_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_uuid` varchar(36) DEFAULT NULL,
  `consonant_id` bigint(20) unsigned NOT NULL,
  `vowel_id` bigint(20) unsigned NOT NULL,
  `blend_code` varchar(20) NOT NULL,
  `blend_folder` varchar(100) DEFAULT NULL,
  `icon_filename` varchar(255) DEFAULT NULL,
  `icon_path` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`blend_3cv_id`),
  UNIQUE KEY `unique_3cv` (`owner_user_uuid`,`consonant_id`,`vowel_id`),
  KEY `idx_owner` (`owner_user_uuid`),
  KEY `idx_consonant` (`consonant_id`),
  KEY `idx_vowel` (`vowel_id`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `exercise_3cv_blends_ibfk_1` FOREIGN KEY (`owner_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE,
  CONSTRAINT `exercise_3cv_blends_ibfk_2` FOREIGN KEY (`consonant_id`) REFERENCES `exercise_consonants` (`consonant_id`) ON DELETE CASCADE,
  CONSTRAINT `exercise_3cv_blends_ibfk_3` FOREIGN KEY (`vowel_id`) REFERENCES `exercise_vowels` (`vowel_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_3cv_blends`
--

LOCK TABLES `exercise_3cv_blends` WRITE;
/*!40000 ALTER TABLE `exercise_3cv_blends` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exercise_3cv_blends` VALUES
(1,NULL,1,1,'B-AH',NULL,NULL,NULL,0,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(2,NULL,1,2,'B-EE',NULL,NULL,NULL,1,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(3,NULL,1,3,'B-OO',NULL,NULL,NULL,2,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(4,NULL,1,4,'B-OH',NULL,NULL,NULL,3,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(5,NULL,2,1,'D-AH',NULL,NULL,NULL,4,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(6,NULL,2,2,'D-EE',NULL,NULL,NULL,5,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(7,NULL,2,3,'D-OO',NULL,NULL,NULL,6,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(8,NULL,2,4,'D-OH',NULL,NULL,NULL,7,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(9,NULL,3,1,'F-AH',NULL,NULL,NULL,8,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(10,NULL,3,2,'F-EE',NULL,NULL,NULL,9,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(11,NULL,3,3,'F-OO',NULL,NULL,NULL,10,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(12,NULL,3,4,'F-OH',NULL,NULL,NULL,11,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(13,NULL,4,1,'G-AH',NULL,NULL,NULL,12,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(14,NULL,4,2,'G-EE',NULL,NULL,NULL,13,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(15,NULL,4,3,'G-OO',NULL,NULL,NULL,14,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(16,NULL,4,4,'G-OH',NULL,NULL,NULL,15,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(17,NULL,5,1,'H-AH',NULL,NULL,NULL,16,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(18,NULL,5,2,'H-EE',NULL,NULL,NULL,17,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(19,NULL,5,3,'H-OO',NULL,NULL,NULL,18,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(20,NULL,5,4,'H-OH',NULL,NULL,NULL,19,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(21,NULL,6,1,'J-AH',NULL,NULL,NULL,20,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(22,NULL,6,2,'J-EE',NULL,NULL,NULL,21,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(23,NULL,6,3,'J-OO',NULL,NULL,NULL,22,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(24,NULL,6,4,'J-OH',NULL,NULL,NULL,23,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(25,NULL,7,1,'K-AH',NULL,NULL,NULL,24,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(26,NULL,7,2,'K-EE',NULL,NULL,NULL,25,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(27,NULL,7,3,'K-OO',NULL,NULL,NULL,26,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(28,NULL,7,4,'K-OH',NULL,NULL,NULL,27,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(29,NULL,8,1,'L-AH',NULL,NULL,NULL,28,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(30,NULL,8,2,'L-EE',NULL,NULL,NULL,29,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(31,NULL,8,3,'L-OO',NULL,NULL,NULL,30,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(32,NULL,8,4,'L-OH',NULL,NULL,NULL,31,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(33,NULL,9,1,'M-AH',NULL,NULL,NULL,32,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(34,NULL,9,2,'M-EE',NULL,NULL,NULL,33,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(35,NULL,9,3,'M-OO',NULL,NULL,NULL,34,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(36,NULL,9,4,'M-OH',NULL,NULL,NULL,35,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(37,NULL,10,1,'N-AH',NULL,NULL,NULL,36,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(38,NULL,10,2,'N-EE',NULL,NULL,NULL,37,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(39,NULL,10,3,'N-OO',NULL,NULL,NULL,38,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(41,NULL,11,1,'P-AH',NULL,NULL,NULL,40,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(42,NULL,11,2,'P-EE',NULL,NULL,NULL,41,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(43,NULL,11,3,'P-OO',NULL,NULL,NULL,42,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(44,NULL,11,4,'P-OH',NULL,NULL,NULL,43,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(45,NULL,12,1,'R-AH',NULL,NULL,NULL,44,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(46,NULL,12,2,'R-EE',NULL,NULL,NULL,45,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(47,NULL,12,3,'R-OO',NULL,NULL,NULL,46,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(48,NULL,12,4,'R-OH',NULL,NULL,NULL,47,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(49,NULL,13,1,'S-AH',NULL,NULL,NULL,48,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(50,NULL,13,2,'S-EE',NULL,NULL,NULL,49,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(51,NULL,13,3,'S-OO',NULL,NULL,NULL,50,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(52,NULL,13,4,'S-OH',NULL,NULL,NULL,51,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(53,NULL,14,1,'T-AH',NULL,NULL,NULL,52,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(54,NULL,14,2,'T-EE',NULL,NULL,NULL,53,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(55,NULL,14,3,'T-OO',NULL,NULL,NULL,54,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(56,NULL,14,4,'T-OH',NULL,NULL,NULL,55,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(57,NULL,15,1,'V-AH',NULL,NULL,NULL,56,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(58,NULL,15,2,'V-EE',NULL,NULL,NULL,57,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(59,NULL,15,3,'V-OO',NULL,NULL,NULL,58,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(60,NULL,15,4,'V-OH',NULL,NULL,NULL,59,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(61,NULL,16,1,'W-AH',NULL,NULL,NULL,60,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(62,NULL,16,2,'W-EE',NULL,NULL,NULL,61,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(63,NULL,16,3,'W-OO',NULL,NULL,NULL,62,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(64,NULL,16,4,'W-OH',NULL,NULL,NULL,63,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(65,NULL,17,1,'Y-AH',NULL,NULL,NULL,64,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(66,NULL,17,2,'Y-EE',NULL,NULL,NULL,65,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(67,NULL,17,3,'Y-OO',NULL,NULL,NULL,66,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(68,NULL,17,4,'be + lly',NULL,NULL,NULL,67,1,'2026-02-05 15:27:56','2026-04-24 02:50:37'),
(69,NULL,18,1,'Z-AH',NULL,NULL,NULL,68,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(70,NULL,18,2,'Z-EE',NULL,NULL,NULL,69,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(71,NULL,18,3,'Z-OO',NULL,NULL,NULL,70,1,'2026-02-05 15:27:56','2026-02-05 15:27:56'),
(72,NULL,18,4,'Z-OH',NULL,NULL,NULL,71,1,'2026-02-05 15:27:56','2026-02-05 15:27:56');
/*!40000 ALTER TABLE `exercise_3cv_blends` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_assignment_cards`
--

DROP TABLE IF EXISTS `exercise_assignment_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_assignment_cards` (
  `assignment_card_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assignment_exercise_id` bigint(20) unsigned NOT NULL,
  `content_type` enum('consonant','vowel','cv_blend','3cv_blend','word') NOT NULL,
  `content_id` bigint(20) unsigned NOT NULL,
  `card_position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`assignment_card_id`),
  KEY `idx_exercise` (`assignment_exercise_id`),
  KEY `idx_content` (`content_type`,`content_id`),
  KEY `idx_position` (`assignment_exercise_id`,`card_position`),
  CONSTRAINT `assignment_cards_ibfk_1` FOREIGN KEY (`assignment_exercise_id`) REFERENCES `exercise_assignment_exercises` (`assignment_exercise_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_assignment_cards`
--

LOCK TABLES `exercise_assignment_cards` WRITE;
/*!40000 ALTER TABLE `exercise_assignment_cards` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exercise_assignment_cards` VALUES
(52,17,'word',24,0,'2026-02-08 17:26:32'),
(53,17,'word',25,1,'2026-02-08 17:26:32'),
(54,17,'word',26,2,'2026-02-08 17:26:32'),
(55,18,'word',7,0,'2026-02-08 17:26:32'),
(56,18,'word',8,1,'2026-02-08 17:26:32'),
(57,18,'word',9,2,'2026-02-08 17:26:32'),
(58,18,'word',10,3,'2026-02-08 17:26:32'),
(59,18,'word',11,4,'2026-02-08 17:26:32'),
(75,29,'cv_blend',1,0,'2026-02-08 21:36:38'),
(76,29,'cv_blend',2,1,'2026-02-08 21:36:38'),
(77,29,'cv_blend',3,2,'2026-02-08 21:36:38'),
(90,34,'consonant',1,0,'2026-02-11 20:30:55'),
(91,34,'consonant',1,1,'2026-02-11 20:30:55'),
(92,34,'consonant',1,2,'2026-02-11 20:30:55'),
(93,35,'consonant',4,0,'2026-02-11 20:30:55'),
(94,35,'consonant',4,1,'2026-02-11 20:30:55'),
(95,35,'consonant',4,2,'2026-02-11 20:30:55'),
(96,36,'word',49,0,'2026-02-11 20:30:55'),
(97,36,'word',50,1,'2026-02-11 20:30:55'),
(98,36,'word',51,2,'2026-02-11 20:30:55'),
(99,36,'word',52,3,'2026-02-11 20:30:55'),
(100,37,'3cv_blend',49,0,'2026-02-11 20:30:55'),
(101,37,'3cv_blend',50,1,'2026-02-11 20:30:55'),
(102,37,'3cv_blend',51,2,'2026-02-11 20:30:55'),
(103,37,'3cv_blend',52,3,'2026-02-11 20:30:55'),
(113,41,'vowel',3,0,'2026-02-12 02:51:00'),
(114,41,'vowel',3,1,'2026-02-12 02:51:00'),
(115,41,'vowel',3,2,'2026-02-12 02:51:00'),
(116,42,'vowel',4,0,'2026-02-12 02:51:00'),
(117,42,'vowel',4,1,'2026-02-12 02:51:00'),
(118,42,'vowel',4,2,'2026-02-12 02:51:00'),
(119,43,'vowel',1,0,'2026-02-12 02:51:00'),
(120,43,'vowel',1,1,'2026-02-12 02:51:00'),
(121,43,'vowel',1,2,'2026-02-12 02:51:00'),
(122,44,'vowel',2,0,'2026-02-12 02:51:00'),
(123,44,'vowel',2,1,'2026-02-12 02:51:00'),
(124,44,'vowel',2,2,'2026-02-12 02:51:00'),
(125,45,'cv_blend',3,0,'2026-02-18 15:12:54'),
(126,45,'cv_blend',4,1,'2026-02-18 15:12:54'),
(127,45,'cv_blend',5,2,'2026-02-18 15:12:54'),
(128,46,'consonant',11,0,'2026-02-19 19:46:21'),
(129,46,'consonant',11,1,'2026-02-19 19:46:21'),
(130,46,'consonant',11,2,'2026-02-19 19:46:21'),
(131,47,'consonant',1,0,'2026-02-19 19:46:21'),
(132,47,'consonant',1,1,'2026-02-19 19:46:21'),
(133,47,'consonant',1,2,'2026-02-19 19:46:21'),
(134,48,'consonant',9,0,'2026-02-19 19:46:21'),
(135,48,'consonant',9,1,'2026-02-19 19:46:21'),
(136,48,'consonant',9,2,'2026-02-19 19:46:21'),
(137,49,'consonant',1,0,'2026-03-06 11:53:47'),
(138,49,'consonant',1,1,'2026-03-06 11:53:47'),
(139,49,'vowel',1,2,'2026-03-06 11:53:47'),
(140,50,'consonant',6,0,'2026-03-06 11:53:47'),
(141,50,'consonant',6,1,'2026-03-06 11:53:47'),
(142,50,'consonant',6,2,'2026-03-06 11:53:47'),
(143,51,'word',2,0,'2026-03-06 11:53:47'),
(144,51,'word',3,1,'2026-03-06 11:53:47'),
(145,51,'word',4,2,'2026-03-06 11:53:47'),
(146,52,'consonant',1,0,'2026-03-09 16:32:13'),
(147,52,'consonant',2,1,'2026-03-09 16:32:13'),
(148,52,'consonant',3,2,'2026-03-09 16:32:13'),
(169,58,'vowel',1,0,'2026-04-28 14:43:49'),
(170,58,'vowel',1,1,'2026-04-28 14:43:49'),
(171,58,'vowel',1,2,'2026-04-28 14:43:49'),
(172,58,'vowel',1,3,'2026-04-28 14:43:49'),
(173,59,'vowel',4,0,'2026-04-28 14:43:49'),
(174,59,'vowel',4,1,'2026-04-28 14:43:49'),
(175,59,'vowel',4,2,'2026-04-28 14:43:49'),
(176,59,'vowel',4,3,'2026-04-28 14:43:49'),
(177,60,'vowel',3,0,'2026-04-28 14:43:49'),
(178,60,'vowel',3,1,'2026-04-28 14:43:49'),
(179,60,'vowel',3,2,'2026-04-28 14:43:49'),
(180,60,'vowel',3,3,'2026-04-28 14:43:49'),
(181,61,'vowel',2,0,'2026-04-28 14:43:49'),
(182,61,'vowel',2,1,'2026-04-28 14:43:49'),
(183,61,'vowel',2,2,'2026-04-28 14:43:49'),
(184,61,'vowel',2,3,'2026-04-28 14:43:49'),
(185,62,'consonant',9,0,'2026-04-28 14:43:49'),
(186,62,'consonant',9,1,'2026-04-28 14:43:49'),
(187,62,'consonant',9,2,'2026-04-28 14:43:49'),
(188,62,'consonant',9,3,'2026-04-28 14:43:49');
/*!40000 ALTER TABLE `exercise_assignment_cards` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_assignment_exercises`
--

DROP TABLE IF EXISTS `exercise_assignment_exercises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_assignment_exercises` (
  `assignment_exercise_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assignment_group_id` bigint(20) unsigned NOT NULL,
  `exercise_name` varchar(200) NOT NULL,
  `card_count` int(11) NOT NULL DEFAULT 3,
  `orientation` enum('horizontal','vertical') NOT NULL DEFAULT 'horizontal',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`assignment_exercise_id`),
  KEY `idx_assignment_group` (`assignment_group_id`),
  KEY `idx_order` (`assignment_group_id`,`display_order`),
  CONSTRAINT `assignment_exercises_ibfk_1` FOREIGN KEY (`assignment_group_id`) REFERENCES `exercise_assignment_groups` (`assignment_group_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_assignment_exercises`
--

LOCK TABLES `exercise_assignment_exercises` WRITE;
/*!40000 ALTER TABLE `exercise_assignment_exercises` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exercise_assignment_exercises` VALUES
(17,2,'J words',3,'horizontal',0,'2026-02-08 17:26:32','2026-02-08 17:26:32'),
(18,2,'C words',5,'vertical',1,'2026-02-08 17:26:32','2026-02-08 17:26:32'),
(29,8,'err',3,'horizontal',0,'2026-02-08 21:36:38','2026-02-08 21:36:38'),
(34,9,'B Letter',3,'vertical',0,'2026-02-11 20:30:55','2026-02-11 20:30:55'),
(35,9,'Letter G',3,'horizontal',1,'2026-02-11 20:30:55','2026-02-11 20:30:55'),
(36,9,'S words',4,'vertical',2,'2026-02-11 20:30:55','2026-02-11 20:30:55'),
(37,9,'S 3cv blends',4,'horizontal',3,'2026-02-11 20:30:55','2026-02-11 20:30:55'),
(41,11,'OO',3,'horizontal',0,'2026-02-12 02:51:00','2026-02-12 02:51:00'),
(42,11,'OH',3,'horizontal',1,'2026-02-12 02:51:00','2026-02-12 02:51:00'),
(43,11,'AH',3,'horizontal',2,'2026-02-12 02:51:00','2026-02-12 02:51:00'),
(44,11,'EE',3,'horizontal',3,'2026-02-12 02:51:00','2026-02-12 02:51:00'),
(45,12,'CV Blending',3,'horizontal',0,'2026-02-18 15:12:54','2026-02-18 15:12:54'),
(46,10,'P',3,'horizontal',0,'2026-02-19 19:46:21','2026-02-19 19:46:21'),
(47,10,'B',3,'horizontal',1,'2026-02-19 19:46:21','2026-02-19 19:46:21'),
(48,10,'M',3,'horizontal',2,'2026-02-19 19:46:21','2026-02-19 19:46:21'),
(49,1,'b sound',3,'horizontal',0,'2026-03-06 11:53:47','2026-03-06 11:53:47'),
(50,1,'J sound',3,'vertical',1,'2026-03-06 11:53:47','2026-03-06 11:53:47'),
(51,1,'b words',3,'horizontal',2,'2026-03-06 11:53:47','2026-03-06 11:53:47'),
(52,13,'test',3,'horizontal',0,'2026-03-09 16:32:13','2026-03-09 16:32:13'),
(58,14,'Vowels',4,'horizontal',0,'2026-04-28 14:43:49','2026-04-28 14:43:49'),
(59,14,'Vowels',4,'horizontal',1,'2026-04-28 14:43:49','2026-04-28 14:43:49'),
(60,14,'Vowels',4,'horizontal',2,'2026-04-28 14:43:49','2026-04-28 14:43:49'),
(61,14,'Vowels',4,'horizontal',3,'2026-04-28 14:43:49','2026-04-28 14:43:49'),
(62,14,'M',4,'horizontal',4,'2026-04-28 14:43:49','2026-04-28 14:43:49');
/*!40000 ALTER TABLE `exercise_assignment_exercises` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_assignment_groups`
--

DROP TABLE IF EXISTS `exercise_assignment_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_assignment_groups` (
  `assignment_group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by_user_uuid` varchar(36) NOT NULL,
  `assignment_name` varchar(200) NOT NULL,
  `assignment_description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`assignment_group_id`),
  KEY `idx_created_by` (`created_by_user_uuid`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `assignment_groups_ibfk_1` FOREIGN KEY (`created_by_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_assignment_groups`
--

LOCK TABLES `exercise_assignment_groups` WRITE;
/*!40000 ALTER TABLE `exercise_assignment_groups` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exercise_assignment_groups` VALUES
(1,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','Chris\'s Exercises','Chris work',1,'2026-02-07 23:13:22','2026-03-06 11:53:47'),
(2,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','Jake\'s assignment','Jake needs help with words',0,'2026-02-08 15:59:54','2026-04-28 14:45:59'),
(8,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','test err','err',1,'2026-02-08 21:36:09','2026-02-08 21:36:38'),
(9,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','Renee Test','Showing Renee how this works',1,'2026-02-11 20:29:31','2026-02-11 20:30:55'),
(10,'SUPER-USER-UUID-0000-000000000001','M P B','Bilabials sounds - voiced, voiceless, nasal',1,'2026-02-12 02:49:56','2026-02-19 19:46:21'),
(11,'SUPER-USER-UUID-0000-000000000001','OO OH AH EE','Vowel practice',1,'2026-02-12 02:51:00','2026-02-12 02:51:00'),
(12,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','Assignment for Jake','Assignment',1,'2026-02-18 15:12:54','2026-02-18 15:12:54'),
(13,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','test','test',1,'2026-03-09 16:32:13','2026-03-09 16:32:13'),
(14,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','Lizzy','',1,'2026-04-28 14:43:28','2026-04-28 14:43:49');
/*!40000 ALTER TABLE `exercise_assignment_groups` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_assignment_progress`
--

DROP TABLE IF EXISTS `exercise_assignment_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_assignment_progress` (
  `progress_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assignment_group_id` bigint(20) unsigned NOT NULL,
  `user_uuid` varchar(36) NOT NULL,
  `assignment_exercise_id` bigint(20) unsigned NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `attempts` int(11) DEFAULT 1,
  PRIMARY KEY (`progress_id`),
  UNIQUE KEY `unique_user_exercise` (`user_uuid`,`assignment_exercise_id`),
  KEY `idx_assignment` (`assignment_group_id`),
  KEY `idx_user` (`user_uuid`),
  KEY `idx_exercise` (`assignment_exercise_id`),
  CONSTRAINT `assignment_progress_ibfk_1` FOREIGN KEY (`assignment_group_id`) REFERENCES `exercise_assignment_groups` (`assignment_group_id`) ON DELETE CASCADE,
  CONSTRAINT `assignment_progress_ibfk_2` FOREIGN KEY (`user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE,
  CONSTRAINT `assignment_progress_ibfk_3` FOREIGN KEY (`assignment_exercise_id`) REFERENCES `exercise_assignment_exercises` (`assignment_exercise_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_assignment_progress`
--

LOCK TABLES `exercise_assignment_progress` WRITE;
/*!40000 ALTER TABLE `exercise_assignment_progress` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `exercise_assignment_progress` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_assignment_users`
--

DROP TABLE IF EXISTS `exercise_assignment_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_assignment_users` (
  `assignment_user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assignment_group_id` bigint(20) unsigned NOT NULL,
  `assigned_to_user_uuid` varchar(36) NOT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`assignment_user_id`),
  UNIQUE KEY `unique_assignment_user` (`assignment_group_id`,`assigned_to_user_uuid`),
  KEY `idx_assignment` (`assignment_group_id`),
  KEY `idx_user` (`assigned_to_user_uuid`),
  CONSTRAINT `assignment_users_ibfk_1` FOREIGN KEY (`assignment_group_id`) REFERENCES `exercise_assignment_groups` (`assignment_group_id`) ON DELETE CASCADE,
  CONSTRAINT `assignment_users_ibfk_2` FOREIGN KEY (`assigned_to_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_assignment_users`
--

LOCK TABLES `exercise_assignment_users` WRITE;
/*!40000 ALTER TABLE `exercise_assignment_users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exercise_assignment_users` VALUES
(14,8,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','2026-02-08 21:36:38'),
(19,11,'SUPER-USER-UUID-0000-000000000001','2026-02-12 02:51:00'),
(21,10,'SUPER-USER-UUID-0000-000000000001','2026-02-19 19:46:21'),
(23,13,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','2026-03-09 16:32:13'),
(25,14,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','2026-04-28 14:43:49');
/*!40000 ALTER TABLE `exercise_assignment_users` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_assignments`
--

DROP TABLE IF EXISTS `exercise_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_assignments` (
  `assignment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assigned_by_user_uuid` varchar(36) NOT NULL,
  `assigned_to_user_uuid` varchar(36) NOT NULL,
  `content_type` enum('consonant','vowel','cv_blend','3cv_blend','word') NOT NULL,
  `content_id` bigint(20) unsigned NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_template` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`assignment_id`),
  UNIQUE KEY `unique_assignment` (`assigned_to_user_uuid`,`content_type`,`content_id`),
  KEY `idx_assigned_by` (`assigned_by_user_uuid`),
  KEY `idx_assigned_to` (`assigned_to_user_uuid`),
  KEY `idx_content` (`content_type`,`content_id`),
  KEY `idx_assignments_to_active` (`assigned_to_user_uuid`,`is_active`),
  KEY `idx_template` (`is_template`),
  CONSTRAINT `exercise_assignments_ibfk_1` FOREIGN KEY (`assigned_by_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE,
  CONSTRAINT `exercise_assignments_ibfk_2` FOREIGN KEY (`assigned_to_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_assignments`
--

LOCK TABLES `exercise_assignments` WRITE;
/*!40000 ALTER TABLE `exercise_assignments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `exercise_assignments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_card_group_assignments`
--

DROP TABLE IF EXISTS `exercise_card_group_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_card_group_assignments` (
  `assignment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_uuid` varchar(36) NOT NULL DEFAULT '',
  `card_type` varchar(20) NOT NULL,
  `parent_card_type` varchar(20) NOT NULL DEFAULT '',
  `card_id` bigint(20) unsigned NOT NULL,
  `group_name` varchar(100) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`assignment_id`),
  UNIQUE KEY `uq_owner_parent_card_group` (`owner_user_uuid`,`parent_card_type`,`card_type`,`card_id`,`group_name`),
  KEY `idx_owner_parent_group` (`owner_user_uuid`,`parent_card_type`,`group_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1357 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_card_group_assignments`
--

LOCK TABLES `exercise_card_group_assignments` WRITE;
/*!40000 ALTER TABLE `exercise_card_group_assignments` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exercise_card_group_assignments` VALUES
(1,'','consonant','consonant',1,'Stage 1',2,'2026-04-22 18:12:07'),
(2,'','consonant','consonant',2,'Stage 1',4,'2026-04-22 18:12:07'),
(3,'','consonant','consonant',3,'Stage 2',11,'2026-04-22 18:12:07'),
(4,'','consonant','consonant',4,'Stage 2',10,'2026-04-22 18:12:07'),
(5,'','consonant','consonant',5,'Stage 1',6,'2026-04-22 18:12:07'),
(6,'','consonant','consonant',6,'Stage 3',2,'2026-04-22 18:12:07'),
(7,'','consonant','consonant',7,'Stage 2',9,'2026-04-22 18:12:07'),
(8,'','consonant','consonant',8,'Stage 2',13,'2026-04-22 18:12:07'),
(9,'','consonant','consonant',9,'Stage 1',0,'2026-04-22 18:12:07'),
(10,'','consonant','consonant',10,'Stage 1',5,'2026-04-22 18:12:07'),
(11,'','consonant','consonant',11,'Stage 1',1,'2026-04-22 18:12:07'),
(12,'','consonant','consonant',12,'Stage 3',4,'2026-04-22 18:12:07'),
(13,'','consonant','consonant',13,'Stage 2',14,'2026-04-22 18:12:07'),
(14,'','consonant','consonant',14,'Stage 1',3,'2026-04-22 18:12:07'),
(15,'','consonant','consonant',15,'Stage 2',12,'2026-04-22 18:12:07'),
(16,'','consonant','consonant',16,'Stage 1',7,'2026-04-22 18:12:07'),
(17,'','consonant','consonant',17,'Stage 1',8,'2026-04-22 18:12:07'),
(18,'','consonant','consonant',18,'Stage 2',15,'2026-04-22 18:12:07'),
(19,'','consonant','consonant',19,'Stage 3',1,'2026-04-22 18:12:07'),
(20,'','consonant','consonant',20,'Stage 3',0,'2026-04-22 18:12:07'),
(21,'','consonant','consonant',21,'Stage 3',3,'2026-04-22 18:12:07'),
(32,'','vowel','vowel',1,'Stage 1',1,'2026-04-22 18:12:07'),
(33,'','vowel','vowel',2,'Stage 1',4,'2026-04-22 18:12:07'),
(34,'','vowel','vowel',3,'Stage 1',3,'2026-04-22 18:12:07'),
(35,'','vowel','vowel',4,'Stage 1',2,'2026-04-22 18:12:07'),
(36,'','vowel','vowel',6,'Stage 2',5,'2026-04-22 18:12:07'),
(37,'','vowel','vowel',7,'Stage 2',6,'2026-04-22 18:12:07'),
(38,'','vowel','vowel',8,'Stage 2',7,'2026-04-22 18:12:07'),
(39,'','vowel','vowel',9,'Stage 3',8,'2026-04-22 18:12:07'),
(40,'','vowel','vowel',10,'Stage 3',9,'2026-04-22 18:12:07'),
(41,'','vowel','vowel',11,'Stage 3',10,'2026-04-22 18:12:07'),
(42,'','vowel','vowel',12,'Stage 3',11,'2026-04-22 18:12:07'),
(47,'','cv_blend','cv_blend',1,'CV by Cons Stage 1',8,'2026-04-22 18:12:07'),
(48,'','cv_blend','cv_blend',2,'CV by Cons Stage 1',11,'2026-04-22 18:12:07'),
(49,'','cv_blend','cv_blend',3,'CV by Vowel Stage 1',7,'2026-04-22 18:12:07'),
(50,'','cv_blend','cv_blend',4,'CV by Vowel Stage 1',6,'2026-04-22 18:12:07'),
(51,'','cv_blend','cv_blend',5,'CV by Cons Stage 1',10,'2026-04-22 18:12:07'),
(52,'','cv_blend','cv_blend',6,'CV by Cons Stage 1',9,'2026-04-22 18:12:07'),
(53,'','cv_blend','cv_blend',7,'CV by Vowel Stage 1',8,'2026-04-22 18:12:07'),
(54,'','cv_blend','cv_blend',8,'CV by Cons Stage 3',68,'2026-04-22 18:12:07'),
(55,'','cv_blend','cv_blend',9,'CV by Cons Stage 3',71,'2026-04-22 18:12:07'),
(56,'','cv_blend','cv_blend',10,'CV by Cons Stage 3',69,'2026-04-22 18:12:07'),
(57,'','cv_blend','cv_blend',11,'CV by Cons Stage 3',70,'2026-04-22 18:12:07'),
(58,'','cv_blend','cv_blend',12,'CV by Cons Stage 1',16,'2026-04-22 18:12:07'),
(59,'','cv_blend','cv_blend',13,'CV by Cons Stage 1',19,'2026-04-22 18:12:07'),
(60,'','cv_blend','cv_blend',14,'CV by Vowel Stage 1',13,'2026-04-22 18:12:07'),
(61,'','cv_blend','cv_blend',15,'CV by Vowel Stage 1',12,'2026-04-22 18:12:07'),
(62,'','cv_blend','cv_blend',16,'CV by Cons Stage 1',17,'2026-04-22 18:12:07'),
(63,'','cv_blend','cv_blend',17,'CV by Cons Stage 1',18,'2026-04-22 18:12:07'),
(64,'','cv_blend','cv_blend',18,'CV by Vowel Stage 1',14,'2026-04-22 18:12:07'),
(65,'','cv_blend','cv_blend',19,'CV by Cons Stage 2',44,'2026-04-22 18:12:07'),
(66,'','cv_blend','cv_blend',20,'CV by Cons Stage 2',47,'2026-04-22 18:12:07'),
(67,'','cv_blend','cv_blend',21,'CV by Cons Stage 2',46,'2026-04-22 18:12:07'),
(68,'','cv_blend','cv_blend',22,'CV by Cons Stage 2',45,'2026-04-22 18:12:07'),
(69,'','cv_blend','cv_blend',23,'CV by Cons Stage 2',40,'2026-04-22 18:12:07'),
(70,'','cv_blend','cv_blend',24,'CV by Cons Stage 2',43,'2026-04-22 18:12:07'),
(71,'','cv_blend','cv_blend',25,'CV by Vowel Stage 1',31,'2026-04-22 18:12:07'),
(72,'','cv_blend','cv_blend',26,'CV by Vowel Stage 1',30,'2026-04-22 18:12:07'),
(73,'','cv_blend','cv_blend',27,'CV by Cons Stage 2',42,'2026-04-22 18:12:07'),
(74,'','cv_blend','cv_blend',28,'CV by Cons Stage 2',41,'2026-04-22 18:12:07'),
(75,'','cv_blend','cv_blend',29,'CV by Vowel Stage 1',32,'2026-04-22 18:12:07'),
(76,'','cv_blend','cv_blend',30,'CV by Cons Stage 1',33,'2026-04-22 18:12:07'),
(77,'','cv_blend','cv_blend',31,'CV by Cons Stage 1',36,'2026-04-22 18:12:07'),
(78,'','cv_blend','cv_blend',32,'CV by Vowel Stage 1',25,'2026-04-22 18:12:07'),
(79,'','cv_blend','cv_blend',33,'CV by Vowel Stage 1',24,'2026-04-22 18:12:07'),
(80,'','cv_blend','cv_blend',34,'CV by Cons Stage 1',34,'2026-04-22 18:12:07'),
(81,'','cv_blend','cv_blend',35,'CV by Cons Stage 1',35,'2026-04-22 18:12:07'),
(82,'','cv_blend','cv_blend',36,'CV by Vowel Stage 1',26,'2026-04-22 18:12:07'),
(83,'','cv_blend','cv_blend',37,'CV by Cons Stage 3',76,'2026-04-22 18:12:07'),
(84,'','cv_blend','cv_blend',38,'CV by Cons Stage 3',79,'2026-04-22 18:12:07'),
(85,'','cv_blend','cv_blend',39,'CV by Cons Stage 3',77,'2026-04-22 18:12:07'),
(86,'','cv_blend','cv_blend',40,'CV by Cons Stage 3',78,'2026-04-22 18:12:07'),
(87,'','cv_blend','cv_blend',41,'CV by Cons Stage 2',36,'2026-04-22 18:12:07'),
(88,'','cv_blend','cv_blend',42,'CV by Cons Stage 2',39,'2026-04-22 18:12:07'),
(89,'','cv_blend','cv_blend',43,'CV by Vowel Stage 1',28,'2026-04-22 18:12:07'),
(90,'','cv_blend','cv_blend',44,'CV by Vowel Stage 1',27,'2026-04-22 18:12:07'),
(91,'','cv_blend','cv_blend',45,'CV by Cons Stage 2',37,'2026-04-22 18:12:07'),
(92,'','cv_blend','cv_blend',46,'CV by Cons Stage 2',38,'2026-04-22 18:12:07'),
(93,'','cv_blend','cv_blend',47,'CV by Vowel Stage 1',29,'2026-04-22 18:12:07'),
(94,'','cv_blend','cv_blend',48,'CV by Cons Stage 2',52,'2026-04-22 18:12:07'),
(95,'','cv_blend','cv_blend',49,'CV by Cons Stage 2',55,'2026-04-22 18:12:07'),
(96,'','cv_blend','cv_blend',50,'CV by Cons Stage 2',53,'2026-04-22 18:12:07'),
(97,'','cv_blend','cv_blend',51,'CV by Cons Stage 2',54,'2026-04-22 18:12:07'),
(98,'','cv_blend','cv_blend',52,'CV by Cons Stage 1',0,'2026-04-22 18:12:07'),
(99,'','cv_blend','cv_blend',53,'CV by Vowel Stage 1',1,'2026-04-22 18:12:07'),
(100,'','cv_blend','cv_blend',54,'CV by Cons Stage 1',3,'2026-04-22 18:12:07'),
(101,'','cv_blend','cv_blend',55,'CV by Vowel Stage 1',0,'2026-04-22 18:12:07'),
(102,'','cv_blend','cv_blend',56,'CV by Cons Stage 1',1,'2026-04-22 18:12:07'),
(103,'','cv_blend','cv_blend',57,'CV by Cons Stage 1',2,'2026-04-22 18:12:07'),
(104,'','cv_blend','cv_blend',58,'CV by Vowel Stage 1',2,'2026-04-22 18:12:07'),
(105,'','cv_blend','cv_blend',59,'CV by Cons Stage 1',20,'2026-04-22 18:12:07'),
(106,'','cv_blend','cv_blend',60,'CV by Cons Stage 1',24,'2026-04-22 18:12:07'),
(107,'','cv_blend','cv_blend',61,'CV by Vowel Stage 1',16,'2026-04-22 18:12:07'),
(108,'','cv_blend','cv_blend',62,'CV by Vowel Stage 1',15,'2026-04-22 18:12:07'),
(109,'','cv_blend','cv_blend',63,'CV by Cons Stage 1',23,'2026-04-22 18:12:07'),
(111,'','cv_blend','cv_blend',65,'CV by Vowel Stage 1',17,'2026-04-22 18:12:07'),
(112,'','cv_blend','cv_blend',66,'CV by Cons Stage 1',4,'2026-04-22 18:12:07'),
(113,'','cv_blend','cv_blend',67,'CV by Cons Stage 1',7,'2026-04-22 18:12:07'),
(114,'','cv_blend','cv_blend',68,'CV by Vowel Stage 1',4,'2026-04-22 18:12:07'),
(115,'','cv_blend','cv_blend',69,'CV by Vowel Stage 1',3,'2026-04-22 18:12:07'),
(116,'','cv_blend','cv_blend',70,'CV by Cons Stage 1',5,'2026-04-22 18:12:07'),
(117,'','cv_blend','cv_blend',71,'CV by Cons Stage 1',6,'2026-04-22 18:12:07'),
(118,'','cv_blend','cv_blend',72,'CV by Vowel Stage 1',5,'2026-04-22 18:12:07'),
(119,'','cv_blend','cv_blend',73,'CV by Cons Stage 3',80,'2026-04-22 18:12:07'),
(120,'','cv_blend','cv_blend',74,'CV by Cons Stage 3',83,'2026-04-22 18:12:07'),
(121,'','cv_blend','cv_blend',75,'CV by Cons Stage 3',81,'2026-04-22 18:12:07'),
(122,'','cv_blend','cv_blend',76,'CV by Cons Stage 3',82,'2026-04-22 18:12:07'),
(123,'','cv_blend','cv_blend',77,'CV by Cons Stage 2',56,'2026-04-22 18:12:07'),
(124,'','cv_blend','cv_blend',78,'CV by Cons Stage 2',59,'2026-04-22 18:12:07'),
(125,'','cv_blend','cv_blend',79,'CV by Cons Stage 3',64,'2026-04-22 18:12:07'),
(126,'','cv_blend','cv_blend',80,'CV by Cons Stage 3',67,'2026-04-22 18:12:07'),
(127,'','cv_blend','cv_blend',81,'CV by Cons Stage 3',65,'2026-04-22 18:12:07'),
(128,'','cv_blend','cv_blend',82,'CV by Cons Stage 3',66,'2026-04-22 18:12:07'),
(129,'','cv_blend','cv_blend',83,'CV by Cons Stage 2',57,'2026-04-22 18:12:07'),
(130,'','cv_blend','cv_blend',84,'CV by Cons Stage 2',58,'2026-04-22 18:12:07'),
(131,'','cv_blend','cv_blend',85,'CV by Cons Stage 1',12,'2026-04-22 18:12:07'),
(132,'','cv_blend','cv_blend',86,'CV by Cons Stage 1',15,'2026-04-22 18:12:07'),
(133,'','cv_blend','cv_blend',87,'CV by Vowel Stage 1',10,'2026-04-22 18:12:07'),
(134,'','cv_blend','cv_blend',88,'CV by Cons Stage 3',72,'2026-04-22 18:12:07'),
(135,'','cv_blend','cv_blend',89,'CV by Cons Stage 3',75,'2026-04-22 18:12:07'),
(136,'','cv_blend','cv_blend',90,'CV by Cons Stage 3',73,'2026-04-22 18:12:07'),
(137,'','cv_blend','cv_blend',91,'CV by Cons Stage 3',74,'2026-04-22 18:12:07'),
(138,'','cv_blend','cv_blend',92,'CV by Vowel Stage 1',9,'2026-04-22 18:12:07'),
(139,'','cv_blend','cv_blend',93,'CV by Cons Stage 1',13,'2026-04-22 18:12:07'),
(140,'','cv_blend','cv_blend',94,'CV by Vowel Stage 1',11,'2026-04-22 18:12:07'),
(141,'','cv_blend','cv_blend',95,'CV by Cons Stage 1',14,'2026-04-22 18:12:07'),
(142,'','cv_blend','cv_blend',96,'CV by Cons Stage 2',48,'2026-04-22 18:12:07'),
(143,'','cv_blend','cv_blend',97,'CV by Cons Stage 2',51,'2026-04-22 18:12:07'),
(144,'','cv_blend','cv_blend',98,'CV by Cons Stage 2',49,'2026-04-22 18:12:07'),
(145,'','cv_blend','cv_blend',99,'CV by Cons Stage 2',50,'2026-04-22 18:12:07'),
(146,'','cv_blend','cv_blend',100,'CV by Cons Stage 1',25,'2026-04-22 18:12:07'),
(147,'','cv_blend','cv_blend',101,'CV by Vowel Stage 1',19,'2026-04-22 18:12:07'),
(148,'','cv_blend','cv_blend',102,'CV by Cons Stage 1',28,'2026-04-22 18:12:07'),
(149,'','cv_blend','cv_blend',103,'CV by Vowel Stage 1',18,'2026-04-22 18:12:07'),
(150,'','cv_blend','cv_blend',104,'CV by Cons Stage 1',26,'2026-04-22 18:12:07'),
(151,'','cv_blend','cv_blend',105,'CV by Cons Stage 1',27,'2026-04-22 18:12:07'),
(152,'','cv_blend','cv_blend',106,'CV by Vowel Stage 1',20,'2026-04-22 18:12:07'),
(153,'','cv_blend','cv_blend',107,'CV by Cons Stage 1',29,'2026-04-22 18:12:07'),
(154,'','cv_blend','cv_blend',108,'CV by Cons Stage 1',32,'2026-04-22 18:12:07'),
(155,'','cv_blend','cv_blend',109,'CV by Vowel Stage 1',22,'2026-04-22 18:12:07'),
(156,'','cv_blend','cv_blend',110,'CV by Vowel Stage 1',21,'2026-04-22 18:12:07'),
(157,'','cv_blend','cv_blend',111,'CV by Cons Stage 1',30,'2026-04-22 18:12:07'),
(158,'','cv_blend','cv_blend',112,'CV by Cons Stage 1',31,'2026-04-22 18:12:07'),
(159,'','cv_blend','cv_blend',113,'CV by Vowel Stage 1',23,'2026-04-22 18:12:07'),
(160,'','cv_blend','cv_blend',114,'CV by Cons Stage 2',60,'2026-04-22 18:12:07'),
(161,'','cv_blend','cv_blend',115,'CV by Cons Stage 2',63,'2026-04-22 18:12:07'),
(162,'','cv_blend','cv_blend',116,'CV by Cons Stage 2',61,'2026-04-22 18:12:07'),
(163,'','cv_blend','cv_blend',117,'CV by Cons Stage 2',62,'2026-04-22 18:12:07'),
(164,'','cv_blend','cv_blend',118,'CV by Cons Stage 1',22,'2026-04-22 18:12:07'),
(174,'','3cv_blend','3cv_blend',4,'CV1CV2',3,'2026-04-22 18:12:07'),
(175,'','3cv_blend','3cv_blend',5,'C1V1C2V2 Stage 1',23,'2026-04-22 18:12:07'),
(176,'','3cv_blend','3cv_blend',11,'CV1CV2',10,'2026-04-22 18:12:07'),
(177,'','3cv_blend','3cv_blend',15,'CV1CV2',14,'2026-04-22 18:12:07'),
(178,'','3cv_blend','3cv_blend',22,'C1V1C2V2 Stage 1',24,'2026-04-22 18:12:07'),
(179,'','3cv_blend','3cv_blend',24,'C1V1C2V2 Stage 2',23,'2026-04-22 18:12:07'),
(180,'','3cv_blend','3cv_blend',27,'C1V1C2V2 Stage 1',25,'2026-04-22 18:12:07'),
(181,'','3cv_blend','3cv_blend',33,'CV1CV2',32,'2026-04-22 18:12:07'),
(182,'','3cv_blend','3cv_blend',45,'CV1CV2',44,'2026-04-22 18:12:07'),
(183,'','3cv_blend','3cv_blend',55,'C1V1C2V2 Stage 1',26,'2026-04-22 18:12:07'),
(184,'','3cv_blend','3cv_blend',56,'CV1CV2',55,'2026-04-22 18:12:07'),
(185,'','3cv_blend','3cv_blend',65,'CV1CV2',0,'2026-04-22 18:12:07'),
(186,'','3cv_blend','3cv_blend',68,'C1V1C2V2 Stage 1',0,'2026-04-22 18:12:07'),
(187,'','3cv_blend','3cv_blend',72,'C1V1C2V2 Stage 1',1,'2026-04-22 18:12:07'),
(188,'','3cv_blend','3cv_blend',73,'C1V1C2V2 Stage 1',2,'2026-04-22 18:12:07'),
(189,'','3cv_blend','3cv_blend',74,'C1V1C2V2 Stage 2',0,'2026-04-22 18:12:07'),
(190,'','3cv_blend','3cv_blend',75,'C1V1C2V2 Stage 2',0,'2026-04-22 18:12:07'),
(191,'','3cv_blend','3cv_blend',76,'C1V1C2V2 Stage 2',0,'2026-04-22 18:12:07'),
(192,'','3cv_blend','3cv_blend',81,'C1V1C2V2 Stage 1',3,'2026-04-22 18:12:07'),
(193,'','3cv_blend','3cv_blend',83,'C1V1C2V2 Stage 1',4,'2026-04-22 18:12:07'),
(194,'','3cv_blend','3cv_blend',84,'C1V1C2V2 Stage 1',5,'2026-04-22 18:12:07'),
(195,'','3cv_blend','3cv_blend',85,'C1V1C2V2 Stage 1',6,'2026-04-22 18:12:07'),
(196,'','3cv_blend','3cv_blend',86,'C1V1C2V2 Stage 1',7,'2026-04-22 18:12:07'),
(197,'','3cv_blend','3cv_blend',87,'C1V1C2V2 Stage 1',8,'2026-04-22 18:12:07'),
(198,'','3cv_blend','3cv_blend',88,'C1V1C2V2 Stage 2',0,'2026-04-22 18:12:07'),
(199,'','3cv_blend','3cv_blend',89,'CV1CV2',0,'2026-04-22 18:12:07'),
(201,'','3cv_blend','3cv_blend',96,'C1V1C2V2 Stage 1',9,'2026-04-22 18:12:07'),
(202,'','3cv_blend','3cv_blend',98,'C1V1C2V2 Stage 1',10,'2026-04-22 18:12:07'),
(204,'','3cv_blend','3cv_blend',102,'C1V1C2V2 Stage 1',11,'2026-04-22 18:12:07'),
(205,'','3cv_blend','3cv_blend',111,'C1V1C2V2 Stage 1',12,'2026-04-22 18:12:07'),
(206,'','3cv_blend','3cv_blend',112,'CV1CV2',0,'2026-04-22 18:12:07'),
(207,'','3cv_blend','3cv_blend',113,'CV1CV2',0,'2026-04-22 18:12:07'),
(208,'','3cv_blend','3cv_blend',117,'C1V1C2V2 Stage 1',13,'2026-04-22 18:12:07'),
(209,'','3cv_blend','3cv_blend',119,'CV1CV2',0,'2026-04-22 18:12:07'),
(210,'','3cv_blend','3cv_blend',121,'C1V1C2V2 Stage 1',14,'2026-04-22 18:12:07'),
(211,'','3cv_blend','3cv_blend',125,'CV1CV2',0,'2026-04-22 18:12:07'),
(212,'','3cv_blend','3cv_blend',126,'C1V1C2V2 Stage 2',0,'2026-04-22 18:12:07'),
(213,'','3cv_blend','3cv_blend',127,'C1V1C2V2 Stage 2',0,'2026-04-22 18:12:07'),
(214,'','3cv_blend','3cv_blend',128,'C1V1C2V2 Stage 2',0,'2026-04-22 18:12:07'),
(215,'','3cv_blend','3cv_blend',129,'C1V1C2V2 Stage 2',0,'2026-04-22 18:12:07'),
(216,'','3cv_blend','3cv_blend',134,'C1V1C2V2 Stage 2',0,'2026-04-22 18:12:07'),
(217,'','3cv_blend','3cv_blend',135,'C1V1C2V2 Stage 2',0,'2026-04-22 18:12:07'),
(218,'','3cv_blend','3cv_blend',139,'CV1CV2',0,'2026-04-22 18:12:07'),
(219,'','3cv_blend','3cv_blend',140,'C1V1C2V2 Stage 1',15,'2026-04-22 18:12:07'),
(220,'','3cv_blend','3cv_blend',142,'C1V1C2V2 Stage 1',16,'2026-04-22 18:12:07'),
(221,'','3cv_blend','3cv_blend',143,'C1V1C2V2 Stage 1',17,'2026-04-22 18:12:07'),
(222,'','3cv_blend','3cv_blend',148,'C1V1C2V2 Stage 1',18,'2026-04-22 18:12:07'),
(223,'','3cv_blend','3cv_blend',149,'C1V1C2V2 Stage 1',19,'2026-04-22 18:12:07'),
(224,'','3cv_blend','3cv_blend',150,'C1V1C2V2 Stage 1',20,'2026-04-22 18:12:07'),
(225,'','3cv_blend','3cv_blend',151,'C1V1C2V2 Stage 1',21,'2026-04-22 18:12:07'),
(226,'','3cv_blend','3cv_blend',152,'C1V1C2V2 Stage 1',22,'2026-04-22 18:12:07'),
(227,'','3cv_blend','3cv_blend',167,'CVCV',0,'2026-04-22 18:12:07'),
(228,'','3cv_blend','3cv_blend',168,'CVCV',0,'2026-04-22 18:12:07'),
(229,'','3cv_blend','3cv_blend',169,'CVCV',0,'2026-04-22 18:12:07'),
(231,'','3cv_blend','3cv_blend',171,'CVCV',0,'2026-04-22 18:12:07'),
(232,'','3cv_blend','3cv_blend',172,'CVCV',0,'2026-04-22 18:12:07'),
(233,'','3cv_blend','3cv_blend',173,'CVCV',0,'2026-04-22 18:12:07'),
(234,'','3cv_blend','3cv_blend',174,'CVCV',0,'2026-04-22 18:12:07'),
(235,'','3cv_blend','3cv_blend',175,'CVCV',0,'2026-04-22 18:12:07'),
(236,'','3cv_blend','3cv_blend',176,'CVCV',0,'2026-04-22 18:12:07'),
(237,'','3cv_blend','3cv_blend',177,'CVCV',0,'2026-04-22 18:12:07'),
(238,'','3cv_blend','3cv_blend',178,'CVCV',0,'2026-04-22 18:12:07'),
(239,'','3cv_blend','3cv_blend',179,'CVCV',0,'2026-04-22 18:12:07'),
(240,'','3cv_blend','3cv_blend',180,'CVCV',0,'2026-04-22 18:12:07'),
(301,'','word','word',66,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(302,'','word','word',67,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(303,'','word','word',69,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(304,'','word','word',70,'CVC-Bilabial',0,'2026-04-22 18:12:07'),
(305,'','word','word',71,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(306,'','word','word',77,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(307,'','word','word',78,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(308,'','word','word',79,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(309,'','word','word',80,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(310,'','word','word',82,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(311,'','word','word',90,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(312,'','word','word',91,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(313,'','word','word',92,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(314,'','word','word',93,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(315,'','word','word',95,'CVC-Bilabial',0,'2026-04-22 18:12:07'),
(316,'','word','word',97,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(317,'','word','word',99,'CVC-Bilabial',0,'2026-04-22 18:12:07'),
(318,'','word','word',101,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(319,'','word','word',103,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(320,'','word','word',104,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(321,'','word','word',105,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(322,'','word','word',106,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(323,'','word','word',107,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(324,'','word','word',108,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(325,'','word','word',109,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(326,'','word','word',110,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(327,'','word','word',114,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(328,'','word','word',115,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(329,'','word','word',116,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(330,'','word','word',118,'CVC-Bilabial',0,'2026-04-22 18:12:07'),
(331,'','word','word',120,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(332,'','word','word',122,'CVC-Bilabial',0,'2026-04-22 18:12:07'),
(333,'','word','word',123,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(334,'','word','word',124,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(335,'','word','word',130,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(336,'','word','word',131,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(337,'','word','word',132,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(338,'','word','word',133,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(339,'','word','word',136,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(340,'','word','word',137,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(341,'','word','word',138,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(342,'','word','word',141,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(343,'','word','word',144,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(344,'','word','word',145,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(345,'','word','word',146,'CVC-Alveolar',0,'2026-04-22 18:12:07'),
(346,'','word','word',147,'CVC-Bilabial/Alveolar',0,'2026-04-22 18:12:07'),
(347,'','word','word',181,'VC',0,'2026-04-22 18:12:07'),
(348,'','word','word',182,'VC',0,'2026-04-22 18:12:07'),
(349,'','word','word',183,'VC',0,'2026-04-22 18:12:07'),
(350,'','word','word',184,'VC',0,'2026-04-22 18:12:07'),
(351,'','word','word',185,'VC',0,'2026-04-22 18:12:07'),
(352,'','word','word',186,'VC',0,'2026-04-22 18:12:07'),
(353,'','word','word',187,'VC',0,'2026-04-22 18:12:07'),
(354,'','word','word',188,'VC',0,'2026-04-22 18:12:07'),
(355,'','word','word',189,'VC',0,'2026-04-22 18:12:07'),
(356,'','word','word',190,'VC',0,'2026-04-22 18:12:07'),
(357,'','word','word',191,'VC',0,'2026-04-22 18:12:07'),
(358,'','word','word',192,'VC',0,'2026-04-22 18:12:07'),
(359,'','word','word',193,'VC',0,'2026-04-22 18:12:07'),
(360,'','word','word',194,'VC',0,'2026-04-22 18:12:07'),
(361,'','word','word',195,'VC',0,'2026-04-22 18:12:07'),
(362,'','word','word',196,'VC',0,'2026-04-22 18:12:07'),
(363,'','word','word',197,'VC',0,'2026-04-22 18:12:07'),
(364,'','word','word',198,'VC',0,'2026-04-22 18:12:07'),
(365,'','word','word',199,'VC',0,'2026-04-22 18:12:07'),
(366,'','word','word',200,'VC',0,'2026-04-22 18:12:07'),
(367,'','word','word',201,'VC',0,'2026-04-22 18:12:07'),
(368,'','word','word',202,'VC',0,'2026-04-22 18:12:07'),
(369,'','word','word',203,'VC',0,'2026-04-22 18:12:07'),
(370,'','word','word',204,'VC',0,'2026-04-22 18:12:07'),
(371,'','word','word',205,'VC',0,'2026-04-22 18:12:07'),
(436,'','3cv_blend','3cv_blend',94,'CVCV',1,'2026-04-25 00:20:06'),
(437,'','3cv_blend','3cv_blend',100,'CVCV',2,'2026-04-25 00:20:29'),
(438,'','consonant','3cv_blend',2,'ISO Sounds',1,'2026-04-29 00:41:03'),
(444,'','word','vowel',189,'Words',0,'2026-04-29 00:46:40'),
(445,'','word','vowel',202,'Words',2,'2026-04-29 00:46:40'),
(446,'','word','vowel',103,'Words',1,'2026-04-29 00:46:40'),
(447,'','cv_blend','consonant',2,'Blend 1',1,'2026-04-29 01:03:56'),
(448,'','cv_blend','consonant',3,'Blend 1',2,'2026-04-29 01:03:56'),
(449,'','cv_blend','consonant',4,'Blend 1',3,'2026-04-29 01:03:56'),
(450,'','consonant','cv_blend',1,'Consonants',1,'2026-04-30 12:43:46'),
(451,'','consonant','cv_blend',11,'Consonants',2,'2026-04-30 12:43:46'),
(452,'','consonant','cv_blend',9,'Consonants',3,'2026-04-30 12:43:46'),
(453,'','consonant','cv_blend',2,'Consonants',4,'2026-04-30 12:43:46'),
(454,'','consonant','cv_blend',14,'Consonants',5,'2026-04-30 12:43:46'),
(455,'','consonant','cv_blend',10,'Consonants',6,'2026-04-30 12:43:46'),
(456,'','consonant','cv_blend',8,'Consonants',7,'2026-04-30 12:43:46'),
(457,'','consonant','cv_blend',7,'Consonants',8,'2026-04-30 12:43:46'),
(458,'','consonant','cv_blend',4,'Consonants',9,'2026-04-30 12:43:46'),
(459,'','consonant','cv_blend',3,'Consonants',10,'2026-04-30 12:43:46'),
(460,'','consonant','cv_blend',15,'Consonants',11,'2026-04-30 12:43:46'),
(461,'','consonant','cv_blend',13,'Consonants',12,'2026-04-30 12:43:46'),
(462,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',4,'CV1CV2',3,'2026-04-30 12:54:27'),
(463,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',5,'C1V1C2V2 Stage 1',23,'2026-04-30 12:54:27'),
(464,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',11,'CV1CV2',10,'2026-04-30 12:54:27'),
(465,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',15,'CV1CV2',14,'2026-04-30 12:54:27'),
(466,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',22,'C1V1C2V2 Stage 1',24,'2026-04-30 12:54:27'),
(467,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',24,'C1V1C2V2 Stage 2',23,'2026-04-30 12:54:27'),
(468,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',27,'C1V1C2V2 Stage 1',25,'2026-04-30 12:54:27'),
(469,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',33,'CV1CV2',32,'2026-04-30 12:54:27'),
(470,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',45,'CV1CV2',44,'2026-04-30 12:54:27'),
(471,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',55,'C1V1C2V2 Stage 1',26,'2026-04-30 12:54:27'),
(472,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',56,'CV1CV2',55,'2026-04-30 12:54:27'),
(473,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',65,'CV1CV2',0,'2026-04-30 12:54:27'),
(474,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',68,'C1V1C2V2 Stage 1',0,'2026-04-30 12:54:27'),
(475,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',72,'C1V1C2V2 Stage 1',1,'2026-04-30 12:54:27'),
(476,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',73,'C1V1C2V2 Stage 1',2,'2026-04-30 12:54:27'),
(477,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',74,'C1V1C2V2 Stage 2',0,'2026-04-30 12:54:27'),
(478,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',75,'C1V1C2V2 Stage 2',0,'2026-04-30 12:54:27'),
(479,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',76,'C1V1C2V2 Stage 2',0,'2026-04-30 12:54:27'),
(480,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',81,'C1V1C2V2 Stage 1',3,'2026-04-30 12:54:27'),
(481,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',83,'C1V1C2V2 Stage 1',4,'2026-04-30 12:54:27'),
(482,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',84,'C1V1C2V2 Stage 1',5,'2026-04-30 12:54:27'),
(483,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',85,'C1V1C2V2 Stage 1',6,'2026-04-30 12:54:27'),
(484,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',86,'C1V1C2V2 Stage 1',7,'2026-04-30 12:54:27'),
(485,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',87,'C1V1C2V2 Stage 1',8,'2026-04-30 12:54:27'),
(486,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',88,'C1V1C2V2 Stage 2',0,'2026-04-30 12:54:27'),
(487,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',89,'CV1CV2',0,'2026-04-30 12:54:27'),
(488,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',96,'C1V1C2V2 Stage 1',9,'2026-04-30 12:54:27'),
(489,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',98,'C1V1C2V2 Stage 1',10,'2026-04-30 12:54:27'),
(490,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',102,'C1V1C2V2 Stage 1',11,'2026-04-30 12:54:27'),
(491,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',111,'C1V1C2V2 Stage 1',12,'2026-04-30 12:54:27'),
(492,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',112,'CV1CV2',0,'2026-04-30 12:54:27'),
(493,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',113,'CV1CV2',0,'2026-04-30 12:54:27'),
(494,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',117,'C1V1C2V2 Stage 1',13,'2026-04-30 12:54:27'),
(495,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',119,'CV1CV2',0,'2026-04-30 12:54:27'),
(496,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',121,'C1V1C2V2 Stage 1',14,'2026-04-30 12:54:27'),
(497,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',125,'CV1CV2',0,'2026-04-30 12:54:27'),
(498,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',126,'C1V1C2V2 Stage 2',0,'2026-04-30 12:54:27'),
(499,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',127,'C1V1C2V2 Stage 2',0,'2026-04-30 12:54:27'),
(500,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',128,'C1V1C2V2 Stage 2',0,'2026-04-30 12:54:27'),
(501,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',129,'C1V1C2V2 Stage 2',0,'2026-04-30 12:54:27'),
(502,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',134,'C1V1C2V2 Stage 2',0,'2026-04-30 12:54:27'),
(503,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',135,'C1V1C2V2 Stage 2',0,'2026-04-30 12:54:27'),
(504,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',139,'CV1CV2',0,'2026-04-30 12:54:27'),
(505,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',140,'C1V1C2V2 Stage 1',15,'2026-04-30 12:54:27'),
(506,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',142,'C1V1C2V2 Stage 1',16,'2026-04-30 12:54:27'),
(507,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',143,'C1V1C2V2 Stage 1',17,'2026-04-30 12:54:27'),
(508,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',148,'C1V1C2V2 Stage 1',18,'2026-04-30 12:54:27'),
(509,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',149,'C1V1C2V2 Stage 1',19,'2026-04-30 12:54:27'),
(510,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',150,'C1V1C2V2 Stage 1',20,'2026-04-30 12:54:27'),
(511,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',151,'C1V1C2V2 Stage 1',21,'2026-04-30 12:54:27'),
(512,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',152,'C1V1C2V2 Stage 1',22,'2026-04-30 12:54:27'),
(513,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',167,'CVCV',0,'2026-04-30 12:54:27'),
(514,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',168,'CVCV',0,'2026-04-30 12:54:27'),
(515,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',169,'CVCV',0,'2026-04-30 12:54:27'),
(516,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',171,'CVCV',0,'2026-04-30 12:54:27'),
(517,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',172,'CVCV',0,'2026-04-30 12:54:27'),
(518,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',173,'CVCV',0,'2026-04-30 12:54:27'),
(519,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',174,'CVCV',0,'2026-04-30 12:54:27'),
(520,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',175,'CVCV',0,'2026-04-30 12:54:27'),
(521,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',176,'CVCV',0,'2026-04-30 12:54:27'),
(522,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',177,'CVCV',0,'2026-04-30 12:54:27'),
(523,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',178,'CVCV',0,'2026-04-30 12:54:27'),
(524,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',179,'CVCV',0,'2026-04-30 12:54:27'),
(525,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',180,'CVCV',0,'2026-04-30 12:54:27'),
(526,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',94,'CVCV',1,'2026-04-30 12:54:27'),
(527,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','3cv_blend','3cv_blend',100,'CVCV',2,'2026-04-30 12:54:27'),
(589,'','cv_blend','cv',1,'CV by Cons Stage 1',8,'2026-04-30 13:56:50'),
(590,'','cv_blend','cv',2,'CV by Cons Stage 1',11,'2026-04-30 13:56:50'),
(591,'','cv_blend','cv',3,'CV by Vowel Stage 1',7,'2026-04-30 13:56:50'),
(592,'','cv_blend','cv',4,'CV by Vowel Stage 1',6,'2026-04-30 13:56:50'),
(593,'','cv_blend','cv',5,'CV by Cons Stage 1',10,'2026-04-30 13:56:50'),
(594,'','cv_blend','cv',6,'CV by Cons Stage 1',9,'2026-04-30 13:56:50'),
(595,'','cv_blend','cv',7,'CV by Vowel Stage 1',8,'2026-04-30 13:56:50'),
(596,'','cv_blend','cv',8,'CV by Cons Stage 3',68,'2026-04-30 13:56:50'),
(597,'','cv_blend','cv',9,'CV by Cons Stage 3',71,'2026-04-30 13:56:50'),
(598,'','cv_blend','cv',10,'CV by Cons Stage 3',69,'2026-04-30 13:56:50'),
(599,'','cv_blend','cv',11,'CV by Cons Stage 3',70,'2026-04-30 13:56:50'),
(600,'','cv_blend','cv',12,'CV by Cons Stage 1',16,'2026-04-30 13:56:50'),
(601,'','cv_blend','cv',13,'CV by Cons Stage 1',19,'2026-04-30 13:56:50'),
(602,'','cv_blend','cv',14,'CV by Vowel Stage 1',13,'2026-04-30 13:56:50'),
(603,'','cv_blend','cv',15,'CV by Vowel Stage 1',12,'2026-04-30 13:56:50'),
(604,'','cv_blend','cv',16,'CV by Cons Stage 1',17,'2026-04-30 13:56:50'),
(605,'','cv_blend','cv',17,'CV by Cons Stage 1',18,'2026-04-30 13:56:50'),
(606,'','cv_blend','cv',18,'CV by Vowel Stage 1',14,'2026-04-30 13:56:50'),
(607,'','cv_blend','cv',19,'CV by Cons Stage 2',44,'2026-04-30 13:56:50'),
(608,'','cv_blend','cv',20,'CV by Cons Stage 2',47,'2026-04-30 13:56:50'),
(609,'','cv_blend','cv',21,'CV by Cons Stage 2',46,'2026-04-30 13:56:50'),
(610,'','cv_blend','cv',22,'CV by Cons Stage 2',45,'2026-04-30 13:56:50'),
(611,'','cv_blend','cv',23,'CV by Cons Stage 2',40,'2026-04-30 13:56:50'),
(612,'','cv_blend','cv',24,'CV by Cons Stage 2',43,'2026-04-30 13:56:50'),
(613,'','cv_blend','cv',25,'CV by Vowel Stage 1',31,'2026-04-30 13:56:50'),
(614,'','cv_blend','cv',26,'CV by Vowel Stage 1',30,'2026-04-30 13:56:50'),
(615,'','cv_blend','cv',27,'CV by Cons Stage 2',42,'2026-04-30 13:56:50'),
(616,'','cv_blend','cv',28,'CV by Cons Stage 2',41,'2026-04-30 13:56:50'),
(617,'','cv_blend','cv',29,'CV by Vowel Stage 1',32,'2026-04-30 13:56:50'),
(618,'','cv_blend','cv',30,'CV by Cons Stage 1',33,'2026-04-30 13:56:50'),
(619,'','cv_blend','cv',31,'CV by Cons Stage 1',36,'2026-04-30 13:56:50'),
(620,'','cv_blend','cv',32,'CV by Vowel Stage 1',25,'2026-04-30 13:56:50'),
(621,'','cv_blend','cv',33,'CV by Vowel Stage 1',24,'2026-04-30 13:56:50'),
(622,'','cv_blend','cv',34,'CV by Cons Stage 1',34,'2026-04-30 13:56:50'),
(623,'','cv_blend','cv',35,'CV by Cons Stage 1',35,'2026-04-30 13:56:50'),
(624,'','cv_blend','cv',36,'CV by Vowel Stage 1',26,'2026-04-30 13:56:50'),
(625,'','cv_blend','cv',37,'CV by Cons Stage 3',76,'2026-04-30 13:56:50'),
(626,'','cv_blend','cv',38,'CV by Cons Stage 3',79,'2026-04-30 13:56:50'),
(627,'','cv_blend','cv',39,'CV by Cons Stage 3',77,'2026-04-30 13:56:50'),
(628,'','cv_blend','cv',40,'CV by Cons Stage 3',78,'2026-04-30 13:56:50'),
(629,'','cv_blend','cv',41,'CV by Cons Stage 2',36,'2026-04-30 13:56:50'),
(630,'','cv_blend','cv',42,'CV by Cons Stage 2',39,'2026-04-30 13:56:50'),
(631,'','cv_blend','cv',43,'CV by Vowel Stage 1',28,'2026-04-30 13:56:50'),
(632,'','cv_blend','cv',44,'CV by Vowel Stage 1',27,'2026-04-30 13:56:50'),
(633,'','cv_blend','cv',45,'CV by Cons Stage 2',37,'2026-04-30 13:56:50'),
(634,'','cv_blend','cv',46,'CV by Cons Stage 2',38,'2026-04-30 13:56:50'),
(635,'','cv_blend','cv',47,'CV by Vowel Stage 1',29,'2026-04-30 13:56:50'),
(636,'','cv_blend','cv',48,'CV by Cons Stage 2',52,'2026-04-30 13:56:50'),
(637,'','cv_blend','cv',49,'CV by Cons Stage 2',55,'2026-04-30 13:56:50'),
(638,'','cv_blend','cv',50,'CV by Cons Stage 2',53,'2026-04-30 13:56:50'),
(639,'','cv_blend','cv',51,'CV by Cons Stage 2',54,'2026-04-30 13:56:50'),
(640,'','cv_blend','cv',52,'CV by Cons Stage 1',0,'2026-04-30 13:56:50'),
(641,'','cv_blend','cv',53,'CV by Vowel Stage 1',1,'2026-04-30 13:56:50'),
(642,'','cv_blend','cv',54,'CV by Cons Stage 1',3,'2026-04-30 13:56:50'),
(643,'','cv_blend','cv',55,'CV by Vowel Stage 1',0,'2026-04-30 13:56:50'),
(644,'','cv_blend','cv',56,'CV by Cons Stage 1',1,'2026-04-30 13:56:50'),
(645,'','cv_blend','cv',57,'CV by Cons Stage 1',2,'2026-04-30 13:56:50'),
(646,'','cv_blend','cv',58,'CV by Vowel Stage 1',2,'2026-04-30 13:56:50'),
(647,'','cv_blend','cv',59,'CV by Cons Stage 1',20,'2026-04-30 13:56:50'),
(648,'','cv_blend','cv',60,'CV by Cons Stage 1',24,'2026-04-30 13:56:50'),
(649,'','cv_blend','cv',61,'CV by Vowel Stage 1',16,'2026-04-30 13:56:50'),
(650,'','cv_blend','cv',62,'CV by Vowel Stage 1',15,'2026-04-30 13:56:50'),
(651,'','cv_blend','cv',63,'CV by Cons Stage 1',23,'2026-04-30 13:56:50'),
(652,'','cv_blend','cv',65,'CV by Vowel Stage 1',17,'2026-04-30 13:56:50'),
(653,'','cv_blend','cv',66,'CV by Cons Stage 1',4,'2026-04-30 13:56:50'),
(654,'','cv_blend','cv',67,'CV by Cons Stage 1',7,'2026-04-30 13:56:50'),
(655,'','cv_blend','cv',68,'CV by Vowel Stage 1',4,'2026-04-30 13:56:50'),
(656,'','cv_blend','cv',69,'CV by Vowel Stage 1',3,'2026-04-30 13:56:50'),
(657,'','cv_blend','cv',70,'CV by Cons Stage 1',5,'2026-04-30 13:56:50'),
(658,'','cv_blend','cv',71,'CV by Cons Stage 1',6,'2026-04-30 13:56:50'),
(659,'','cv_blend','cv',72,'CV by Vowel Stage 1',5,'2026-04-30 13:56:50'),
(660,'','cv_blend','cv',73,'CV by Cons Stage 3',80,'2026-04-30 13:56:50'),
(661,'','cv_blend','cv',74,'CV by Cons Stage 3',83,'2026-04-30 13:56:50'),
(662,'','cv_blend','cv',75,'CV by Cons Stage 3',81,'2026-04-30 13:56:50'),
(663,'','cv_blend','cv',76,'CV by Cons Stage 3',82,'2026-04-30 13:56:50'),
(664,'','cv_blend','cv',77,'CV by Cons Stage 2',56,'2026-04-30 13:56:50'),
(665,'','cv_blend','cv',78,'CV by Cons Stage 2',59,'2026-04-30 13:56:50'),
(666,'','cv_blend','cv',79,'CV by Cons Stage 3',64,'2026-04-30 13:56:50'),
(667,'','cv_blend','cv',80,'CV by Cons Stage 3',67,'2026-04-30 13:56:50'),
(668,'','cv_blend','cv',81,'CV by Cons Stage 3',65,'2026-04-30 13:56:50'),
(669,'','cv_blend','cv',82,'CV by Cons Stage 3',66,'2026-04-30 13:56:50'),
(670,'','cv_blend','cv',83,'CV by Cons Stage 2',57,'2026-04-30 13:56:50'),
(671,'','cv_blend','cv',84,'CV by Cons Stage 2',58,'2026-04-30 13:56:50'),
(672,'','cv_blend','cv',85,'CV by Cons Stage 1',12,'2026-04-30 13:56:50'),
(673,'','cv_blend','cv',86,'CV by Cons Stage 1',15,'2026-04-30 13:56:50'),
(674,'','cv_blend','cv',87,'CV by Vowel Stage 1',10,'2026-04-30 13:56:50'),
(675,'','cv_blend','cv',88,'CV by Cons Stage 3',72,'2026-04-30 13:56:50'),
(676,'','cv_blend','cv',89,'CV by Cons Stage 3',75,'2026-04-30 13:56:50'),
(677,'','cv_blend','cv',90,'CV by Cons Stage 3',73,'2026-04-30 13:56:50'),
(678,'','cv_blend','cv',91,'CV by Cons Stage 3',74,'2026-04-30 13:56:50'),
(679,'','cv_blend','cv',92,'CV by Vowel Stage 1',9,'2026-04-30 13:56:50'),
(680,'','cv_blend','cv',93,'CV by Cons Stage 1',13,'2026-04-30 13:56:50'),
(681,'','cv_blend','cv',94,'CV by Vowel Stage 1',11,'2026-04-30 13:56:50'),
(682,'','cv_blend','cv',95,'CV by Cons Stage 1',14,'2026-04-30 13:56:50'),
(683,'','cv_blend','cv',96,'CV by Cons Stage 2',48,'2026-04-30 13:56:50'),
(684,'','cv_blend','cv',97,'CV by Cons Stage 2',51,'2026-04-30 13:56:50'),
(685,'','cv_blend','cv',98,'CV by Cons Stage 2',49,'2026-04-30 13:56:50'),
(686,'','cv_blend','cv',99,'CV by Cons Stage 2',50,'2026-04-30 13:56:50'),
(687,'','cv_blend','cv',100,'CV by Cons Stage 1',25,'2026-04-30 13:56:50'),
(688,'','cv_blend','cv',101,'CV by Vowel Stage 1',19,'2026-04-30 13:56:50'),
(689,'','cv_blend','cv',102,'CV by Cons Stage 1',28,'2026-04-30 13:56:50'),
(690,'','cv_blend','cv',103,'CV by Vowel Stage 1',18,'2026-04-30 13:56:50'),
(691,'','cv_blend','cv',104,'CV by Cons Stage 1',26,'2026-04-30 13:56:50'),
(692,'','cv_blend','cv',105,'CV by Cons Stage 1',27,'2026-04-30 13:56:50'),
(693,'','cv_blend','cv',106,'CV by Vowel Stage 1',20,'2026-04-30 13:56:50'),
(694,'','cv_blend','cv',107,'CV by Cons Stage 1',29,'2026-04-30 13:56:50'),
(695,'','cv_blend','cv',108,'CV by Cons Stage 1',32,'2026-04-30 13:56:50'),
(696,'','cv_blend','cv',109,'CV by Vowel Stage 1',22,'2026-04-30 13:56:50'),
(697,'','cv_blend','cv',110,'CV by Vowel Stage 1',21,'2026-04-30 13:56:50'),
(698,'','cv_blend','cv',111,'CV by Cons Stage 1',30,'2026-04-30 13:56:50'),
(699,'','cv_blend','cv',112,'CV by Cons Stage 1',31,'2026-04-30 13:56:50'),
(700,'','cv_blend','cv',113,'CV by Vowel Stage 1',23,'2026-04-30 13:56:50'),
(701,'','cv_blend','cv',114,'CV by Cons Stage 2',60,'2026-04-30 13:56:50'),
(702,'','cv_blend','cv',115,'CV by Cons Stage 2',63,'2026-04-30 13:56:50'),
(703,'','cv_blend','cv',116,'CV by Cons Stage 2',61,'2026-04-30 13:56:50'),
(704,'','cv_blend','cv',117,'CV by Cons Stage 2',62,'2026-04-30 13:56:50'),
(705,'','cv_blend','cv',118,'CV by Cons Stage 1',22,'2026-04-30 13:56:50'),
(844,'','cv_blend','cv_blending',1,'CV by Cons Stage 1',8,'2026-04-30 13:56:50'),
(845,'','cv_blend','cv_blending',2,'CV by Cons Stage 1',11,'2026-04-30 13:56:50'),
(846,'','cv_blend','cv_blending',3,'CV by Vowel Stage 1',7,'2026-04-30 13:56:50'),
(847,'','cv_blend','cv_blending',4,'CV by Vowel Stage 1',6,'2026-04-30 13:56:50'),
(848,'','cv_blend','cv_blending',5,'CV by Cons Stage 1',10,'2026-04-30 13:56:50'),
(849,'','cv_blend','cv_blending',6,'CV by Cons Stage 1',9,'2026-04-30 13:56:50'),
(850,'','cv_blend','cv_blending',7,'CV by Vowel Stage 1',8,'2026-04-30 13:56:50'),
(851,'','cv_blend','cv_blending',8,'CV by Cons Stage 3',68,'2026-04-30 13:56:50'),
(852,'','cv_blend','cv_blending',9,'CV by Cons Stage 3',71,'2026-04-30 13:56:50'),
(853,'','cv_blend','cv_blending',10,'CV by Cons Stage 3',69,'2026-04-30 13:56:50'),
(854,'','cv_blend','cv_blending',11,'CV by Cons Stage 3',70,'2026-04-30 13:56:50'),
(855,'','cv_blend','cv_blending',12,'CV by Cons Stage 1',16,'2026-04-30 13:56:50'),
(856,'','cv_blend','cv_blending',13,'CV by Cons Stage 1',19,'2026-04-30 13:56:50'),
(857,'','cv_blend','cv_blending',14,'CV by Vowel Stage 1',13,'2026-04-30 13:56:50'),
(858,'','cv_blend','cv_blending',15,'CV by Vowel Stage 1',12,'2026-04-30 13:56:50'),
(859,'','cv_blend','cv_blending',16,'CV by Cons Stage 1',17,'2026-04-30 13:56:50'),
(860,'','cv_blend','cv_blending',17,'CV by Cons Stage 1',18,'2026-04-30 13:56:50'),
(861,'','cv_blend','cv_blending',18,'CV by Vowel Stage 1',14,'2026-04-30 13:56:50'),
(862,'','cv_blend','cv_blending',19,'CV by Cons Stage 2',44,'2026-04-30 13:56:50'),
(863,'','cv_blend','cv_blending',20,'CV by Cons Stage 2',47,'2026-04-30 13:56:50'),
(864,'','cv_blend','cv_blending',21,'CV by Cons Stage 2',46,'2026-04-30 13:56:50'),
(865,'','cv_blend','cv_blending',22,'CV by Cons Stage 2',45,'2026-04-30 13:56:50'),
(866,'','cv_blend','cv_blending',23,'CV by Cons Stage 2',40,'2026-04-30 13:56:50'),
(867,'','cv_blend','cv_blending',24,'CV by Cons Stage 2',43,'2026-04-30 13:56:50'),
(868,'','cv_blend','cv_blending',25,'CV by Vowel Stage 1',31,'2026-04-30 13:56:50'),
(869,'','cv_blend','cv_blending',26,'CV by Vowel Stage 1',30,'2026-04-30 13:56:50'),
(870,'','cv_blend','cv_blending',27,'CV by Cons Stage 2',42,'2026-04-30 13:56:50'),
(871,'','cv_blend','cv_blending',28,'CV by Cons Stage 2',41,'2026-04-30 13:56:50'),
(872,'','cv_blend','cv_blending',29,'CV by Vowel Stage 1',32,'2026-04-30 13:56:50'),
(873,'','cv_blend','cv_blending',30,'CV by Cons Stage 1',33,'2026-04-30 13:56:50'),
(874,'','cv_blend','cv_blending',31,'CV by Cons Stage 1',36,'2026-04-30 13:56:50'),
(875,'','cv_blend','cv_blending',32,'CV by Vowel Stage 1',25,'2026-04-30 13:56:50'),
(876,'','cv_blend','cv_blending',33,'CV by Vowel Stage 1',24,'2026-04-30 13:56:50'),
(877,'','cv_blend','cv_blending',34,'CV by Cons Stage 1',34,'2026-04-30 13:56:50'),
(878,'','cv_blend','cv_blending',35,'CV by Cons Stage 1',35,'2026-04-30 13:56:50'),
(879,'','cv_blend','cv_blending',36,'CV by Vowel Stage 1',26,'2026-04-30 13:56:50'),
(880,'','cv_blend','cv_blending',37,'CV by Cons Stage 3',76,'2026-04-30 13:56:50'),
(881,'','cv_blend','cv_blending',38,'CV by Cons Stage 3',79,'2026-04-30 13:56:50'),
(882,'','cv_blend','cv_blending',39,'CV by Cons Stage 3',77,'2026-04-30 13:56:50'),
(883,'','cv_blend','cv_blending',40,'CV by Cons Stage 3',78,'2026-04-30 13:56:50'),
(884,'','cv_blend','cv_blending',41,'CV by Cons Stage 2',36,'2026-04-30 13:56:50'),
(885,'','cv_blend','cv_blending',42,'CV by Cons Stage 2',39,'2026-04-30 13:56:50'),
(886,'','cv_blend','cv_blending',43,'CV by Vowel Stage 1',28,'2026-04-30 13:56:50'),
(887,'','cv_blend','cv_blending',44,'CV by Vowel Stage 1',27,'2026-04-30 13:56:50'),
(888,'','cv_blend','cv_blending',45,'CV by Cons Stage 2',37,'2026-04-30 13:56:50'),
(889,'','cv_blend','cv_blending',46,'CV by Cons Stage 2',38,'2026-04-30 13:56:50'),
(890,'','cv_blend','cv_blending',47,'CV by Vowel Stage 1',29,'2026-04-30 13:56:50'),
(891,'','cv_blend','cv_blending',48,'CV by Cons Stage 2',52,'2026-04-30 13:56:50'),
(892,'','cv_blend','cv_blending',49,'CV by Cons Stage 2',55,'2026-04-30 13:56:50'),
(893,'','cv_blend','cv_blending',50,'CV by Cons Stage 2',53,'2026-04-30 13:56:50'),
(894,'','cv_blend','cv_blending',51,'CV by Cons Stage 2',54,'2026-04-30 13:56:50'),
(895,'','cv_blend','cv_blending',52,'CV by Cons Stage 1',0,'2026-04-30 13:56:50'),
(896,'','cv_blend','cv_blending',53,'CV by Vowel Stage 1',1,'2026-04-30 13:56:50'),
(897,'','cv_blend','cv_blending',54,'CV by Cons Stage 1',3,'2026-04-30 13:56:50'),
(898,'','cv_blend','cv_blending',55,'CV by Vowel Stage 1',0,'2026-04-30 13:56:50'),
(899,'','cv_blend','cv_blending',56,'CV by Cons Stage 1',1,'2026-04-30 13:56:50'),
(900,'','cv_blend','cv_blending',57,'CV by Cons Stage 1',2,'2026-04-30 13:56:50'),
(901,'','cv_blend','cv_blending',58,'CV by Vowel Stage 1',2,'2026-04-30 13:56:50'),
(902,'','cv_blend','cv_blending',59,'CV by Cons Stage 1',20,'2026-04-30 13:56:50'),
(903,'','cv_blend','cv_blending',60,'CV by Cons Stage 1',24,'2026-04-30 13:56:50'),
(904,'','cv_blend','cv_blending',61,'CV by Vowel Stage 1',16,'2026-04-30 13:56:50'),
(905,'','cv_blend','cv_blending',62,'CV by Vowel Stage 1',15,'2026-04-30 13:56:50'),
(906,'','cv_blend','cv_blending',63,'CV by Cons Stage 1',23,'2026-04-30 13:56:50'),
(907,'','cv_blend','cv_blending',65,'CV by Vowel Stage 1',17,'2026-04-30 13:56:50'),
(908,'','cv_blend','cv_blending',66,'CV by Cons Stage 1',4,'2026-04-30 13:56:50'),
(909,'','cv_blend','cv_blending',67,'CV by Cons Stage 1',7,'2026-04-30 13:56:50'),
(910,'','cv_blend','cv_blending',68,'CV by Vowel Stage 1',4,'2026-04-30 13:56:50'),
(911,'','cv_blend','cv_blending',69,'CV by Vowel Stage 1',3,'2026-04-30 13:56:50'),
(912,'','cv_blend','cv_blending',70,'CV by Cons Stage 1',5,'2026-04-30 13:56:50'),
(913,'','cv_blend','cv_blending',71,'CV by Cons Stage 1',6,'2026-04-30 13:56:50'),
(914,'','cv_blend','cv_blending',72,'CV by Vowel Stage 1',5,'2026-04-30 13:56:50'),
(915,'','cv_blend','cv_blending',73,'CV by Cons Stage 3',80,'2026-04-30 13:56:50'),
(916,'','cv_blend','cv_blending',74,'CV by Cons Stage 3',83,'2026-04-30 13:56:50'),
(917,'','cv_blend','cv_blending',75,'CV by Cons Stage 3',81,'2026-04-30 13:56:50'),
(918,'','cv_blend','cv_blending',76,'CV by Cons Stage 3',82,'2026-04-30 13:56:50'),
(919,'','cv_blend','cv_blending',77,'CV by Cons Stage 2',56,'2026-04-30 13:56:50'),
(920,'','cv_blend','cv_blending',78,'CV by Cons Stage 2',59,'2026-04-30 13:56:50'),
(921,'','cv_blend','cv_blending',79,'CV by Cons Stage 3',64,'2026-04-30 13:56:50'),
(922,'','cv_blend','cv_blending',80,'CV by Cons Stage 3',67,'2026-04-30 13:56:50'),
(923,'','cv_blend','cv_blending',81,'CV by Cons Stage 3',65,'2026-04-30 13:56:50'),
(924,'','cv_blend','cv_blending',82,'CV by Cons Stage 3',66,'2026-04-30 13:56:50'),
(925,'','cv_blend','cv_blending',83,'CV by Cons Stage 2',57,'2026-04-30 13:56:50'),
(926,'','cv_blend','cv_blending',84,'CV by Cons Stage 2',58,'2026-04-30 13:56:50'),
(927,'','cv_blend','cv_blending',85,'CV by Cons Stage 1',12,'2026-04-30 13:56:50'),
(928,'','cv_blend','cv_blending',86,'CV by Cons Stage 1',15,'2026-04-30 13:56:50'),
(929,'','cv_blend','cv_blending',87,'CV by Vowel Stage 1',10,'2026-04-30 13:56:50'),
(930,'','cv_blend','cv_blending',88,'CV by Cons Stage 3',72,'2026-04-30 13:56:50'),
(931,'','cv_blend','cv_blending',89,'CV by Cons Stage 3',75,'2026-04-30 13:56:50'),
(932,'','cv_blend','cv_blending',90,'CV by Cons Stage 3',73,'2026-04-30 13:56:50'),
(933,'','cv_blend','cv_blending',91,'CV by Cons Stage 3',74,'2026-04-30 13:56:50'),
(934,'','cv_blend','cv_blending',92,'CV by Vowel Stage 1',9,'2026-04-30 13:56:50'),
(935,'','cv_blend','cv_blending',93,'CV by Cons Stage 1',13,'2026-04-30 13:56:50'),
(936,'','cv_blend','cv_blending',94,'CV by Vowel Stage 1',11,'2026-04-30 13:56:50'),
(937,'','cv_blend','cv_blending',95,'CV by Cons Stage 1',14,'2026-04-30 13:56:50'),
(938,'','cv_blend','cv_blending',96,'CV by Cons Stage 2',48,'2026-04-30 13:56:50'),
(939,'','cv_blend','cv_blending',97,'CV by Cons Stage 2',51,'2026-04-30 13:56:50'),
(940,'','cv_blend','cv_blending',98,'CV by Cons Stage 2',49,'2026-04-30 13:56:50'),
(941,'','cv_blend','cv_blending',99,'CV by Cons Stage 2',50,'2026-04-30 13:56:50'),
(942,'','cv_blend','cv_blending',100,'CV by Cons Stage 1',25,'2026-04-30 13:56:50'),
(943,'','cv_blend','cv_blending',101,'CV by Vowel Stage 1',19,'2026-04-30 13:56:50'),
(944,'','cv_blend','cv_blending',102,'CV by Cons Stage 1',28,'2026-04-30 13:56:50'),
(945,'','cv_blend','cv_blending',103,'CV by Vowel Stage 1',18,'2026-04-30 13:56:50'),
(946,'','cv_blend','cv_blending',104,'CV by Cons Stage 1',26,'2026-04-30 13:56:50'),
(947,'','cv_blend','cv_blending',105,'CV by Cons Stage 1',27,'2026-04-30 13:56:50'),
(948,'','cv_blend','cv_blending',106,'CV by Vowel Stage 1',20,'2026-04-30 13:56:50'),
(949,'','cv_blend','cv_blending',107,'CV by Cons Stage 1',29,'2026-04-30 13:56:50'),
(950,'','cv_blend','cv_blending',108,'CV by Cons Stage 1',32,'2026-04-30 13:56:50'),
(951,'','cv_blend','cv_blending',109,'CV by Vowel Stage 1',22,'2026-04-30 13:56:50'),
(952,'','cv_blend','cv_blending',110,'CV by Vowel Stage 1',21,'2026-04-30 13:56:50'),
(953,'','cv_blend','cv_blending',111,'CV by Cons Stage 1',30,'2026-04-30 13:56:50'),
(954,'','cv_blend','cv_blending',112,'CV by Cons Stage 1',31,'2026-04-30 13:56:50'),
(955,'','cv_blend','cv_blending',113,'CV by Vowel Stage 1',23,'2026-04-30 13:56:50'),
(956,'','cv_blend','cv_blending',114,'CV by Cons Stage 2',60,'2026-04-30 13:56:50'),
(957,'','cv_blend','cv_blending',115,'CV by Cons Stage 2',63,'2026-04-30 13:56:50'),
(958,'','cv_blend','cv_blending',116,'CV by Cons Stage 2',61,'2026-04-30 13:56:50'),
(959,'','cv_blend','cv_blending',117,'CV by Cons Stage 2',62,'2026-04-30 13:56:50'),
(960,'','cv_blend','cv_blending',118,'CV by Cons Stage 1',22,'2026-04-30 13:56:50'),
(1099,'','cv_blend','syllable_shifts',1,'CV by Cons Stage 1',8,'2026-04-30 13:56:50'),
(1100,'','cv_blend','syllable_shifts',2,'CV by Cons Stage 1',11,'2026-04-30 13:56:50'),
(1101,'','cv_blend','syllable_shifts',3,'CV by Vowel Stage 1',7,'2026-04-30 13:56:50'),
(1102,'','cv_blend','syllable_shifts',4,'CV by Vowel Stage 1',6,'2026-04-30 13:56:50'),
(1103,'','cv_blend','syllable_shifts',5,'CV by Cons Stage 1',10,'2026-04-30 13:56:50'),
(1104,'','cv_blend','syllable_shifts',6,'CV by Cons Stage 1',9,'2026-04-30 13:56:50'),
(1105,'','cv_blend','syllable_shifts',7,'CV by Vowel Stage 1',8,'2026-04-30 13:56:50'),
(1106,'','cv_blend','syllable_shifts',8,'CV by Cons Stage 3',68,'2026-04-30 13:56:50'),
(1107,'','cv_blend','syllable_shifts',9,'CV by Cons Stage 3',71,'2026-04-30 13:56:50'),
(1108,'','cv_blend','syllable_shifts',10,'CV by Cons Stage 3',69,'2026-04-30 13:56:50'),
(1109,'','cv_blend','syllable_shifts',11,'CV by Cons Stage 3',70,'2026-04-30 13:56:50'),
(1110,'','cv_blend','syllable_shifts',12,'CV by Cons Stage 1',16,'2026-04-30 13:56:50'),
(1111,'','cv_blend','syllable_shifts',13,'CV by Cons Stage 1',19,'2026-04-30 13:56:50'),
(1112,'','cv_blend','syllable_shifts',14,'CV by Vowel Stage 1',13,'2026-04-30 13:56:50'),
(1113,'','cv_blend','syllable_shifts',15,'CV by Vowel Stage 1',12,'2026-04-30 13:56:50'),
(1114,'','cv_blend','syllable_shifts',16,'CV by Cons Stage 1',17,'2026-04-30 13:56:50'),
(1115,'','cv_blend','syllable_shifts',17,'CV by Cons Stage 1',18,'2026-04-30 13:56:50'),
(1116,'','cv_blend','syllable_shifts',18,'CV by Vowel Stage 1',14,'2026-04-30 13:56:50'),
(1117,'','cv_blend','syllable_shifts',19,'CV by Cons Stage 2',44,'2026-04-30 13:56:50'),
(1118,'','cv_blend','syllable_shifts',20,'CV by Cons Stage 2',47,'2026-04-30 13:56:50'),
(1119,'','cv_blend','syllable_shifts',21,'CV by Cons Stage 2',46,'2026-04-30 13:56:50'),
(1120,'','cv_blend','syllable_shifts',22,'CV by Cons Stage 2',45,'2026-04-30 13:56:50'),
(1121,'','cv_blend','syllable_shifts',23,'CV by Cons Stage 2',40,'2026-04-30 13:56:50'),
(1122,'','cv_blend','syllable_shifts',24,'CV by Cons Stage 2',43,'2026-04-30 13:56:50'),
(1123,'','cv_blend','syllable_shifts',25,'CV by Vowel Stage 1',31,'2026-04-30 13:56:50'),
(1124,'','cv_blend','syllable_shifts',26,'CV by Vowel Stage 1',30,'2026-04-30 13:56:50'),
(1125,'','cv_blend','syllable_shifts',27,'CV by Cons Stage 2',42,'2026-04-30 13:56:50'),
(1126,'','cv_blend','syllable_shifts',28,'CV by Cons Stage 2',41,'2026-04-30 13:56:50'),
(1127,'','cv_blend','syllable_shifts',29,'CV by Vowel Stage 1',32,'2026-04-30 13:56:50'),
(1128,'','cv_blend','syllable_shifts',30,'CV by Cons Stage 1',33,'2026-04-30 13:56:50'),
(1129,'','cv_blend','syllable_shifts',31,'CV by Cons Stage 1',36,'2026-04-30 13:56:50'),
(1130,'','cv_blend','syllable_shifts',32,'CV by Vowel Stage 1',25,'2026-04-30 13:56:50'),
(1131,'','cv_blend','syllable_shifts',33,'CV by Vowel Stage 1',24,'2026-04-30 13:56:50'),
(1132,'','cv_blend','syllable_shifts',34,'CV by Cons Stage 1',34,'2026-04-30 13:56:50'),
(1133,'','cv_blend','syllable_shifts',35,'CV by Cons Stage 1',35,'2026-04-30 13:56:50'),
(1134,'','cv_blend','syllable_shifts',36,'CV by Vowel Stage 1',26,'2026-04-30 13:56:50'),
(1135,'','cv_blend','syllable_shifts',37,'CV by Cons Stage 3',76,'2026-04-30 13:56:50'),
(1136,'','cv_blend','syllable_shifts',38,'CV by Cons Stage 3',79,'2026-04-30 13:56:50'),
(1137,'','cv_blend','syllable_shifts',39,'CV by Cons Stage 3',77,'2026-04-30 13:56:50'),
(1138,'','cv_blend','syllable_shifts',40,'CV by Cons Stage 3',78,'2026-04-30 13:56:50'),
(1139,'','cv_blend','syllable_shifts',41,'CV by Cons Stage 2',36,'2026-04-30 13:56:50'),
(1140,'','cv_blend','syllable_shifts',42,'CV by Cons Stage 2',39,'2026-04-30 13:56:50'),
(1141,'','cv_blend','syllable_shifts',43,'CV by Vowel Stage 1',28,'2026-04-30 13:56:50'),
(1142,'','cv_blend','syllable_shifts',44,'CV by Vowel Stage 1',27,'2026-04-30 13:56:50'),
(1143,'','cv_blend','syllable_shifts',45,'CV by Cons Stage 2',37,'2026-04-30 13:56:50'),
(1144,'','cv_blend','syllable_shifts',46,'CV by Cons Stage 2',38,'2026-04-30 13:56:50'),
(1145,'','cv_blend','syllable_shifts',47,'CV by Vowel Stage 1',29,'2026-04-30 13:56:50'),
(1146,'','cv_blend','syllable_shifts',48,'CV by Cons Stage 2',52,'2026-04-30 13:56:50'),
(1147,'','cv_blend','syllable_shifts',49,'CV by Cons Stage 2',55,'2026-04-30 13:56:50'),
(1148,'','cv_blend','syllable_shifts',50,'CV by Cons Stage 2',53,'2026-04-30 13:56:50'),
(1149,'','cv_blend','syllable_shifts',51,'CV by Cons Stage 2',54,'2026-04-30 13:56:50'),
(1150,'','cv_blend','syllable_shifts',52,'CV by Cons Stage 1',0,'2026-04-30 13:56:50'),
(1151,'','cv_blend','syllable_shifts',53,'CV by Vowel Stage 1',1,'2026-04-30 13:56:50'),
(1152,'','cv_blend','syllable_shifts',54,'CV by Cons Stage 1',3,'2026-04-30 13:56:50'),
(1153,'','cv_blend','syllable_shifts',55,'CV by Vowel Stage 1',0,'2026-04-30 13:56:50'),
(1154,'','cv_blend','syllable_shifts',56,'CV by Cons Stage 1',1,'2026-04-30 13:56:50'),
(1155,'','cv_blend','syllable_shifts',57,'CV by Cons Stage 1',2,'2026-04-30 13:56:50'),
(1156,'','cv_blend','syllable_shifts',58,'CV by Vowel Stage 1',2,'2026-04-30 13:56:50'),
(1157,'','cv_blend','syllable_shifts',59,'CV by Cons Stage 1',20,'2026-04-30 13:56:50'),
(1158,'','cv_blend','syllable_shifts',60,'CV by Cons Stage 1',24,'2026-04-30 13:56:50'),
(1159,'','cv_blend','syllable_shifts',61,'CV by Vowel Stage 1',16,'2026-04-30 13:56:50'),
(1160,'','cv_blend','syllable_shifts',62,'CV by Vowel Stage 1',15,'2026-04-30 13:56:50'),
(1161,'','cv_blend','syllable_shifts',63,'CV by Cons Stage 1',23,'2026-04-30 13:56:50'),
(1162,'','cv_blend','syllable_shifts',65,'CV by Vowel Stage 1',17,'2026-04-30 13:56:50'),
(1163,'','cv_blend','syllable_shifts',66,'CV by Cons Stage 1',4,'2026-04-30 13:56:50'),
(1164,'','cv_blend','syllable_shifts',67,'CV by Cons Stage 1',7,'2026-04-30 13:56:50'),
(1165,'','cv_blend','syllable_shifts',68,'CV by Vowel Stage 1',4,'2026-04-30 13:56:50'),
(1166,'','cv_blend','syllable_shifts',69,'CV by Vowel Stage 1',3,'2026-04-30 13:56:50'),
(1167,'','cv_blend','syllable_shifts',70,'CV by Cons Stage 1',5,'2026-04-30 13:56:50'),
(1168,'','cv_blend','syllable_shifts',71,'CV by Cons Stage 1',6,'2026-04-30 13:56:50'),
(1169,'','cv_blend','syllable_shifts',72,'CV by Vowel Stage 1',5,'2026-04-30 13:56:50'),
(1170,'','cv_blend','syllable_shifts',73,'CV by Cons Stage 3',80,'2026-04-30 13:56:50'),
(1171,'','cv_blend','syllable_shifts',74,'CV by Cons Stage 3',83,'2026-04-30 13:56:50'),
(1172,'','cv_blend','syllable_shifts',75,'CV by Cons Stage 3',81,'2026-04-30 13:56:50'),
(1173,'','cv_blend','syllable_shifts',76,'CV by Cons Stage 3',82,'2026-04-30 13:56:50'),
(1174,'','cv_blend','syllable_shifts',77,'CV by Cons Stage 2',56,'2026-04-30 13:56:50'),
(1175,'','cv_blend','syllable_shifts',78,'CV by Cons Stage 2',59,'2026-04-30 13:56:50'),
(1176,'','cv_blend','syllable_shifts',79,'CV by Cons Stage 3',64,'2026-04-30 13:56:50'),
(1177,'','cv_blend','syllable_shifts',80,'CV by Cons Stage 3',67,'2026-04-30 13:56:50'),
(1178,'','cv_blend','syllable_shifts',81,'CV by Cons Stage 3',65,'2026-04-30 13:56:50'),
(1179,'','cv_blend','syllable_shifts',82,'CV by Cons Stage 3',66,'2026-04-30 13:56:50'),
(1180,'','cv_blend','syllable_shifts',83,'CV by Cons Stage 2',57,'2026-04-30 13:56:50'),
(1181,'','cv_blend','syllable_shifts',84,'CV by Cons Stage 2',58,'2026-04-30 13:56:50'),
(1182,'','cv_blend','syllable_shifts',85,'CV by Cons Stage 1',12,'2026-04-30 13:56:50'),
(1183,'','cv_blend','syllable_shifts',86,'CV by Cons Stage 1',15,'2026-04-30 13:56:50'),
(1184,'','cv_blend','syllable_shifts',87,'CV by Vowel Stage 1',10,'2026-04-30 13:56:50'),
(1185,'','cv_blend','syllable_shifts',88,'CV by Cons Stage 3',72,'2026-04-30 13:56:50'),
(1186,'','cv_blend','syllable_shifts',89,'CV by Cons Stage 3',75,'2026-04-30 13:56:50'),
(1187,'','cv_blend','syllable_shifts',90,'CV by Cons Stage 3',73,'2026-04-30 13:56:50'),
(1188,'','cv_blend','syllable_shifts',91,'CV by Cons Stage 3',74,'2026-04-30 13:56:50'),
(1189,'','cv_blend','syllable_shifts',92,'CV by Vowel Stage 1',9,'2026-04-30 13:56:50'),
(1190,'','cv_blend','syllable_shifts',93,'CV by Cons Stage 1',13,'2026-04-30 13:56:50'),
(1191,'','cv_blend','syllable_shifts',94,'CV by Vowel Stage 1',11,'2026-04-30 13:56:50'),
(1192,'','cv_blend','syllable_shifts',95,'CV by Cons Stage 1',14,'2026-04-30 13:56:50'),
(1193,'','cv_blend','syllable_shifts',96,'CV by Cons Stage 2',48,'2026-04-30 13:56:50'),
(1194,'','cv_blend','syllable_shifts',97,'CV by Cons Stage 2',51,'2026-04-30 13:56:50'),
(1195,'','cv_blend','syllable_shifts',98,'CV by Cons Stage 2',49,'2026-04-30 13:56:50'),
(1196,'','cv_blend','syllable_shifts',99,'CV by Cons Stage 2',50,'2026-04-30 13:56:50'),
(1197,'','cv_blend','syllable_shifts',100,'CV by Cons Stage 1',25,'2026-04-30 13:56:50'),
(1198,'','cv_blend','syllable_shifts',101,'CV by Vowel Stage 1',19,'2026-04-30 13:56:50'),
(1199,'','cv_blend','syllable_shifts',102,'CV by Cons Stage 1',28,'2026-04-30 13:56:50'),
(1200,'','cv_blend','syllable_shifts',103,'CV by Vowel Stage 1',18,'2026-04-30 13:56:50'),
(1201,'','cv_blend','syllable_shifts',104,'CV by Cons Stage 1',26,'2026-04-30 13:56:50'),
(1202,'','cv_blend','syllable_shifts',105,'CV by Cons Stage 1',27,'2026-04-30 13:56:50'),
(1203,'','cv_blend','syllable_shifts',106,'CV by Vowel Stage 1',20,'2026-04-30 13:56:50'),
(1204,'','cv_blend','syllable_shifts',107,'CV by Cons Stage 1',29,'2026-04-30 13:56:50'),
(1205,'','cv_blend','syllable_shifts',108,'CV by Cons Stage 1',32,'2026-04-30 13:56:50'),
(1206,'','cv_blend','syllable_shifts',109,'CV by Vowel Stage 1',22,'2026-04-30 13:56:50'),
(1207,'','cv_blend','syllable_shifts',110,'CV by Vowel Stage 1',21,'2026-04-30 13:56:50'),
(1208,'','cv_blend','syllable_shifts',111,'CV by Cons Stage 1',30,'2026-04-30 13:56:50'),
(1209,'','cv_blend','syllable_shifts',112,'CV by Cons Stage 1',31,'2026-04-30 13:56:50'),
(1210,'','cv_blend','syllable_shifts',113,'CV by Vowel Stage 1',23,'2026-04-30 13:56:50'),
(1211,'','cv_blend','syllable_shifts',114,'CV by Cons Stage 2',60,'2026-04-30 13:56:50'),
(1212,'','cv_blend','syllable_shifts',115,'CV by Cons Stage 2',63,'2026-04-30 13:56:50'),
(1213,'','cv_blend','syllable_shifts',116,'CV by Cons Stage 2',61,'2026-04-30 13:56:50'),
(1214,'','cv_blend','syllable_shifts',117,'CV by Cons Stage 2',62,'2026-04-30 13:56:50'),
(1215,'','cv_blend','syllable_shifts',118,'CV by Cons Stage 1',22,'2026-04-30 13:56:50'),
(1216,'','consonant','syllable_shifts',1,'Consonants',1,'2026-04-30 13:56:50'),
(1217,'','consonant','syllable_shifts',11,'Consonants',2,'2026-04-30 13:56:50'),
(1218,'','consonant','syllable_shifts',9,'Consonants',3,'2026-04-30 13:56:50'),
(1219,'','consonant','syllable_shifts',2,'Consonants',4,'2026-04-30 13:56:50'),
(1220,'','consonant','syllable_shifts',14,'Consonants',5,'2026-04-30 13:56:50'),
(1221,'','consonant','syllable_shifts',10,'Consonants',6,'2026-04-30 13:56:50'),
(1222,'','consonant','syllable_shifts',8,'Consonants',7,'2026-04-30 13:56:50'),
(1223,'','consonant','syllable_shifts',7,'Consonants',8,'2026-04-30 13:56:50'),
(1224,'','consonant','syllable_shifts',4,'Consonants',9,'2026-04-30 13:56:50'),
(1225,'','consonant','syllable_shifts',3,'Consonants',10,'2026-04-30 13:56:50'),
(1226,'','consonant','syllable_shifts',15,'Consonants',11,'2026-04-30 13:56:50'),
(1227,'','consonant','syllable_shifts',13,'Consonants',12,'2026-04-30 13:56:50'),
(1356,'','vowel','syllable_shifts',11,'CV by Cons Stage 3',1,'2026-04-30 14:39:19');
/*!40000 ALTER TABLE `exercise_card_group_assignments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_card_overrides`
--

DROP TABLE IF EXISTS `exercise_card_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_card_overrides` (
  `override_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_uuid` varchar(36) NOT NULL,
  `card_type` enum('consonant','vowel','cv_blend','3cv_blend','word') NOT NULL,
  `card_id` bigint(20) unsigned NOT NULL,
  `sound_text` varchar(100) DEFAULT NULL,
  `image_path` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`override_id`),
  UNIQUE KEY `uq_user_card` (`user_uuid`,`card_type`,`card_id`),
  KEY `idx_user` (`user_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_card_overrides`
--

LOCK TABLES `exercise_card_overrides` WRITE;
/*!40000 ALTER TABLE `exercise_card_overrides` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exercise_card_overrides` VALUES
(1,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','cv_blend',1,'B-AH','/assets/portal/exercises/images/generated_images_cv/00_B-AH.jpg','2026-04-23 12:04:39','2026-04-23 12:04:39');
/*!40000 ALTER TABLE `exercise_card_overrides` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_consonants`
--

DROP TABLE IF EXISTS `exercise_consonants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_consonants` (
  `consonant_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_uuid` varchar(36) DEFAULT NULL,
  `consonant_code` varchar(10) NOT NULL,
  `consonant_label` varchar(50) DEFAULT NULL,
  `consonant_folder` varchar(24) DEFAULT NULL,
  `image_filename` varchar(255) DEFAULT NULL,
  `image_path` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`consonant_id`),
  KEY `idx_owner` (`owner_user_uuid`),
  KEY `idx_active` (`is_active`),
  KEY `idx_order` (`display_order`),
  KEY `idx_exercise_owner_active` (`owner_user_uuid`,`is_active`),
  CONSTRAINT `exercise_consonants_ibfk_1` FOREIGN KEY (`owner_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_consonants`
--

LOCK TABLES `exercise_consonants` WRITE;
/*!40000 ALTER TABLE `exercise_consonants` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exercise_consonants` VALUES
(1,NULL,'B','B','Stage 1','00_B.png','/assets/portal/exercises/images/generated_images_consonants/00_B.png',5,1,'2026-02-05 15:27:56','2026-04-22 17:22:37'),
(2,NULL,'D','D','Stage 1','00_D.png','/assets/portal/exercises/images/generated_images_consonants/00_D.png',1,1,'2026-02-05 15:27:56','2026-04-22 17:22:19'),
(3,NULL,'F','F','Stage 2','00_F.png','/assets/portal/exercises/images/generated_images_consonants/00_F.png',11,1,'2026-02-05 15:27:56','2026-03-25 00:26:44'),
(4,NULL,'G','G','Stage 2','00_G.png','/assets/portal/exercises/images/generated_images_consonants/00_G.png',10,1,'2026-02-05 15:27:56','2026-03-25 00:26:44'),
(5,NULL,'H','H','Stage 1','00_H.png','/assets/portal/exercises/images/generated_images_consonants/00_H.png',6,1,'2026-02-05 15:27:56','2026-04-22 17:22:37'),
(6,NULL,'J','J','Stage 3','00_J.png','/assets/portal/exercises/images/generated_images_consonants/00_J.png',19,1,'2026-02-05 15:27:56','2026-03-25 00:27:48'),
(7,NULL,'K','K','Stage 2','00_K.png','/assets/portal/exercises/images/generated_images_consonants/00_K.png',9,1,'2026-02-05 15:27:56','2026-03-25 00:26:44'),
(8,NULL,'L','L','Stage 2','00_L.png','/assets/portal/exercises/images/generated_images_consonants/00_L.png',13,1,'2026-02-05 15:27:56','2026-03-25 00:26:44'),
(9,NULL,'M','M','Stage 1','00_M.png','/assets/portal/exercises/images/generated_images_consonants/00_M.png',7,1,'2026-02-05 15:27:56','2026-04-22 17:22:37'),
(10,NULL,'N','N','Stage 1','00_N.png','/assets/portal/exercises/images/generated_images_consonants/00_N.png',2,1,'2026-02-05 15:27:56','2026-04-22 17:22:19'),
(11,NULL,'P','P','Stage 1','00_P.png','/assets/portal/exercises/images/generated_images_consonants/00_P.png',8,1,'2026-02-05 15:27:56','2026-04-22 17:22:47'),
(12,NULL,'R','R','Stage 3','00_R.png','/assets/portal/exercises/images/generated_images_consonants/00_R.png',20,1,'2026-02-05 15:27:56','2026-03-25 00:26:44'),
(13,NULL,'S','S','Stage 2','00_S.png','/assets/portal/exercises/images/generated_images_consonants/00_S.png',14,1,'2026-02-05 15:27:56','2026-03-25 00:26:44'),
(14,NULL,'T','T','Stage 1','00_T.png','/assets/portal/exercises/images/generated_images_consonants/00_T.png',0,1,'2026-02-05 15:27:56','2026-04-22 17:22:19'),
(15,NULL,'V','V','Stage 2','00_V.png','/assets/portal/exercises/images/generated_images_consonants/00_V.png',12,1,'2026-02-05 15:27:56','2026-03-25 00:26:44'),
(16,NULL,'W','W','Stage 1','00_W.png','/assets/portal/exercises/images/generated_images_consonants/00_W.png',3,1,'2026-02-05 15:27:56','2026-04-22 17:22:19'),
(17,NULL,'Y','Y','Stage 1','00_Y.png','/assets/portal/exercises/images/generated_images_consonants/00_Y.png',4,1,'2026-02-05 15:27:56','2026-04-22 17:22:19'),
(18,NULL,'Z','Z','Stage 2','00_Z.png','/assets/portal/exercises/images/generated_images_consonants/00_Z.png',15,1,'2026-02-05 15:27:56','2026-03-25 00:26:44'),
(19,NULL,'CH','CH','Stage 3','00_CH.png','/assets/portal/exercises/images/generated_images_consonants/00_CH.png',17,1,'2026-03-09 16:48:55','2026-03-25 00:26:44'),
(20,NULL,'SH','SH','Stage 3','00_SH.png','/assets/portal/exercises/images/generated_images_consonants/00_SH.png',16,1,'2026-03-09 16:48:55','2026-03-25 00:26:44'),
(21,NULL,'TH','TH','Stage 3','00_TH.png','/assets/portal/exercises/images/generated_images_consonants/00_TH.png',18,1,'2026-03-09 16:48:55','2026-03-25 00:27:48');
/*!40000 ALTER TABLE `exercise_consonants` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_cv_blends`
--

DROP TABLE IF EXISTS `exercise_cv_blends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_cv_blends` (
  `cv_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_uuid` varchar(36) DEFAULT NULL,
  `consonant_id` bigint(20) unsigned DEFAULT NULL,
  `vowel_id` bigint(20) unsigned DEFAULT NULL,
  `cv_type` varchar(50) DEFAULT NULL,
  `cv_code` varchar(20) NOT NULL,
  `cv_folder` varchar(24) DEFAULT NULL,
  `icon_filename` varchar(255) DEFAULT NULL,
  `icon_path` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`cv_id`),
  UNIQUE KEY `unique_cv` (`owner_user_uuid`,`consonant_id`,`vowel_id`),
  KEY `idx_owner` (`owner_user_uuid`),
  KEY `idx_consonant` (`consonant_id`),
  KEY `idx_vowel` (`vowel_id`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `exercise_cv_blends_ibfk_1` FOREIGN KEY (`owner_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE,
  CONSTRAINT `exercise_cv_blends_ibfk_2` FOREIGN KEY (`consonant_id`) REFERENCES `exercise_consonants` (`consonant_id`) ON DELETE CASCADE,
  CONSTRAINT `exercise_cv_blends_ibfk_3` FOREIGN KEY (`vowel_id`) REFERENCES `exercise_vowels` (`vowel_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_cv_blends`
--

LOCK TABLES `exercise_cv_blends` WRITE;
/*!40000 ALTER TABLE `exercise_cv_blends` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exercise_cv_blends` VALUES
(1,NULL,NULL,NULL,NULL,'BAH','CV by Cons Stage 1','00_Bah.png','/assets/portal/exercises/images/generated_images_cv/00_B-AH.png',8,1,'2026-03-09 14:52:07','2026-04-23 23:35:26'),
(2,NULL,NULL,NULL,NULL,'BEE','CV by Cons Stage 1','00_Bee.png','/assets/portal/exercises/images/generated_images_cv/00_BEE.png',11,1,'2026-03-09 14:52:07','2026-04-23 23:35:43'),
(3,NULL,NULL,NULL,NULL,'BEH','CV by Vowel Stage 1','00_Beh.png','/assets/portal/exercises/images/generated_images_cv/00_Beh.png',7,1,'2026-03-09 14:52:07','2026-04-23 23:35:51'),
(4,NULL,NULL,NULL,NULL,'BIH','CV by Vowel Stage 1','00_Bih.png','/assets/portal/exercises/images/generated_images_cv/00_BIH.png',6,1,'2026-03-09 14:52:07','2026-04-23 23:37:48'),
(5,NULL,NULL,NULL,NULL,'BOO','CV by Cons Stage 1','00_Boo.png','/assets/portal/exercises/images/generated_images_cv/00_Boo.png',10,1,'2026-03-09 14:52:07','2026-04-23 23:38:05'),
(6,NULL,NULL,NULL,NULL,'BOH','CV by Cons Stage 1','00_Bo.png','/assets/portal/exercises/images/generated_images_cv/00_Bo.png',9,1,'2026-03-09 14:52:07','2026-04-23 23:37:58'),
(7,NULL,NULL,NULL,NULL,'BUH','CV by Vowel Stage 1','00_Buh.png','/assets/portal/exercises/images/generated_images_cv/00_Buh.png',8,1,'2026-03-09 14:52:07','2026-04-23 23:38:12'),
(8,NULL,NULL,NULL,NULL,'CHA','CV by Cons Stage 3','00_Cha.png','/assets/portal/exercises/images/generated_images_cv/00_CHA.png',68,1,'2026-03-09 14:52:07','2026-04-23 23:45:16'),
(9,NULL,NULL,NULL,NULL,'CHEE','CV by Cons Stage 3','00_Chee.png','/assets/portal/exercises/images/generated_images_cv/00_Chee.png',71,1,'2026-03-09 14:52:07','2026-04-23 23:45:29'),
(10,NULL,NULL,NULL,NULL,'CHOH','CV by Cons Stage 3','00_Choh.png','/assets/portal/exercises/images/generated_images_cv/00_Choh.png',69,1,'2026-03-09 14:52:07','2026-04-23 23:45:53'),
(11,NULL,NULL,NULL,NULL,'CHOO','CV by Cons Stage 3','00_Choo.png','/assets/portal/exercises/images/generated_images_cv/00_Choo.png',70,1,'2026-03-09 14:52:07','2026-04-23 23:46:03'),
(12,NULL,NULL,NULL,NULL,'DAH','CV by Cons Stage 1','00_Da.png','/assets/portal/exercises/images/generated_images_cv/00_DAH.png',16,1,'2026-03-09 14:52:08','2026-04-23 23:49:30'),
(13,NULL,NULL,NULL,NULL,'DEE','CV by Cons Stage 1','00_Dee.png','/assets/portal/exercises/images/generated_images_cv/00_DEE.png',19,1,'2026-03-09 14:52:08','2026-04-23 23:53:43'),
(14,NULL,NULL,NULL,NULL,'DEH','CV by Vowel Stage 1','00_Deh.png','/assets/portal/exercises/images/generated_images_cv/00_Deh.png',13,1,'2026-03-09 14:52:08','2026-04-24 00:01:40'),
(15,NULL,NULL,NULL,NULL,'DIH','CV by Vowel Stage 1','00_Dih.png','/assets/portal/exercises/images/generated_images_cv/00_Dih.png',12,1,'2026-03-09 14:52:08','2026-04-24 01:13:26'),
(16,NULL,NULL,NULL,NULL,'DOH','CV by Cons Stage 1','00_Doh.png','/assets/portal/exercises/images/generated_images_cv/00_DOH.png',17,1,'2026-03-09 14:52:08','2026-04-24 00:54:27'),
(17,NULL,NULL,NULL,NULL,'DOO','CV by Cons Stage 1','00_Doo.png','/assets/portal/exercises/images/generated_images_cv/00_DOO.jpg',18,1,'2026-03-09 14:52:08','2026-04-24 01:13:08'),
(18,NULL,NULL,NULL,NULL,'DUH','CV by Vowel Stage 1','00_Duh.png','/assets/portal/exercises/images/generated_images_cv/00_Duh.png',14,1,'2026-03-09 14:52:08','2026-04-24 00:54:40'),
(19,NULL,NULL,NULL,NULL,'FAH','CV by Cons Stage 2','00_Fa.png','/assets/portal/exercises/images/generated_images_cv/00_FAH.png',44,1,'2026-03-09 14:52:08','2026-04-24 01:13:50'),
(20,NULL,NULL,NULL,NULL,'FEE','CV by Cons Stage 2','00_Fee.png','/assets/portal/exercises/images/generated_images_cv/00_Fee.png',47,1,'2026-03-09 14:52:08','2026-04-24 01:13:56'),
(21,NULL,NULL,NULL,NULL,'FOO','CV by Cons Stage 2','00_Foo.png','/assets/portal/exercises/images/generated_images_cv/00_Foo.png',46,1,'2026-03-09 14:52:08','2026-04-24 01:31:08'),
(22,NULL,NULL,NULL,NULL,'FOH','CV by Cons Stage 2','00_Fo.png','/assets/portal/exercises/images/generated_images_cv/00_FOH.png',45,1,'2026-03-09 14:52:08','2026-04-24 01:30:49'),
(23,NULL,NULL,NULL,NULL,'GAH','CV by Cons Stage 2','00_Ga.png','/assets/portal/exercises/images/generated_images_cv/00_GAH.png',40,1,'2026-03-09 14:52:08','2026-04-24 01:35:17'),
(24,NULL,NULL,NULL,NULL,'GEE','CV by Cons Stage 2','00_Gee.png','/assets/portal/exercises/images/generated_images_cv/00_Gee.png',43,1,'2026-03-09 14:52:08','2026-04-24 01:35:25'),
(25,NULL,NULL,NULL,NULL,'GEH','CV by Vowel Stage 1','00_Geh.png','/assets/portal/exercises/images/generated_images_cv/00_GEH.jpg',31,1,'2026-03-09 14:52:08','2026-04-24 01:45:32'),
(26,NULL,NULL,NULL,NULL,'GIH','CV by Vowel Stage 1','00_Gih.png','/assets/portal/exercises/images/generated_images_cv/00_Gih.png',30,1,'2026-03-09 14:52:08','2026-04-24 01:35:34'),
(27,NULL,NULL,NULL,NULL,'GOO','CV by Cons Stage 2','00_Goo.png','/assets/portal/exercises/images/generated_images_cv/00_GOO.png',42,1,'2026-03-09 14:52:08','2026-04-24 01:38:23'),
(28,NULL,NULL,NULL,NULL,'GOH','CV by Cons Stage 2','00_Go.png','/assets/portal/exercises/images/generated_images_cv/00_Go.png',41,1,'2026-03-09 14:52:08','2026-04-24 01:35:42'),
(29,NULL,NULL,NULL,NULL,'GUH','CV by Vowel Stage 1','00_Guh.png','/assets/portal/exercises/images/generated_images_cv/00_Guh.png',32,1,'2026-03-09 14:52:08','2026-04-24 01:38:42'),
(30,NULL,NULL,NULL,NULL,'HAH','CV by Cons Stage 1','00_Ha.png','/assets/portal/exercises/images/generated_images_cv/00_HAH.jpg',33,1,'2026-03-09 14:52:08','2026-04-24 01:49:22'),
(31,NULL,NULL,NULL,NULL,'HEE','CV by Cons Stage 1','00_Hee.png','/assets/portal/exercises/images/generated_images_cv/00_H-EE.png',36,1,'2026-03-09 14:52:08','2026-04-24 15:01:18'),
(32,NULL,NULL,NULL,NULL,'HEH','CV by Vowel Stage 1','00_Heh.png','/assets/portal/exercises/images/generated_images_cv/00_HEH.png',25,1,'2026-03-09 14:52:08','2026-04-24 15:00:59'),
(33,NULL,NULL,NULL,NULL,'HIH','CV by Vowel Stage 1','00_Hih.png','/assets/portal/exercises/images/generated_images_cv/00_Hih.png',24,1,'2026-03-09 14:52:08','2026-04-24 14:55:22'),
(34,NULL,NULL,NULL,NULL,'HOH','CV by Cons Stage 1','00_Hoh.png','/assets/portal/exercises/images/generated_images_cv/00_Hoh.png',34,1,'2026-03-09 14:52:08','2026-04-24 01:49:42'),
(35,NULL,NULL,NULL,NULL,'HOO','CV by Cons Stage 1','00_Hoo.png','/assets/portal/exercises/images/generated_images_cv/00_Hoo.png',35,1,'2026-03-09 14:52:08','2026-04-24 01:49:57'),
(36,NULL,NULL,NULL,NULL,'HUH','CV by Vowel Stage 1','00_Huh.png','/assets/portal/exercises/images/generated_images_cv/00_Huh.png',26,1,'2026-03-09 14:52:08','2026-04-24 14:51:58'),
(37,NULL,NULL,NULL,NULL,'JAH','CV by Cons Stage 3','00_Ja.png','/assets/portal/exercises/images/generated_images_cv/00_Ja.png',76,1,'2026-03-09 14:52:08','2026-04-24 02:36:34'),
(38,NULL,NULL,NULL,NULL,'JEE','CV by Cons Stage 3','00_Jee.png','/assets/portal/exercises/images/generated_images_cv/00_Jee.png',79,1,'2026-03-09 14:52:08','2026-04-24 02:36:15'),
(39,NULL,NULL,NULL,NULL,'JOH','CV by Cons Stage 3','00_Joh.png','/assets/portal/exercises/images/generated_images_cv/00_JOH.jpg',77,1,'2026-03-09 14:52:08','2026-04-24 02:36:02'),
(40,NULL,NULL,NULL,NULL,'JOO','CV by Cons Stage 3','00_Joo.png','/assets/portal/exercises/images/generated_images_cv/00_Joo.png',78,1,'2026-03-09 14:52:08','2026-04-24 02:32:38'),
(41,NULL,NULL,NULL,NULL,'KAH','CV by Cons Stage 2','00_Ka.png','/assets/portal/exercises/images/generated_images_cv/00_KAH.jpg',36,1,'2026-03-09 14:52:08','2026-04-24 02:03:56'),
(42,NULL,NULL,NULL,NULL,'KEE','CV by Cons Stage 2','00_Kee.png','/assets/portal/exercises/images/generated_images_cv/00_Kee.png',39,1,'2026-03-09 14:52:08','2026-04-24 02:32:28'),
(43,NULL,NULL,NULL,NULL,'KEH','CV by Vowel Stage 1','00_Keh.png','/assets/portal/exercises/images/generated_images_cv/00_Keh.png',28,1,'2026-03-09 14:52:08','2026-04-24 02:32:02'),
(44,NULL,NULL,NULL,NULL,'KIH','CV by Vowel Stage 1','00_Kih.png','/assets/portal/exercises/images/generated_images_cv/00_Kih.png',27,1,'2026-03-09 14:52:09','2026-04-24 02:32:16'),
(45,NULL,NULL,NULL,NULL,'KOH','CV by Cons Stage 2','00_Koh.png','/assets/portal/exercises/images/generated_images_cv/00_Koh.png',37,1,'2026-03-09 14:52:09','2026-04-24 02:06:47'),
(46,NULL,NULL,NULL,NULL,'KOO','CV by Cons Stage 2','00_Koo.png','/assets/portal/exercises/images/generated_images_cv/00_KOO.jpg',38,1,'2026-03-09 14:52:09','2026-04-24 02:08:07'),
(47,NULL,NULL,NULL,NULL,'KUH','CV by Vowel Stage 1','00_Kuh.png','/assets/portal/exercises/images/generated_images_cv/00_Kuh.png',29,1,'2026-03-09 14:52:09','2026-04-24 02:11:45'),
(48,NULL,NULL,NULL,NULL,'LOH','CV by Cons Stage 2','00_La.png','/assets/portal/exercises/images/generated_images_cv/00_LOH.jpg',52,1,'2026-03-09 14:52:09','2026-04-24 02:00:04'),
(49,NULL,NULL,NULL,NULL,'LEE','CV by Cons Stage 2','00_Lee.png','/assets/portal/exercises/images/generated_images_cv/00_LEE.jpg',55,1,'2026-03-09 14:52:09','2026-04-24 01:57:31'),
(50,NULL,NULL,NULL,NULL,'LAH','CV by Cons Stage 2','00_Loh.png','/assets/portal/exercises/images/generated_images_cv/00_Loh.png',53,1,'2026-03-09 14:52:09','2026-04-24 01:50:17'),
(51,NULL,NULL,NULL,NULL,'LOO','CV by Cons Stage 2','00_Loo.png','/assets/portal/exercises/images/generated_images_cv/00_LOO.jpg',54,1,'2026-03-09 14:52:09','2026-04-24 01:54:57'),
(52,NULL,NULL,NULL,NULL,'MAH','CV by Cons Stage 1','00_Ma.png','/assets/portal/exercises/images/generated_images_cv/00_Ma.png',0,1,'2026-03-09 14:52:09','2026-04-24 02:43:28'),
(53,NULL,NULL,NULL,NULL,'MEH','CV by Vowel Stage 1','00_Meh.png','/assets/portal/exercises/images/generated_images_cv/00_Meh.png',1,1,'2026-03-09 14:52:09','2026-04-24 02:43:46'),
(54,NULL,NULL,NULL,NULL,'MEE','CV by Cons Stage 1','00_Me.png','/assets/portal/exercises/images/generated_images_cv/00_MEE.png',3,1,'2026-03-09 14:52:09','2026-04-24 14:51:40'),
(55,NULL,NULL,NULL,NULL,'MIH','CV by Vowel Stage 1','00_Mih.png','/assets/portal/exercises/images/generated_images_cv/00_Mih.png',0,1,'2026-03-09 14:52:09','2026-04-24 02:43:56'),
(56,NULL,NULL,NULL,NULL,'MOH','CV by Cons Stage 1','00_Moh.png','/assets/portal/exercises/images/generated_images_cv/00_MOH.png',1,1,'2026-03-09 14:52:09','2026-04-24 15:05:02'),
(57,NULL,NULL,NULL,NULL,'MOO','CV by Cons Stage 1','00_Moo.png','/assets/portal/exercises/images/generated_images_cv/00_Moo.png',2,1,'2026-03-09 14:52:09','2026-04-24 02:43:12'),
(58,NULL,NULL,NULL,NULL,'MUH','CV by Vowel Stage 1','00_Muh.png','/assets/portal/exercises/images/generated_images_cv/00_MUH.png',2,1,'2026-03-09 14:52:09','2026-04-24 15:10:13'),
(59,NULL,NULL,NULL,NULL,'NAH','CV by Cons Stage 1','00_Na.png','/assets/portal/exercises/images/generated_images_cv/00_NAH.png',20,1,'2026-03-09 14:52:09','2026-04-24 22:38:33'),
(60,NULL,NULL,NULL,NULL,'NEE','CV by Cons Stage 1','00_Nee.png','/assets/portal/exercises/images/generated_images_cv/00_NEE.jpg',24,1,'2026-03-09 14:52:09','2026-04-24 22:40:53'),
(61,NULL,NULL,NULL,NULL,'NEH','CV by Vowel Stage 1','00_Neh.png','/assets/portal/exercises/images/generated_images_cv/00_Neh.png',16,1,'2026-03-09 14:52:09','2026-04-24 22:47:44'),
(62,NULL,NULL,NULL,NULL,'NIH','CV by Vowel Stage 1','00_Nih.png','/assets/portal/exercises/images/generated_images_cv/00_NIH.jpg',15,1,'2026-03-09 14:52:09','2026-04-24 23:06:12'),
(63,NULL,NULL,NULL,NULL,'NOO','CV by Cons Stage 1','00_Noo.png','/assets/portal/exercises/images/generated_images_cv/00_NOO.jpg',23,1,'2026-03-09 14:52:09','2026-04-24 22:58:45'),
(64,NULL,NULL,NULL,NULL,'N-O','CV by Cons Stage 1','00_No.png','/assets/portal/exercises/images/generated_images_cv/00_N-O.jpg',21,0,'2026-03-09 14:52:09','2026-04-24 22:56:42'),
(65,NULL,NULL,NULL,NULL,'NUH','CV by Vowel Stage 1','00_Nuh.png','/assets/portal/exercises/images/generated_images_cv/00_N-UH.jpg',17,1,'2026-03-09 14:52:09','2026-04-24 23:00:29'),
(66,NULL,NULL,NULL,NULL,'PAH','CV by Cons Stage 1','00_Pa.png','/assets/portal/exercises/images/generated_images_cv/00_Pa.png',4,1,'2026-03-09 14:52:09','2026-04-24 02:42:01'),
(67,NULL,NULL,NULL,NULL,'PEE','CV by Cons Stage 1','00_Pea.png','/assets/portal/exercises/images/generated_images_cv/00_PEE.jpg',7,1,'2026-03-09 14:52:09','2026-04-24 22:44:45'),
(68,NULL,NULL,NULL,NULL,'PEH','CV by Vowel Stage 1','00_Peh.png','/assets/portal/exercises/images/generated_images_cv/00_PEH.jpg',4,1,'2026-03-09 14:52:09','2026-04-24 22:50:39'),
(69,NULL,NULL,NULL,NULL,'PIH','CV by Vowel Stage 1','00_Pih.png','/assets/portal/exercises/images/generated_images_cv/00_Pih.png',3,1,'2026-03-09 14:52:09','2026-04-24 02:41:48'),
(70,NULL,NULL,NULL,NULL,'POH','CV by Cons Stage 1','00_Poh.png','/assets/portal/exercises/images/generated_images_cv/00_POH.jpg',5,1,'2026-03-09 14:52:09','2026-04-24 22:52:52'),
(71,NULL,NULL,NULL,NULL,'POO','CV by Cons Stage 1','00_Poo.png','/assets/portal/exercises/images/generated_images_cv/00_Poo.png',6,1,'2026-03-09 14:52:09','2026-04-24 02:41:23'),
(72,NULL,NULL,NULL,NULL,'PUH','CV by Vowel Stage 1','00_Puh.png','/assets/portal/exercises/images/generated_images_cv/00_Puh.png',5,1,'2026-03-09 14:52:09','2026-04-24 02:41:33'),
(73,NULL,NULL,NULL,NULL,'RAH','CV by Cons Stage 3','00_Ra.png','/assets/portal/exercises/images/generated_images_cv/00_RAH.jpg',80,1,'2026-03-09 14:52:09','2026-04-24 02:39:50'),
(74,NULL,NULL,NULL,NULL,'REE','CV by Cons Stage 3','00_Ree.png','/assets/portal/exercises/images/generated_images_cv/00_REE.jpg',83,1,'2026-03-09 14:52:09','2026-04-24 22:54:14'),
(75,NULL,NULL,NULL,NULL,'ROH','CV by Cons Stage 3','00_Roh.png','/assets/portal/exercises/images/generated_images_cv/00_Roh.png',81,1,'2026-03-09 14:52:09','2026-04-24 02:40:04'),
(76,NULL,NULL,NULL,NULL,'ROO','CV by Cons Stage 3','00_Roo.png','/assets/portal/exercises/images/generated_images_cv/00_ROO.jpg',82,1,'2026-03-09 14:52:10','2026-04-24 22:47:10'),
(77,NULL,NULL,NULL,NULL,'SAH','CV by Cons Stage 2','00_Sa.png','/assets/portal/exercises/images/generated_images_cv/00_SAH.jpg',56,1,'2026-03-09 14:52:10','2026-04-24 23:08:08'),
(78,NULL,NULL,NULL,NULL,'SEE','CV by Cons Stage 2','00_See.png','/assets/portal/exercises/images/generated_images_cv/00_SEE.jpg',59,1,'2026-03-09 14:52:10','2026-04-24 23:12:26'),
(79,NULL,NULL,NULL,NULL,'SHAH','CV by Cons Stage 3','00_Sha.png','/assets/portal/exercises/images/generated_images_cv/00_SHAH.jpg',64,1,'2026-03-09 14:52:10','2026-04-24 23:15:09'),
(80,NULL,NULL,NULL,NULL,'SHEE','CV by Cons Stage 3','00_Shee.png','/assets/portal/exercises/images/generated_images_cv/00_SHEE.jpg',67,1,'2026-03-09 14:52:10','2026-04-24 23:54:19'),
(81,NULL,NULL,NULL,NULL,'SH-OH','CV by Cons Stage 3','00_Shoh.png','/assets/portal/exercises/images/generated_images_cv/00_Shoh.png',65,1,'2026-03-09 14:52:10','2026-03-25 00:31:22'),
(82,NULL,NULL,NULL,NULL,'SHOO','CV by Cons Stage 3','00_Shoo.png','/assets/portal/exercises/images/generated_images_cv/00_SHOO.jpg',66,1,'2026-03-09 14:52:10','2026-04-24 23:56:41'),
(83,NULL,NULL,NULL,NULL,'SOH','CV by Cons Stage 2','00_Soh.png','/assets/portal/exercises/images/generated_images_cv/00_Soh.png',57,1,'2026-03-09 14:52:10','2026-04-24 02:45:15'),
(84,NULL,NULL,NULL,NULL,'SOO','CV by Cons Stage 2','00_Soo.png','/assets/portal/exercises/images/generated_images_cv/00_SOO.jpg',58,1,'2026-03-09 14:52:10','2026-04-24 23:52:19'),
(85,NULL,NULL,NULL,NULL,'TAH','CV by Cons Stage 1','00_Ta.png','/assets/portal/exercises/images/generated_images_cv/00_TAH.jpg',12,1,'2026-03-09 14:52:10','2026-04-24 23:49:02'),
(86,NULL,NULL,NULL,NULL,'TEE','CV by Cons Stage 1','00_Tee.png','/assets/portal/exercises/images/generated_images_cv/00_T-EE.jpg',15,1,'2026-03-09 14:52:10','2026-04-24 23:58:23'),
(87,NULL,NULL,NULL,NULL,'TEH','CV by Vowel Stage 1','00_Teh.png','/assets/portal/exercises/images/generated_images_cv/00_Teh.png',10,1,'2026-03-09 14:52:10','2026-04-24 23:59:13'),
(88,NULL,NULL,NULL,NULL,'THAH','CV by Cons Stage 3','00_Tha.png','/assets/portal/exercises/images/generated_images_cv/00_THAH.jpg',72,1,'2026-03-09 14:52:10','2026-04-25 00:02:46'),
(89,NULL,NULL,NULL,NULL,'THEE','CV by Cons Stage 3','00_Thee.png','/assets/portal/exercises/images/generated_images_cv/00_Thee.png',75,1,'2026-03-09 14:52:10','2026-04-25 00:03:09'),
(90,NULL,NULL,NULL,NULL,'TH-OH','CV by Cons Stage 3','00_Thoh.png','/assets/portal/exercises/images/generated_images_cv/00_Thoh.png',73,1,'2026-03-09 14:52:10','2026-03-25 00:31:22'),
(91,NULL,NULL,NULL,NULL,'TH-OO','CV by Cons Stage 3','00_Thoo.png','/assets/portal/exercises/images/generated_images_cv/00_Thoo.png',74,1,'2026-03-09 14:52:10','2026-03-25 00:31:22'),
(92,NULL,NULL,NULL,NULL,'TIH','CV by Vowel Stage 1','00_Tih.png','/assets/portal/exercises/images/generated_images_cv/00_Tih.png',9,1,'2026-03-09 14:52:10','2026-04-24 23:58:46'),
(93,NULL,NULL,NULL,NULL,'TOE','CV by Cons Stage 1','00_Toe.png','/assets/portal/exercises/images/generated_images_cv/00_Toe.png',13,1,'2026-03-09 14:52:10','2026-04-24 02:45:53'),
(94,NULL,NULL,NULL,NULL,'TUH','CV by Vowel Stage 1','00_Tuh.png','/assets/portal/exercises/images/generated_images_cv/00_Tuh.png',11,1,'2026-03-09 14:52:10','2026-04-24 02:44:51'),
(95,NULL,NULL,NULL,NULL,'TOO','CV by Cons Stage 1','00_Two.png','/assets/portal/exercises/images/generated_images_cv/00_Two.png',14,1,'2026-03-09 14:52:10','2026-04-24 02:46:05'),
(96,NULL,NULL,NULL,NULL,'V-A','CV by Cons Stage 2','00_Va.png','/assets/portal/exercises/images/generated_images_cv/00_Va.png',48,1,'2026-03-09 14:52:10','2026-03-25 00:31:22'),
(97,NULL,NULL,NULL,NULL,'V-EE','CV by Cons Stage 2','00_Vee.png','/assets/portal/exercises/images/generated_images_cv/00_Vee.png',51,1,'2026-03-09 14:52:10','2026-03-25 00:31:22'),
(98,NULL,NULL,NULL,NULL,'VOH','CV by Cons Stage 2','00_Voh.png','/assets/portal/exercises/images/generated_images_cv/00_Voh.png',49,1,'2026-03-09 14:52:10','2026-04-24 02:44:26'),
(99,NULL,NULL,NULL,NULL,'V-OO','CV by Cons Stage 2','00_Voo.png','/assets/portal/exercises/images/generated_images_cv/00_Voo.png',50,1,'2026-03-09 14:52:10','2026-03-25 00:31:22'),
(100,NULL,NULL,NULL,NULL,'WAH','CV by Cons Stage 1','00_Wa.png','/assets/portal/exercises/images/generated_images_cv/00_Wa.png',25,1,'2026-03-09 14:52:10','2026-04-24 02:47:31'),
(101,NULL,NULL,NULL,NULL,'W-EH','CV by Vowel Stage 1','00_Weh.png','/assets/portal/exercises/images/generated_images_cv/00_Weh.png',19,1,'2026-03-09 14:52:10','2026-03-25 00:33:45'),
(102,NULL,NULL,NULL,NULL,'WEE','CV by Cons Stage 1','00_We.png','/assets/portal/exercises/images/generated_images_cv/00_We.png',28,1,'2026-03-09 14:52:10','2026-04-24 02:47:44'),
(103,NULL,NULL,NULL,NULL,'W-IH','CV by Vowel Stage 1','00_Wih.png','/assets/portal/exercises/images/generated_images_cv/00_Wih.png',18,1,'2026-03-09 14:52:10','2026-03-25 00:33:45'),
(104,NULL,NULL,NULL,NULL,'W-OH','CV by Cons Stage 1','00_Woh.png','/assets/portal/exercises/images/generated_images_cv/00_Woh.png',26,1,'2026-03-09 14:52:10','2026-04-22 14:41:13'),
(105,NULL,NULL,NULL,NULL,'WOO','CV by Cons Stage 1','00_Woo.png','/assets/portal/exercises/images/generated_images_cv/00_Woo.png',27,1,'2026-03-09 14:52:10','2026-04-24 02:48:04'),
(106,NULL,NULL,NULL,NULL,'W-UH','CV by Vowel Stage 1','00_Wuh.png','/assets/portal/exercises/images/generated_images_cv/00_Wuh.png',20,1,'2026-03-09 14:52:10','2026-03-25 00:33:45'),
(107,NULL,NULL,NULL,NULL,'YAH','CV by Cons Stage 1','00_Ya.png','/assets/portal/exercises/images/generated_images_cv/00_Ya.png',29,1,'2026-03-09 14:52:10','2026-04-24 02:48:24'),
(108,NULL,NULL,NULL,NULL,'Y-EE','CV by Cons Stage 1','00_Yee.png','/assets/portal/exercises/images/generated_images_cv/00_Yee.png',32,1,'2026-03-09 14:52:11','2026-04-22 14:41:13'),
(109,NULL,NULL,NULL,NULL,'Y-EH','CV by Vowel Stage 1','00_Yeh.png','/assets/portal/exercises/images/generated_images_cv/00_Yeh.png',22,1,'2026-03-09 14:52:11','2026-03-25 00:33:45'),
(110,NULL,NULL,NULL,NULL,'Y-IH','CV by Vowel Stage 1','00_Yih.png','/assets/portal/exercises/images/generated_images_cv/00_Yih.png',21,1,'2026-03-09 14:52:11','2026-03-25 00:33:45'),
(111,NULL,NULL,NULL,NULL,'YOH','CV by Cons Stage 1','00_Yoh.png','/assets/portal/exercises/images/generated_images_cv/00_Yoh.png',30,1,'2026-03-09 14:52:11','2026-04-24 02:48:46'),
(112,NULL,NULL,NULL,NULL,'Y-OO','CV by Cons Stage 1','00_Yoo.png','/assets/portal/exercises/images/generated_images_cv/00_Yoo.png',31,1,'2026-03-09 14:52:11','2026-04-22 14:41:13'),
(113,NULL,NULL,NULL,NULL,'YUH','CV by Vowel Stage 1','00_Yuh.png','/assets/portal/exercises/images/generated_images_cv/00_Yuh.png',23,1,'2026-03-09 14:52:11','2026-04-24 02:47:17'),
(114,NULL,NULL,NULL,NULL,'Z-A','CV by Cons Stage 2','00_Za.png','/assets/portal/exercises/images/generated_images_cv/00_Za.png',60,1,'2026-03-09 14:52:11','2026-03-25 00:31:22'),
(115,NULL,NULL,NULL,NULL,'Z-EE','CV by Cons Stage 2','00_Zee.png','/assets/portal/exercises/images/generated_images_cv/00_Zee.png',63,1,'2026-03-09 14:52:11','2026-03-25 00:31:22'),
(116,NULL,NULL,NULL,NULL,'Z-OH','CV by Cons Stage 2','00_Zoh.png','/assets/portal/exercises/images/generated_images_cv/00_Zoh.png',61,1,'2026-03-09 14:52:11','2026-03-25 00:31:22'),
(117,NULL,NULL,NULL,NULL,'Z-OO','CV by Cons Stage 2','00_Zoo.png','/assets/portal/exercises/images/generated_images_cv/00_Zoo.png',62,1,'2026-03-09 14:52:11','2026-03-25 00:31:22'),
(118,NULL,NULL,NULL,NULL,'NOH','CV by Cons Stage 1',NULL,'/assets/portal/exercises/images/generated_images_cv/00_N-OH.jpg',22,1,'2026-04-22 13:57:09','2026-04-24 02:42:22'),
(119,NULL,NULL,NULL,NULL,'Z-ZZ',NULL,NULL,'/assets/portal/exercises/images/generated_images_cv/00_Z-ZZ.jpg',0,0,'2026-04-22 23:26:32','2026-04-22 23:34:43');
/*!40000 ALTER TABLE `exercise_cv_blends` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_success_media`
--

DROP TABLE IF EXISTS `exercise_success_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_success_media` (
  `media_id` int(11) NOT NULL AUTO_INCREMENT,
  `media_type` enum('video','image') NOT NULL DEFAULT 'video',
  `owner_user_uuid` char(36) NOT NULL,
  `media_name` varchar(255) NOT NULL,
  `media_filename` varchar(255) NOT NULL,
  `media_path` varchar(500) NOT NULL,
  `file_size_bytes` int(11) NOT NULL,
  `duration_seconds` decimal(4,2) DEFAULT 5.00,
  `allow_sound` tinyint(1) DEFAULT NULL,
  `autoplay_loop` tinyint(1) DEFAULT NULL,
  `display_order` int(11) DEFAULT 999,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`media_id`),
  KEY `idx_owner` (`owner_user_uuid`),
  KEY `idx_active` (`is_active`),
  KEY `idx_display_order` (`display_order`),
  KEY `idx_media_type` (`media_type`),
  CONSTRAINT `exercise_success_media_ibfk_1` FOREIGN KEY (`owner_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores success celebration media (videos and images) for exercise completion';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_success_media`
--

LOCK TABLES `exercise_success_media` WRITE;
/*!40000 ALTER TABLE `exercise_success_media` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exercise_success_media` VALUES
(1,'video','fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','Good Job Happy Face Sound','success_video_6990b704acb9a7.91034590.mp4','/assets/portal/exercises/videos/success_video_6990b704acb9a7.91034590.mp4',4390987,5.00,1,1,999,1,'2026-02-14 17:55:16','2026-02-14 17:55:16'),
(2,'video','fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','You Did It','success_video_6990c2ec3c38e7.85529070.mp4','/assets/portal/exercises/videos/success_video_6990c2ec3c38e7.85529070.mp4',9511650,5.00,1,1,999,1,'2026-02-14 18:46:04','2026-02-14 18:46:04'),
(3,'video','fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','Way To Go','success_video_6990c30a1eef13.95412075.mp4','/assets/portal/exercises/videos/success_video_6990c30a1eef13.95412075.mp4',6529135,5.00,1,1,999,0,'2026-02-14 18:46:34','2026-02-14 18:58:59'),
(4,'video','fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','Way to Go','success_video_6990c75d9a6317.71963897.mp4','/assets/portal/exercises/videos/success_video_6990c75d9a6317.71963897.mp4',6529135,5.00,1,1,999,0,'2026-02-14 19:05:01','2026-02-14 19:05:54'),
(5,'video','fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','Way to go','success_video_6990c7e67cf3e2.89545062.mp4','/assets/portal/exercises/videos/success_video_6990c7e67cf3e2.89545062.mp4',6529135,5.00,1,1,999,0,'2026-02-14 19:07:18','2026-02-14 19:07:40'),
(6,'video','fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','WayToGo','success_video_6990da247f9034.38675423.mp4','/assets/portal/exercises/media/success_video_6990da247f9034.38675423.mp4',6529135,5.00,1,1,999,0,'2026-02-14 20:25:08','2026-02-14 20:32:46'),
(7,'image','fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','Great Work','success_image_6990db16caa099.06895255.png','/assets/portal/exercises/media/success_image_6990db16caa099.06895255.png',1719302,5.00,NULL,NULL,999,0,'2026-02-14 20:29:10','2026-02-14 20:32:51'),
(8,'video','fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','Way to go','success_video_6991e8c83af278.78878575.mp4','/assets/portal/exercises/media/success_video_6991e8c83af278.78878575.mp4',6529135,5.00,1,1,999,1,'2026-02-15 15:39:52','2026-02-15 15:39:52'),
(9,'image','fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','Great Work','success_image_6991e922e38101.72881337.png','/assets/portal/exercises/media/success_image_6991e922e38101.72881337.png',1719302,5.00,NULL,NULL,999,1,'2026-02-15 15:41:22','2026-02-15 15:41:22'),
(10,'video','SUPER-USER-UUID-0000-000000000001','You Did It','success_video_69ebb01b4cf210.28639471.mp4','/assets/portal/exercises/media/success_video_69ebb01b4cf210.28639471.mp4',9511650,5.00,1,0,999,1,'2026-04-24 18:02:03','2026-04-24 18:02:03');
/*!40000 ALTER TABLE `exercise_success_media` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_vowels`
--

DROP TABLE IF EXISTS `exercise_vowels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_vowels` (
  `vowel_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_uuid` varchar(36) DEFAULT NULL,
  `vowel_code` varchar(10) NOT NULL,
  `vowel_type` enum('short','long','special') NOT NULL,
  `vowel_label` varchar(50) DEFAULT NULL,
  `vowel_folder` varchar(24) DEFAULT NULL,
  `image_filename` varchar(255) DEFAULT NULL,
  `image_path` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`vowel_id`),
  KEY `idx_owner` (`owner_user_uuid`),
  KEY `idx_active` (`is_active`),
  KEY `idx_order` (`display_order`),
  CONSTRAINT `exercise_vowels_ibfk_1` FOREIGN KEY (`owner_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_vowels`
--

LOCK TABLES `exercise_vowels` WRITE;
/*!40000 ALTER TABLE `exercise_vowels` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exercise_vowels` VALUES
(1,NULL,'AH','short','AH','Stage 1','01_AH.png','/assets/portal/exercises/images/generated_images_vowels/01_AH.png',1,1,'2026-02-05 15:27:56','2026-03-09 17:09:16'),
(2,NULL,'EE','long','EE','Stage 1','04_EE.png','/assets/portal/exercises/images/generated_images_vowels/04_EE.png',4,1,'2026-02-05 15:27:56','2026-03-09 17:09:16'),
(3,NULL,'OO','long','OO','Stage 1','03_OO.png','/assets/portal/exercises/images/generated_images_vowels/03_OO.png',3,1,'2026-02-05 15:27:56','2026-03-09 17:09:16'),
(4,NULL,'OH','long','OH','Stage 1','02_OH.png','/assets/portal/exercises/images/generated_images_vowels/02_OH.png',2,1,'2026-02-05 15:27:56','2026-03-09 17:09:16'),
(6,NULL,'IH','short','IH','Stage 2','05_IH.png','/assets/portal/exercises/images/generated_images_vowels/05_IH.png',5,1,'2026-03-09 14:26:58','2026-03-09 17:10:03'),
(7,NULL,'EH','short','EH','Stage 2','06_EH.png','/assets/portal/exercises/images/generated_images_vowels/06_EH.png',6,1,'2026-03-09 14:26:58','2026-03-09 17:10:03'),
(8,NULL,'UH','short','UH','Stage 2','07_UH.png','/assets/portal/exercises/images/generated_images_vowels/07_UH.png',7,1,'2026-03-09 14:26:58','2026-03-09 17:10:03'),
(9,NULL,'OW','special','OW','Stage 3','08_OW.png','/assets/portal/exercises/images/generated_images_vowels/08_OW.png',8,1,'2026-03-09 14:26:58','2026-03-09 17:11:31'),
(10,NULL,'OI','special','OI','Stage 3','09_OI.png','/assets/portal/exercises/images/generated_images_vowels/09_OI.png',9,1,'2026-03-09 14:26:58','2026-03-09 17:11:31'),
(11,NULL,'AE','short','AE','Stage 3','10_AE.png','/assets/portal/exercises/images/generated_images_vowels/10_AE.png',10,1,'2026-03-09 14:26:58','2026-03-09 17:11:31'),
(12,NULL,'EYE','special','EYE','Stage 3','11_EYE.png','/assets/portal/exercises/images/generated_images_vowels/11_EYE.png',11,1,'2026-03-09 14:26:58','2026-03-09 17:11:31');
/*!40000 ALTER TABLE `exercise_vowels` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `exercise_words`
--

DROP TABLE IF EXISTS `exercise_words`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_words` (
  `word_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_uuid` varchar(36) DEFAULT NULL,
  `word_text` varchar(100) NOT NULL,
  `word_category` varchar(50) DEFAULT NULL,
  `words_folder` varchar(24) DEFAULT NULL,
  `syllable_count` int(11) DEFAULT 1,
  `syllable_breakdown` varchar(200) DEFAULT NULL,
  `image_filename` varchar(255) DEFAULT NULL,
  `image_path` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`word_id`),
  KEY `idx_owner` (`owner_user_uuid`),
  KEY `idx_category` (`word_category`),
  KEY `idx_active` (`is_active`),
  KEY `idx_order` (`display_order`),
  CONSTRAINT `exercise_words_ibfk_1` FOREIGN KEY (`owner_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_words`
--

LOCK TABLES `exercise_words` WRITE;
/*!40000 ALTER TABLE `exercise_words` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `exercise_words` VALUES
(4,NULL,'bubble',NULL,'CV1CV2',2,'bu-bble','00_bubble.png','/assets/portal/exercises/images/generated_images_words/00_bubble.png',3,1,'2026-02-05 15:27:56','2026-03-09 18:23:57'),
(5,NULL,'bunny',NULL,'C1V1C2V2 Stage 1',2,'bu-nny','00_bunny.png','/assets/portal/exercises/images/generated_images_words/00_bunny.png',23,1,'2026-02-05 15:27:56','2026-04-22 14:32:09'),
(11,NULL,'cookie',NULL,'CV1CV2',2,'coo-kie','00_cookie.png','/assets/portal/exercises/images/generated_images_words/00_cookie.png',10,1,'2026-02-05 15:27:56','2026-04-25 00:10:02'),
(15,NULL,'daddy',NULL,'CV1CV2',2,'da-ddy','00_daddy.png','/assets/portal/exercises/images/generated_images_words/00_daddy.png',14,1,'2026-02-05 15:27:56','2026-03-09 18:23:57'),
(22,NULL,'happy',NULL,'C1V1C2V2 Stage 1',2,'ha-ppy','00_happy.png','/assets/portal/exercises/images/generated_images_words/00_happy.png',24,1,'2026-02-05 15:27:56','2026-04-22 14:32:09'),
(24,NULL,'jelly',NULL,'C1V1C2V2 Stage 2',2,'je-lly','00_jelly.png','/assets/portal/exercises/images/generated_images_words/00_jelly.png',23,1,'2026-02-05 15:27:56','2026-03-09 18:26:23'),
(27,NULL,'kitty',NULL,'C1V1C2V2 Stage 1',2,'ki-tty','00_kitty.png','/assets/portal/exercises/images/generated_images_words/00_kitty.png',25,1,'2026-02-05 15:27:56','2026-04-22 14:32:09'),
(33,NULL,'mommy',NULL,'CV1CV2',2,'mo-mmy','00_mommy.png','/assets/portal/exercises/images/generated_images_words/00_mommy.png',32,1,'2026-02-05 15:27:56','2026-03-09 18:23:57'),
(45,NULL,'puppy',NULL,'CV1CV2',2,'pu-ppy','00_puppy.png','/assets/portal/exercises/images/generated_images_words/00_puppy.png',44,1,'2026-02-05 15:27:56','2026-03-09 18:23:57'),
(55,NULL,'tummy',NULL,'C1V1C2V2 Stage 1',2,'tu-mmy','00_tummy.png','/assets/portal/exercises/images/generated_images_words/00_tummy.png',26,1,'2026-02-05 15:27:56','2026-04-22 14:32:09'),
(56,NULL,'turtle',NULL,'CV1CV2',2,'tur-tle','00_turtle.png','/assets/portal/exercises/images/generated_images_words/00_turtle.png',55,1,'2026-02-05 15:27:56','2026-04-25 00:13:17'),
(65,NULL,'baby',NULL,'CV1CV2',1,'ba-by','00_baby.png','/assets/portal/exercises/images/generated_images_words/00_baby.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:52:43'),
(66,NULL,'bat',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_bat.png','/assets/portal/exercises/images/generated_images_words/00_bat.png',0,1,'2026-03-06 12:41:56','2026-04-24 17:44:07'),
(67,NULL,'bed',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_bed.png','/assets/portal/exercises/images/generated_images_words/00_bed.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(68,NULL,'belly',NULL,'C1V1C2V2 Stage 1',1,'be+ lly','00_belly.png','/assets/portal/exercises/images/generated_images_cv/00_be__lly.png',0,1,'2026-03-06 12:41:56','2026-04-24 14:48:20'),
(69,NULL,'bet',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_bet.png','/assets/portal/exercises/images/generated_images_words/00_bet.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(70,NULL,'bib',NULL,'CVC-Bilabial',1,NULL,'00_bib.png','/assets/portal/exercises/images/generated_images_words/00_bib.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:04'),
(71,NULL,'bin',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_bin.png','/assets/portal/exercises/images/generated_images_words/00_bin.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(72,NULL,'bony',NULL,'C1V1C2V2 Stage 1',1,'bo-ny','00_bony.png','/assets/portal/exercises/images/generated_images_words/00_bony.png',1,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(73,NULL,'buggy',NULL,'C1V1C2V2 Stage 1',1,'bug-gy','00_buggy.png','/assets/portal/exercises/images/generated_images_words/00_buggy.png',2,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(74,NULL,'chewy',NULL,'C1V1C2V2 Stage 2',1,'chew-y','00_chewy.png','/assets/portal/exercises/images/generated_images_words/00_chewy.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:52:43'),
(75,NULL,'chili',NULL,'C1V1C2V2 Stage 2',1,'chil-i','00_chili.png','/assets/portal/exercises/images/generated_images_words/00_chili.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:52:43'),
(76,NULL,'chubby',NULL,'C1V1C2V2 Stage 2',1,'Chu-bby','00_chubby.png','/assets/portal/exercises/images/generated_images_words/00_chubby.png',0,1,'2026-03-06 12:41:56','2026-04-25 00:10:29'),
(77,NULL,'dad',NULL,'CVC-Alveolar',1,NULL,'00_dad.png','/assets/portal/exercises/images/generated_images_words/00_dad.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:59'),
(78,NULL,'dam',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_dam.png','/assets/portal/exercises/images/generated_images_words/00_dam.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(79,NULL,'dead',NULL,'CVC-Alveolar',1,NULL,'00_dead.png','/assets/portal/exercises/images/generated_images_words/00_dead.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:59'),
(80,NULL,'den',NULL,'CVC-Alveolar',1,NULL,'00_den.png','/assets/portal/exercises/images/generated_images_words/00_den.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:59'),
(81,NULL,'dino',NULL,'C1V1C2V2 Stage 1',1,'di-no','00_dino.png','/assets/portal/exercises/images/generated_images_words/00_dino.png',3,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(82,NULL,'dip',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_dip.png','/assets/portal/exercises/images/generated_images_words/00_dip.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(83,NULL,'dirty',NULL,'C1V1C2V2 Stage 1',1,'dir-ty','00_dirty.png','/assets/portal/exercises/images/generated_images_words/00_dirty.png',4,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(84,NULL,'dolly',NULL,'C1V1C2V2 Stage 1',1,'dol-ly','00_dolly.png','/assets/portal/exercises/images/generated_images_words/00_dolly.png',5,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(85,NULL,'gummy',NULL,'C1V1C2V2 Stage 1',1,'gum-my','00_gummy.png','/assets/portal/exercises/images/generated_images_words/00_gummy.png',6,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(86,NULL,'hippo',NULL,'C1V1C2V2 Stage 1',1,'hip-po','00_hippo.png','/assets/portal/exercises/images/generated_images_words/00_hippo.png',7,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(87,NULL,'honey',NULL,'C1V1C2V2 Stage 1',1,'hon-ey','00_honey.png','/assets/portal/exercises/images/generated_images_words/00_honey.png',8,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(88,NULL,'jolly',NULL,'C1V1C2V2 Stage 2',1,'jo+ lly','00_jolly.png','/assets/portal/exercises/images/generated_images_words/00_jolly.png',0,1,'2026-03-06 12:41:56','2026-04-25 00:09:26'),
(89,NULL,'lolly',NULL,'CV1CV2',1,'lo-lly','00_lolly.png','/assets/portal/exercises/images/generated_images_words/00_lolly.png',0,1,'2026-03-06 12:41:56','2026-04-25 00:09:10'),
(90,NULL,'mad',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_mad.png','/assets/portal/exercises/images/generated_images_words/00_mad.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(91,NULL,'man',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_man.png','/assets/portal/exercises/images/generated_images_words/00_man.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(92,NULL,'map',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_map.png','/assets/portal/exercises/images/generated_images_words/00_map.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(93,NULL,'mat',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_mat.png','/assets/portal/exercises/images/generated_images_words/00_mat.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(94,NULL,'mimi',NULL,'CV1CV2',1,'mi-mi','00_mimi.png','/assets/portal/exercises/images/generated_images_words/00_mimi.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:52:43'),
(95,NULL,'mom',NULL,'CVC-Bilabial',1,NULL,'00_mom.png','/assets/portal/exercises/images/generated_images_words/00_mom.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:04'),
(96,NULL,'money',NULL,'C1V1C2V2 Stage 1',1,'mo-ney','00_money.png','/assets/portal/exercises/images/generated_images_words/00_money.png',9,1,'2026-03-06 12:41:56','2026-04-24 13:21:01'),
(97,NULL,'mud',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_mud.png','/assets/portal/exercises/images/generated_images_words/00_mud.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(98,NULL,'muddy',NULL,'C1V1C2V2 Stage 1',1,'mud-dy','00_muddy.png','/assets/portal/exercises/images/generated_images_words/00_muddy.png',10,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(99,NULL,'mum',NULL,'CVC-Bilabial',1,NULL,'00_mum.png','/assets/portal/exercises/images/generated_images_words/00_mum.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:04'),
(100,NULL,'nana',NULL,'CV1CV2',1,'na-na','00_nana.png','/assets/portal/exercises/images/generated_images_words/00_nana.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:52:43'),
(101,NULL,'nap',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_nap.png','/assets/portal/exercises/images/generated_images_words/00_nap.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(102,NULL,'navy',NULL,'C1V1C2V2 Stage 1',1,'na-vy','00_navy.png','/assets/portal/exercises/images/generated_images_words/00_navy.png',11,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(103,NULL,'net',NULL,'CVC-Alveolar',1,NULL,'00_net.png','/assets/portal/exercises/images/generated_images_words/00_net.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:59'),
(104,NULL,'nip',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_nip.png','/assets/portal/exercises/images/generated_images_words/00_nip.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(105,NULL,'nun',NULL,'CVC-Alveolar',1,NULL,'00_nun.png','/assets/portal/exercises/images/generated_images_words/00_nun.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:59'),
(106,NULL,'nut',NULL,'CVC-Alveolar',1,NULL,'00_nut.png','/assets/portal/exercises/images/generated_images_words/00_nut.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:59'),
(107,NULL,'pad',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_pad.png','/assets/portal/exercises/images/generated_images_words/00_pad.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(108,NULL,'pan',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_pan.png','/assets/portal/exercises/images/generated_images_words/00_pan.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(109,NULL,'pat',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_pat.png','/assets/portal/exercises/images/generated_images_words/00_pat.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(110,NULL,'pen',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_pen.png','/assets/portal/exercises/images/generated_images_words/00_pen.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(111,NULL,'penny',NULL,'C1V1C2V2 Stage 1',1,'pe-nny','00_penny.png','/assets/portal/exercises/images/generated_images_words/00_penny.png',12,1,'2026-03-06 12:41:56','2026-04-25 00:11:23'),
(112,NULL,'people',NULL,'CV1CV2',1,'peo-ple','00_people.png','/assets/portal/exercises/images/generated_images_words/00_people.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:52:43'),
(113,NULL,'pepper',NULL,'CV1CV2',1,'pe-pper','00_pepper.png','/assets/portal/exercises/images/generated_images_words/00_pepper.png',0,1,'2026-03-06 12:41:56','2026-04-25 00:12:14'),
(114,NULL,'pet',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_pet.png','/assets/portal/exercises/images/generated_images_words/00_pet.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(115,NULL,'pin',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_pin.png','/assets/portal/exercises/images/generated_images_words/00_pin.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(116,NULL,'pit',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_pit.png','/assets/portal/exercises/images/generated_images_words/00_pit.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(117,NULL,'pony',NULL,'C1V1C2V2 Stage 1',1,'po-ny','00_pony.png','/assets/portal/exercises/images/generated_images_words/00_pony.png',13,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(118,NULL,'pop',NULL,'CVC-Bilabial',1,NULL,'00_pop.png','/assets/portal/exercises/images/generated_images_words/00_pop.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:04'),
(119,NULL,'poppy',NULL,'CV1CV2',1,'po-ppy','00_poppy.png','/assets/portal/exercises/images/generated_images_words/00_poppy.png',0,1,'2026-03-06 12:41:56','2026-04-25 00:12:41'),
(120,NULL,'pot',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_pot.png','/assets/portal/exercises/images/generated_images_words/00_pot.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(121,NULL,'potty',NULL,'C1V1C2V2 Stage 1',1,'po-tty','00_potty.png','/assets/portal/exercises/images/generated_images_words/00_potty.png',14,1,'2026-03-06 12:41:56','2026-04-25 00:11:56'),
(122,NULL,'pup',NULL,'CVC-Bilabial',1,NULL,'00_pup.png','/assets/portal/exercises/images/generated_images_words/00_pup.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:04'),
(123,NULL,'sad',NULL,'CVC-Alveolar',1,NULL,'00_sad.png','/assets/portal/exercises/images/generated_images_words/00_sad.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:59'),
(124,NULL,'sap',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_sap.png','/assets/portal/exercises/images/generated_images_words/00_sap.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(125,NULL,'sassy',NULL,'CV1CV2',1,'sa-ssy','00_sassy.png','/assets/portal/exercises/images/generated_images_words/00_sassy.png',0,1,'2026-03-06 12:41:56','2026-04-25 00:11:40'),
(126,NULL,'shady',NULL,'C1V1C2V2 Stage 2',1,'sha-dy','00_shady.png','/assets/portal/exercises/images/generated_images_words/00_shady.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:52:43'),
(127,NULL,'shaggy',NULL,'C1V1C2V2 Stage 2',1,'sha-ggy','00_shaggy.png','/assets/portal/exercises/images/generated_images_words/00_shaggy.png',0,1,'2026-03-06 12:41:56','2026-04-25 00:11:09'),
(128,NULL,'shiny',NULL,'C1V1C2V2 Stage 2',1,'shi-ny','00_shiny.png','/assets/portal/exercises/images/generated_images_words/00_shiny.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:52:43'),
(129,NULL,'silly',NULL,'C1V1C2V2 Stage 2',1,'sil-ly','00_silly.png','/assets/portal/exercises/images/generated_images_words/00_silly.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:52:43'),
(130,NULL,'sip',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_sip.png','/assets/portal/exercises/images/generated_images_words/00_sip.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(131,NULL,'sis',NULL,'CVC-Alveolar',1,NULL,'00_sis.png','/assets/portal/exercises/images/generated_images_words/00_sis.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:59'),
(132,NULL,'sit',NULL,'CVC-Alveolar',1,NULL,'00_sit.png','/assets/portal/exercises/images/generated_images_words/00_sit.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:59'),
(133,NULL,'sob',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_sob.png','/assets/portal/exercises/images/generated_images_words/00_sob.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(134,NULL,'soda',NULL,'C1V1C2V2 Stage 2',1,'so-da','00_soda.png','/assets/portal/exercises/images/generated_images_words/00_soda.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:52:43'),
(135,NULL,'sugar',NULL,'C1V1C2V2 Stage 2',1,'su-gar','00_sugar.png','/assets/portal/exercises/images/generated_images_words/00_sugar.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:52:43'),
(136,NULL,'sum',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_sum.png','/assets/portal/exercises/images/generated_images_words/00_sum.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(137,NULL,'tan',NULL,'CVC-Alveolar',1,NULL,'00_tan.png','/assets/portal/exercises/images/generated_images_words/00_tan.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:59'),
(138,NULL,'tap',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_tap.png','/assets/portal/exercises/images/generated_images_words/00_tap.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(139,NULL,'tater',NULL,'CV1CV2',1,'ta-ter','00_tater.png','/assets/portal/exercises/images/generated_images_words/00_tater.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:52:43'),
(140,NULL,'teddy',NULL,'C1V1C2V2 Stage 1',1,'te-ddy','00_teddy.png','/assets/portal/exercises/images/generated_images_words/00_teddy.png',15,1,'2026-03-06 12:41:56','2026-04-25 00:10:55'),
(141,NULL,'ten',NULL,'CVC-Alveolar',1,NULL,'00_ten.png','/assets/portal/exercises/images/generated_images_words/00_ten.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:59'),
(142,NULL,'tidy',NULL,'C1V1C2V2 Stage 1',1,'ti-dy','00_tidy.png','/assets/portal/exercises/images/generated_images_words/00_tidy.png',16,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(143,NULL,'tiny',NULL,'C1V1C2V2 Stage 1',1,'ti-ny','00_tiny.png','/assets/portal/exercises/images/generated_images_words/00_tiny.png',17,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(144,NULL,'toot',NULL,'CVC-Alveolar',1,NULL,'00_toot.png','/assets/portal/exercises/images/generated_images_words/00_toot.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:28:59'),
(145,NULL,'top',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_top.png','/assets/portal/exercises/images/generated_images_words/00_top.png',0,1,'2026-03-06 12:41:56','2026-03-09 18:30:09'),
(146,NULL,'tot',NULL,'CVC-Alveolar',1,NULL,'00_tot.png','/assets/portal/exercises/images/generated_images_words/00_tot.png',0,1,'2026-03-06 12:41:56','2026-04-24 17:48:57'),
(147,NULL,'tub',NULL,'CVC-Bilabial/Alveolar',1,NULL,'00_tub.png','/assets/portal/exercises/images/generated_images_words/00_tub.png',0,1,'2026-03-06 12:41:56','2026-04-24 17:49:17'),
(148,NULL,'tuba',NULL,'C1V1C2V2 Stage 1',1,'tu-ba','00_tuba.png','/assets/portal/exercises/images/generated_images_words/00_tuba.png',18,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(149,NULL,'tuna',NULL,'C1V1C2V2 Stage 1',1,'tu-na','00_tuna.png','/assets/portal/exercises/images/generated_images_words/00_tuna.png',19,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(150,NULL,'water',NULL,'C1V1C2V2 Stage 1',1,'wa-ter','00_water.png','/assets/portal/exercises/images/generated_images_words/00_water.png',20,1,'2026-03-06 12:41:56','2026-04-22 14:32:09'),
(151,NULL,'weepy',NULL,'C1V1C2V2 Stage 1',1,'wee-py','00_weepy.png','/assets/portal/exercises/images/generated_images_words/00_weepy.png',21,1,'2026-03-06 12:41:56','2026-04-25 00:13:34'),
(152,NULL,'yummy',NULL,'C1V1C2V2 Stage 1',1,'yu-mmy','00_yummy.png','/assets/portal/exercises/images/generated_images_words/00_yummy.png',22,1,'2026-03-06 12:41:56','2026-04-25 00:13:00'),
(167,NULL,'Mama',NULL,'CVCV',2,'ma-ma','00_mama.png','/assets/portal/exercises/images/generated_images_words/00_mama.png',0,1,'2026-03-10 00:32:39','2026-03-10 00:32:39'),
(168,NULL,'Dada',NULL,'CVCV',2,'da-da','00_dada.png','/assets/portal/exercises/images/generated_images_words/00_dada.png',0,1,'2026-03-10 00:32:39','2026-03-10 00:32:39'),
(169,NULL,'Papa',NULL,'CVCV',2,'pa-pa','00_papa.png','/assets/portal/exercises/images/generated_images_words/00_papa.png',0,1,'2026-03-10 00:32:39','2026-03-10 00:32:39'),
(170,NULL,'Mimi',NULL,'CVCV',2,'mi-mi','00_mimi.png','/assets/portal/exercises/images/generated_images_words/00_mimi.png',0,0,'2026-03-10 00:32:39','2026-04-25 00:08:54'),
(171,NULL,'Neigh Neigh',NULL,'CVCV',2,'neigh-neigh','00_neighneigh.png','/assets/portal/exercises/images/generated_images_words/00_neighneigh.png',0,1,'2026-03-10 00:32:39','2026-03-10 00:32:39'),
(172,NULL,'Moo Moo',NULL,'CVCV',2,'moo-moo','00_moomoo.png','/assets/portal/exercises/images/generated_images_words/00_moomoo.png',0,1,'2026-03-10 00:32:39','2026-03-10 00:32:39'),
(173,NULL,'Baa Baa',NULL,'CVCV',2,'baa-baa','00_baabaa.png','/assets/portal/exercises/images/generated_images_words/00_baabaa.png',0,1,'2026-03-10 00:32:39','2026-04-24 14:44:48'),
(174,NULL,'Pee Pee',NULL,'CVCV',2,'pee-pee','00_peepee.png','/assets/portal/exercises/images/generated_images_words/00_peepee.png',0,1,'2026-03-10 00:32:39','2026-03-10 00:32:39'),
(175,NULL,'Woof Woof',NULL,'CVCV',2,'woof-woof','00_woofwoof.png','/assets/portal/exercises/images/generated_images_words/00_woofwoof.png',0,1,'2026-03-10 00:32:39','2026-03-10 00:32:39'),
(176,NULL,'Boo Boo',NULL,'CVCV',2,'boo-boo','00_booboo.png','/assets/portal/exercises/images/generated_images_words/00_booboo.png',0,1,'2026-03-10 00:32:39','2026-03-10 00:32:39'),
(177,NULL,'Wawa',NULL,'CVCV',2,'wa-wa','00_wawa.png','/assets/portal/exercises/images/generated_images_words/00_wawa.png',0,1,'2026-03-10 00:32:39','2026-03-10 00:32:39'),
(178,NULL,'Yoyo',NULL,'CVCV',2,'yo-yo','00_yoyo.png','/assets/portal/exercises/images/generated_images_words/00_yoyo.png',0,1,'2026-03-10 00:32:39','2026-03-10 00:32:39'),
(179,NULL,'Coco',NULL,'CVCV',2,'co-co','00_coco.png','/assets/portal/exercises/images/generated_images_words/00_coco.png',0,1,'2026-03-10 00:32:39','2026-03-10 00:32:39'),
(180,NULL,'Hoo Hoo',NULL,'CVCV',2,'hoo-hoo','00_hoohoo.png','/assets/portal/exercises/images/generated_images_words/00_hoohoo.png',0,1,'2026-03-10 00:32:39','2026-03-10 00:32:39'),
(181,NULL,'Up',NULL,'VC',1,NULL,'00_up.png','/assets/portal/exercises/images/generated_images_words/00_up.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(182,NULL,'In',NULL,'VC',1,NULL,'00_in.png','/assets/portal/exercises/images/generated_images_words/00_in.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(183,NULL,'On',NULL,'VC',1,NULL,'00_on.png','/assets/portal/exercises/images/generated_images_words/00_on.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(184,NULL,'Out',NULL,'VC',1,NULL,'00_out.png','/assets/portal/exercises/images/generated_images_words/00_out.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(185,NULL,'At',NULL,'VC',1,NULL,'00_at.png','/assets/portal/exercises/images/generated_images_words/00_at.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(186,NULL,'It',NULL,'VC',1,NULL,'00_it.png','/assets/portal/exercises/images/generated_images_words/00_it.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(187,NULL,'Oat',NULL,'VC',1,NULL,'00_oat.png','/assets/portal/exercises/images/generated_images_words/00_oat.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(188,NULL,'Ape',NULL,'VC',1,NULL,'00_ape.png','/assets/portal/exercises/images/generated_images_words/00_ape.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(189,NULL,'Aim',NULL,'VC',1,NULL,'00_aim.png','/assets/portal/exercises/images/generated_images_words/00_aim.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(190,NULL,'Eat',NULL,'VC',1,NULL,'00_eat.png','/assets/portal/exercises/images/generated_images_words/00_eat.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(191,NULL,'Add',NULL,'VC',1,NULL,'00_add.png','/assets/portal/exercises/images/generated_images_words/00_add.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(192,NULL,'Odd',NULL,'VC',1,NULL,'00_odd.png','/assets/portal/exercises/images/generated_images_words/00_odd.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(193,NULL,'Aid',NULL,'VC',1,NULL,'00_aid.png','/assets/portal/exercises/images/generated_images_words/00_aid.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(194,NULL,'Us',NULL,'VC',1,NULL,'00_us.png','/assets/portal/exercises/images/generated_images_words/00_us.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(195,NULL,'Ice',NULL,'VC',1,NULL,'00_ice.png','/assets/portal/exercises/images/generated_images_words/00_ice.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(196,NULL,'Ace',NULL,'VC',1,NULL,'00_ace.png','/assets/portal/exercises/images/generated_images_words/00_ace.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(197,NULL,'Ooze',NULL,'VC',1,NULL,'00_ooze.png','/assets/portal/exercises/images/generated_images_words/00_ooze.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(198,NULL,'Is',NULL,'VC',1,NULL,'00_is.png','/assets/portal/exercises/images/generated_images_words/00_is.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(199,NULL,'As',NULL,'VC',1,NULL,'00_as.png','/assets/portal/exercises/images/generated_images_words/00_as.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(200,NULL,'Ill',NULL,'VC',1,NULL,'00_ill.png','/assets/portal/exercises/images/generated_images_words/00_ill.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(201,NULL,'Owl',NULL,'VC',1,NULL,'00_owl.png','/assets/portal/exercises/images/generated_images_words/00_owl.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(202,NULL,'All',NULL,'VC',1,NULL,'00_all.png','/assets/portal/exercises/images/generated_images_words/00_all.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(203,NULL,'Ash',NULL,'VC',1,NULL,'00_ash.png','/assets/portal/exercises/images/generated_images_words/00_ash.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(204,NULL,'Itch',NULL,'VC',1,NULL,'00_itch.png','/assets/portal/exercises/images/generated_images_words/00_itch.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23'),
(205,NULL,'Ouch',NULL,'VC',1,NULL,'00_ouch.png','/assets/portal/exercises/images/generated_images_words/00_ouch.png',0,1,'2026-03-10 01:18:23','2026-03-10 01:18:23');
/*!40000 ALTER TABLE `exercise_words` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `instruction_videos`
--

DROP TABLE IF EXISTS `instruction_videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `instruction_videos` (
  `video_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_uuid` varchar(36) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `video_filename` varchar(255) NOT NULL,
  `video_path` text NOT NULL,
  `source_type` enum('upload','url') NOT NULL DEFAULT 'upload',
  `thumbnail_path` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `slot_key` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`video_id`),
  UNIQUE KEY `uq_slot_key` (`slot_key`),
  KEY `idx_owner` (`owner_user_uuid`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`),
  KEY `idx_order` (`display_order`),
  CONSTRAINT `instruction_videos_ibfk_1` FOREIGN KEY (`owner_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instruction_videos`
--

LOCK TABLES `instruction_videos` WRITE;
/*!40000 ALTER TABLE `instruction_videos` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `instruction_videos` VALUES
(1,'SUPER-USER-UUID-0000-000000000001','Sound Iso test1','This is a description','vid_69e9666eb85d70.55746053.mp4','/assets/portal/exercises/videos/vid_69e9666eb85d70.55746053.mp4','upload',NULL,NULL,NULL,0,1,'2026-04-23 00:23:10','2026-04-23 00:29:53','panel-letters');
/*!40000 ALTER TABLE `instruction_videos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `mka_account_domains`
--

DROP TABLE IF EXISTS `mka_account_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mka_account_domains` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) NOT NULL,
  `account_uuid` varchar(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain` (`domain`),
  KEY `idx_account` (`account_uuid`),
  CONSTRAINT `mka_account_domains_ibfk_1` FOREIGN KEY (`account_uuid`) REFERENCES `mka_accounts` (`account_uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mka_account_domains`
--

LOCK TABLES `mka_account_domains` WRITE;
/*!40000 ALTER TABLE `mka_account_domains` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `mka_account_domains` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `mka_accounts`
--

DROP TABLE IF EXISTS `mka_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mka_accounts` (
  `account_uuid` varchar(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `owner_user_uuid` varchar(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`account_uuid`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_owner` (`owner_user_uuid`),
  CONSTRAINT `mka_accounts_ibfk_1` FOREIGN KEY (`owner_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mka_accounts`
--

LOCK TABLES `mka_accounts` WRITE;
/*!40000 ALTER TABLE `mka_accounts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `mka_accounts` VALUES
('ac13d2fa-3764-4f28-8329-5dee02908b6c','Jane EselPe','jane-eselpe','1f28a1b6-d767-47b7-b05a-8035df5a5ac5','2026-02-05 13:28:00','2026-02-05 13:28:00'),
('e58a4c39-3abd-4c8e-9cfe-1bb2c646bb21','John Speechguy','john-speechguy','fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','2026-02-05 13:30:23','2026-02-05 13:30:23'),
('f85a25b1-55dc-4145-bd9d-95fa58477c81','COMPANY_7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','SLUG_7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','2026-04-29 22:23:59','2026-04-29 22:23:59'),
('SUPER-ACCOUNT-UUID-0000-000000000001','SpeechApp Master','speechapp-master','SUPER-USER-UUID-0000-000000000001','2026-02-05 02:13:46','2026-02-05 02:13:46');
/*!40000 ALTER TABLE `mka_accounts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `mka_api_keys`
--

DROP TABLE IF EXISTS `mka_api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mka_api_keys` (
  `api_key` varchar(128) NOT NULL,
  `user_uuid` varchar(36) NOT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`api_key`),
  KEY `idx_user` (`user_uuid`),
  CONSTRAINT `mka_api_keys_ibfk_1` FOREIGN KEY (`user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mka_api_keys`
--

LOCK TABLES `mka_api_keys` WRITE;
/*!40000 ALTER TABLE `mka_api_keys` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `mka_api_keys` VALUES
('3cf5c68f-a6d7-4bd1-a039-d95ed0e671e7','85174e90-eb49-4da9-8b3b-dd845e8a239e','active','2026-02-25 15:46:38','2026-02-11 15:46:38'),
('6c9002fb-1449-4398-b70e-ac0e5b447c73','fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','active','2029-01-01 00:00:00','2026-02-05 13:30:23'),
('a28e1f4c-e617-41d1-aba0-c6935751c87f','7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','active',NULL,'2026-04-29 22:23:29'),
('a6a99203-954f-4bf5-bbfa-ac8942abb3fb','c6f5365b-e3bb-4db5-a52a-0b4640480306','active','2026-02-25 16:08:06','2026-02-11 16:08:06'),
('c7ed6242-93a5-4900-911d-18c19070b401','15396209-52d7-4122-ab0e-46b28b8aae7c','active','2026-02-25 20:14:42','2026-02-11 20:14:42'),
('f5c43881-049b-4047-a750-e14b7e5d6b66','1f28a1b6-d767-47b7-b05a-8035df5a5ac5','inactive','2029-01-01 00:00:00','2026-02-05 13:28:00'),
('SUPER-API-KEY-0000-000000000001','SUPER-USER-UUID-0000-000000000001','active',NULL,'2026-02-05 02:13:46');
/*!40000 ALTER TABLE `mka_api_keys` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `mka_logs`
--

DROP TABLE IF EXISTS `mka_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mka_logs` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `user_uuid` varchar(36) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_event` (`event_type`),
  KEY `idx_user` (`user_uuid`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `mka_logs_ibfk_1` FOREIGN KEY (`user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mka_logs`
--

LOCK TABLES `mka_logs` WRITE;
/*!40000 ALTER TABLE `mka_logs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `mka_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `mka_settings`
--

DROP TABLE IF EXISTS `mka_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mka_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mka_settings`
--

LOCK TABLES `mka_settings` WRITE;
/*!40000 ALTER TABLE `mka_settings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `mka_settings` VALUES
('terms_of_use','<h1>Terms of Use</h1>\n<p><strong>Effective Date:</strong> [ENTER DATE]<br>\n<strong>Platform:</strong> MKAdvantage / Virtuops<br>\n<strong>Operated by:</strong> [YOUR COMPANY LEGAL NAME] (&quot;Company,&quot; &quot;we,&quot; &quot;us,&quot; or &quot;our&quot;)</p>\n\n<p>Please read these Terms of Use (&quot;Terms&quot;) carefully before accessing or using the MKAdvantage / Virtuops speech therapy platform (&quot;Platform&quot;). By registering for, accessing, or using the Platform, you (&quot;User&quot;) agree to be bound by these Terms. If you do not agree, do not use the Platform.</p>\n\n<hr>\n\n<h2>1. Acceptance of Terms</h2>\n<p>These Terms constitute a legally binding agreement between you and the Company. Your continued use of the Platform following any modification of these Terms constitutes acceptance of the revised Terms. We will notify users of material changes via email or prominent notice within the Platform.</p>\n\n<h2>2. Description of Services</h2>\n<p>The Platform provides web-based speech-language pathology (SLP) exercise tools, content management, patient/client portal access, and related administrative features for licensed speech-language pathologists, clinical administrators, and their authorized patients or clients (&quot;Services&quot;). The Platform is not a substitute for in-person clinical evaluation, diagnosis, or treatment by a licensed professional.</p>\n\n<h2>3. Eligibility</h2>\n<p>To use the Platform you must:</p>\n<ul>\n  <li>Be at least 18 years of age, or access the Platform only under the supervision of a parent, guardian, or licensed clinician;</li>\n  <li>Have the legal authority to enter into these Terms on behalf of yourself or any organization you represent;</li>\n  <li>Not be barred from receiving services under applicable law.</li>\n</ul>\n<p>Enterprise administrators who invite end users (patients/clients) represent and warrant that they have obtained all necessary consents, including parental consent for minors.</p>\n\n<h2>4. Account Registration and Security</h2>\n<p>You agree to provide accurate, current, and complete information during registration and to keep your account credentials confidential. You are responsible for all activity that occurs under your account. Notify us immediately at <strong>support@virtuops.com</strong> of any unauthorized use. We reserve the right to suspend or terminate accounts that violate these Terms.</p>\n\n<h2>5. Subscription Plans and Payment</h2>\n<p>Access to certain features requires a paid subscription. By subscribing you authorize us to charge the applicable fees to your designated payment method on a recurring basis. All fees are non-refundable except as required by applicable law or as expressly stated in a separate written agreement. We reserve the right to modify pricing upon 30 days&apos; written notice. Failure to pay may result in suspension or termination of access.</p>\n\n<h2>6. Permitted Use and Restrictions</h2>\n<p>You may use the Platform solely for its intended clinical and educational purposes. You agree <strong>not</strong> to:</p>\n<ul>\n  <li>Reverse engineer, decompile, or disassemble any portion of the Platform;</li>\n  <li>Reproduce, redistribute, sell, or sublicense Platform content without express written permission;</li>\n  <li>Use automated scripts, bots, or scrapers to access the Platform;</li>\n  <li>Upload malicious code, viruses, or any content that infringes third-party intellectual property rights;</li>\n  <li>Use the Platform for any unlawful purpose or in violation of applicable professional licensing requirements;</li>\n  <li>Circumvent any security or access-control features of the Platform.</li>\n</ul>\n\n<h2>7. Healthcare Disclaimer and No Medical Advice</h2>\n<p>The Platform provides tools and resources to support speech-language therapy but does <strong>not</strong> constitute medical advice, diagnosis, or treatment. Users who are licensed clinicians are solely responsible for the clinical decisions made using Platform tools. The Company does not practice medicine or speech-language pathology and assumes no liability for clinical outcomes.</p>\n\n<h2>8. Intellectual Property</h2>\n<p>All content, software, trademarks, logos, and materials on the Platform (&quot;Company IP&quot;) are owned by or licensed to the Company and are protected by applicable intellectual property laws. Nothing in these Terms grants you any right to use Company IP except as expressly permitted herein. Content you upload or create within the Platform (&quot;User Content&quot;) remains your property; however, you grant the Company a non-exclusive, royalty-free license to host, display, and process your User Content solely to provide the Services.</p>\n\n<h2>9. Confidentiality</h2>\n<p>Each party may have access to confidential information of the other. Each party agrees to maintain the confidentiality of the other&apos;s confidential information using the same degree of care it uses for its own confidential information, but in no event less than reasonable care, and not to disclose such information to third parties without prior written consent, except as required by law.</p>\n\n<h2>10. Disclaimers of Warranties</h2>\n<p>THE PLATFORM IS PROVIDED &quot;AS IS&quot; AND &quot;AS AVAILABLE&quot; WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, NON-INFRINGEMENT, OR UNINTERRUPTED, ERROR-FREE OPERATION. THE COMPANY DOES NOT WARRANT THAT THE PLATFORM WILL MEET YOUR REQUIREMENTS OR THAT ALL DEFECTS WILL BE CORRECTED.</p>\n\n<h2>11. Limitation of Liability</h2>\n<p>TO THE FULLEST EXTENT PERMITTED BY APPLICABLE LAW, THE COMPANY AND ITS OFFICERS, DIRECTORS, EMPLOYEES, AND AGENTS SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS OR REVENUE, ARISING OUT OF OR IN CONNECTION WITH YOUR USE OF OR INABILITY TO USE THE PLATFORM, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGES. THE COMPANY&apos;S TOTAL CUMULATIVE LIABILITY FOR ANY CLAIM ARISING OUT OF OR RELATING TO THESE TERMS OR THE PLATFORM SHALL NOT EXCEED THE GREATER OF (A) THE FEES PAID BY YOU IN THE TWELVE (12) MONTHS PRECEDING THE CLAIM OR (B) ONE HUNDRED U.S. DOLLARS ($100).</p>\n\n<h2>12. Indemnification</h2>\n<p>You agree to indemnify, defend, and hold harmless the Company and its officers, directors, employees, and agents from and against any claims, liabilities, damages, losses, and expenses (including reasonable attorneys&apos; fees) arising out of or in connection with: (a) your use of or access to the Platform; (b) your violation of these Terms; (c) your violation of any applicable law or regulation; or (d) your User Content.</p>\n\n<h2>13. Term and Termination</h2>\n<p>These Terms remain in effect while you use the Platform. The Company may suspend or terminate your access at any time for any reason, including violation of these Terms, with or without notice. Upon termination, all licenses granted herein will immediately cease. Sections 7–15 survive termination.</p>\n\n<h2>14. Governing Law and Dispute Resolution</h2>\n<p>These Terms shall be governed by the laws of the State of [YOUR STATE], without regard to conflict-of-law principles. Any dispute arising out of or relating to these Terms or the Platform shall be submitted to binding arbitration administered by the American Arbitration Association (&quot;AAA&quot;) under its Commercial Arbitration Rules, with proceedings conducted in [YOUR CITY, STATE]. Notwithstanding the foregoing, either party may seek injunctive or other equitable relief in any court of competent jurisdiction to prevent irreparable harm.</p>\n\n<h2>15. General Provisions</h2>\n<ul>\n  <li><strong>Entire Agreement.</strong> These Terms, together with our Privacy Policy and any applicable service agreements, constitute the entire agreement between you and the Company regarding the Platform.</li>\n  <li><strong>Severability.</strong> If any provision is found unenforceable, the remaining provisions remain in full force.</li>\n  <li><strong>Waiver.</strong> Failure to enforce any right does not constitute a waiver of that right.</li>\n  <li><strong>Assignment.</strong> You may not assign your rights under these Terms without prior written consent. The Company may assign its rights freely.</li>\n  <li><strong>Notices.</strong> Legal notices to the Company should be sent to <strong>[YOUR LEGAL ADDRESS]</strong> or <strong>support@virtuops.com</strong>.</li>\n</ul>\n\n<hr>\n<p><em>Last updated: [ENTER DATE]. These Terms supersede all prior agreements relating to the subject matter herein.</em></p>\n','2026-04-22 18:38:17');
/*!40000 ALTER TABLE `mka_settings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `mka_user_accounts`
--

DROP TABLE IF EXISTS `mka_user_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mka_user_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_uuid` varchar(36) NOT NULL,
  `account_uuid` varchar(36) NOT NULL,
  `role` enum('SUPER_USER','OWNER','ADMIN','STAFF','CONTRACTOR','PATIENT') NOT NULL DEFAULT 'PATIENT',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_account` (`user_uuid`,`account_uuid`),
  KEY `idx_user` (`user_uuid`),
  KEY `idx_account` (`account_uuid`),
  KEY `idx_role` (`role`),
  CONSTRAINT `mka_user_accounts_ibfk_1` FOREIGN KEY (`user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE,
  CONSTRAINT `mka_user_accounts_ibfk_2` FOREIGN KEY (`account_uuid`) REFERENCES `mka_accounts` (`account_uuid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mka_user_accounts`
--

LOCK TABLES `mka_user_accounts` WRITE;
/*!40000 ALTER TABLE `mka_user_accounts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `mka_user_accounts` VALUES
(1,'SUPER-USER-UUID-0000-000000000001','SUPER-ACCOUNT-UUID-0000-000000000001','SUPER_USER','active','2026-02-05 02:13:48'),
(2,'1f28a1b6-d767-47b7-b05a-8035df5a5ac5','ac13d2fa-3764-4f28-8329-5dee02908b6c','OWNER','inactive','2026-02-05 13:28:00'),
(3,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','e58a4c39-3abd-4c8e-9cfe-1bb2c646bb21','OWNER','active','2026-02-05 13:30:23'),
(21,'7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','f85a25b1-55dc-4145-bd9d-95fa58477c81','OWNER','active','2026-04-29 22:23:59');
/*!40000 ALTER TABLE `mka_user_accounts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `mka_users`
--

DROP TABLE IF EXISTS `mka_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mka_users` (
  `UserUUID` varchar(36) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `company_slug` varchar(100) NOT NULL,
  `Domain` varchar(255) DEFAULT NULL,
  `user_type` enum('super_user','enterprise_admin','end_user') NOT NULL DEFAULT 'end_user',
  `parent_user_uuid` varchar(36) DEFAULT NULL,
  `Status` varchar(12) DEFAULT 'trial',
  `TrialExpires` datetime DEFAULT NULL,
  `IsPaid` enum('y','n') DEFAULT 'n',
  `email_confirmed` enum('y','n') DEFAULT 'n',
  `email_confirmation_token` varchar(64) DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `affiliate_code` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `stripe_customer_id` varchar(100) DEFAULT NULL,
  `affiliated_by_user_uuid` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`UserUUID`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `company_slug` (`company_slug`),
  UNIQUE KEY `affiliate_code` (`affiliate_code`),
  KEY `idx_email` (`Email`),
  KEY `idx_user_type` (`user_type`),
  KEY `idx_parent` (`parent_user_uuid`),
  KEY `idx_users_parent_type` (`parent_user_uuid`,`user_type`),
  KEY `fk_affiliated_by` (`affiliated_by_user_uuid`),
  CONSTRAINT `fk_affiliated_by` FOREIGN KEY (`affiliated_by_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE SET NULL,
  CONSTRAINT `mka_users_ibfk_1` FOREIGN KEY (`parent_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mka_users`
--

LOCK TABLES `mka_users` WRITE;
/*!40000 ALTER TABLE `mka_users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `mka_users` VALUES
('15396209-52d7-4122-ab0e-46b28b8aae7c','samantha@speechpeople123.com','$2y$12$kNxZLEJuxAUuXFOPRrT7duzi3q4Lxs3afXKdaPQ8i0vy7OZE7djVi','samantha@speechpeople123.com','','SLUG_15396209-52d7-4122-ab0e-46b28b8aae7c','DOMAIN_15396209-52d7-4122-ab0e-46b28b8aae7c','enterprise_admin',NULL,'active','2026-02-25 20:14:42','n','n','eb07d0e99c4c14ef94f6129abb405df3e864dc607661ca3e3af190cea266f026','2026-02-11 20:14:42','2026-04-30 00:13:06',NULL,NULL,NULL,NULL),
('1f28a1b6-d767-47b7-b05a-8035df5a5ac5','jane@slphero.com','$2y$12$mR5MQ61uzzeQGHEgPoqCb.QcIjvmlHuY8jRL042nYy1ejE/d6ivSG','Jane EselPe','Jane EselPe','jane-eselpe',NULL,'enterprise_admin','SUPER-USER-UUID-0000-000000000001','trial','2029-01-01 00:00:00','n','y',NULL,'2026-02-05 13:28:00','2026-02-16 00:22:17',NULL,NULL,NULL,NULL),
('7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','chrisschaft@gmail.com','$2y$12$DN/Inb6vgPUw9gIzAbtvXuEQqOuUPVqB8en1tcqM.g0ZgvIwQ59mW','Chris SpeechApp','The SpeechTest','SLUG_7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','DOMAIN_7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','enterprise_admin',NULL,'active','2026-05-13 22:23:29','y','y',NULL,'2026-04-29 22:23:29','2026-04-30 00:59:29',NULL,'/uploads/avatars/avatar_7c6bd799ca4f4e6a8da9c9450d84c3a4.png','cus_UQa7SRDJl22xvc',NULL),
('85174e90-eb49-4da9-8b3b-dd845e8a239e','simon_parent@tester.com','$2y$12$nZL8ULsrxPJJUkmXr.9N5e0G/pgklKt2pdYC3ui7rLSuKnvi75gy6','simon_parent@tester.com','','SLUG_85174e90-eb49-4da9-8b3b-dd845e8a239e','DOMAIN_85174e90-eb49-4da9-8b3b-dd845e8a239e','end_user',NULL,'active','2026-02-25 15:46:38','n','n','716900b6832c891c1313f17a770f2f7d0adb671fd6f39da713d03a0e0f142d29','2026-02-11 15:46:38','2026-04-30 00:13:06',NULL,NULL,NULL,NULL),
('c6f5365b-e3bb-4db5-a52a-0b4640480306','alissa@speech-123.com','$2y$12$ZUVE0lCp257jdiQpDFHqXOe08cZ0Q/9/vsdzaX1.PLbmoShgPA3FK','alissa@speech-123.com','','SLUG_c6f5365b-e3bb-4db5-a52a-0b4640480306','DOMAIN_c6f5365b-e3bb-4db5-a52a-0b4640480306','enterprise_admin',NULL,'active','2026-02-25 16:08:06','n','n','22932a151cea92fa50f821a33cce52e4ddbfd2c2b9a1f62aef9b6c8def149f37','2026-02-11 16:08:06','2026-04-30 00:13:06',NULL,NULL,NULL,NULL),
('fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','john@speechguy.com','$2y$12$Ni4im25elKPitv5AUHeH2OThvku2vz.E7BtI65WphTcinSp4UZ3Ke','John Speechguy','John Speechguy','john-speechguy',NULL,'enterprise_admin','SUPER-USER-UUID-0000-000000000001','trial','2029-01-01 00:00:00','n','y',NULL,'2026-02-05 13:30:23','2026-04-23 12:51:35',NULL,'/uploads/avatars/avatar_fb2e4fe476444102ac627798c3e6dd5a.webp',NULL,NULL),
('SUPER-USER-UUID-0000-000000000001','reneehill@cr-tc.com','$2y$12$pxAeRq0URMSqL7qfDHdNY.UeEc3vuI1oTweX/9qOd42ZHIlXawnWO','Super User','SpeechApp Master','speechapp-master',NULL,'super_user',NULL,'active',NULL,'y','y',NULL,'2026-02-05 02:12:53','2026-02-16 00:22:24',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `mka_users` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_affiliations`
--

DROP TABLE IF EXISTS `patient_affiliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_affiliations` (
  `affiliation_uuid` varchar(36) NOT NULL DEFAULT uuid(),
  `patient_uuid` varchar(36) NOT NULL,
  `slp_uuid` varchar(36) NOT NULL,
  `affiliated_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active',
  `deactivated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`affiliation_uuid`),
  UNIQUE KEY `unique_active_affiliation` (`patient_uuid`,`slp_uuid`,`status`),
  KEY `idx_slp_active` (`slp_uuid`,`status`),
  KEY `idx_patient` (`patient_uuid`),
  CONSTRAINT `patient_affiliations_ibfk_1` FOREIGN KEY (`patient_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE,
  CONSTRAINT `patient_affiliations_ibfk_2` FOREIGN KEY (`slp_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_affiliations`
--

LOCK TABLES `patient_affiliations` WRITE;
/*!40000 ALTER TABLE `patient_affiliations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_affiliations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `patient_invites`
--

DROP TABLE IF EXISTS `patient_invites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_invites` (
  `invite_uuid` varchar(36) NOT NULL,
  `slp_uuid` varchar(36) NOT NULL,
  `patient_email` varchar(255) NOT NULL,
  `patient_name` varchar(255) DEFAULT NULL,
  `invite_token` varchar(64) NOT NULL,
  `status` enum('pending','accepted','expired') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `accepted_user_uuid` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`invite_uuid`),
  UNIQUE KEY `invite_token` (`invite_token`),
  KEY `accepted_user_uuid` (`accepted_user_uuid`),
  KEY `idx_token` (`invite_token`),
  KEY `idx_slp` (`slp_uuid`),
  KEY `idx_email` (`patient_email`),
  KEY `idx_status` (`status`),
  CONSTRAINT `patient_invites_ibfk_1` FOREIGN KEY (`slp_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE,
  CONSTRAINT `patient_invites_ibfk_2` FOREIGN KEY (`accepted_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_invites`
--

LOCK TABLES `patient_invites` WRITE;
/*!40000 ALTER TABLE `patient_invites` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `patient_invites` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `payment_transactions`
--

DROP TABLE IF EXISTS `payment_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_transactions` (
  `transaction_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payer_user_uuid` varchar(36) NOT NULL,
  `receiver_user_uuid` varchar(36) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `payment_provider` enum('paypal','stripe','manual') NOT NULL,
  `provider_transaction_id` varchar(100) DEFAULT NULL,
  `subscription_uuid` varchar(36) DEFAULT NULL,
  `tier_uuid` varchar(36) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`transaction_id`),
  KEY `idx_payer` (`payer_user_uuid`),
  KEY `idx_receiver` (`receiver_user_uuid`),
  KEY `idx_subscription` (`subscription_uuid`),
  KEY `idx_provider` (`payment_provider`,`provider_transaction_id`),
  KEY `tier_uuid` (`tier_uuid`),
  KEY `idx_payments_receiver_status` (`receiver_user_uuid`,`status`),
  CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`payer_user_uuid`) REFERENCES `mka_users` (`UserUUID`),
  CONSTRAINT `payment_transactions_ibfk_2` FOREIGN KEY (`receiver_user_uuid`) REFERENCES `mka_users` (`UserUUID`),
  CONSTRAINT `payment_transactions_ibfk_3` FOREIGN KEY (`subscription_uuid`) REFERENCES `user_subscriptions` (`subscription_uuid`) ON DELETE SET NULL,
  CONSTRAINT `payment_transactions_ibfk_4` FOREIGN KEY (`tier_uuid`) REFERENCES `product_tiers` (`tier_uuid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_transactions`
--

LOCK TABLES `payment_transactions` WRITE;
/*!40000 ALTER TABLE `payment_transactions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `payment_transactions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `product_tiers`
--

DROP TABLE IF EXISTS `product_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_tiers` (
  `tier_uuid` varchar(36) NOT NULL,
  `name` varchar(50) NOT NULL,
  `plan_id` varchar(50) NOT NULL,
  `owner_user_uuid` varchar(36) DEFAULT NULL,
  `price_monthly` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_yearly` decimal(10,2) NOT NULL DEFAULT 0.00,
  `limit_sounds_words` int(11) DEFAULT 0,
  `features_json` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`tier_uuid`),
  UNIQUE KEY `plan_id` (`plan_id`),
  KEY `idx_owner` (`owner_user_uuid`),
  KEY `idx_plan_id` (`plan_id`),
  CONSTRAINT `product_tiers_ibfk_1` FOREIGN KEY (`owner_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_tiers`
--

LOCK TABLES `product_tiers` WRITE;
/*!40000 ALTER TABLE `product_tiers` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `product_tiers` VALUES
('TIER-ENTERPRISE-0000-000000000004','enterprise','super-enterprise-plan',NULL,99.99,999.99,-1,NULL,'2026-02-05 02:12:53','2026-02-05 16:34:36'),
('TIER-LITE-0000-000000000001','lite','super-lite-plan',NULL,9.99,99.99,15,NULL,'2026-02-05 02:12:53','2026-02-05 16:34:36'),
('TIER-PRO-0000-000000000003','pro','super-pro-plan',NULL,29.99,299.99,60,NULL,'2026-02-05 02:12:53','2026-02-05 16:34:36'),
('TIER-STANDARD-0000-000000000002','standard','super-standard-plan',NULL,19.99,199.99,35,NULL,'2026-02-05 02:12:53','2026-02-05 16:34:36');
/*!40000 ALTER TABLE `product_tiers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `slp_capacity_addons`
--

DROP TABLE IF EXISTS `slp_capacity_addons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `slp_capacity_addons` (
  `addon_uuid` varchar(36) NOT NULL DEFAULT uuid(),
  `slp_uuid` varchar(36) NOT NULL,
  `pack_size` int(11) NOT NULL,
  `price_monthly` decimal(10,2) NOT NULL,
  `stripe_subscription_id` varchar(100) DEFAULT NULL,
  `status` enum('active','cancelled') DEFAULT 'active',
  `purchased_at` timestamp NULL DEFAULT current_timestamp(),
  `cancelled_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`addon_uuid`),
  KEY `idx_slp_active` (`slp_uuid`,`status`),
  CONSTRAINT `slp_capacity_addons_ibfk_1` FOREIGN KEY (`slp_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slp_capacity_addons`
--

LOCK TABLES `slp_capacity_addons` WRITE;
/*!40000 ALTER TABLE `slp_capacity_addons` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `slp_capacity_addons` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tier_content_limits`
--

DROP TABLE IF EXISTS `tier_content_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tier_content_limits` (
  `limit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tier_uuid` varchar(36) NOT NULL,
  `content_type` enum('consonant','vowel','cv_blend','word') NOT NULL,
  `content_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`limit_id`),
  UNIQUE KEY `unique_tier_content` (`tier_uuid`,`content_type`,`content_id`),
  KEY `idx_tier` (`tier_uuid`),
  KEY `idx_content` (`content_type`,`content_id`),
  CONSTRAINT `tier_content_limits_ibfk_1` FOREIGN KEY (`tier_uuid`) REFERENCES `product_tiers` (`tier_uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tier_content_limits`
--

LOCK TABLES `tier_content_limits` WRITE;
/*!40000 ALTER TABLE `tier_content_limits` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `tier_content_limits` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_credits`
--

DROP TABLE IF EXISTS `user_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_credits` (
  `credit_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_uuid` varchar(36) NOT NULL,
  `credit_type` enum('affiliate_discount','promo','refund','adjustment') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`credit_id`),
  KEY `user_uuid` (`user_uuid`),
  CONSTRAINT `user_credits_ibfk_1` FOREIGN KEY (`user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_credits`
--

LOCK TABLES `user_credits` WRITE;
/*!40000 ALTER TABLE `user_credits` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `user_credits` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_default_success_media`
--

DROP TABLE IF EXISTS `user_default_success_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_default_success_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_uuid` char(36) NOT NULL,
  `media_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_default` (`user_uuid`),
  KEY `idx_user` (`user_uuid`),
  KEY `idx_media` (`media_id`),
  CONSTRAINT `user_default_success_media_ibfk_1` FOREIGN KEY (`user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE,
  CONSTRAINT `user_default_success_media_ibfk_2` FOREIGN KEY (`media_id`) REFERENCES `exercise_success_media` (`media_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores default success media for each user in regular exercises';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_default_success_media`
--

LOCK TABLES `user_default_success_media` WRITE;
/*!40000 ALTER TABLE `user_default_success_media` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_default_success_media` VALUES
(1,'fb2e4fe4-7644-4102-ac62-7798c3e6dd5a',8,'2026-02-15 15:39:10','2026-03-10 14:44:35'),
(6,'SUPER-USER-UUID-0000-000000000001',9,'2026-04-22 16:16:50','2026-04-22 16:16:50');
/*!40000 ALTER TABLE `user_default_success_media` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_exercise_progress`
--

DROP TABLE IF EXISTS `user_exercise_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_exercise_progress` (
  `progress_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_uuid` varchar(36) NOT NULL,
  `session_uuid` varchar(36) NOT NULL,
  `content_type` enum('consonant','vowel','cv_blend','word') NOT NULL,
  `content_id` bigint(20) unsigned NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `attempts` int(11) DEFAULT 1,
  `practiced_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`progress_id`),
  KEY `idx_user` (`user_uuid`),
  KEY `idx_session` (`session_uuid`),
  KEY `idx_content` (`content_type`,`content_id`),
  KEY `idx_practiced` (`practiced_at`),
  CONSTRAINT `user_exercise_progress_ibfk_1` FOREIGN KEY (`user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_exercise_progress`
--

LOCK TABLES `user_exercise_progress` WRITE;
/*!40000 ALTER TABLE `user_exercise_progress` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `user_exercise_progress` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_subscriptions`
--

DROP TABLE IF EXISTS `user_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_subscriptions` (
  `subscription_uuid` varchar(36) NOT NULL,
  `user_uuid` varchar(36) NOT NULL,
  `tier_uuid` varchar(36) NOT NULL,
  `paypal_subscription_id` varchar(100) DEFAULT NULL,
  `stripe_subscription_id` varchar(100) DEFAULT NULL,
  `payment_provider` enum('paypal','stripe','manual') NOT NULL DEFAULT 'manual',
  `status` enum('trial','active','cancelled','expired','suspended') NOT NULL DEFAULT 'trial',
  `started_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `stripe_customer_id` varchar(100) DEFAULT NULL,
  `stripe_price_id` varchar(100) DEFAULT NULL,
  `base_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`subscription_uuid`),
  KEY `idx_user` (`user_uuid`),
  KEY `idx_tier` (`tier_uuid`),
  KEY `idx_paypal` (`paypal_subscription_id`),
  KEY `idx_stripe` (`stripe_subscription_id`),
  KEY `idx_status` (`status`),
  KEY `idx_subscriptions_user_status` (`user_uuid`,`status`),
  KEY `idx_subscriptions_expires` (`expires_at`,`status`),
  CONSTRAINT `user_subscriptions_ibfk_1` FOREIGN KEY (`user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE,
  CONSTRAINT `user_subscriptions_ibfk_2` FOREIGN KEY (`tier_uuid`) REFERENCES `product_tiers` (`tier_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_subscriptions`
--

LOCK TABLES `user_subscriptions` WRITE;
/*!40000 ALTER TABLE `user_subscriptions` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_subscriptions` VALUES
('de9ced10-029c-11f1-8b40-020155471422','fb2e4fe4-7644-4102-ac62-7798c3e6dd5a','TIER-ENTERPRISE-0000-000000000004',NULL,NULL,'manual','active','2026-02-05 14:13:39',NULL,NULL,'2026-02-05 14:13:39',NULL,NULL,0.00,0.00,0.00),
('e486ef3a-0828-442d-8b33-d9c22f983bec','7c6bd799-ca4f-4e6a-8da9-c9450d84c3a4','TIER-ENTERPRISE-0000-000000000004',NULL,'sub_1TRiws0VuoH6Rs7180JSV7WD','stripe','active','2026-04-30 00:59:29',NULL,NULL,'2026-04-30 00:59:29',NULL,'price_1Syhhx0VuoH6Rs71NAihTFvY',0.00,0.00,0.00);
/*!40000 ALTER TABLE `user_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-04-30 15:12:29
