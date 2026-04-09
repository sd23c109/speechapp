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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Links assignments to success media for completion celebration';
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`assignment_id`),
  UNIQUE KEY `unique_assignment` (`assigned_to_user_uuid`,`content_type`,`content_id`),
  KEY `idx_assigned_by` (`assigned_by_user_uuid`),
  KEY `idx_assigned_to` (`assigned_to_user_uuid`),
  KEY `idx_content` (`content_type`,`content_id`),
  KEY `idx_assignments_to_active` (`assigned_to_user_uuid`,`is_active`),
  CONSTRAINT `exercise_assignments_ibfk_1` FOREIGN KEY (`assigned_by_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE,
  CONSTRAINT `exercise_assignments_ibfk_2` FOREIGN KEY (`assigned_to_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `exercise_cv_blends`
--

DROP TABLE IF EXISTS `exercise_cv_blends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exercise_cv_blends` (
  `cv_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_uuid` varchar(36) DEFAULT NULL,
  `consonant_id` bigint(20) unsigned NOT NULL,
  `vowel_id` bigint(20) unsigned NOT NULL,
  `cv_code` varchar(20) NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores success celebration media (videos and images) for exercise completion';
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `thumbnail_path` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`video_id`),
  KEY `idx_owner` (`owner_user_uuid`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`is_active`),
  KEY `idx_order` (`display_order`),
  CONSTRAINT `instruction_videos_ibfk_1` FOREIGN KEY (`owner_user_uuid`) REFERENCES `mka_users` (`UserUUID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores default success media for each user in regular exercises';
/*!40101 SET character_set_client = @saved_cs_client */;

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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-02-18 13:00:17
