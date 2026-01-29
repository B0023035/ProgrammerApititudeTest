-- MySQL dump 10.13  Distrib 8.0.32, for Linux (x86_64)
--
-- Host: localhost    Database: laravel
-- ------------------------------------------------------
-- Server version	8.0.32

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `answers`
--

DROP TABLE IF EXISTS `answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `answers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `question_id` bigint unsigned NOT NULL,
  `part` tinyint NOT NULL,
  `choice` enum('A','B','C','D','E') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `answers_user_id_question_id_unique` (`user_id`,`question_id`),
  CONSTRAINT `answers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `answers`
--

LOCK TABLES `answers` WRITE;
/*!40000 ALTER TABLE `answers` DISABLE KEYS */;
INSERT INTO `answers` VALUES (5,1,1,1,'A',0,'2025-09-03 06:04:53','2025-09-03 06:04:53'),(6,1,2,1,'A',0,'2025-09-03 06:04:53','2025-09-03 06:04:53'),(7,1,3,1,'A',0,'2025-09-03 06:04:53','2025-09-03 06:04:53'),(8,1,4,1,'C',0,'2025-09-03 06:04:53','2025-09-03 06:04:53');
/*!40000 ALTER TABLE `answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-exam_answers_1_1','a:4:{i:1;s:1:\"D\";i:2;s:1:\"A\";i:3;s:1:\"A\";i:4;s:1:\"C\";}',1757382654),('laravel-cache-exam_session_1_062e67e8-f776-4ddc-abb8-b79dae1503fc','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-09 00:51:21.326267\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-09 00:51:21.326267\";s:8:\"timezone\";s:3:\"UTC\";}}}',1757380881),('laravel-cache-exam_session_1_0bc4cb03-3217-493e-a3c5-e1d1739e9345','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:2;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 06:09:22.039025\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 06:09:22.039025\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756881562),('laravel-cache-exam_session_1_12d0998d-0aa8-4f16-a678-509b4eb25bce','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:2;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 06:04:53.446759\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 06:04:53.446759\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756881293),('laravel-cache-exam_session_1_1ef2e6e4-cd91-4668-944d-8fa5eebf45d9','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:2;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:58:46.304627\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:58:46.304627\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880926),('laravel-cache-exam_session_1_253aa998-5b80-4d39-b2ae-ffe16d52cab6','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:56:55.407858\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:56:55.407858\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880815),('laravel-cache-exam_session_1_2be9e3bb-8a0f-44a0-967f-09b5a4b825b6','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:59:58.034459\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:59:58.034459\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880998),('laravel-cache-exam_session_1_2ffda402-19f4-4403-92ea-1aefec52e706','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:50:04.873336\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:50:04.873336\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880404),('laravel-cache-exam_session_1_32953ef1-555c-41e1-a5ee-e380fd18772e','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:2;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-09 00:50:43.449463\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-09 00:50:43.449463\";s:8:\"timezone\";s:3:\"UTC\";}}}',1757380843),('laravel-cache-exam_session_1_33271909-2e7a-4c8c-b257-99d0e3ca7801','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:55:57.668433\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:55:57.668433\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880757),('laravel-cache-exam_session_1_356ea9a2-73ed-4030-ae7e-63baaf17806f','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:16:48.710486\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:16:48.710486\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756878408),('laravel-cache-exam_session_1_392c6951-7829-46ab-87e1-6566256ea314','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:56:01.749093\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:56:01.749093\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880761),('laravel-cache-exam_session_1_415cfa34-40e7-42d4-89e8-05aa0dcf4de3','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:03:58.672833\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:03:58.672833\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756877638),('laravel-cache-exam_session_1_44cf86e6-43eb-4737-a2f3-f9ea4312f430','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:34:45.879787\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:34:45.879787\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756879485),('laravel-cache-exam_session_1_45fe4f3f-eab4-466c-97a1-5282b0f78dd0','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 06:01:31.332601\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 06:01:31.332601\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756881091),('laravel-cache-exam_session_1_4d0e9e1e-85e8-4410-a3db-7ea68d186ff6','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:58:39.476546\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:58:39.476546\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880919),('laravel-cache-exam_session_1_4d31d1c9-693c-422c-8975-5af7b1eeeb00','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:04:44.630469\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:04:44.630469\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756877684),('laravel-cache-exam_session_1_5bd06319-4e5d-4e6b-9c79-550c890c6a3a','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:2;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:58:42.627715\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:58:42.627715\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880922),('laravel-cache-exam_session_1_60525533-b0e0-420e-aea4-f02dd1841c68','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 06:02:02.022729\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 06:02:02.022729\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756881122),('laravel-cache-exam_session_1_63f1b158-3ee0-4a54-85b5-b8c6d64275db','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:20:43.328842\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:20:43.328842\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756878643),('laravel-cache-exam_session_1_6a2b3dd8-a7de-4d94-8c60-22b57711a999','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:19:25.388455\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:19:25.388455\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756878565),('laravel-cache-exam_session_1_6b958a45-21e2-4dec-b257-b10fd3ba0156','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:25:52.494491\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:25:52.494491\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756878952),('laravel-cache-exam_session_1_75a343d3-f3fa-4df4-ae4f-eeded3fa7cc0','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:44:16.728433\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:44:16.728433\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880056),('laravel-cache-exam_session_1_7a06e7ae-c733-41fc-90f4-7c79e9031361','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:58:46.375060\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:58:46.375060\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880926),('laravel-cache-exam_session_1_7bf8e9b9-c90c-44d3-a2f6-a5096cff0ec8','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:56:07.614600\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:56:07.614600\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880767),('laravel-cache-exam_session_1_7ea70ee7-f4c7-490a-92c0-be41b53b8b72','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:57:55.624671\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:57:55.624671\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880875),('laravel-cache-exam_session_1_7ed50607-0d08-42e2-b2fd-0f7703d804af','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:57:50.004873\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:57:50.004873\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880870),('laravel-cache-exam_session_1_7f84f5fb-5c58-4129-a8be-dbe6ad3bd27f','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:2;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-09 00:51:21.259539\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-09 00:51:21.259539\";s:8:\"timezone\";s:3:\"UTC\";}}}',1757380881),('laravel-cache-exam_session_1_87837d45-2df3-412c-b1cb-d8f83bdc275a','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 06:03:25.052683\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 06:03:25.052683\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756881205),('laravel-cache-exam_session_1_95767b90-7c56-49b4-a72c-19db63148b10','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:56:56.521179\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:56:56.521179\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880816),('laravel-cache-exam_session_1_96075557-5ef6-47c2-bcf2-6e4e587818f1','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:10:05.009216\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:10:05.009216\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756878005),('laravel-cache-exam_session_1_9e3ca464-5fa7-42e1-9173-a49b81488a6a','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:55:59.844679\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:55:59.844679\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880759),('laravel-cache-exam_session_1_a0429aac-6839-424c-8323-06ef8dde21a4','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:57:51.548096\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:57:51.548096\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880871),('laravel-cache-exam_session_1_b47e5abf-e147-416e-b3d3-d3b0d50b1845','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:2;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:57:55.555811\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:57:55.555811\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880875),('laravel-cache-exam_session_1_b8b34714-f21e-42d6-8715-7c7becc427f9','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:18:25.556092\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:18:25.556092\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756878505),('laravel-cache-exam_session_1_c48bb181-f7e4-4164-afc8-e9bae963201c','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:28:40.352290\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:28:40.352290\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756879120),('laravel-cache-exam_session_1_d9d1fe14-4ecf-4da2-93fb-447925d2f6ce','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:2;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:49:42.912801\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:49:42.912801\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880382),('laravel-cache-exam_session_1_dc9afa88-8fea-408d-b9b1-96b886374131','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:2;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:50:04.783207\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:50:04.783207\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880404),('laravel-cache-exam_session_1_e0a3c401-98bc-426a-a649-3e39068720ae','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 03:49:47.938289\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 03:49:47.938289\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756873187),('laravel-cache-exam_session_1_e3edad3e-8592-4762-b309-302d11075366','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 06:09:22.156206\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 06:09:22.156206\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756881562),('laravel-cache-exam_session_1_e81e50b4-be5e-4329-a6ca-d68320594809','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:54:56.815033\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:54:56.815033\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880696),('laravel-cache-exam_session_1_f1c9a1a2-6add-4e85-8543-e8d98e57fc81','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:27:17.212652\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:27:17.212652\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756879037),('laravel-cache-exam_session_1_f3f47b87-d08b-4d68-8c1b-b8dc5931141b','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:43:33.843804\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:43:33.843804\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880013),('laravel-cache-exam_session_1_f78538eb-e97d-4dfb-9236-c51b22c53f57','a:4:{s:7:\"user_id\";i:1;s:15:\"exam_session_id\";i:1;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:55:50.972538\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:55:50.972538\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880750),('laravel-cache-exam_session_2_3458e3e8-8bdc-41d1-bde8-652dabb9d0fa','a:4:{s:7:\"user_id\";i:2;s:15:\"exam_session_id\";i:2;s:4:\"part\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-09 00:56:02.430597\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-09 00:56:02.430597\";s:8:\"timezone\";s:3:\"UTC\";}}}',1757381162),('laravel-cache-practice_session_1_07aefbf7-bb41-4da3-a871-46a2efdf83d0','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:45:20.889920\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:45:20.889920\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756876520),('laravel-cache-practice_session_1_08b64ad7-1b35-4fca-a042-a98f59477c6c','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 03:47:23.987536\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 03:47:23.987536\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756873043),('laravel-cache-practice_session_1_09a67824-b402-4efa-b239-ad960bb2666d','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:35:42.657214\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:35:42.657214\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756875942),('laravel-cache-practice_session_1_120383bf-f104-4d62-8b44-67f3d796ff78','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-09 01:58:53.747231\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-09 01:58:53.747231\";s:8:\"timezone\";s:3:\"UTC\";}}}',1757384933),('laravel-cache-practice_session_1_122f8b3f-0ad3-49bc-8826-7dea43b3defd','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:34:18.122366\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:34:18.122366\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756879458),('laravel-cache-practice_session_1_14bbdcf6-8bef-4beb-84b1-15a29c1ca5be','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:29:48.458094\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:29:48.458094\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756875588),('laravel-cache-practice_session_1_224ac4a9-788f-410b-9f04-eb0696620df6','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:29:47.582427\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:29:47.582427\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756875587),('laravel-cache-practice_session_1_3133c65f-2adc-4aca-8729-fc6594b1bedc','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:29:51.476379\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:29:51.476379\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756875591),('laravel-cache-practice_session_1_372b9d9f-0c1c-45dd-b372-0e8a03db5d32','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-09 00:51:30.129917\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-09 00:51:30.129917\";s:8:\"timezone\";s:3:\"UTC\";}}}',1757380890),('laravel-cache-practice_session_1_45a2a6de-d81c-4217-b80c-0e60860e0a05','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:32:01.200505\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:32:01.200505\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756875721),('laravel-cache-practice_session_1_48e182b7-7217-417c-b660-94a6d7468fd0','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 03:01:51.661020\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 03:01:51.661020\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756870311),('laravel-cache-practice_session_1_5f17f061-65a3-41ff-96a2-aee835594a40','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 02:12:42.215735\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 02:12:42.215735\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756867362),('laravel-cache-practice_session_1_738fdff0-8405-4db1-8d35-a9e33573af88','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 06:06:36.403305\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 06:06:36.403305\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756881396),('laravel-cache-practice_session_1_79d1a124-2ceb-4e1c-813c-664e60e7b755','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:22:44.720499\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:22:44.720499\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756875164),('laravel-cache-practice_session_1_7fa41002-dda8-4c27-95ab-deb11fe4a53d','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 02:12:43.184092\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 02:12:43.184092\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756867363),('laravel-cache-practice_session_1_87ab12d3-dc50-47af-8294-465cf81ace0d','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 06:09:09.233109\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 06:09:09.233109\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756881549),('laravel-cache-practice_session_1_8a1f4e99-211e-4074-a35c-101d7afef0f2','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:39:23.952120\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:39:23.952120\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756876163),('laravel-cache-practice_session_1_917b5d54-b364-47b8-8d1a-ee41a388428e','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 03:01:54.059362\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 03:01:54.059362\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756870314),('laravel-cache-practice_session_1_9247cb8b-6ce0-457a-90ca-01e28c0028b9','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:53:37.013111\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:53:37.013111\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756877017),('laravel-cache-practice_session_1_972043c3-3dfc-4105-afca-dfd7bd399067','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:53:40.925732\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:53:40.925732\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756877020),('laravel-cache-practice_session_1_99aa1f4f-604e-4234-a368-21e63475188c','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 06:06:09.365037\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 06:06:09.365037\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756881369),('laravel-cache-practice_session_1_9eab782a-32d2-4060-b2da-bcde691e436c','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:27:45.194994\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:27:45.194994\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756875465),('laravel-cache-practice_session_1_bbc003de-1f30-4bca-bed8-827d35f998f0','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:29:50.148473\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:29:50.148473\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756875590),('laravel-cache-practice_session_1_bc9416b5-900e-48f2-ae41-422cb3fac60d','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:39:57.216700\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:39:57.216700\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756876197),('laravel-cache-practice_session_1_c05db9cc-4f56-4d9f-8812-accd94ecdc91','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 06:09:12.338514\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 06:09:12.338514\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756881552),('laravel-cache-practice_session_1_d417222f-aa20-42cc-ac94-edc1b2e34044','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:22:42.335708\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:22:42.335708\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756875162),('laravel-cache-practice_session_1_d669db7e-0aa8-4999-8c24-3914d9ef36e9','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:49:50.293892\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:49:50.293892\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756880390),('laravel-cache-practice_session_1_df1c5e4b-dfa2-4506-8d0c-67fdb71fc8bc','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 02:13:54.749384\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 02:13:54.749384\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756867434),('laravel-cache-practice_session_1_e66341e1-883d-4554-89cb-50c70d330513','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 06:06:12.759561\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 06:06:12.759561\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756881372),('laravel-cache-practice_session_1_e6fcf681-ebfc-42b0-b38a-a96f9383df28','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 02:12:38.081069\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 02:12:38.081069\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756867358),('laravel-cache-practice_session_1_ea69a80a-e1dd-4083-9274-9dd70f898282','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:34:14.670564\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:34:14.670564\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756879454),('laravel-cache-practice_session_1_f25d74d7-8a67-4889-858d-b21d23d60830','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 04:48:02.444627\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 04:48:02.444627\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756876682),('laravel-cache-practice_session_1_fb93cb2b-e516-4b94-854e-0023a371d668','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 06:09:05.244794\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 06:09:05.244794\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756881545),('laravel-cache-practice_session_1_fcd1f50c-af25-4e5b-be6d-1dbc35f534aa','a:2:{s:7:\"user_id\";i:1;s:10:\"started_at\";O:25:\"Illuminate\\Support\\Carbon\":4:{s:4:\"date\";s:26:\"2025-09-03 05:34:20.165682\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";s:18:\"dumpDateProperties\";a:2:{s:4:\"date\";s:26:\"2025-09-03 05:34:20.165682\";s:8:\"timezone\";s:3:\"UTC\";}}}',1756879460);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `choices`
--

DROP TABLE IF EXISTS `choices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `choices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question_id` bigint unsigned NOT NULL,
  `label` enum('A','B','C','D','E') COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `choices_question_id_label_unique` (`question_id`,`label`),
  CONSTRAINT `choices_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `choices`
--

LOCK TABLES `choices` WRITE;
/*!40000 ALTER TABLE `choices` DISABLE KEYS */;
INSERT INTO `choices` VALUES (1,1,'A','f',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(2,1,'B','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(3,1,'C','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(4,1,'D','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(5,1,'E','j',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(6,2,'A','a',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(7,2,'B','c',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(8,2,'C','x',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(9,2,'D','y',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(10,2,'E','z',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(11,3,'A','d',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(12,3,'B','e',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(13,3,'C','f',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(14,3,'D','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(15,3,'E','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(16,4,'A','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(17,4,'B','j',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(18,4,'C','k',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(19,4,'D','l',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(20,4,'E','m',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(21,5,'A','d',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(22,5,'B','f',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(23,5,'C','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(24,5,'D','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(25,5,'E','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(26,6,'A','a',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(27,6,'B','b',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(28,6,'C','x',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(29,6,'D','y',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(30,6,'E','z',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(31,7,'A','b',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(32,7,'B','e',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(33,7,'C','l',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(34,7,'D','m',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(35,7,'E','n',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(36,8,'A','r',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(37,8,'B','s',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(38,8,'C','t',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(39,8,'D','v',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(40,8,'E','w',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(41,9,'A','a',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(42,9,'B','b',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(43,9,'C','c',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(44,9,'D','r',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(45,9,'E','s',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(46,10,'A','e',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(47,10,'B','f',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(48,10,'C','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(49,10,'D','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(50,10,'E','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(51,11,'A','m',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(52,11,'B','n',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(53,11,'C','o',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(54,11,'D','p',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(55,11,'E','q',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(56,12,'A','e',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(57,12,'B','f',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(58,12,'C','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(59,12,'D','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(60,12,'E','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(61,13,'A','d',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(62,13,'B','e',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(63,13,'C','p',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(64,13,'D','q',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(65,13,'E','r',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(66,14,'A','o',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(67,14,'B','p',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(68,14,'C','q',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(69,14,'D','r',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(70,14,'E','s',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(71,15,'A','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(72,15,'B','j',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(73,15,'C','k',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(74,15,'D','l',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(75,15,'E','m',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(76,16,'A','k',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(77,16,'B','o',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(78,16,'C','p',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(79,16,'D','q',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(80,16,'E','r',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(81,17,'A','e',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(82,17,'B','f',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(83,17,'C','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(84,17,'D','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(85,17,'E','j',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(86,18,'A','b',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(87,18,'B','c',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(88,18,'C','f',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(89,18,'D','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(90,18,'E','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(91,19,'A','j',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(92,19,'B','k',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(93,19,'C','l',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(94,19,'D','m',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(95,19,'E','n',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(96,20,'A','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(97,20,'B','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(98,20,'C','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(99,20,'D','j',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(100,20,'E','k',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(101,21,'A','e',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(102,21,'B','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(103,21,'C','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(104,21,'D','j',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(105,21,'E','l',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(106,22,'A','f',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(107,22,'B','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(108,22,'C','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(109,22,'D','j',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(110,22,'E','k',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(111,23,'A','c',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(112,23,'B','d',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(113,23,'C','e',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(114,23,'D','f',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(115,23,'E','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(116,24,'A','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(117,24,'B','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(118,24,'C','j',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(119,24,'D','k',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(120,24,'E','l',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(121,25,'A','d',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(122,25,'B','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(123,25,'C','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(124,25,'D','m',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(125,25,'E','n',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(126,26,'A','c',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(127,26,'B','d',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(128,26,'C','e',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(129,26,'D','f',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(130,26,'E','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(131,27,'A','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(132,27,'B','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(133,27,'C','j',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(134,27,'D','k',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(135,27,'E','l',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(136,28,'A','m',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(137,28,'B','n',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(138,28,'C','o',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(139,28,'D','p',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(140,28,'E','q',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(141,29,'A','r',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(142,29,'B','s',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(143,29,'C','t',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(144,29,'D','v',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(145,29,'E','w',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(146,30,'A','a',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(147,30,'B','b',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(148,30,'C','c',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(149,30,'D','d',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(150,30,'E','e',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(151,31,'A','f',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(152,31,'B','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(153,31,'C','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(154,31,'D','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(155,31,'E','j',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(156,32,'A','k',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(157,32,'B','l',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(158,32,'C','m',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(159,32,'D','n',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(160,32,'E','o',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(161,33,'A','p',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(162,33,'B','q',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(163,33,'C','r',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(164,33,'D','s',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(165,33,'E','t',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(166,34,'A','u',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(167,34,'B','v',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(168,34,'C','w',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(169,34,'D','x',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(170,34,'E','y',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(171,35,'A','z',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(172,35,'B','a',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(173,35,'C','b',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(174,35,'D','c',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(175,35,'E','d',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(176,36,'A','e',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(177,36,'B','f',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(178,36,'C','g',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(179,36,'D','h',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(180,36,'E','i',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(181,37,'A','j',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(182,37,'B','k',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(183,37,'C','l',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(184,37,'D','m',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(185,37,'E','n',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(186,38,'A','o',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(187,38,'B','p',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(188,38,'C','q',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(189,38,'D','r',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(190,38,'E','s',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(191,39,'A','t',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(192,39,'B','u',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(193,39,'C','v',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(194,39,'D','w',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(195,39,'E','x',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(196,40,'A','y',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(197,40,'B','z',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(198,40,'C','a',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(199,40,'D','b',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20'),(200,40,'E','c',NULL,0,'2025-09-01 02:43:20','2025-09-01 02:43:20');
/*!40000 ALTER TABLE `choices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_sessions`
--

DROP TABLE IF EXISTS `exam_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `current_part` tinyint NOT NULL DEFAULT '1',
  `current_question` tinyint NOT NULL DEFAULT '1',
  `remaining_time` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exam_sessions_user_id_foreign` (`user_id`),
  CONSTRAINT `exam_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_sessions`
--

LOCK TABLES `exam_sessions` WRITE;
/*!40000 ALTER TABLE `exam_sessions` DISABLE KEYS */;
INSERT INTO `exam_sessions` VALUES (1,1,'2025-09-03 03:49:47',NULL,2,1,1790,'2025-09-03 03:49:47','2025-09-09 00:50:54'),(2,2,'2025-09-09 00:56:02',NULL,1,1,1800,'2025-09-09 00:56:02','2025-09-09 00:56:02');
/*!40000 ALTER TABLE `exam_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (28,'0001_01_01_000000_create_users_table',1),(29,'0001_01_01_000001_create_cache_table',1),(30,'0001_01_01_000002_create_jobs_table',1),(31,'2025_07_16_052933_add_is_admin_to_users_table',1),(32,'2025_07_16_060036_create_admins_table',1),(33,'2025_09_01_015050_create_exam_sessions_table',1),(34,'2025_09_01_015052_create_answers_table',1),(35,'2025_09_01_015054_create_questions_table',1),(36,'2025_09_01_015055_create_choices_table',1),(37,'2025_09_01_054608_create_practice_questions_table',2),(38,'2025_09_03_010740_add_explanation_to_practice_questions_table',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `practice_choices`
--

DROP TABLE IF EXISTS `practice_choices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `practice_choices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question_id` bigint unsigned NOT NULL,
  `label` enum('A','B','C','D','E') COLLATE utf8mb4_unicode_ci NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `practice_choices_question_id_foreign` (`question_id`),
  CONSTRAINT `practice_choices_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `practice_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `practice_choices`
--

LOCK TABLES `practice_choices` WRITE;
/*!40000 ALTER TABLE `practice_choices` DISABLE KEYS */;
INSERT INTO `practice_choices` VALUES (1,1,'A','a',NULL,1,'2025-09-02 01:27:07','2025-09-02 01:27:07'),(2,1,'B','b',NULL,0,'2025-09-02 01:27:07','2025-09-02 01:27:07'),(3,1,'C','c',NULL,0,'2025-09-02 01:27:07','2025-09-02 01:27:07'),(4,1,'D','d',NULL,0,'2025-09-02 01:27:07','2025-09-02 01:27:07'),(5,1,'E','e',NULL,0,'2025-09-02 01:27:07','2025-09-02 01:27:07'),(6,2,'A','a',NULL,0,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(7,2,'B','b',NULL,0,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(8,2,'C','c',NULL,0,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(9,2,'D','d',NULL,0,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(10,2,'E','e',NULL,1,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(11,3,'A','d',NULL,0,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(12,3,'B','e',NULL,0,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(13,3,'C','f',NULL,0,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(14,3,'D','g',NULL,1,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(15,3,'E','h',NULL,0,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(16,4,'A','a',NULL,0,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(17,4,'B','b',NULL,0,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(18,4,'C','c',NULL,0,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(19,4,'D','x',NULL,0,'2025-09-02 01:27:59','2025-09-02 01:27:59'),(20,4,'E','y',NULL,1,'2025-09-02 01:27:59','2025-09-02 01:27:59');
/*!40000 ALTER TABLE `practice_choices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `practice_questions`
--

DROP TABLE IF EXISTS `practice_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `practice_questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part` tinyint unsigned NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `explanation` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `practice_questions`
--

LOCK TABLES `practice_questions` WRITE;
/*!40000 ALTER TABLE `practice_questions` DISABLE KEYS */;
INSERT INTO `practice_questions` VALUES (1,1,'a b a b a b a b\r\n','この問題では配列が ab ab ab ab という順になっています。\r\nしたがって次に来る文字は a となります。\r\n',NULL,NULL,NULL),(2,1,'a a b b c c d d\r\n','この問題では配列が aa bb cc dd という順になっています。\r\nしたがって次に来る文字は e となります。\r\n',NULL,NULL,NULL),(3,1,'c a d a e a f a\r\n','この問題では配列が ca da ea fa という順になっています。\r\nしたがって次に来る文字は g となります。\r\n',NULL,NULL,NULL),(4,1,'a x b y a x b y a x b\r\n','この問題では配列が axby axby axb という順になっています。\r\nしたがって次に来る文字は y となります。\r\n',NULL,NULL,NULL);
/*!40000 ALTER TABLE `practice_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part` tinyint NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `number` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
INSERT INTO `questions` VALUES (1,1,'e e f g g h i i',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',1),(2,1,'a z a y b z b y c',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',2),(3,1,'d e f d e f g h i',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',3),(4,1,'c d e x y z f g h x y z',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',4),(5,1,'d e f d e g d e',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',5),(6,1,'a b c z a b c y a b c',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',6),(7,1,'f g b h i b j k b',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',7),(8,1,'t s r t s r t s',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',8),(9,1,'a r b s c t a r b',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',9),(10,1,'b c c d e e f g',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',10),(11,1,'e f h i k l',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',11),(12,1,'a b c c d e f f g',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',12),(13,1,'a m n b o p c',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',13),(14,1,'t t t s s r q q q p',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',14),(15,1,'d d f f h h j j',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',15),(16,1,'m n m n k l o p o p k l',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',16),(17,1,'c d d e e e f f f',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',17),(18,1,'g f e d',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',18),(19,1,'d f h j l',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',19),(20,1,'a b c i j d e f i j',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',20),(21,1,'e f g e f g h e f g h i',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',21),(22,1,'b c b d e d f g f h i',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',22),(23,1,'a a b a b c c d c',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',23),(24,1,'a i b c i d e f',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',24),(25,1,'c e h l',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',25),(26,1,'a b d e h i m n',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',26),(27,1,'b e c f d g e',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',27),(28,1,'a g b h c',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',28),(29,1,'a d h k o',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',29),(30,1,'e f g h j k l n o',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',30),(31,1,'a e i b f',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',31),(32,1,'a e d h g',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',32),(33,1,'z d w g t',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',33),(34,1,'z e i y f j x g',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',34),(35,1,'c q r e u v g',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',35),(36,1,'k s j t i u h',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',36),(37,1,'r s j t u h v w',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',37),(38,1,'i e a j f b k',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',38),(39,1,'h e b i f c j',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',39),(40,1,'n j f m i e l',NULL,'2025-09-01 02:38:04','2025-09-01 02:38:04',40);
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('vHP6OcwvmVJVFZZC9PfUqfg0TLxiBpsJ9k83MqZU',2,'172.25.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRW9tM2N1MnNrcVFaZmZOQnZNUk5GMDZza0FxQTBCaXFpbWNUaWNxcyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czoyNToiaHR0cDovL2xvY2FsaG9zdC9wcmFjdGljZSI7fX0=',1757382251),('WKkhLNH5JbE4A7Gj2gWDVjNWdaEuMO0zWpDwcYrQ',1,'172.25.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiSHZuM1F5OExFamJPYXJLUm56T3JwWHg0a2xwbHczUFR3aGpMWmlOayI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3QvdGVzdC1zdGFydCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1757383207);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'B0023035','B0023035@ib.yic.ac.jp',NULL,'$2y$12$Q5avZVx8kq.1dKiQqCuO1eSGgKI2Dw4G.r5DOMxM6GkIMrCJ3Waa2','TrySSj1Dvd1jPT7dYFLPv5vJEOrBid0fxwCFLiMNpGg2kRIpLNuuMXVH1YcF','2025-09-01 03:08:10','2025-09-01 03:08:10',0),(2,'test','test@test',NULL,'$2y$12$ZlswNdCvlrXb/0klEUgthuOIsrU6gv8CUHGBSwTVHl3mdFuye5EPu',NULL,'2025-09-09 00:51:55','2025-09-09 00:51:55',0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-09  2:22:04
