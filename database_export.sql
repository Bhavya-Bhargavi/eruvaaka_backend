-- MySQL dump 10.13  Distrib 8.4.9, for Win64 (x86_64)
--
-- Host: localhost    Database: eruvahli_androidApp
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

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
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
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
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_09_04_000000_make_optional_registration_fields_nullable',1),(2,'2026_09_04_000001_remove_email_uniqueness_from_users',2),(3,'0001_01_01_000000_create_users_table',3),(4,'0001_01_01_000001_create_cache_table',3),(5,'0001_01_01_000002_create_jobs_table',3),(6,'0001_01_01_000003_create_revoked_tokens_table',3),(7,'0001_01_01_000004_create_bookmarks_table',3),(8,'2026_09_05_000001_add_payment_subscription_fields',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_password_reset_tokens_user` (`user_id`),
  CONSTRAINT `fk_password_reset_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
-- Table structure for table `payment_orders`
--

DROP TABLE IF EXISTS `payment_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `razorpay_order_id` varchar(100) NOT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `amount` int(10) unsigned NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'INR',
  `receipt` varchar(100) NOT NULL,
  `plan_type` varchar(255) DEFAULT NULL,
  `signature` char(64) DEFAULT NULL,
  `status` enum('created','paid') NOT NULL DEFAULT 'created',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `razorpay_order_id` (`razorpay_order_id`),
  UNIQUE KEY `receipt` (`receipt`),
  UNIQUE KEY `razorpay_payment_id` (`razorpay_payment_id`),
  KEY `fk_payment_orders_user` (`user_id`),
  CONSTRAINT `fk_payment_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_orders`
--

