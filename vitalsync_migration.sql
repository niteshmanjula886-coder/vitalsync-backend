-- ========================================================
-- VitalSync Database Migration Script
-- Target Database: vitalsync
-- Preserves existing tables and data (users, password_resets)
-- ========================================================

USE `vitalsync`;

-- 1. Extend users table with profile fields
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `phone` varchar(20) DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `dob` varchar(50) DEFAULT NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `age` int(3) DEFAULT NULL AFTER `dob`,
  ADD COLUMN IF NOT EXISTS `gender` varchar(20) DEFAULT NULL AFTER `age`,
  ADD COLUMN IF NOT EXISTS `height` decimal(5,2) DEFAULT NULL AFTER `gender`,
  ADD COLUMN IF NOT EXISTS `weight` decimal(5,2) DEFAULT NULL AFTER `height`,
  ADD COLUMN IF NOT EXISTS `activity_level` varchar(50) DEFAULT NULL AFTER `weight`,
  ADD COLUMN IF NOT EXISTS `primary_goal` varchar(100) DEFAULT 'Fitness' AFTER `activity_level`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;

-- 2. Reminders Table
CREATE TABLE IF NOT EXISTS `reminders` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Login Sessions Table
CREATE TABLE IF NOT EXISTS `login_sessions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Vitals Readings Table
CREATE TABLE IF NOT EXISTS `vitals_readings` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
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
  KEY `idx_vitals_user_type` (`user_id`, `reading_type`),
  KEY `idx_vitals_recorded` (`recorded_at`),
  CONSTRAINT `fk_vitals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Activity Goals Table
CREATE TABLE IF NOT EXISTS `activity_goals` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL UNIQUE,
  `daily_step_goal` int(11) DEFAULT 8000,
  `daily_calorie_goal` int(11) DEFAULT 500,
  `daily_active_minutes_goal` int(11) DEFAULT 45,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_activity_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Activity Tracking Sessions Table
CREATE TABLE IF NOT EXISTS `activity_sessions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
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
  KEY `idx_activity_user_time` (`user_id`, `start_time`),
  CONSTRAINT `fk_activity_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Sleep Records Table
CREATE TABLE IF NOT EXISTS `sleep_records` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
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
  UNIQUE KEY `idx_sleep_user_date` (`user_id`, `date_string`),
  CONSTRAINT `fk_sleep_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8. Hydration Goals & Logs
CREATE TABLE IF NOT EXISTS `hydration_goals` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL UNIQUE,
  `daily_target_ml` int(11) DEFAULT 2000,
  `reminders_enabled` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_hydration_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `hydration_logs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `date_string` date NOT NULL,
  `amount_ml` int(11) NOT NULL,
  `logged_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_hydration_user_date` (`user_id`, `date_string`),
  CONSTRAINT `fk_hydration_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9. Exercise Library & Custom Exercises
CREATE TABLE IF NOT EXISTS `exercises` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 10. Workout Sessions, Session Exercises, Sets
CREATE TABLE IF NOT EXISTS `workout_sessions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `session_name` varchar(150) NOT NULL,
  `split_type` varchar(50) NOT NULL,
  `date_string` date NOT NULL,
  `timestamp_formatted` varchar(100) NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 45,
  `total_volume_kg` decimal(8,2) DEFAULT 0.00,
  `estimated_calories_burned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_workout_user` (`user_id`, `date_string`),
  CONSTRAINT `fk_workout_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `workout_session_exercises` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `workout_session_id` int(10) UNSIGNED NOT NULL,
  `exercise_id` int(10) UNSIGNED DEFAULT NULL,
  `exercise_name` varchar(150) NOT NULL,
  `target_muscle` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session_ex` (`workout_session_id`),
  CONSTRAINT `fk_session_ex` FOREIGN KEY (`workout_session_id`) REFERENCES `workout_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `workout_exercise_sets` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_exercise_id` int(10) UNSIGNED NOT NULL,
  `set_number` int(11) NOT NULL,
  `reps` int(11) NOT NULL,
  `weight_kg` decimal(6,2) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_set_ex` (`session_exercise_id`),
  CONSTRAINT `fk_set_session_ex` FOREIGN KEY (`session_exercise_id`) REFERENCES `workout_session_exercises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 11. Food Items, Meal Entries, Nutrition Goals
CREATE TABLE IF NOT EXISTS `food_items` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `meal_entries` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `food_item_id` int(10) UNSIGNED DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` decimal(5,2) NOT NULL DEFAULT 1.00,
  `calculated_calories` int(11) NOT NULL,
  `calculated_protein` decimal(6,2) NOT NULL,
  `calculated_carbs` decimal(6,2) NOT NULL,
  `calculated_fat` decimal(6,2) NOT NULL,
  `date_string` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_meal_user_date` (`user_id`, `date_string`),
  CONSTRAINT `fk_meal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `nutrition_goals` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL UNIQUE,
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
  CONSTRAINT `fk_nutrition_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 12. AI Chat Messages
CREATE TABLE IF NOT EXISTS `ai_chat_messages` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `sender` varchar(20) NOT NULL,
  `message_text` text NOT NULL,
  `time_text` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ai_user` (`user_id`, `created_at`),
  CONSTRAINT `fk_ai_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 13. Connected Devices, Notifications, Health Permissions
CREATE TABLE IF NOT EXISTS `connected_devices` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
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

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(50) DEFAULT 'BELL',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`, `is_read`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `health_permissions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL UNIQUE,
  `vital_signs` tinyint(1) DEFAULT 1,
  `activity` tinyint(1) DEFAULT 1,
  `sleep` tinyint(1) DEFAULT 1,
  `hydration` tinyint(1) DEFAULT 0,
  `write_health_data` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

