-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: vitalsync
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
-- Table structure for table `activity_goals`
--

DROP TABLE IF EXISTS `activity_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_goals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `daily_step_goal` int(11) DEFAULT 8000,
  `daily_calorie_goal` int(11) DEFAULT 500,
  `daily_active_minutes_goal` int(11) DEFAULT 45,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_activity_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_goals`
--

LOCK TABLES `activity_goals` WRITE;
/*!40000 ALTER TABLE `activity_goals` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_goals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_sessions`
--

DROP TABLE IF EXISTS `activity_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `duration_seconds` int(11) NOT NULL,
  `distance_km` decimal(6,2) DEFAULT 0.00,
  `calories_burned` int(11) DEFAULT 0,
  `avg_pace` varchar(20) DEFAULT NULL,
  `avg_heart_rate` int(11) DEFAULT NULL,
  `steps` int(11) DEFAULT 0,
  `start_time` datetime NOT NULL DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_activity_user_time` (`user_id`,`start_time`),
  CONSTRAINT `fk_activity_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_sessions`
--

LOCK TABLES `activity_sessions` WRITE;
/*!40000 ALTER TABLE `activity_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_chat_messages`
--

DROP TABLE IF EXISTS `ai_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_chat_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `sender` varchar(20) NOT NULL,
  `message_text` text NOT NULL,
  `time_text` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ai_user` (`user_id`,`created_at`),
  CONSTRAINT `fk_ai_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_chat_messages`
--

LOCK TABLES `ai_chat_messages` WRITE;
/*!40000 ALTER TABLE `ai_chat_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `connected_devices`
--

DROP TABLE IF EXISTS `connected_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `connected_devices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_type` varchar(50) DEFAULT 'Smart Watch',
  `is_connected` tinyint(1) DEFAULT 1,
  `battery_level` int(11) DEFAULT 88,
  `last_synced` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_devices_user` (`user_id`),
  CONSTRAINT `fk_devices_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `connected_devices`
--

LOCK TABLES `connected_devices` WRITE;
/*!40000 ALTER TABLE `connected_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `connected_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exercises`
--

DROP TABLE IF EXISTS `exercises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exercises` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `target_muscle` varchar(100) NOT NULL,
  `default_weight_kg` decimal(5,2) DEFAULT 20.00,
  `default_reps` int(11) DEFAULT 10,
  `notes` text DEFAULT NULL,
  `is_custom` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_exercise_user` (`user_id`),
  CONSTRAINT `fk_exercises_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exercises`
--

LOCK TABLES `exercises` WRITE;
/*!40000 ALTER TABLE `exercises` DISABLE KEYS */;
INSERT INTO `exercises` VALUES (1,NULL,'e1','Barbell Bench Press','Chest',60.00,10,'Keep shoulder blades retracted and feet planted.',0,'2026-10-27 08:59:26'),(2,NULL,'e2','Incline Dumbbell Press','Upper Chest',22.00,10,'Focus on upper chest contraction at top.',0,'2026-10-27 08:59:26'),(3,NULL,'e3','Overhead Barbell Press','Shoulders',40.00,8,'Core braced, press vertically overhead.',0,'2026-10-27 08:59:26'),(4,NULL,'e4','Triceps Rope Pushdown','Triceps',25.00,12,'Keep elbows stationary at sides.',0,'2026-10-27 08:59:26'),(5,NULL,'e5','Incline Chest Fly','Chest',16.00,12,'Slight bend in elbows throughout movement.',0,'2026-10-27 08:59:26'),(6,NULL,'e6','Lateral Raises','Side Deltoids',10.00,15,'Lead with elbows for side shoulder isolation.',0,'2026-10-27 08:59:26'),(7,NULL,'e7','Barbell Deadlift','Back & Posture',100.00,5,'Flat spine, drive through heels.',0,'2026-10-27 08:59:26'),(8,NULL,'e8','Lat Pulldown','Lats',50.00,10,'Pull bar to upper chest squeezing lats.',0,'2026-10-27 08:59:26'),(9,NULL,'e9','Seated Cable Row','Mid Back',45.00,12,'Keep torso upright and squeeze shoulder blades.',0,'2026-10-27 08:59:26'),(10,NULL,'e10','Dumbbell Bicep Curls','Biceps',14.00,12,'Control eccentric negative movement.',0,'2026-10-27 08:59:26'),(11,NULL,'e11','Face Pulls','Rear Delts',20.00,15,'Pull rope towards forehead, flare elbows.',0,'2026-10-27 08:59:26'),(12,NULL,'e12','Barbell Back Squat','Quadriceps',80.00,8,'Break at hips, squat to parallel depth.',0,'2026-10-27 08:59:26'),(13,NULL,'e13','Romanian Deadlift','Hamstrings',70.00,10,'Hinge at hips feeling hamstring stretch.',0,'2026-10-27 08:59:26'),(14,NULL,'e14','Leg Press','Quadriceps & Glutes',140.00,12,'Controlled depth without lower back rounding.',0,'2026-10-27 08:59:26'),(15,NULL,'e15','Standing Calf Raises','Calves',50.00,15,'Full stretch at bottom, squeeze at peak.',0,'2026-10-27 08:59:26');
/*!40000 ALTER TABLE `exercises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `food_items`
--

DROP TABLE IF EXISTS `food_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `food_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'VEG',
  `serving_size_amount` decimal(6,2) NOT NULL,
  `serving_unit` varchar(50) NOT NULL,
  `calories` int(11) NOT NULL,
  `protein_grams` decimal(6,2) NOT NULL,
  `carbs_grams` decimal(6,2) NOT NULL,
  `fat_grams` decimal(6,2) NOT NULL,
  `is_custom` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_food_user` (`user_id`),
  CONSTRAINT `fk_food_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `food_items`
--

LOCK TABLES `food_items` WRITE;
/*!40000 ALTER TABLE `food_items` DISABLE KEYS */;
INSERT INTO `food_items` VALUES (1,NULL,'f1','Paneer Tikka (Grilled Cottage Cheese)','VEG',100.00,'100g (4 pieces)',260,18.00,6.00,18.00,0,'2026-10-27 08:59:26'),(2,NULL,'f2','Roti / Phulka (Whole Wheat Flatbread)','VEG',30.00,'1 piece (30g)',80,2.80,15.00,0.80,0,'2026-10-27 08:59:26'),(3,NULL,'f3','Chicken Breast Curry (Home Cooked)','NON_VEG',150.00,'150g (1 bowl)',220,28.00,4.00,10.00,0,'2026-10-27 08:59:26'),(4,NULL,'f4','Egg Bhurji (Scrambled Eggs with Spices)','EGG',100.00,'2 eggs (100g)',170,13.00,2.50,12.00,0,'2026-10-27 08:59:26'),(5,NULL,'f5','Dal Tadka (Cooked Yellow Lentils)','VEG',150.00,'150g (1 katori)',140,7.50,18.00,4.20,0,'2026-10-27 08:59:26'),(6,NULL,'f6','Steamed Basmati Rice','VEG',150.00,'150g (1 bowl)',195,3.80,42.00,0.60,0,'2026-10-27 08:59:26'),(7,NULL,'f7','Greek Yogurt (Hung Curd)','VEG',100.00,'100g (1 cup)',95,10.00,4.50,4.00,0,'2026-10-27 08:59:26'),(8,NULL,'f8','Whey Protein Scoop in Water','VEG',30.00,'1 scoop (30g)',120,24.00,2.00,1.50,0,'2026-10-27 08:59:26'),(9,NULL,'f9','Masala Dosa with Chutney','VEG',150.00,'1 dosa (150g)',320,6.00,48.00,11.00,0,'2026-10-27 08:59:26'),(10,NULL,'f10','Idli with Sambar (2 pieces)','VEG',120.00,'2 idlis + sambar',180,6.50,36.00,1.20,0,'2026-10-27 08:59:26'),(11,NULL,'f11','Moong Dal Chilla (Lentil Pancake)','VEG',80.00,'1 chilla (80g)',130,8.00,18.00,3.00,0,'2026-10-27 08:59:26'),(12,NULL,'f12','Chana Masala (Chickpea Curry)','VEG',150.00,'150g (1 bowl)',210,9.00,32.00,5.00,0,'2026-10-27 08:59:26');
/*!40000 ALTER TABLE `food_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `health_permissions`
--

DROP TABLE IF EXISTS `health_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `health_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `vital_signs` tinyint(1) DEFAULT 1,
  `activity` tinyint(1) DEFAULT 1,
  `sleep` tinyint(1) DEFAULT 1,
  `hydration` tinyint(1) DEFAULT 0,
  `write_health_data` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `health_permissions`
--

LOCK TABLES `health_permissions` WRITE;
/*!40000 ALTER TABLE `health_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `health_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hydration_goals`
--

DROP TABLE IF EXISTS `hydration_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hydration_goals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `daily_target_ml` int(11) DEFAULT 2000,
  `reminders_enabled` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_hydration_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hydration_goals`
--

LOCK TABLES `hydration_goals` WRITE;
/*!40000 ALTER TABLE `hydration_goals` DISABLE KEYS */;
/*!40000 ALTER TABLE `hydration_goals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hydration_logs`
--

DROP TABLE IF EXISTS `hydration_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hydration_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `date_string` date NOT NULL,
  `amount_ml` int(11) NOT NULL,
  `logged_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_hydration_user_date` (`user_id`,`date_string`),
  CONSTRAINT `fk_hydration_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hydration_logs`
--

LOCK TABLES `hydration_logs` WRITE;
/*!40000 ALTER TABLE `hydration_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `hydration_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_sessions`
--

DROP TABLE IF EXISTS `login_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_os` varchar(50) DEFAULT 'Android',
  `ip_address` varchar(45) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `last_active` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_sessions`
--

LOCK TABLES `login_sessions` WRITE;
/*!40000 ALTER TABLE `login_sessions` DISABLE KEYS */;
INSERT INTO `login_sessions` VALUES (1,2,'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-IN) WindowsPowerShell/5.1.26100.9711','Android','127.0.0.1',NULL,1,'2026-10-27 08:04:15','2026-10-27 08:04:15'),(2,2,'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-IN) WindowsPowerShell/5.1.26100.9711','Android','127.0.0.1',NULL,1,'2026-10-27 08:07:35','2026-10-27 08:07:35'),(3,4,'Pixel 7 Pro','Android','::1',NULL,1,'2026-10-27 08:21:58','2026-10-27 08:21:58');
/*!40000 ALTER TABLE `login_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meal_entries`
--

DROP TABLE IF EXISTS `meal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meal_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `food_item_id` int(10) unsigned DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` decimal(5,2) NOT NULL DEFAULT 1.00,
  `calculated_calories` int(11) NOT NULL,
  `calculated_protein` decimal(6,2) NOT NULL,
  `calculated_carbs` decimal(6,2) NOT NULL,
  `calculated_fat` decimal(6,2) NOT NULL,
  `date_string` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_meal_user_date` (`user_id`,`date_string`),
  CONSTRAINT `fk_meal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meal_entries`
--

LOCK TABLES `meal_entries` WRITE;
/*!40000 ALTER TABLE `meal_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `meal_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(50) DEFAULT 'BELL',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`is_read`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nutrition_goals`
--

DROP TABLE IF EXISTS `nutrition_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nutrition_goals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `age` int(11) DEFAULT 28,
  `weight_kg` decimal(5,2) DEFAULT 68.50,
  `height_cm` decimal(5,2) DEFAULT 175.00,
  `activity_level` varchar(50) DEFAULT 'Moderately Active',
  `fitness_goal` varchar(50) DEFAULT 'MUSCLE_GAIN',
  `dietary_preference` varchar(50) DEFAULT 'NON_VEGETARIAN',
  `daily_calorie_target` int(11) DEFAULT 2200,
  `daily_protein_target` decimal(6,2) DEFAULT 130.00,
  `daily_carbs_target` decimal(6,2) DEFAULT 250.00,
  `daily_fat_target` decimal(6,2) DEFAULT 65.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_nutrition_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nutrition_goals`
--

LOCK TABLES `nutrition_goals` WRITE;
/*!40000 ALTER TABLE `nutrition_goals` DISABLE KEYS */;
/*!40000 ALTER TABLE `nutrition_goals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reminders`
--

DROP TABLE IF EXISTS `reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reminders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(100) NOT NULL,
  `time` varchar(20) NOT NULL,
  `frequency` varchar(50) DEFAULT 'Every day',
  `repeat_days` varchar(50) DEFAULT '0,1,2,3,4,5,6',
  `sound_vibration` tinyint(1) DEFAULT 1,
  `is_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_reminder_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reminders`
--

LOCK TABLES `reminders` WRITE;
/*!40000 ALTER TABLE `reminders` DISABLE KEYS */;
INSERT INTO `reminders` VALUES (2,2,'Exercise','06:30 PM','Mon, Wed, Fri','1,3,5',1,1,'2026-10-27 08:10:00','2026-10-27 08:10:00'),(3,2,'Sleep','10:30 PM','Every day','0,1,2,3,4,5,6',1,1,'2026-10-27 08:10:00','2026-10-27 08:10:00'),(4,2,'Medication','09:00 AM','Every day','0,1,2,3,4,5,6',1,1,'2026-10-27 08:10:00','2026-10-27 08:10:00'),(6,2,'Evening Meditation','09:00 PM','Every day','0,1,2,3,4,5,6',1,1,'2026-10-27 08:19:04','2026-10-27 08:19:04');
/*!40000 ALTER TABLE `reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sleep_records`
--

DROP TABLE IF EXISTS `sleep_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sleep_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `date_string` date NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `sleep_score` int(11) DEFAULT 80,
  `quality_label` varchar(50) DEFAULT 'Restful',
  `bedtime` varchar(20) DEFAULT '10:30 PM',
  `wake_time` varchar(20) DEFAULT '06:15 AM',
  `deep_sleep_minutes` int(11) DEFAULT 95,
  `light_sleep_minutes` int(11) DEFAULT 250,
  `rem_sleep_minutes` int(11) DEFAULT 85,
  `awake_minutes` int(11) DEFAULT 22,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_sleep_user_date` (`user_id`,`date_string`),
  CONSTRAINT `fk_sleep_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sleep_records`
--

LOCK TABLES `sleep_records` WRITE;
/*!40000 ALTER TABLE `sleep_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `sleep_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `dob` varchar(50) DEFAULT NULL,
  `age` int(3) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `activity_level` varchar(50) DEFAULT NULL,
  `primary_goal` varchar(100) DEFAULT 'Fitness',
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'nitesh','nitesvgho132@gmail.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Fitness','$2y$10$YYNtZrphCXPCvJtPuWMRB.4VqL2NCQwQy6NaRjbZ2R0wO4w757ybm','2026-10-26 04:38:17','2026-10-27 08:01:54','uploads/profiles/user_1_0d5dfe7246e3b986.jpg'),(2,'Alex Morgan','alex.morgan@email.com','+1 (202) 555-0147','14 May 1996',30,'Male',175.00,68.50,'Moderately active','Improve Fitness','$2y$10$//yhc1OmTTVTy6kgdQktG.YgGqMqpzXJezSVyCuZ1qd2gLE79KKM6','2026-10-27 08:03:22','2026-10-27 08:23:53','uploads/profiles/user_2_29c0e56edb733110.jpg'),(3,'Alex Morgan','alex.morgan.20261027@email.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Fitness','$2y$10$DJdm.OTwpUYIptmC2FF6yeRMkIBcXVJuSbivfdbJSuanB592Jh/CW','2026-10-27 08:20:15','2026-10-27 08:20:15',NULL),(4,'Alex Morgan','alex.morgan.t0820z7@email.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Fitness','$2y$10$8r2fBWD1uLygo4hmTvlR/uDKJMxEWMcJTLBUPgc0WyawF8NN7Er4O','2026-10-27 08:21:15','2026-10-27 08:21:15',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vitals_readings`
--

DROP TABLE IF EXISTS `vitals_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vitals_readings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `reading_type` varchar(50) NOT NULL,
  `value_numeric` decimal(8,2) DEFAULT NULL,
  `value_secondary` decimal(8,2) DEFAULT NULL,
  `value_text` varchar(50) DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `status_label` varchar(50) DEFAULT 'Normal',
  `source` varchar(100) DEFAULT 'Manual Entry',
  `notes` text DEFAULT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vitals_user_type` (`user_id`,`reading_type`),
  KEY `idx_vitals_recorded` (`recorded_at`),
  CONSTRAINT `fk_vitals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vitals_readings`
--

LOCK TABLES `vitals_readings` WRITE;
/*!40000 ALTER TABLE `vitals_readings` DISABLE KEYS */;
/*!40000 ALTER TABLE `vitals_readings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workout_exercise_sets`
--

DROP TABLE IF EXISTS `workout_exercise_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workout_exercise_sets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_exercise_id` int(10) unsigned NOT NULL,
  `set_number` int(11) NOT NULL,
  `reps` int(11) NOT NULL,
  `weight_kg` decimal(6,2) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_set_ex` (`session_exercise_id`),
  CONSTRAINT `fk_set_session_ex` FOREIGN KEY (`session_exercise_id`) REFERENCES `workout_session_exercises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workout_exercise_sets`
--

LOCK TABLES `workout_exercise_sets` WRITE;
/*!40000 ALTER TABLE `workout_exercise_sets` DISABLE KEYS */;
/*!40000 ALTER TABLE `workout_exercise_sets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workout_session_exercises`
--

DROP TABLE IF EXISTS `workout_session_exercises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workout_session_exercises` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workout_session_id` int(10) unsigned NOT NULL,
  `exercise_id` int(10) unsigned DEFAULT NULL,
  `exercise_name` varchar(150) NOT NULL,
  `target_muscle` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session_ex` (`workout_session_id`),
  CONSTRAINT `fk_session_ex` FOREIGN KEY (`workout_session_id`) REFERENCES `workout_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workout_session_exercises`
--

LOCK TABLES `workout_session_exercises` WRITE;
/*!40000 ALTER TABLE `workout_session_exercises` DISABLE KEYS */;
/*!40000 ALTER TABLE `workout_session_exercises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workout_sessions`
--

DROP TABLE IF EXISTS `workout_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workout_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `session_name` varchar(150) NOT NULL,
  `split_type` varchar(50) NOT NULL,
  `date_string` date NOT NULL,
  `timestamp_formatted` varchar(100) NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 45,
  `total_volume_kg` decimal(8,2) DEFAULT 0.00,
  `estimated_calories_burned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_workout_user` (`user_id`,`date_string`),
  CONSTRAINT `fk_workout_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workout_sessions`
--

LOCK TABLES `workout_sessions` WRITE;
/*!40000 ALTER TABLE `workout_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `workout_sessions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-27 14:30:48