LOCK TABLES `payment_orders` WRITE;
/*!40000 ALTER TABLE `payment_orders` DISABLE KEYS */;
INSERT INTO `payment_orders` VALUES (1,13,'order_TYPJThfY7sjhzn',NULL,99900,'INR','eru_1a63165d6b3aacd852d727fc','monthly',NULL,'created','2026-09-05 10:07:00','2026-09-05 10:07:00');
/*!40000 ALTER TABLE `payment_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revoked_tokens`
--

DROP TABLE IF EXISTS `revoked_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `revoked_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token_id` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_id` (`token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revoked_tokens`
--

LOCK TABLES `revoked_tokens` WRITE;
/*!40000 ALTER TABLE `revoked_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `revoked_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `plan_type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password_hash` text DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `mandal` varchar(255) DEFAULT NULL,
  `pincode` varchar(6) DEFAULT NULL,
  `crop_interests` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`crop_interests`)),
  `role` varchar(30) DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `subscription_status` varchar(255) NOT NULL DEFAULT 'expired',
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'bhav','',NULL,'bhav','bha@gmail.com',NULL,'$2y$12$Ghy5OXEA9uLGTxLbCPikrOh4mJ1Lymcctiw3Z96DxujOdtqcGcZ6C',NULL,NULL,NULL,NULL,NULL,'user',1,'expired',NULL,NULL,'2026-08-28 06:15:54','2026-09-02 15:54:15'),(2,'bhavy','',NULL,'bhavy','bhav@gmail.com',NULL,'$2y$12$wSNkUBp3TL0HaRFWl7cJie6I6qleaPikPIQz9ivMzIFd.oga8N2xS',NULL,NULL,NULL,NULL,NULL,'user',1,'expired',NULL,NULL,'2026-08-28 06:16:42','2026-09-02 15:54:15'),(3,'bhavyaa','',NULL,'bhavyaa','bhavy@gmail.com',NULL,'$2y$12$fd9hWAub3J.bQzFV7J2a5.GrGzUmuWzSW0aW01iOcYj6Hz4ZvsqAS',NULL,NULL,NULL,NULL,NULL,'user',1,'expired',NULL,NULL,'2026-08-28 06:30:40','2026-09-02 15:54:15'),(4,'Test','User','9876543210',NULL,'test9876543210@example.com',NULL,'$2y$12$eDnhX0EnsTQoo6MtwIIdouDQrEKi5DVO50.lHQbBv16GYJ7txiQdG','Telangana','Hyderabad','Secunderabad','500001','[\"Rice\"]','user',1,'expired','994097','2026-09-02 10:36:27','2026-09-02 10:30:03','2026-09-02 10:31:27'),(5,'John','Doe','9876543200',NULL,'john11@example.com',NULL,'$2y$12$tDG1X2UTB/4GpynHsJV12.bOjduP5q/0bIbjAdIZg0R6jG8F9tNCS','Karnataka','Bengaluru Urban','Yelahanka','560064','[\"Rice\",\"Cotton\"]','user',1,'expired','413366','2026-09-04 07:52:39','2026-09-02 10:31:54','2026-09-04 07:47:39'),(6,'anu','roy','9876541200',NULL,'anu@example.com',NULL,'$2y$12$QL4m4iSbDXcitq/p76uIXenyoQ5kV9keOSrIFHSqRGvITnlx0ucDK','Karnataka','Bengaluru Urban','Yelahanka','560064','[\"Rice\",\"Cotton\"]','user',1,'expired',NULL,NULL,'2026-09-04 07:48:07','2026-09-04 07:48:07'),(7,'Api','Tester','9767194477',NULL,'not-an-email',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'user',1,'expired','568179','2026-09-04 08:17:28','2026-09-04 07:53:55','2026-09-04 08:12:28'),(8,'Api','Tester','9693212211',NULL,'johntest@example.com',NULL,'$2y$12$vd2bKrR//ueplckbm25NQ.h/qaewp5k7E0X6M5brx0/1cxY1E6PZa','Karnataka','Bengaluru Urban','Yelahanka','560064','[\"Rice\",\"Cotton\"]','user',1,'expired',NULL,NULL,'2026-09-04 07:56:58','2026-09-04 07:56:58'),(10,'Api','Tester','9969470465',NULL,'johntest@example.com',NULL,'$2y$12$s6w7qodlmlE2hDet2W1hyOCOFVjap/qaBxZ7LUCYwCXDDvRX6bdam','Karnataka','Bengaluru Urban','Yelahanka','560064','[\"Rice\",\"Cotton\"]','user',1,'expired',NULL,NULL,'2026-09-04 07:58:33','2026-09-04 07:58:33'),(11,'atest','blastnameoe','9001901911',NULL,'johntest@example.com',NULL,'$2y$12$sJ3crmaadRb5p59ffYM4u.0cfOrRVGSatXkRjLO.p9CYahQlFAKNu','Karnataka','Bengaluru Urban','Yelahanka','560064','[\"Rice\",\"Cotton\"]','user',1,'expired','314728','2026-09-04 08:23:29','2026-09-04 08:03:16','2026-09-04 08:18:29'),(12,'aetest','blastnameoe','9001901912',NULL,'johntest@example.com',NULL,'$2y$12$2GFpAz28bF2en.mYyBgu5.aPsr.xI5E4h4o2RwHTUfimNXE7w7m0y','Karnataka','Bengaluru Urban','Yelahanka','560064','[\"Rice\",\"Cotton\"]','user',1,'expired',NULL,NULL,'2026-09-04 08:03:37','2026-09-04 08:03:37'),(13,'aetest','blastnameoe','9001900912',NULL,'johntest@example.com',NULL,'$2y$12$RTeoZtzTzFanSt48A2j75ubvpYE6CSXlTY0d5QhDikb38HtG4hKjG','Karnataka','Bengaluru Urban','Yelahanka','560064','[\"Rice\",\"Cotton\"]','user',1,'expired',NULL,NULL,'2026-09-04 08:05:09','2026-09-05 10:06:41');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'eruvahli_androidApp'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-05 21:14:38
