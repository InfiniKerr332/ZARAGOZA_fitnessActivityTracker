-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: fitness_tracker_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activities` (
  `activity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `exercise_id` int(10) unsigned NOT NULL,
  `activity_date` date NOT NULL,
  `duration_minutes` decimal(6,2) DEFAULT NULL,
  `distance_km` decimal(7,3) DEFAULT NULL,
  `sets` int(10) unsigned DEFAULT NULL,
  `reps` int(10) unsigned DEFAULT NULL,
  `weight_used_kg` decimal(6,2) DEFAULT NULL,
  `calories_burned` decimal(8,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('planned','completed') NOT NULL DEFAULT 'completed',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`activity_id`),
  KEY `idx_activity_user` (`user_id`),
  KEY `idx_activity_date` (`activity_date`),
  KEY `idx_activity_exercise` (`exercise_id`),
  CONSTRAINT `fk_activity_exercise` FOREIGN KEY (`exercise_id`) REFERENCES `exercise_types` (`exercise_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activities`
--

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
INSERT INTO `activities` VALUES (57,18,3,'2026-05-03',19.50,0.000,0,0,0.00,234.00,NULL,'completed','2026-05-03 10:08:31',NULL),(67,20,1,'2026-05-04',23.00,NULL,NULL,NULL,NULL,262.00,NULL,'completed','2026-05-04 22:24:00',NULL),(68,20,1,'2026-05-04',23.00,NULL,NULL,NULL,NULL,262.00,NULL,'completed','2026-05-04 22:24:12',NULL),(69,18,33,'2026-05-05',23.00,NULL,NULL,NULL,NULL,1035.00,NULL,'completed','2026-05-05 07:12:27',NULL),(70,18,33,'2026-05-05',23.00,NULL,NULL,NULL,NULL,1035.00,NULL,'completed','2026-05-05 08:18:38',NULL),(71,18,33,'2026-05-05',23.00,NULL,NULL,NULL,NULL,1035.00,NULL,'completed','2026-05-05 08:18:43',NULL),(72,21,3,'2026-05-10',20.00,NULL,NULL,NULL,NULL,240.00,NULL,'completed','2026-05-10 07:54:15',NULL),(73,21,3,'2026-05-10',20.00,NULL,NULL,NULL,NULL,240.00,NULL,'completed','2026-05-10 07:54:36',NULL),(74,22,2,'2026-05-12',12.00,NULL,NULL,NULL,NULL,42.00,NULL,'completed','2026-05-12 09:54:10','2026-05-12 09:55:00'),(75,22,1,'2026-05-12',23.00,NULL,NULL,NULL,NULL,262.00,NULL,'planned','2026-05-12 09:57:10','2026-05-12 11:20:02'),(76,22,1,'2026-05-12',20.00,NULL,NULL,NULL,NULL,228.00,NULL,'completed','2026-05-12 09:57:22',NULL),(77,22,17,'2026-05-12',20.00,NULL,NULL,NULL,NULL,140.00,NULL,'completed','2026-05-12 11:19:19','2026-05-12 11:19:39'),(78,22,2,'2026-05-12',223.00,NULL,NULL,NULL,NULL,781.00,NULL,'completed','2026-05-12 11:22:17',NULL),(79,22,11,'2026-05-12',23.00,NULL,NULL,NULL,NULL,69.00,NULL,'completed','2026-05-12 11:22:26',NULL);
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_categories`
--

DROP TABLE IF EXISTS `activity_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_categories` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(60) NOT NULL,
  `icon` varchar(50) NOT NULL DEFAULT 'activity',
  `color_hex` varchar(7) NOT NULL DEFAULT '#00D4AA',
  `description` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_categories`
--

LOCK TABLES `activity_categories` WRITE;
/*!40000 ALTER TABLE `activity_categories` DISABLE KEYS */;
INSERT INTO `activity_categories` VALUES (1,'Cardio','activity','#00D4AA','Cardiovascular and endurance exercises',NULL),(2,'Strength','dumbbell','#7C3AED','Weight training and resistance exercises',NULL),(3,'Flexibility','wind','#F59E0B','Stretching, yoga, and mobility work',NULL),(4,'Sports','trophy','#3B82F6','Team and individual sport activities',NULL),(5,'HIIT','flame','#EF4444','High-intensity interval training',NULL),(6,'Swimming','waves','#06B6D4','Pool and open water swimming',NULL),(7,'Cycling','bike','#10B981','Road, mountain, and stationary cycling',NULL),(8,'Martial Arts','shield','#8B5CF6','Combat sports and self-defense training',NULL),(10,'Kentucky','activity','#7deb24',NULL,NULL);
/*!40000 ALTER TABLE `activity_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `body_metrics`
--

DROP TABLE IF EXISTS `body_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `body_metrics` (
  `metric_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `recorded_date` date NOT NULL,
  `weight_kg` decimal(6,2) DEFAULT NULL,
  `body_fat_pct` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(5,2) DEFAULT NULL,
  `waist_cm` decimal(5,2) DEFAULT NULL,
  `chest_cm` decimal(5,2) DEFAULT NULL,
  `arm_cm` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`metric_id`),
  KEY `idx_metrics_user` (`user_id`),
  KEY `idx_metrics_date` (`recorded_date`),
  CONSTRAINT `fk_metrics_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `body_metrics`
--

LOCK TABLES `body_metrics` WRITE;
/*!40000 ALTER TABLE `body_metrics` DISABLE KEYS */;
INSERT INTO `body_metrics` VALUES (16,18,'2026-05-03',59.60,NULL,20.70,NULL,NULL,NULL,NULL,'2026-05-03 10:10:12',NULL),(17,18,'2026-05-03',55.90,NULL,19.40,NULL,NULL,NULL,NULL,'2026-05-03 12:14:08',NULL),(18,18,'2026-05-03',69.80,NULL,24.20,NULL,NULL,NULL,NULL,'2026-05-03 12:14:25',NULL),(22,21,'2026-05-10',60.00,NULL,20.76,NULL,NULL,NULL,NULL,'2026-05-10 07:56:58','2026-05-10 08:02:14'),(23,21,'2026-05-10',65.00,NULL,22.49,NULL,NULL,NULL,NULL,'2026-05-10 08:03:45','2026-05-10 08:44:51'),(24,16,'2026-05-10',60.00,NULL,NULL,NULL,NULL,NULL,NULL,'2026-05-10 08:20:25',NULL),(25,21,'2026-05-10',55.00,16.60,19.03,NULL,NULL,NULL,NULL,'2026-05-10 08:44:45',NULL),(26,21,'2026-05-10',56.00,17.10,19.38,NULL,NULL,NULL,NULL,'2026-05-10 08:45:06',NULL),(27,22,'2026-05-12',56.00,12.20,19.15,NULL,NULL,NULL,NULL,'2026-05-12 09:55:43',NULL),(28,22,'2026-05-12',57.00,12.60,19.49,NULL,NULL,NULL,NULL,'2026-05-12 11:20:55',NULL);
/*!40000 ALTER TABLE `body_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exercise_types`
--

DROP TABLE IF EXISTS `exercise_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exercise_types` (
  `exercise_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `exercise_name` varchar(100) NOT NULL,
  `unit` varchar(30) NOT NULL DEFAULT 'minutes',
  `muscle_group` varchar(100) DEFAULT NULL,
  `difficulty` enum('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  `calories_per_unit` decimal(7,3) NOT NULL DEFAULT 0.000,
  `description` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`exercise_id`),
  KEY `idx_exercise_category` (`category_id`),
  CONSTRAINT `fk_exercise_category` FOREIGN KEY (`category_id`) REFERENCES `activity_categories` (`category_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercise_types`
--

LOCK TABLES `exercise_types` WRITE;
/*!40000 ALTER TABLE `exercise_types` DISABLE KEYS */;
INSERT INTO `exercise_types` VALUES (1,1,'Running','minutes',NULL,'beginner',11.400,'Outdoor or treadmill running',NULL),(2,1,'Walking','minutes',NULL,'beginner',3.500,'Brisk walking',NULL),(3,1,'Jump Rope','minutes',NULL,'beginner',12.000,'Skipping rope',NULL),(4,1,'Stair Climbing','minutes',NULL,'beginner',9.500,'Stair climbing machine or actual stairs',NULL),(5,2,'Bench Press','sets',NULL,'beginner',8.000,'Barbell bench press',NULL),(6,2,'Squat','sets',NULL,'beginner',10.000,'Barbell back squat',NULL),(7,2,'Deadlift','sets',NULL,'beginner',9.500,'Conventional deadlift',NULL),(8,2,'Pull-ups','sets',NULL,'beginner',7.000,'Bodyweight pull-ups',NULL),(10,2,'Dumbbell Curl','sets',NULL,'beginner',3.500,'Bicep curls with dumbbells',NULL),(11,3,'Yoga','minutes',NULL,'beginner',3.000,'Yoga flow and poses',NULL),(12,3,'Static Stretching','minutes',NULL,'beginner',2.000,'Hold stretches for 30+ seconds',NULL),(14,4,'Basketball','minutes',NULL,'beginner',8.500,'Full court basketball',NULL),(15,4,'Football','minutes',NULL,'beginner',9.000,'Soccer / football',NULL),(16,4,'Tennis','minutes',NULL,'beginner',7.500,'Singles or doubles tennis',NULL),(17,4,'Badminton','minutes',NULL,'beginner',7.000,'Badminton singles',NULL),(18,5,'Burpees','minutes',NULL,'beginner',14.000,'Full-body HIIT burpees',NULL),(19,5,'Box Jumps','minutes',NULL,'beginner',12.500,'Plyometric box jumps',NULL),(20,5,'Kettlebell Swings','minutes',NULL,'beginner',13.000,'Two-handed kettlebell swings',NULL),(21,6,'Freestyle Swimming','minutes',NULL,'beginner',9.000,'Front crawl in pool',NULL),(22,6,'Backstroke','minutes',NULL,'beginner',7.500,'Backstroke swimming',NULL),(23,6,'Breaststroke','minutes',NULL,'beginner',8.500,'Breaststroke swimming',NULL),(24,7,'Road Cycling','minutes',NULL,'beginner',9.000,'Outdoor road cycling',NULL),(25,7,'Stationary Bike','minutes',NULL,'beginner',7.000,'Indoor exercise bike',NULL),(26,7,'Mountain Biking','minutes',NULL,'beginner',11.000,'Off-road mountain biking',NULL),(27,8,'Boxing','minutes',NULL,'beginner',10.500,'Boxing training & sparring',NULL),(28,8,'Karate','minutes',NULL,'beginner',8.500,'Karate forms and sparring',NULL),(29,8,'Muay Thai','minutes',NULL,'beginner',11.500,'Muay Thai training',NULL),(31,2,'Push ups','per minute of exercise',NULL,'beginner',10.000,NULL,NULL),(32,1,'Push ups','minutes',NULL,'beginner',34.000,NULL,'2026-05-04 13:37:27'),(33,3,'231','minutes',NULL,'beginner',45.000,NULL,NULL);
/*!40000 ALTER TABLE `exercise_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goals`
--

DROP TABLE IF EXISTS `goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goals` (
  `goal_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `goal_type` varchar(60) NOT NULL,
  `target_value` decimal(10,2) NOT NULL,
  `current_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(30) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','completed','expired','paused') NOT NULL DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`goal_id`),
  KEY `idx_goal_user` (`user_id`),
  KEY `idx_goal_status` (`status`),
  CONSTRAINT `fk_goal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goals`
--

LOCK TABLES `goals` WRITE;
/*!40000 ALTER TABLE `goals` DISABLE KEYS */;
INSERT INTO `goals` VALUES (15,18,'Calorie Burn',2163.00,234.00,'kcal','2026-05-03','2026-08-03','active','Auto-calculated based on your registration details.','2026-05-03 09:38:33',NULL),(19,18,'Weight Gain',77.00,69.80,'kg','2026-05-04','2026-05-28','active',NULL,'2026-05-04 21:46:21',NULL),(20,18,'Calorie Burn',6000.00,0.00,'kcal','2026-05-04','2026-08-20','active',NULL,'2026-05-04 21:46:40',NULL),(21,21,'Weight Loss',70.00,65.00,'kg','2026-05-10','2026-05-10','active',NULL,'2026-05-10 08:04:29','2026-05-10 08:44:07'),(22,16,'Maintain Weight',60.00,60.00,'kg','2026-05-10','2026-05-12','active',NULL,'2026-05-10 08:28:35',NULL),(23,21,'Gain Muscle',56.50,56.00,'kg','2026-05-10','2026-05-10','active',NULL,'2026-05-10 08:46:19','2026-05-10 08:46:22'),(24,21,'Improve Fitness',21.00,0.00,'sessions','2026-05-10','2026-05-11','active',NULL,'2026-05-10 08:46:35','2026-05-10 08:46:37'),(25,21,'Gain Muscle',57.00,56.00,'kg','2026-05-10','2026-05-10','active',NULL,'2026-05-10 08:47:24',NULL),(26,22,'Gain Muscle',57.00,57.00,'kg','2026-05-12','2026-05-15','completed',NULL,'2026-05-12 09:56:01',NULL),(27,22,'Gain Muscle',59.00,59.00,'kg','2026-05-12','2026-05-14','completed',NULL,'2026-05-12 11:21:27',NULL);
/*!40000 ALTER TABLE `goals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nutrition_logs`
--

DROP TABLE IF EXISTS `nutrition_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nutrition_logs` (
  `nutrition_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `log_date` date NOT NULL,
  `meal_type` enum('breakfast','lunch','dinner','snack','supplement') NOT NULL DEFAULT 'snack',
  `food_name` varchar(150) NOT NULL,
  `serving_size` varchar(60) DEFAULT NULL,
  `calories` decimal(8,2) NOT NULL DEFAULT 0.00,
  `protein_g` decimal(7,2) NOT NULL DEFAULT 0.00,
  `carbs_g` decimal(7,2) NOT NULL DEFAULT 0.00,
  `fat_g` decimal(7,2) NOT NULL DEFAULT 0.00,
  `fiber_g` decimal(7,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`nutrition_id`),
  KEY `idx_nutrition_user` (`user_id`),
  KEY `idx_nutrition_date` (`log_date`),
  CONSTRAINT `fk_nutrition_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nutrition_logs`
--

LOCK TABLES `nutrition_logs` WRITE;
/*!40000 ALTER TABLE `nutrition_logs` DISABLE KEYS */;
INSERT INTO `nutrition_logs` VALUES (28,18,'2026-05-03','breakfast','Chicken','56GRM',107.00,21.40,0.00,1.80,0.00,NULL,'2026-05-03 10:10:56',NULL),(32,20,'2026-05-04','lunch','Cheese (Cheddar)','1 oz',113.00,7.00,0.40,9.30,0.00,NULL,'2026-05-04 22:24:41',NULL),(33,20,'2026-05-04','lunch','Cheese (Cheddar)','1 oz',113.00,7.00,0.40,9.30,0.00,NULL,'2026-05-04 22:24:45',NULL),(34,18,'2026-05-05','breakfast','Chicken','56GRM',107.00,21.40,0.00,1.80,0.00,NULL,'2026-05-05 07:12:52',NULL),(35,21,'2026-05-10','breakfast','Chicken Breast (Grilled)','100g',165.00,31.00,0.00,3.60,0.00,NULL,'2026-05-10 07:55:37',NULL),(36,22,'2026-05-12','breakfast','Chicken Breast (Grilled)','100g',165.00,31.00,0.00,3.60,0.00,NULL,'2026-05-12 09:55:14',NULL),(37,22,'2026-05-12','breakfast','Chicken Breast (Grilled)','100g',165.00,31.00,0.00,3.60,0.00,NULL,'2026-05-12 09:55:17',NULL),(38,22,'2026-05-12','breakfast','Chicken Breast (Grilled)','100g',165.00,31.00,0.00,3.60,0.00,NULL,'2026-05-12 09:55:20','2026-05-12 11:20:41'),(39,22,'2026-05-12','breakfast','Beef Steak (Sirloin)','100g',206.00,26.00,0.00,10.60,0.00,NULL,'2026-05-12 11:20:15','2026-05-12 11:20:37'),(40,22,'2026-05-12','breakfast','Beef Steak (Sirloin)','100g',206.00,26.00,0.00,10.60,0.00,NULL,'2026-05-12 11:20:32',NULL);
/*!40000 ALTER TABLE `nutrition_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_action` (`action`),
  KEY `idx_logs_date` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=252 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (5,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 00:32:14'),(6,16,'Report Export','Admin exported \'users\' report (CSV).','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 00:37:42'),(7,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 01:01:39'),(8,16,'Report Export','Admin exported \'users\' report (CSV).','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 01:01:55'),(9,16,'Admin Action','Deactivated user ID 14. Reason: In appropriate','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 01:33:12'),(10,16,'System Restore','Restored deleted activity (ID: 56)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 01:33:19'),(11,16,'Admin Action','Restored user ID 14','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 01:33:38'),(12,16,'Admin Action','Deactivated user ID 14. Reason: In appropriate','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 08:10:05'),(13,16,'Admin Action','Restored user ID 14','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 08:10:18'),(14,16,'Admin Action','Deactivated user ID 14. Reason: yu','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 08:10:34'),(15,16,'Admin Action','Restored user ID 14','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 08:10:44'),(16,16,'Report Export','Admin exported \'users\' report (CSV).','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 08:11:11'),(17,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 08:12:27'),(18,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 08:57:09'),(19,16,'Admin Action','Updated user ID 14 role to admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 08:58:03'),(23,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 09:09:55'),(25,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 09:14:19'),(26,16,'Admin Action','Updated user ID 14 role to user','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 09:14:33'),(29,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 09:22:44'),(30,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 09:25:22'),(33,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 09:48:53'),(34,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 10:01:52'),(35,16,'Admin Action','Deactivated user ID 14. Reason: ija','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 10:07:17'),(36,16,'Exercise Deleted','Admin soft-deleted exercise: ','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-27 10:07:30'),(38,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 07:37:31'),(39,16,'Exercise Restored','Admin restored exercise: ','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 07:39:32'),(40,16,'Exercise Restored','Admin restored exercise: ','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 07:39:43'),(41,16,'Admin Action','Updated user ID 13 role to admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 07:41:49'),(42,16,'Admin Action','Updated user ID 13 role to user','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 07:41:55'),(43,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 08:04:33'),(44,16,'User Restore','Restored soft-deleted user (ID: 14) from Delete Logs','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 08:11:27'),(45,16,'Exercise Deleted','Admin soft-deleted exercise: ','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 08:11:53'),(46,16,'Exercise Restore','Restored soft-deleted exercise: ID 3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 08:21:34'),(47,16,'Exercise Deleted','Admin soft-deleted exercise: ','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 08:21:44'),(48,16,'Exercise Restore','Restored soft-deleted exercise: ID 4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 08:22:00'),(49,16,'Exercise Deleted','Admin soft-deleted exercise: ','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 08:23:16'),(50,16,'Permanent Delete','Permanently removed exercise: Foam Rolling and all related activity logs','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 08:23:41'),(51,16,'Admin Action','Updated user ID 17 role to user','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:01:29'),(52,16,'Admin Action','Deleted user ID 17 (soft). Reason: Yes','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:03:34'),(53,16,'Permanent Delete','Permanently removed user (ID: 17) and all associated data','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:07:56'),(54,16,'Exercise Created','Admin added new exercise: Push ups','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:10:26'),(55,16,'Exercise Updated','Admin updated exercise: Push ups','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:10:38'),(56,16,'Exercise Deleted','Admin soft-deleted exercise: ','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:10:57'),(57,16,'Permanent Delete','Permanently removed exercise: Push ups and all related activity logs','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:11:26'),(58,16,'Exercise Deleted','Admin soft-deleted exercise: ','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:15:05'),(59,16,'Permanent Delete','Permanently removed exercise: Push-ups and all related activity logs','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:15:13'),(60,16,'Exercise Created','Admin added new exercise: Push ups','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:15:54'),(61,16,'Admin Action','Updated user ID 14 role to admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:16:29'),(64,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:20:11'),(65,16,'Admin Action','Deleted user ID 14 (soft). Reason: ble','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:20:20'),(66,16,'Permanent Delete','Permanently removed user (ID: 14) and all associated data','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:20:26'),(67,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:22:27'),(68,18,'Account Verification','User verified their email and activated their account.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:38:33'),(69,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:47:30'),(70,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:48:19'),(71,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:50:00'),(72,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 09:51:49'),(73,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 13:11:18'),(74,16,'Admin Action','Updated user ID 18 role to admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 13:11:28'),(75,18,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 13:11:34'),(76,16,'User Login','User successfully logged into the system.','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-03 13:11:52'),(116,18,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 16:13:29'),(117,18,'admin_role_change','Changed user #19 role to admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 16:20:05'),(118,18,'admin_role_change','Changed user #19 role to user','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 16:20:12'),(119,18,'admin_deactivate','Deactivated user #19','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 20:59:12'),(120,18,'admin_deactivate','Deactivated user #13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 21:16:48'),(121,18,'admin_restore','Restored user #13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 21:16:56'),(122,18,'admin_restore','Restored user #19','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 21:16:59'),(123,18,'admin_deactivate','Deactivated user #19','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 21:18:31'),(124,18,'category_delete','Deleted category #3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 21:36:34'),(125,18,'exercise_create','Created exercise','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 21:37:22'),(126,18,'exercise_delete','Deleted exercise #32','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 21:37:27'),(127,18,'category_restore','Restored category #3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 21:43:32'),(128,18,'admin_restore','Restored user #19','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 21:43:38'),(129,18,'goal_create','Created goal','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 21:46:21'),(130,18,'goal_create','Created goal','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 21:46:40'),(131,18,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 21:47:31'),(132,20,'register','New user registered','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 22:23:47'),(133,20,'activity_create','Created activity','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 22:24:00'),(134,20,'activity_complete','Completed activity #67','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 22:24:06'),(135,20,'activity_relog','Re-logged activity #67','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 22:24:12'),(136,20,'nutrition_create','Logged nutrition','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 22:24:41'),(137,20,'nutrition_relog','Re-logged nutrition #32','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 22:24:45'),(138,20,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 22:29:33'),(143,18,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 22:34:37'),(144,18,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 22:35:00'),(145,18,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 22:43:56'),(146,18,'category_delete','Deleted category #7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 23:08:46'),(147,18,'category_restore','Restored category #7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 23:08:56'),(148,18,'exercise_delete','Deleted exercise #3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 23:09:17'),(149,18,'exercise_restore','Restored exercise #3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 23:09:31'),(150,18,'admin_deactivate','Deactivated user #13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 23:09:41'),(151,18,'admin_restore','Restored user #13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 23:09:48'),(152,18,'admin_deactivate','Deactivated user #13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 23:10:00'),(153,18,'admin_permanent_delete','Permanently deleted user #13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 23:10:05'),(154,18,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 23:15:09'),(155,18,'password_reset','User reset their password via email verification','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 23:17:10'),(156,18,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 23:17:15'),(157,18,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-04 23:55:07'),(160,16,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 00:02:38'),(161,16,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 00:03:11'),(162,18,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 00:03:23'),(163,18,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 00:04:56'),(164,18,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:08:55'),(165,18,'admin_deactivate','Deactivated user #19','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:09:21'),(166,18,'admin_permanent_delete','Permanently deleted user #19','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:09:41'),(167,18,'category_delete','Deleted category #1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:09:47'),(168,18,'category_restore','Restored category #1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:09:56'),(169,18,'exercise_delete','Deleted exercise #3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:10:15'),(170,18,'exercise_restore','Restored exercise #3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:10:30'),(171,18,'exercise_create','Created exercise','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:11:54'),(172,18,'activity_create','Created activity','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:12:27'),(173,18,'activity_complete','Completed activity #69','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:12:41'),(174,18,'nutrition_relog','Re-logged nutrition #28','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 07:12:52'),(175,18,'activity_relog','Re-logged activity #69','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 08:18:38'),(176,18,'activity_relog','Re-logged activity #69','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 08:18:43'),(177,18,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-05 08:20:28'),(178,21,'register','New user registered','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 07:50:34'),(179,21,'activity_create','Created activity','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 07:54:15'),(180,21,'activity_complete','Completed activity #72','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 07:54:22'),(181,21,'activity_relog','Re-logged activity #72','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 07:54:36'),(182,21,'nutrition_create','Logged nutrition','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 07:55:37'),(183,21,'metric_create','Logged body metric','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 07:56:58'),(184,21,'metric_delete','Deleted metric #22','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 08:02:14'),(185,21,'metric_create','Logged body metric','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 08:03:45'),(186,21,'goal_create','Created goal','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 08:04:29'),(187,16,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-10 08:10:16'),(188,16,'metric_create','Logged body metric','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-10 08:20:25'),(189,16,'goal_create','Created goal','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-10 08:28:35'),(190,21,'goal_delete','Deleted goal #21','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 08:44:07'),(191,21,'metric_create','Logged body metric','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 08:44:45'),(192,21,'metric_delete','Deleted metric #23','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 08:44:51'),(193,21,'metric_create','Logged body metric','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 08:45:06'),(194,21,'goal_create','Created goal','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 08:46:19'),(195,21,'goal_delete','Deleted goal #23','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 08:46:22'),(196,21,'goal_create','Created goal','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 08:46:35'),(197,21,'goal_delete','Deleted goal #24','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 08:46:37'),(198,21,'goal_create','Created goal','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 08:47:24'),(199,21,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 09:03:32'),(200,18,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 09:03:50'),(201,18,'exercise_delete','Deleted exercise #3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 09:04:24'),(202,18,'exercise_restore','Restored exercise #3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 09:04:59'),(203,18,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-10 09:05:42'),(204,22,'register','New user registered','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:53:37'),(205,22,'activity_create','Created activity','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:54:10'),(206,22,'activity_complete','Completed activity #74','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:54:16'),(207,22,'activity_update','Updated activity #74','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:54:40'),(208,22,'activity_delete','Soft-deleted activity #74','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:55:00'),(209,22,'nutrition_create','Logged nutrition','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:55:14'),(210,22,'nutrition_relog','Re-logged nutrition #36','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:55:17'),(211,22,'nutrition_relog','Re-logged nutrition #37','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:55:20'),(212,22,'metric_create','Logged body metric','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:55:43'),(213,22,'goal_create','Created goal','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:56:01'),(214,22,'goal_complete','Completed goal #26','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:56:10'),(215,22,'activity_create','Created activity','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:57:10'),(216,22,'activity_create','Created activity','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:57:22'),(217,22,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:58:10'),(218,16,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:58:21'),(219,16,'category_create','Created category','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:59:09'),(220,16,'exercise_create','Created exercise','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 09:59:32'),(221,16,'category_delete','Deleted category #9','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 10:00:22'),(222,16,'category_permanent_delete','Permanently deleted category #9','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 10:00:35'),(223,16,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 10:01:01'),(224,22,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:18:08'),(225,22,'activity_create','Created activity','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:19:19'),(226,22,'activity_complete','Completed activity #77','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:19:24'),(227,22,'activity_complete','Completed activity #76','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:19:33'),(228,22,'activity_delete','Soft-deleted activity #77','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:19:39'),(229,22,'activity_update','Updated activity #76','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:19:55'),(230,22,'activity_delete','Soft-deleted activity #75','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:20:02'),(231,22,'nutrition_create','Logged nutrition','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:20:15'),(232,22,'nutrition_relog','Re-logged nutrition #39','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:20:32'),(233,22,'nutrition_delete','Deleted nutrition #39','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:20:37'),(234,22,'nutrition_delete','Deleted nutrition #38','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:20:41'),(235,22,'metric_create','Logged body metric','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:20:55'),(236,22,'goal_create','Created goal','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:21:27'),(237,22,'goal_complete','Completed goal #27','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:21:36'),(238,22,'activity_create','Created activity','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:22:17'),(239,22,'activity_create','Created activity','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:22:26'),(240,22,'activity_complete','Completed activity #79','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:22:32'),(241,22,'activity_complete','Completed activity #78','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:22:37'),(242,22,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:23:25'),(243,16,'login','User logged in','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:23:30'),(244,16,'admin_deactivate','Deactivated user #22','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:23:53'),(245,16,'admin_restore','Restored user #22','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:24:00'),(246,16,'category_create','Created category','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:24:25'),(247,16,'exercise_create','Created exercise','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:24:50'),(248,16,'exercise_delete','Deleted exercise #35','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:25:11'),(249,16,'exercise_permanent_delete','Permanently deleted exercise #35','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:25:20'),(250,16,'category_delete','Deleted category #10','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:25:28'),(251,16,'category_restore','Restored category #10','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-12 11:25:38');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL DEFAULT '',
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL DEFAULT '',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT 'prefer_not_to_say',
  `height_cm` decimal(5,2) DEFAULT NULL,
  `weight_kg` decimal(6,2) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `avatar_url` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deactivation_reason` text DEFAULT NULL,
  `target_weight_kg` decimal(6,2) DEFAULT NULL,
  `daily_calorie_goal` int(10) unsigned DEFAULT NULL,
  `workout_frequency` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (16,'admin','admin@fittrack.com','2026-05-04 23:11:52','$2y$10$OO/wLacAotdENQEudfO7K.MyJDxw8Y8TewDYa3Kf2aQvtuKFDgJae','System',NULL,'Admin',NULL,'prefer_not_to_say',NULL,60.00,'admin',NULL,'2026-04-27 00:31:04','2026-05-10 08:20:25',NULL,NULL,NULL,NULL,NULL),(18,'kenzy','kerrzaragoza43@gmail.com','2026-05-03 09:38:33','$2y$10$wksNtm9DkMrovvARfULwXugIDNtN.lExeMf7n5z/8vflhd/gg6ucO','Kerr',NULL,'Zaragoza','2007-02-06','male',169.80,54.90,'admin',NULL,'2026-05-03 09:38:33','2026-05-04 23:17:10',NULL,NULL,NULL,NULL,NULL),(20,'educify43','educify43@gmail.com','2026-05-04 22:23:47','$2y$10$wZzEFGfe5LaK31X4Z6cblO2kZzYZa1Tv7Uzy5IxpUQRO/vf5/ZoPK','alyzah','','salaon','2006-03-13','prefer_not_to_say',160.00,58.00,'user',NULL,'2026-05-04 22:23:47','2026-05-04 22:23:47',NULL,NULL,NULL,NULL,NULL),(21,'pureskills43','pureskills43@gmail.com','2026-05-10 07:50:34','$2y$10$th6qaiAnVhUCqXIi6dZPMeRNbOTK0GCPn75AFyTIVLbaWjREeayL6','Kerr Xandrex','','Zaragoza','2006-02-16','prefer_not_to_say',170.00,56.00,'user',NULL,'2026-05-10 07:50:34','2026-05-10 08:45:06',NULL,NULL,NULL,NULL,NULL),(22,'pastwonders43','pastwonders43@gmail.com','2026-05-12 09:53:37','$2y$10$mD/2xScNjjiLv/uyJm4RD.YukTBA/Jtm8DQWqxZJRecnu/vRaJT4C','Kerr Xandrex','','Zaragoza','2026-05-07','prefer_not_to_say',171.00,57.00,'user',NULL,'2026-05-12 09:53:37','2026-05-12 11:24:00',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verification_codes`
--

DROP TABLE IF EXISTS `verification_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `verification_codes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(120) NOT NULL,
  `code` varchar(6) NOT NULL,
  `type` enum('registration','password_reset') NOT NULL DEFAULT 'registration',
  `payload` text DEFAULT NULL COMMENT 'JSON-encoded registration data',
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vc_email` (`email`),
  KEY `idx_vc_code` (`code`),
  KEY `idx_vc_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification_codes`
--

LOCK TABLES `verification_codes` WRITE;
/*!40000 ALTER TABLE `verification_codes` DISABLE KEYS */;
INSERT INTO `verification_codes` VALUES (1,'kerrzaragoza43@gmail.com','728603','registration','{\"first_name\":\"Kerr Xandrex\",\"middle_name\":\"Chua\",\"last_name\":\"Zaragoza\",\"username\":\"Kerr\",\"email\":\"kerrzaragoza43@gmail.com\",\"password\":\"12345678\",\"dob\":\"2006-09-06\",\"gender\":\"male\",\"height_cm\":171,\"weight_kg\":59.7}',0,'2026-04-20 09:07:38','2026-04-20 08:59:02','2026-04-20 08:57:38'),(2,'kerrzaragoza43@gmail.com','554626','registration','{\"first_name\":\"Kerr Xandrex\",\"middle_name\":\"Chua\",\"last_name\":\"Zaragoza\",\"username\":\"Kerr\",\"email\":\"kerrzaragoza43@gmail.com\",\"password\":\"12345678\",\"dob\":\"2006-09-06\",\"gender\":\"male\",\"height_cm\":171,\"weight_kg\":59.7}',0,'2026-04-20 09:09:02','2026-04-20 09:07:02','2026-04-20 08:59:02'),(3,'kerrzaragoza43@gmail.com','092726','registration','{\"first_name\":\"Kerr Xandrex\",\"middle_name\":\"Chua\",\"last_name\":\"Zaragoza\",\"username\":\"Kerr\",\"email\":\"kerrzaragoza43@gmail.com\",\"password\":\"12345678\",\"dob\":null,\"gender\":\"prefer_not_to_say\",\"height_cm\":170,\"weight_kg\":65}',0,'2026-04-20 09:17:02','2026-04-20 09:07:32','2026-04-20 09:07:02'),(4,'kerrzaragoza43@gmail.com','834807','registration','{\"first_name\":\"Kerr Xandrex\",\"middle_name\":\"Chua\",\"last_name\":\"Zaragoza\",\"username\":\"Kerr\",\"email\":\"kerrzaragoza43@gmail.com\",\"password\":\"12345678\",\"dob\":null,\"gender\":\"prefer_not_to_say\",\"height_cm\":170,\"weight_kg\":65}',0,'2026-04-20 09:25:58','2026-04-20 09:16:13','2026-04-20 09:15:58'),(5,'kerrnazeward@gmail.com','271138','registration','{\"first_name\":\"Roy\",\"middle_name\":null,\"last_name\":\"Villanueva\",\"username\":\"Roy\",\"email\":\"kerrnazeward@gmail.com\",\"password\":\"12345678\",\"dob\":null,\"gender\":\"prefer_not_to_say\",\"height_cm\":170,\"weight_kg\":70}',0,'2026-04-20 09:27:47','2026-04-20 09:18:11','2026-04-20 09:17:47'),(6,'educify43@gmail.com','650193','registration','{\"first_name\":\"jaji\",\"middle_name\":null,\"last_name\":\"ki\",\"username\":\"jaji\",\"email\":\"educify43@gmail.com\",\"password\":\"12345678\",\"dob\":null,\"gender\":\"prefer_not_to_say\",\"height_cm\":160,\"weight_kg\":56}',0,'2026-04-20 09:36:09','2026-04-20 09:26:37','2026-04-20 09:26:09'),(7,'pureskills43@gmail.com','249412','registration','{\"first_name\":\"kwr\",\"middle_name\":null,\"last_name\":\"kaer\",\"username\":\"kier\",\"email\":\"pureskills43@gmail.com\",\"password\":\"12345678\",\"dob\":null,\"gender\":\"prefer_not_to_say\",\"height_cm\":170,\"weight_kg\":61}',0,'2026-04-20 09:57:47','2026-04-20 09:48:02','2026-04-20 09:47:47'),(8,'johndoe@example.com','126215','registration','{\"first_name\":\"John\",\"middle_name\":null,\"last_name\":\"Doe\",\"username\":\"johndoe\",\"email\":\"johndoe@example.com\",\"password\":\"password123\",\"dob\":null,\"gender\":\"prefer_not_to_say\",\"height_cm\":175,\"weight_kg\":70}',0,'2026-04-26 09:48:19','2026-04-26 09:40:50','2026-04-26 09:38:19'),(9,'johndoe@example.com','426212','registration','{\"first_name\":\"John\",\"middle_name\":null,\"last_name\":\"Doe\",\"username\":\"johndoe\",\"email\":\"johndoe@example.com\",\"password\":\"password123\",\"dob\":null,\"gender\":\"prefer_not_to_say\",\"height_cm\":175,\"weight_kg\":70}',0,'2026-04-26 09:50:50','2026-04-26 09:45:56','2026-04-26 09:40:50'),(10,'test@example.com','379351','registration','{\"first_name\":\"Test\",\"middle_name\":null,\"last_name\":\"User\",\"username\":\"testuser\",\"email\":\"test@example.com\",\"password\":\"password123\",\"dob\":null,\"gender\":\"prefer_not_to_say\",\"height_cm\":180,\"weight_kg\":80}',0,'2026-04-26 10:38:27',NULL,'2026-04-26 10:28:27'),(11,'kerrzaragoza43@gmail.com','464490','registration','{\"first_name\":\"Kerr Xandrex\",\"middle_name\":null,\"last_name\":\"Zaragoza\",\"username\":\"xliq43\",\"email\":\"kerrzaragoza43@gmail.com\",\"password\":\"12345678\",\"dob\":null,\"gender\":\"prefer_not_to_say\",\"height_cm\":171,\"weight_kg\":55}',0,'2026-04-26 11:50:09','2026-04-26 11:44:06','2026-04-26 11:40:09'),(12,'kerrzaragoza43@gmail.com','266879','registration','{\"first_name\":\"Kerr Xandrex\",\"middle_name\":null,\"last_name\":\"Zaragoza\",\"username\":\"xliq43\",\"email\":\"kerrzaragoza43@gmail.com\",\"password\":\"12345678\",\"dob\":null,\"gender\":\"prefer_not_to_say\",\"height_cm\":171,\"weight_kg\":55}',0,'2026-04-26 11:54:06','2026-04-26 11:59:35','2026-04-26 11:44:06'),(13,'kerrzaragoza43@gmail.com','633513','registration','{\"first_name\":\"Kerr Xandrex\",\"middle_name\":null,\"last_name\":\"Zaragoza\",\"username\":\"xliq43\",\"email\":\"kerrzaragoza43@gmail.com\",\"password\":\"12345678\",\"dob\":null,\"gender\":\"prefer_not_to_say\",\"height_cm\":171,\"weight_kg\":55}',0,'2026-04-26 12:02:35','2026-04-26 12:19:31','2026-04-26 11:59:35'),(14,'kerrzaragoza43@gmail.com','917681','registration','{\"first_name\":\"Kerr Xandrex\",\"middle_name\":null,\"last_name\":\"Zaragoza\",\"username\":\"xliq43\",\"email\":\"kerrzaragoza43@gmail.com\",\"password\":\"12345678\",\"dob\":null,\"gender\":\"prefer_not_to_say\",\"height_cm\":171,\"weight_kg\":55}',0,'2026-04-26 12:22:31','2026-04-26 12:20:26','2026-04-26 12:19:31'),(15,'admintest@example.com','683475','registration','{\"first_name\":\"Admin\",\"middle_name\":null,\"last_name\":\"Test\",\"username\":\"admintest\",\"email\":\"admintest@example.com\",\"password\":\"Password123!\",\"date_of_birth\":\"1990-01-01\",\"gender\":\"male\",\"fitness_goal\":\"build\",\"height_cm\":175,\"weight_kg\":70}',0,'2026-04-26 23:11:26',NULL,'2026-04-26 23:08:26'),(16,'kerrzaragoza43@gmail.com','224781','registration','{\"first_name\":\"Kerr\",\"middle_name\":null,\"last_name\":\"Zaragoza\",\"username\":\"kenzy\",\"email\":\"kerrzaragoza43@gmail.com\",\"password\":\"12345678\",\"date_of_birth\":\"2007-03-16\",\"gender\":\"male\",\"fitness_goal\":\"build\",\"height_cm\":175,\"weight_kg\":54.9}',0,'2026-05-03 09:27:45','2026-05-03 09:38:33','2026-05-03 09:24:45'),(17,'tester777777@gmail.com','785702','registration','{\"first_name\":\"Test\",\"middle_name\":null,\"last_name\":\"User\",\"username\":\"tester777777\",\"email\":\"tester777777@gmail.com\",\"password\":\"password123\",\"date_of_birth\":\"1990-01-01\",\"gender\":\"male\",\"fitness_goal\":\"build\",\"height_cm\":180,\"weight_kg\":80}',0,'2026-05-03 10:02:24',NULL,'2026-05-03 09:59:24'),(18,'testuser@example.com','341618','registration','{\"first_name\":\"Test\",\"middle_name\":null,\"last_name\":\"User\",\"username\":\"testuser\",\"email\":\"testuser@example.com\",\"password\":\"Password123!\",\"date_of_birth\":\"1990-01-01\",\"gender\":\"male\",\"fitness_goal\":\"maintain\",\"height_cm\":180,\"weight_kg\":80}',0,'2026-05-03 10:36:01','2026-05-03 11:03:51','2026-05-03 10:33:01'),(19,'testuser@example.com','870686','registration','{\"first_name\":\"Test\",\"middle_name\":null,\"last_name\":\"User\",\"username\":\"testuser\",\"email\":\"testuser@example.com\",\"password\":\"Password123!\",\"date_of_birth\":\"1990-01-01\",\"gender\":\"male\",\"fitness_goal\":\"maintain\",\"height_cm\":180,\"weight_kg\":80}',0,'2026-05-03 11:06:51',NULL,'2026-05-03 11:03:51'),(20,'pureskills43@gmail.com','952159','registration','{\"username\":\"pureskills43\",\"email\":\"pureskills43@gmail.com\",\"password_hash\":\"$2y$10$NKb1s8CDScaxDjZ8JSsUBeeOs.f5GPHKryLYGZq0VFMDS4Nolj\\/Mi\",\"first_name\":\"Kerr Xandrex\",\"middle_name\":\"\",\"last_name\":\"Zaragoza\",\"height_cm\":171,\"weight_kg\":55}',0,'2026-05-04 13:17:26',NULL,'2026-05-04 13:14:26'),(21,'notdabbi43@gmail.com','675718','registration','{\"username\":\"notdabbi43\",\"email\":\"notdabbi43@gmail.com\",\"password_hash\":\"$2y$10$yrbz8ZZ4ENdiJWgDIrKQR.Ox2t9Czw6NPEEGFIU3bSg4\\/hHxTFk\\/q\",\"first_name\":\"Kerr Xandrex\",\"middle_name\":\"\",\"last_name\":\"Zaragoza\",\"height_cm\":171,\"weight_kg\":65}',0,'2026-05-04 13:24:43','2026-05-04 13:22:08','2026-05-04 13:21:43'),(22,'testuser2@example.com','871431','registration','{\"username\":\"testuser2\",\"email\":\"testuser2@example.com\",\"password_hash\":\"$2y$10$Z2DMmbC4PGCSGiZeYx421eCdTCAA7eZ8diCdFVZQk5IlizNoJhqre\",\"first_name\":\"Test\",\"middle_name\":\"User\",\"last_name\":\"Account\",\"date_of_birth\":\"1990-01-01\",\"primary_goal\":\"Lose Weight\",\"height_cm\":180,\"weight_kg\":80}',0,'2026-05-04 13:52:22',NULL,'2026-05-04 13:49:22'),(23,'educify43@gmail.com','989392','registration','{\"username\":\"educify43\",\"email\":\"educify43@gmail.com\",\"password_hash\":\"$2y$10$7wwOg3d5xdAGD1\\/LWfsJIO1uN.1Kxl5T2c7wnXo8dZ0B3XqAJnTHi\",\"first_name\":\"alyzah\",\"middle_name\":\"\",\"last_name\":\"salaon\",\"date_of_birth\":\"2004-07-08\",\"primary_goal\":\"Lose Weight\",\"height_cm\":158,\"weight_kg\":60}',0,'2026-05-04 21:51:36','2026-05-04 21:49:13','2026-05-04 21:48:36'),(24,'educify43@gmail.com','370401','registration','{\"username\":\"educify43\",\"email\":\"educify43@gmail.com\",\"password_hash\":\"$2y$10$wZzEFGfe5LaK31X4Z6cblO2kZzYZa1Tv7Uzy5IxpUQRO\\/vf5\\/ZoPK\",\"first_name\":\"alyzah\",\"middle_name\":\"\",\"last_name\":\"salaon\",\"date_of_birth\":\"2006-03-13\",\"primary_goal\":\"Lose Weight\",\"height_cm\":160,\"weight_kg\":58}',0,'2026-05-04 22:26:23','2026-05-04 22:23:47','2026-05-04 22:23:23'),(25,'kerrzaragoza43@gmail.com','852676','password_reset',NULL,1,'2026-05-04 23:18:26','2026-05-04 23:15:59','2026-05-04 23:15:26'),(26,'testuser@example.com','448562','registration','{\"username\":\"testuser\",\"email\":\"testuser@example.com\",\"password_hash\":\"$2y$10$tP2Y2brWmAQe8WXde586UeiooNiX\\/rsEn9TFtVzu2DUICrapf2gT2\",\"first_name\":\"Test\",\"middle_name\":\"\",\"last_name\":\"User\",\"date_of_birth\":\"1990-01-01\",\"primary_goal\":\"Lose Weight\",\"height_cm\":180,\"weight_kg\":80}',0,'2026-05-05 00:05:17',NULL,'2026-05-05 00:02:17'),(27,'notdabbi43@gmail.com','084894','registration','{\"username\":\"notdabbi43\",\"email\":\"notdabbi43@gmail.com\",\"password_hash\":\"$2y$10$bANVNWs4e6HR.NqPpp\\/Ih.Ve65A.OBcE5wB\\/I64fXe4Npt1UzrdHa\",\"first_name\":\"Kurt\",\"middle_name\":\"\",\"last_name\":\"Ortega\",\"date_of_birth\":\"2010-10-22\",\"primary_goal\":\"Gain Muscle\",\"height_cm\":177,\"weight_kg\":68}',0,'2026-05-05 08:24:23',NULL,'2026-05-05 08:21:23'),(28,'pureskills43@gmail.com','575910','registration','{\"username\":\"pureskills43\",\"email\":\"pureskills43@gmail.com\",\"password_hash\":\"$2y$10$th6qaiAnVhUCqXIi6dZPMeRNbOTK0GCPn75AFyTIVLbaWjREeayL6\",\"first_name\":\"Kerr Xandrex\",\"middle_name\":\"\",\"last_name\":\"Zaragoza\",\"date_of_birth\":\"2006-02-16\",\"primary_goal\":\"Improve Fitness\",\"height_cm\":170,\"weight_kg\":60}',0,'2026-05-10 07:53:05','2026-05-10 07:50:34','2026-05-10 07:50:05'),(29,'pastwonders43@gmail.com','890441','registration','{\"username\":\"pastwonders43\",\"email\":\"pastwonders43@gmail.com\",\"password_hash\":\"$2y$10$mD\\/2xScNjjiLv\\/uyJm4RD.YukTBA\\/Jtm8DQWqxZJRecnu\\/vRaJT4C\",\"first_name\":\"Kerr Xandrex\",\"middle_name\":\"\",\"last_name\":\"Zaragoza\",\"date_of_birth\":\"2026-05-07\",\"primary_goal\":\"Gain Muscle\",\"height_cm\":171,\"weight_kg\":55}',0,'2026-05-12 09:56:01','2026-05-12 09:53:37','2026-05-12 09:53:01');
/*!40000 ALTER TABLE `verification_codes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-12 11:46:29
