-- VitalSync Test User Seed Script
-- Creates or updates test users with known credentials for local testing:
-- 1. test@vitalsync.com (Password: password123)
-- 2. alex.morgan@email.com (Password: password123)

USE `vitalsync`;

-- Seed test@vitalsync.com
INSERT INTO `users` (
    `full_name`,
    `name`,
    `email`,
    `password_hash`,
    `phone`,
    `dob`,
    `age`,
    `gender`,
    `height`,
    `weight`,
    `activity_level`,
    `primary_goal`
) VALUES (
    'Test User',
    'Test User',
    'test@vitalsync.com',
    '$2y$10$Rxlj/fgP9WJyoBKjSionwugLS3fAJ8ZGWuv41lzBNOYekf038Pn52',
    '+1 555-0199',
    '1995-05-15',
    30,
    'Male',
    175.00,
    70.00,
    'Moderate',
    'Improve Fitness'
) ON DUPLICATE KEY UPDATE
    `password_hash` = '$2y$10$Rxlj/fgP9WJyoBKjSionwugLS3fAJ8ZGWuv41lzBNOYekf038Pn52',
    `full_name` = VALUES(`full_name`);

SET @test_user_id = (SELECT `id` FROM `users` WHERE `email` = 'test@vitalsync.com' LIMIT 1);

INSERT INTO `user_profiles` (
    `user_id`, `full_name`, `age`, `gender`, `height_cm`, `weight_kg`, `blood_group`, `phone`
) VALUES (
    @test_user_id, 'Test User', 30, 'Male', 175.0, 70.0, 'O+', '+1 555-0199'
) ON DUPLICATE KEY UPDATE
    `full_name` = VALUES(`full_name`);

INSERT INTO `user_health_goals` (
    `user_id`, `primary_goal`, `target_steps`, `target_sleep_hours`, `target_water_ml`, `target_calories`, `target_weight_kg`
) VALUES (
    @test_user_id, 'Improve Fitness', 10000, 8.0, 2500, 2200, 68.0
) ON DUPLICATE KEY UPDATE
    `primary_goal` = VALUES(`primary_goal`);

INSERT INTO `user_app_settings` (
    `user_id`, `push_notifications`, `vital_alerts`, `daily_reminders`, `metric_units`, `dark_mode`
) VALUES (
    @test_user_id, 1, 1, 1, 'metric', 0
) ON DUPLICATE KEY UPDATE
    `user_id` = VALUES(`user_id`);

-- Also ensure alex.morgan@email.com password is password123
UPDATE `users` SET `password_hash` = '$2y$10$Rxlj/fgP9WJyoBKjSionwugLS3fAJ8ZGWuv41lzBNOYekf038Pn52' WHERE `email` = 'alex.morgan@email.com';

-- Repeat for vitalsync_db
USE `vitalsync_db`;

INSERT INTO `users` (
    `full_name`,
    `name`,
    `email`,
    `password_hash`,
    `phone`,
    `dob`,
    `age`,
    `gender`,
    `height`,
    `weight`,
    `activity_level`,
    `primary_goal`
) VALUES (
    'Test User',
    'Test User',
    'test@vitalsync.com',
    '$2y$10$Rxlj/fgP9WJyoBKjSionwugLS3fAJ8ZGWuv41lzBNOYekf038Pn52',
    '+1 555-0199',
    '1995-05-15',
    30,
    'Male',
    175.00,
    70.00,
    'Moderate',
    'Improve Fitness'
) ON DUPLICATE KEY UPDATE
    `password_hash` = '$2y$10$Rxlj/fgP9WJyoBKjSionwugLS3fAJ8ZGWuv41lzBNOYekf038Pn52',
    `full_name` = VALUES(`full_name`);

SET @test_user_id = (SELECT `id` FROM `users` WHERE `email` = 'test@vitalsync.com' LIMIT 1);

INSERT INTO `user_profiles` (
    `user_id`, `full_name`, `age`, `gender`, `height_cm`, `weight_kg`, `blood_group`, `phone`
) VALUES (
    @test_user_id, 'Test User', 30, 'Male', 175.0, 70.0, 'O+', '+1 555-0199'
) ON DUPLICATE KEY UPDATE
    `full_name` = VALUES(`full_name`);

INSERT INTO `user_health_goals` (
    `user_id`, `primary_goal`, `target_steps`, `target_sleep_hours`, `target_water_ml`, `target_calories`, `target_weight_kg`
) VALUES (
    @test_user_id, 'Improve Fitness', 10000, 8.0, 2500, 2200, 68.0
) ON DUPLICATE KEY UPDATE
    `primary_goal` = VALUES(`primary_goal`);

INSERT INTO `user_app_settings` (
    `user_id`, `push_notifications`, `vital_alerts`, `daily_reminders`, `metric_units`, `dark_mode`
) VALUES (
    @test_user_id, 1, 1, 1, 'metric', 0
) ON DUPLICATE KEY UPDATE
    `user_id` = VALUES(`user_id`);

UPDATE `users` SET `password_hash` = '$2y$10$Rxlj/fgP9WJyoBKjSionwugLS3fAJ8ZGWuv41lzBNOYekf038Pn52' WHERE `email` = 'alex.morgan@email.com';

