-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 04, 2026 at 06:00 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `company`
--
CREATE DATABASE IF NOT EXISTS `company` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `company`;

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `Emp_ID` int(11) NOT NULL,
  `Emp_Name` varchar(50) NOT NULL,
  `Dept_Name` varchar(50) DEFAULT NULL,
  `Salary` decimal(10,2) DEFAULT NULL,
  `Designation` varchar(50) DEFAULT NULL,
  `Blood_Group` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`Emp_ID`, `Emp_Name`, `Dept_Name`, `Salary`, `Designation`, `Blood_Group`) VALUES
(101, 'Rachna', 'HR', 28000.00, 'Assistant', 'A+'),
(102, 'Sathya', 'IT', 35000.00, 'Developer', 'B+'),
(103, 'Shiva', 'Finance', 50000.00, 'Accountant', 'O+'),
(104, 'Ashok', 'Sales', 60000.00, 'Manager', 'AB+'),
(105, 'Ananya', 'IT', 45000.00, 'Developer', 'A-'),
(106, 'Rahul', 'HR', 32000.00, 'HR Executive', 'O-'),
(107, 'Priya', 'Finance', 55000.00, 'Senior Accountant', 'B-'),
(108, 'Arun', 'Sales', 48000.00, 'Sales Executive', 'A+'),
(109, 'Meena', 'IT', 70000.00, 'Team Lead', 'O+'),
(110, 'Kiran', 'HR', 40000.00, 'HR Manager', 'AB+');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`Emp_ID`);
--
-- Database: `phpmyadmin`
--
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- Table structure for table `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) NOT NULL,
  `col_name` varchar(64) NOT NULL,
  `col_type` varchar(64) NOT NULL,
  `col_length` text DEFAULT NULL,
  `col_collation` varchar(64) NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) DEFAULT '',
  `col_default` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- Table structure for table `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `export_type` varchar(10) NOT NULL,
  `template_name` varchar(64) NOT NULL,
  `template_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- Table structure for table `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

--
-- Dumping data for table `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"vitalsync\",\"table\":\"users\"},{\"db\":\"vitalsync\",\"table\":\"nutrition_goals\"},{\"db\":\"vitalsync\",\"table\":\"vitals_readings\"},{\"db\":\"vitalsync\",\"table\":\"sleep_records\"},{\"db\":\"vitalsync\",\"table\":\"health_permissions\"},{\"db\":\"vitalsync\",\"table\":\"password_resets\"},{\"db\":\"company\",\"table\":\"employee\"},{\"db\":\"student\",\"table\":\"student\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) NOT NULL DEFAULT '',
  `master_table` varchar(64) NOT NULL DEFAULT '',
  `master_field` varchar(64) NOT NULL DEFAULT '',
  `foreign_db` varchar(64) NOT NULL DEFAULT '',
  `foreign_table` varchar(64) NOT NULL DEFAULT '',
  `foreign_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Table structure for table `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `search_name` varchar(64) NOT NULL DEFAULT '',
  `search_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

-- --------------------------------------------------------

--
-- Table structure for table `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text DEFAULT NULL,
  `data_sql` longtext DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Dumping data for table `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2026-11-04 05:00:18', '{\"Console\\/Mode\":\"collapse\"}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Indexes for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Indexes for table `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Indexes for table `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Indexes for table `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Indexes for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Indexes for table `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Indexes for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Indexes for table `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Indexes for table `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Indexes for table `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Indexes for table `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Indexes for table `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Indexes for table `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Database: `student`
--
CREATE DATABASE IF NOT EXISTS `student` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `student`;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) DEFAULT NULL CHECK (`age` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `name`, `age`) VALUES
(1, 'Alice Johnson', 19),
(2, 'Brian Smith', 21),
(3, 'Catherine Lee', 20),
(4, 'David Brown', 22),
(5, 'Emily Davis', 18);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`);
--
-- Database: `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;
--
-- Database: `vitalsync`
--
CREATE DATABASE IF NOT EXISTS `vitalsync` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `vitalsync`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_goals`
--

CREATE TABLE `activity_goals` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `daily_step_goal` int(11) DEFAULT 8000,
  `daily_calorie_goal` int(11) DEFAULT 500,
  `daily_active_minutes_goal` int(11) DEFAULT 45,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `walking_distance_goal_km` decimal(6,2) DEFAULT 3.00,
  `running_distance_goal_km` decimal(6,2) DEFAULT 5.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_goals`
--

INSERT INTO `activity_goals` (`id`, `user_id`, `daily_step_goal`, `daily_calorie_goal`, `daily_active_minutes_goal`, `updated_at`, `walking_distance_goal_km`, `running_distance_goal_km`) VALUES
(1, 2, 5000, 500, 45, '2026-10-30 08:31:23', 3.00, 5.00),
(6, 1, 12000, 500, 45, '2026-10-31 08:21:17', 3.00, 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `activity_sessions`
--

CREATE TABLE `activity_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `duration_seconds` int(11) NOT NULL,
  `distance_km` decimal(6,2) DEFAULT 0.00,
  `calories_burned` int(11) DEFAULT 0,
  `avg_pace` varchar(20) DEFAULT NULL,
  `avg_heart_rate` int(11) DEFAULT NULL,
  `steps` int(11) DEFAULT 0,
  `route_points` text DEFAULT NULL,
  `start_time` datetime NOT NULL DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_sessions`
--

INSERT INTO `activity_sessions` (`id`, `user_id`, `activity_type`, `duration_seconds`, `distance_km`, `calories_burned`, `avg_pace`, `avg_heart_rate`, `steps`, `route_points`, `start_time`, `end_time`, `created_at`) VALUES
(1, 6, 'WALKING', 16, 0.01, 1, '11:45 /km', 108, 15, NULL, '2026-11-02 08:41:50', '2026-11-02 08:42:06', '2026-11-02 07:41:50'),
(2, 6, 'CYCLING', 11, 0.06, 1, '2:52 /km', 138, 0, NULL, '2026-11-02 08:42:30', '2026-11-02 08:42:41', '2026-11-02 07:42:30'),
(3, 1, 'WALKING', 1800, 2.15, 125, '13:57 /km', NULL, 2800, NULL, '2026-11-02 08:31:49', '2026-11-02 09:01:49', '2026-11-02 08:01:49'),
(4, 1, 'CYCLING', 1500, 8.45, 210, '20.3 km/h', NULL, 0, NULL, '2026-11-02 08:49:45', '2026-11-02 09:14:45', '2026-11-02 08:14:45'),
(5, 6, 'CYCLING', 120, 0.02, 35, '0.7 km/h', 138, 0, NULL, '2026-11-02 09:22:55', '2026-11-02 09:24:55', '2026-11-02 08:22:55'),
(6, 6, 'CYCLING', 4, 0.02, 0, '20.5 km/h', 138, 0, NULL, '2026-11-02 10:14:28', '2026-11-02 10:14:32', '2026-11-02 09:14:28');

-- --------------------------------------------------------

--
-- Table structure for table `ai_chat_messages`
--

CREATE TABLE `ai_chat_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `sender` varchar(20) NOT NULL,
  `message_text` text NOT NULL,
  `time_text` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `model_used` varchar(50) DEFAULT 'qwen2.5:1.5b',
  `tokens_used` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_chat_messages`
--

INSERT INTO `ai_chat_messages` (`id`, `user_id`, `sender`, `message_text`, `time_text`, `created_at`, `model_used`, `tokens_used`) VALUES
(1, 2, 'USER', 'How is my resting heart rate today?', '10:09 AM', '2026-10-27 09:09:10', 'llama3.2:3b', 0),
(2, 2, 'AI', 'Your resting heart rate averages around 64–76 BPM, which is in the healthy normal range. Maintaining moderate aerobic activity and staying well-hydrated helps keep your cardiovascular rhythm balanced. Note: VitalSync provides wellness insights, not clinical medical advice.', '10:09 AM', '2026-10-27 09:09:10', 'llama3.2:3b', 0),
(3, 2, 'USER', 'What should my post-workout meal look like today?', '03:45 AM', '2026-10-28 02:45:52', 'llama3.2:3b', 0),
(4, 2, 'AI', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', '03:45 AM', '2026-10-28 02:45:52', 'llama3.2:3b', 0),
(5, 2, 'USER', 'What should my post-workout meal look like today?', '05:38 AM', '2026-10-28 04:38:15', 'llama3.2:3b', 0),
(6, 2, 'AI', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', '05:38 AM', '2026-10-28 04:38:15', 'llama3.2:3b', 0),
(7, 2, 'USER', 'What should my post-workout meal look like today?', '07:39 AM', '2026-10-28 06:39:58', 'llama3.2:3b', 0),
(8, 2, 'AI', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', '07:39 AM', '2026-10-28 06:39:58', 'llama3.2:3b', 0),
(9, 2, 'USER', 'How is my heart rate and sleep?', '06:23 AM', '2026-10-30 05:23:50', 'llama3.2:3b', 0),
(10, 2, 'AI', 'Your resting heart rate averages around 64–76 BPM, which is in the healthy normal range. Maintaining moderate aerobic activity and staying well-hydrated helps keep your cardiovascular rhythm balanced. Note: VitalSync provides wellness insights, not clinical medical advice.', '06:23 AM', '2026-10-30 05:23:50', 'llama3.2:3b', 0),
(11, 1, 'USER', 'Hi Doctor, how is my resting heart rate today?', '05:05 AM', '2026-11-03 04:05:14', 'llama3.2:3b', 0),
(12, 1, 'AI', 'Hello nitesh, your latest resting heart rate is recorded at 74 BPM, which falls comfortably within the healthy clinical range (60–100 BPM). Your cardiovascular rhythm indicates steady autonomic balance. Maintaining structured cardiovascular pacing and adequate hydration will keep your heart rate variability optimal.', '05:05 AM', '2026-11-03 04:05:18', 'llama3.2:fallback', 0),
(24, 1, 'USER', 'Who directed Inception and what is it about?', '06:09 AM', '2026-11-03 05:09:49', 'llama3.2:3b', 0),
(25, 1, 'AI', 'Here is verified information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\n', '06:09 AM', '2026-11-03 05:09:54', 'rag:verified-kb', 0),
(26, 1, 'USER', 'What are the best high protein sources for building muscle?', '06:09 AM', '2026-11-03 05:09:54', 'llama3.2:3b', 0),
(27, 1, 'AI', 'Here is verified information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, note that **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '06:09 AM', '2026-11-03 05:09:58', 'rag:verified-kb', 0),
(28, 1, 'USER', 'How is my resting heart rate today?', '06:09 AM', '2026-11-03 05:09:58', 'llama3.2:3b', 0),
(29, 1, 'AI', 'Here is verified information regarding **Resting Heart Rate Metrics**:\n\nClinical norms: Normal resting heart rate for adults ranges between 60-100 BPM. Well-conditioned athletes frequently exhibit 40-60 BPM (physiological bradycardia). Sustained resting HR > 100 BPM without physical exertion is tachycardia and warrants observation if paired with dizziness, palpitations, or shortness of breath.\n\nAdditionally, note that **Fever, Dehydration & Vitals**: Physiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate ...', '06:09 AM', '2026-11-03 05:10:03', 'rag:verified-kb', 0),
(30, 1, 'USER', 'Who directed Inception and what is it about?', '06:13 AM', '2026-11-03 05:13:07', 'llama3.2:3b', 0),
(31, 1, 'AI', '**Inception (2010)** was written and directed by **Christopher Nolan**! It stars Leonardo DiCaprio as Dom Cobb, an extractor who steals secrets from deep within the subconscious during dream states. With mind-bending practical effects, Hans Zimmer\'s iconic score, and an ambiguous spinning totem ending, it remains one of cinema\'s greatest sci-fi masterpieces.', '06:13 AM', '2026-11-03 05:13:11', 'llama3.2:fallback', 0),
(32, 1, 'USER', 'What are the best high protein sources for building muscle?', '06:13 AM', '2026-11-03 05:13:12', 'llama3.2:3b', 0),
(33, 1, 'AI', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '06:13 AM', '2026-11-03 05:13:16', 'rag:verified-kb', 0),
(34, 1, 'USER', 'How is my resting heart rate today?', '06:13 AM', '2026-11-03 05:13:16', 'llama3.2:3b', 0),
(35, 1, 'AI', 'Here is verified reference information regarding **Resting Heart Rate Metrics**:\n\nClinical norms: Normal resting heart rate for adults ranges between 60-100 BPM. Well-conditioned athletes frequently exhibit 40-60 BPM (physiological bradycardia). Sustained resting HR > 100 BPM without physical exertion is tachycardia and warrants observation if paired with dizziness, palpitations, or shortness of breath.\n\nAdditionally, regarding **Fever, Dehydration & Vitals**: Physiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate ...', '06:13 AM', '2026-11-03 05:13:20', 'rag:verified-kb', 0),
(36, 1, 'USER', 'Who directed Inception and what is it about?', '06:14 AM', '2026-11-03 05:14:50', 'llama3.2:3b', 0),
(37, 1, 'AI', '**Inception (2010)** was written and directed by **Christopher Nolan**! It stars Leonardo DiCaprio as Dom Cobb, an extractor who steals secrets from deep within the subconscious during dream states. With mind-bending practical effects, Hans Zimmer\'s iconic score, and an ambiguous spinning totem ending, it remains one of cinema\'s greatest sci-fi masterpieces.', '06:14 AM', '2026-11-03 05:14:54', 'llama3.2:fallback', 0),
(38, 1, 'USER', 'What are the best high protein sources for building muscle?', '06:14 AM', '2026-11-03 05:14:54', 'llama3.2:3b', 0),
(39, 1, 'AI', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '06:14 AM', '2026-11-03 05:14:59', 'rag:verified-kb', 0),
(40, 1, 'USER', 'How is my resting heart rate today?', '06:14 AM', '2026-11-03 05:14:59', 'llama3.2:3b', 0),
(41, 1, 'AI', 'Your latest resting heart rate is **74 BPM**, which is in the healthy clinical range (60–100 BPM). Your cardiovascular trends reflect balanced autonomic regulation.', '06:14 AM', '2026-11-03 05:15:03', 'vitals:personal-sync', 0),
(42, 6, 'AI', 'Hello nitesh Mv! I am your VitalSync AI assistant powered by Llama 3.2. I\'m here for anything—from daily health, sleep, and workouts, to general knowledge, movies, tech, and everyday curiosity. How can I help you today?', '06:20 AM', '2026-11-03 05:20:24', 'llama3.2:3b', 0),
(43, 6, 'USER', 'how many days to take build muscles', '06:22 AM', '2026-11-03 05:22:05', 'llama3.2:3b', 0),
(44, 6, 'AI', 'That\'s an interesting question! Whether we\'re exploring ideas, analyzing concepts, working through a problem, or discussing health and training, I\'m here to provide clear, actionable, and engaging answers. Feel free to give me more details on what you\'d like to dive into!', '06:22 AM', '2026-11-03 05:22:09', 'llama3.2:fallback', 0),
(45, 1, 'USER', 'List top 3 protein sources in one line.', '07:37 AM', '2026-11-03 06:37:18', 'qwen2.5:1.5b', 0),
(46, 1, 'AI', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Hypertrophy and Muscle Growth Principles**: Hypertrophy Science: Optimal muscle growth requires progressive tension overload, 10-20 tough working sets per muscle group per week, and working within 1-3 Reps in Reserve (RIR). ...', '07:37 AM', '2026-11-03 06:37:22', 'rag:verified-kb', 0),
(47, 6, 'USER', 'hey', '07:40 AM', '2026-11-03 06:40:23', 'qwen2.5:1.5b', 0),
(48, 6, 'AI', 'Hello! I am your VitalSync AI assistant. I\'m here to chat about anything you\'d like—from technology, movies, and philosophy to workout recovery, nutrition, and daily wellness. How can I help you today?', '07:40 AM', '2026-11-03 06:40:28', 'qwen2.5:fallback', 0),
(49, 6, 'USER', 'I need to focus', '07:40 AM', '2026-11-03 06:40:42', 'qwen2.5:1.5b', 0),
(50, 6, 'AI', 'That\'s an interesting question! Whether we\'re exploring ideas, analyzing concepts, working through a problem, or discussing health and training, I\'m here to provide clear, actionable, and engaging answers. Feel free to give me more details on what you\'d like to dive into!', '07:40 AM', '2026-11-03 06:40:46', 'qwen2.5:fallback', 0),
(51, 6, 'USER', 'how to maintain my diet', '07:40 AM', '2026-11-03 06:40:56', 'qwen2.5:1.5b', 0),
(52, 6, 'AI', 'Here is verified reference information regarding **Blood Pressure Stages & Guidelines**:\n\nAHA BP Guidelines: Normal: < 120/< 80 mmHg. Elevated: 120-129/< 80 mmHg. Stage 1 Hypertension: 130-139 / 80-89 mmHg. Stage 2 Hypertension: >= 140 or >= 90 mmHg. Hypertensive Crisis: > 180 and/or > 120 mmHg. Lifestyle modifications: DASH diet, sodium reduction (< 2,300 mg/day), regular aerobic exercise, stress reduction.', '07:40 AM', '2026-11-03 06:41:00', 'rag:verified-kb', 0),
(53, 6, 'USER', 'Chennai best hospital', '07:41 AM', '2026-11-03 06:41:31', 'qwen2.5:1.5b', 0),
(54, 6, 'AI', 'Technology and software engineering thrive on modular architecture, clean abstractions, and continuous iteration. Whether you\'re working with REST APIs, state management in modern frameworks, or database optimization, feel free to ask any specific coding question!', '07:41 AM', '2026-11-03 06:41:35', 'qwen2.5:fallback', 0),
(55, 6, 'USER', 'i have fever', '07:41 AM', '2026-11-03 06:41:59', 'qwen2.5:1.5b', 0),
(56, 6, 'AI', 'Here is verified reference information regarding **Fever, Dehydration & Vitals**:\n\nPhysiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate is elevated alongside low hydration, prioritize electrolyte rehydration (ORS, coconut water). Warning signs: persistent fever > 103┬░F (39.4┬░C), stiff neck, or dyspnea require immediate medical evaluation.', '07:41 AM', '2026-11-03 06:42:04', 'rag:verified-kb', 0),
(57, 6, 'USER', 'give any python cod3', '07:46 AM', '2026-11-03 06:46:52', 'qwen2.5:1.5b', 0),
(58, 6, 'AI', '**Python** is renowned for its clean syntax, readability, and massive ecosystem. Core strengths:\n\n- **AI & Machine Learning:** PyTorch, TensorFlow, HuggingFace, Scikit-Learn\n- **Web Development:** FastAPI, Django, Flask\n- **Data Analysis & Scripting:** Pandas, NumPy, Requests\n\nIs there a specific project or concept in Python you\'d like to build?', '07:46 AM', '2026-11-03 06:46:57', 'qwen2.5:fallback', 0),
(59, 6, 'USER', 'What are the best high protein sources for building muscle?', '08:32 AM', '2026-11-03 07:32:41', 'qwen2.5:1.5b', 0),
(60, 6, 'AI', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '08:32 AM', '2026-11-03 07:32:45', 'rag:verified-kb', 0),
(61, 1, 'USER', 'Hi VitalSync, can you recommend 3 high-protein foods?', '10:15 AM', '2026-11-03 09:15:08', 'qwen2.5:1.5b', 0),
(62, 1, 'AI', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '10:15 AM', '2026-11-03 09:16:38', 'rag:verified-kb', 0),
(63, 1, 'USER', 'Hi VitalSync, can you recommend 3 high-protein foods?', '10:21 AM', '2026-11-03 09:21:53', 'qwen2.5:1.5b', 0),
(64, 1, 'AI', 'Here are 3 **high-protein foods** that work well for most diets (with verified protein content per 100g or standard serving):\n\n1. **Chicken breast** (31g protein/100g) – Lean, versatile, and great for quick meals.  \n2. **Greek yogurt** (10g protein/100g) – High in protein, adds richness to smoothies or meals.  \n3. **Whole eggs** (6–7g protein/egg) – Affordable, nutrient-dense, and perfect for breakfast or snacks.  \n\n💡 *Why these work*: They cover different dietary preferences (animal-based, dairy, and whole foods), are widely', '10:21 AM', '2026-11-03 09:23:29', 'qwen3:4b', 600),
(65, 6, 'USER', 'how i can improve my die', '10:28 AM', '2026-11-03 09:28:34', 'qwen2.5:1.5b', 0),
(66, 6, 'AI', 'Okay, the user just asked \"how i can improve my die\" twice. That\'s... interesting. First, I need to figure out what they meant. \"Die\" could be a typo. Let me think about common typos people make.\n\nIn English, \"die\" is a verb meaning to stop living, but they wrote \"die\" instead of \"diet\" or \"diet\". Wait, no—they might have meant \"diet\" but typed \"die\". Or maybe they meant \"daily\" but wrote \"die\". Hmm. Another possibility: in gaming terms, \"die\" can mean to fail a roll, like in D&D. But the user has been asking about high protein sources for muscle building, so probably not gaming.\n\nWait, looking back at the conversation history: the user previously asked about high protein sources for building muscle. Then they typed \"how i can improve my die\" twice. Given the context, they likely meant \"how can I improve my diet\". The typo \"die\" instead of \"diet\" is common when typing quickly. \n\nSo the user is probably asking about improving their diet for muscle building, given their previous question. They might have made a typo. Let me check the spelling. In English, \"diet\" vs \"die\"—yes, the \'t\' and \'i\' are swapped. So it\'s a typo.\n\nI should respond by clarifying that they likely meant \"diet\" and then provide actionable advice based on their interest in muscle building. Since they previously asked about high protein sources, they might want more detailed dietary strategies.\n\nAlso, the user wrote \"how i\" with lowercase \'i\', which is informal, so they\'re probably a casual user, not a professional. I should keep the response simple and practical.\n\nPossible response structure:\n1. Clarify the typo: \"You might have meant \'diet\'—common typo!\"\n2. Provide concise, actionable tips for improving diet for muscle building, linking back to their previous interest in protein sources.\n3. Avoid medical jargon since they\'re focused on fitness, not health issues.\n4. Keep it encouraging and positive.\n\nAlso, check if there\'s any other possible meaning of \"die\" in context. For example, in computing, \"die\" isn\'t a standard term, but in gaming, as mentioned. But given the history, it\'s unlikely. The user is into muscle building, so diet is the most probable.\n\nI should make sure not to overcomplicate it. The user might be in a hurry or typing on mobile, so the response should be short and to the point.\n\nLet me draft the response:\n\n\"Hey! I think you meant \'diet\' (common typo 😄). For improving your diet to build muscle, here\'s what works:\n\n1. **Prioritize protein**: 1.6-2.2g/kg body weight (e.g., 100g chicken breast = 31g protein)\n2.', '10:28 AM', '2026-11-03 09:30:01', 'qwen3:4b', 600),
(67, 6, 'USER', 'how are you', '04:53 AM', '2026-11-04 03:53:32', 'qwen2.5:1.5b', 0),
(68, 6, 'AI', 'I am **VitalSync Core AI**, a custom-engineered clinical wellness and general-purpose intelligence engine built natively for VitalSync. I run directly inside this workspace without third-party dependencies, grounding my insights in your authenticated health telemetry (heart rate, sleep stages, hydration, steps) alongside verified clinical knowledge bases.', '04:53 AM', '2026-11-04 03:53:32', 'VitalSync-Core-AI-v1', 45),
(69, 6, 'USER', 'how i can maintain my sleep', '04:54 AM', '2026-11-04 03:54:08', 'qwen2.5:1.5b', 0),
(70, 6, 'USER', 'give me diet plan', '04:57 AM', '2026-11-04 03:57:55', 'qwen2.5:1.5b', 0),
(71, 6, 'USER', 'Can you review my resting heart rate and cardiovascular trends today?', '04:58 AM', '2026-11-04 03:58:03', 'qwen2.5:1.5b', 0),
(72, 6, 'USER', 'what is normal\' blood pressure', '05:03 AM', '2026-11-04 04:03:12', 'qwen2.5:1.5b', 0),
(73, 6, 'USER', 'what is normal\' blood pressure', '05:05 AM', '2026-11-04 04:05:06', 'qwen2.5:1.5b', 0),
(74, 6, 'USER', 'good morning', '05:08 AM', '2026-11-04 04:08:53', 'qwen2.5:1.5b', 0),
(75, 6, 'AI', 'Hello there! I am your VitalSync Core AI medical & wellness assistant. How can I help you today with health questions, symptoms, vitals, or workout recovery?', '05:08 AM', '2026-11-04 04:08:53', 'VitalSync-Core-AI-v1', 25),
(76, 6, 'USER', 'give me diet plan', '05:09 AM', '2026-11-04 04:09:07', 'qwen2.5:1.5b', 0),
(77, 6, 'USER', 'weight', '05:09 AM', '2026-11-04 04:09:18', 'qwen2.5:1.5b', 0),
(78, 6, 'AI', 'Hey there! Ready to assist you with medical questions, vitals analytics, or daily recovery. What\'s on your mind?', '05:09 AM', '2026-11-04 04:09:18', 'VitalSync-Core-AI-v1', 18),
(79, 1, 'USER', 'what is normal\' blood pressure', '05:10 AM', '2026-11-04 04:10:25', 'qwen2.5:1.5b', 0),
(80, 1, 'USER', 'what is normal\' blood pressure', '05:14 AM', '2026-11-04 04:14:37', 'qwen2.5:1.5b', 0),
(81, 1, 'AI', 'Blood pressure measures arterial resistance during cardiac contraction (systolic) and relaxation (diastolic). Your VitalSync profile logs your recorded pressure at **120/80 mmHg**. Consistent systolic pressure ≥130 mmHg indicates hypertension requiring dietary sodium reduction, regular cardio, and physician consultation.', '05:14 AM', '2026-11-04 04:14:37', 'VitalSync-Core-AI-v1', 36),
(82, 1, 'USER', 'Can you review my resting heart rate and cardiovascular trends today?', '05:15 AM', '2026-11-04 04:15:34', 'qwen2.5:1.5b', 0),
(83, 1, 'AI', 'Cardiovascular Status: Your heart rate is holding at **72 BPM**. Blood pressure sits at **120/80 mmHg** with SpO2 at **98%**.', '05:15 AM', '2026-11-04 04:15:34', 'VitalSync-Core-AI-v1', 17),
(84, 6, 'USER', 'How was my activity and sleep quality this week?', '05:21 AM', '2026-11-04 04:21:37', 'qwen2.5:1.5b', 0),
(85, 6, 'AI', 'Your last recorded sleep session was **7h 30m** with a quality score of **84/100**. For your goal (General Wellness), adequate deep and REM sleep is crucial for cellular repair and mental clarity.', '05:21 AM', '2026-11-04 04:21:37', 'VitalSync-Core-AI-v1', 31);

-- --------------------------------------------------------

--
-- Table structure for table `ai_daily_insights`
--

CREATE TABLE `ai_daily_insights` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `insight_date` date NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'DAILY_OVERVIEW',
  `title` varchar(255) NOT NULL,
  `summary` text NOT NULL,
  `actionable_advice` text DEFAULT NULL,
  `wellness_score` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `model_used` varchar(50) DEFAULT 'qwen2.5:1.5b',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_daily_insights`
--

INSERT INTO `ai_daily_insights` (`id`, `user_id`, `insight_date`, `category`, `title`, `summary`, `actionable_advice`, `wellness_score`, `is_read`, `model_used`, `created_at`) VALUES
(1, 1, '2026-11-03', 'DAILY_OVERVIEW', 'Activity & Recovery Harmony', 'Your resting heart rate is stable at 72 BPM, and activity is progressing well with 6,420 steps logged today.', 'Hydration is at 1500 ml; drinking an additional glass of water will help maintain optimal cardiac output.', 86, 0, 'qwen2.5:fallback', '2026-11-03 06:37:01'),
(2, 6, '2026-11-03', 'DAILY_OVERVIEW', 'Activity & Recovery Harmony', 'Your resting heart rate is stable at 72 BPM, and activity is progressing well with 6,420 steps logged today.', 'Hydration is at 1500 ml; drinking an additional glass of water will help maintain optimal cardiac output.', 86, 0, 'llama3.2:fallback', '2026-11-03 04:44:04'),
(4, 1, '2026-11-04', 'DAILY_OVERVIEW', 'Activity & Recovery Harmony', 'Your resting heart rate is stable at 72 BPM, and activity is progressing well with 6,420 steps logged today.', 'Hydration is at 2250 ml; drinking an additional glass of water will help maintain optimal cardiac output.', 86, 0, 'qwen2.5:fallback', '2026-11-04 03:37:44'),
(5, 6, '2026-11-04', 'General Wellness', 'Activity & Recovery Balance', 'Your resting heart rate is stable at 72 BPM, and daily movement is on track with 6,420 steps logged today.', 'Hydration is currently at 1500 mL; drinking an additional glass of water will help maintain optimal cardiac output.', 85, 0, 'VitalSync-Core-AI-v1', '2026-11-04 03:49:28');

-- --------------------------------------------------------

--
-- Table structure for table `ai_knowledge_base`
--

CREATE TABLE `ai_knowledge_base` (
  `id` bigint(20) NOT NULL,
  `category` varchar(50) NOT NULL,
  `topic` varchar(150) NOT NULL,
  `keywords` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_knowledge_base`
--

INSERT INTO `ai_knowledge_base` (`id`, `category`, `topic`, `keywords`, `content`, `created_at`) VALUES
(1, 'nutrition', 'High Protein Sources', 'protein best source chicken eggs fish whey lentils soya paneer beef vegetarian', 'Top dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.', '2026-11-03 10:32:50'),
(2, 'nutrition', 'Micronutrients and Recovery', 'vitamins minerals omega 3 fish oil magnesium sleep recovery cramps zinc vitamin d', 'Key recovery nutrients: Omega-3 Fatty Acids (EPA/DHA, 1000-2000mg) reduce systemic inflammation and muscle soreness. Magnesium glycinate (200-400mg) supports sleep architecture and muscle relaxation. Vitamin D3 & Zinc support hormonal balance and immune defense.', '2026-11-03 10:32:50'),
(3, 'medical_basics', 'Fever, Dehydration & Vitals', 'fever temp heart rate elevated sick infection dehydration fluids ors coconut water', 'Physiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate is elevated alongside low hydration, prioritize electrolyte rehydration (ORS, coconut water). Warning signs: persistent fever > 103┬░F (39.4┬░C), stiff neck, or dyspnea require immediate medical evaluation.', '2026-11-03 10:32:50'),
(4, 'medical_basics', 'Resting Heart Rate Metrics', 'resting heart rate bpm bradycardia tachycardia normal range athletes', 'Clinical norms: Normal resting heart rate for adults ranges between 60-100 BPM. Well-conditioned athletes frequently exhibit 40-60 BPM (physiological bradycardia). Sustained resting HR > 100 BPM without physical exertion is tachycardia and warrants observation if paired with dizziness, palpitations, or shortness of breath.', '2026-11-03 10:32:50'),
(5, 'medical_basics', 'Blood Pressure Stages & Guidelines', 'blood pressure hypertension hypotension systolic diastolic mmhg normal bp', 'AHA BP Guidelines: Normal: < 120/< 80 mmHg. Elevated: 120-129/< 80 mmHg. Stage 1 Hypertension: 130-139 / 80-89 mmHg. Stage 2 Hypertension: >= 140 or >= 90 mmHg. Hypertensive Crisis: > 180 and/or > 120 mmHg. Lifestyle modifications: DASH diet, sodium reduction (< 2,300 mg/day), regular aerobic exercise, stress reduction.', '2026-11-03 10:32:50'),
(6, 'fitness', 'Hypertrophy and Muscle Growth Principles', 'hypertrophy muscle build progressive overload sets reps failure volume', 'Hypertrophy Science: Optimal muscle growth requires progressive tension overload, 10-20 tough working sets per muscle group per week, and working within 1-3 Reps in Reserve (RIR). Rep ranges of 6-12 reps with 2-3 minutes rest optimize mechanical tension and metabolic stress. Daily protein target: 1.6-2.2 grams per kg of total bodyweight.', '2026-11-03 10:32:50'),
(7, 'general_wellness', 'Sleep Architecture and Circadian Health', 'sleep stages rem deep sleep circadian rhythm melatonin insomnia', 'Optimal sleep architecture consists of 4-6 cycles of 90 minutes each, balancing Deep NREM (physical and muscle tissue repair, glymphatic brain clearance) and REM (cognitive consolidation, mood regulation). Sleep hygiene rules: keep bedroom cool (18-20┬░C / 65-68┬░F), avoid blue light 60 mins before bed, finish eating 2-3 hours prior to sleep.', '2026-11-03 10:32:50'),
(8, 'nutrition', 'Hydration and Electrolyte Balance', 'hydration water intake electrolytes sodium potassium water target', 'Hydration Physiology: Baseline daily fluid intake is roughly 30-35 ml per kg of bodyweight, increasing by 500-1000 ml per hour of intense exercise or sweating. Key intracellular and extracellular electrolytes: Sodium, Potassium, Magnesium. Sipping fluids evenly throughout the waking hours is more effective for cell hydration than chugging large amounts at once.', '2026-11-03 10:32:50'),
(9, 'general_wellness', 'Stress, Cortisol, and Heart Rate Variability (HRV)', 'stress cortisol hrv heart rate variability burnout breathing', 'Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HRV, impair anabolic muscle synthesis, and disrupt sleep architecture. 5 minutes of resonance breathing (4s inhale, 6s exhale) swiftly engages parasympathetic tone.', '2026-11-03 10:32:50');

-- --------------------------------------------------------

--
-- Table structure for table `connected_devices`
--

CREATE TABLE `connected_devices` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_type` varchar(50) DEFAULT 'Smart Watch',
  `is_connected` tinyint(1) DEFAULT 1,
  `battery_level` int(11) DEFAULT 88,
  `last_synced` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `connected_devices`
--

INSERT INTO `connected_devices` (`id`, `user_id`, `device_name`, `device_type`, `is_connected`, `battery_level`, `last_synced`, `created_at`) VALUES
(4, 2, 'VitalSync Smart Scale', 'Bluetooth Scale', 1, 95, '2026-10-28 02:46:28', '2026-10-28 02:46:28'),
(5, 2, 'VitalSync Smart Scale', 'Bluetooth Scale', 1, 95, '2026-10-28 04:38:34', '2026-10-28 04:38:34'),
(6, 2, 'VitalSync Smart Scale', 'Bluetooth Scale', 1, 95, '2026-10-28 06:40:29', '2026-10-28 06:40:29'),
(7, 1, 'Galaxy Watch 6 Pro', 'SMARTWATCH', 1, 88, '2026-10-31 06:49:35', '2026-10-31 06:48:40'),
(8, 1, 'Galaxy Watch Ultra', 'SMARTWATCH', 1, 92, '2026-10-31 07:03:56', '2026-10-31 07:03:56'),
(9, 1, 'Fitbit Charge 6', 'smartwatch', 1, 85, '2026-10-31 07:37:35', '2026-10-31 07:37:34'),
(10, 2, 'HP Track', 'heart rate monitor', 1, 85, '2026-10-31 07:37:38', '2026-10-31 07:37:38'),
(11, 1, 'Pixel Watch 3', 'smartwatch', 1, 85, '2026-10-31 07:38:47', '2026-10-31 07:38:47'),
(12, 2, 'HP Track', 'heart rate monitor', 1, 85, '2026-10-31 07:44:14', '2026-10-31 07:44:14'),
(13, 2, 'HP Track', 'heart rate monitor', 1, 85, '2026-10-31 07:45:58', '2026-10-31 07:45:58'),
(14, 2, 'HP Track', 'heart rate monitor', 1, 85, '2026-10-31 07:45:59', '2026-10-31 07:45:59'),
(15, 2, 'HP Track', 'heart rate monitor', 1, 85, '2026-10-31 07:47:33', '2026-10-31 07:47:33'),
(16, 2, 'HP Track', 'heart rate monitor', 1, 85, '2026-10-31 07:47:48', '2026-10-31 07:47:48'),
(17, 2, 'HP Track', 'smartwatch', 1, 85, '2026-10-31 07:53:34', '2026-10-31 07:53:34'),
(18, 2, 'HP Track', 'smartwatch', 1, 85, '2026-10-31 07:53:47', '2026-10-31 07:53:47'),
(19, 2, 'HP Track', 'smartwatch', 1, 85, '2026-10-31 07:54:06', '2026-10-31 07:54:06'),
(20, 2, 'HP Track', 'smartwatch', 1, 85, '2026-10-31 07:54:22', '2026-10-31 07:54:22');

-- --------------------------------------------------------

--
-- Table structure for table `exercises`
--

CREATE TABLE `exercises` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `target_muscle` varchar(100) NOT NULL,
  `default_weight_kg` decimal(5,2) DEFAULT 20.00,
  `default_reps` int(11) DEFAULT 10,
  `notes` text DEFAULT NULL,
  `is_custom` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exercises`
--

INSERT INTO `exercises` (`id`, `user_id`, `code`, `name`, `target_muscle`, `default_weight_kg`, `default_reps`, `notes`, `is_custom`, `created_at`) VALUES
(1, NULL, 'e1', 'Barbell Bench Press', 'Chest', 60.00, 10, 'Keep shoulder blades retracted and feet planted.', 0, '2026-10-27 08:59:26'),
(2, NULL, 'e2', 'Incline Dumbbell Press', 'Upper Chest', 22.00, 10, 'Focus on upper chest contraction at top.', 0, '2026-10-27 08:59:26'),
(3, NULL, 'e3', 'Overhead Barbell Press', 'Shoulders', 40.00, 8, 'Core braced, press vertically overhead.', 0, '2026-10-27 08:59:26'),
(4, NULL, 'e4', 'Triceps Rope Pushdown', 'Triceps', 25.00, 12, 'Keep elbows stationary at sides.', 0, '2026-10-27 08:59:26'),
(5, NULL, 'e5', 'Incline Chest Fly', 'Chest', 16.00, 12, 'Slight bend in elbows throughout movement.', 0, '2026-10-27 08:59:26'),
(6, NULL, 'e6', 'Lateral Raises', 'Side Deltoids', 10.00, 15, 'Lead with elbows for side shoulder isolation.', 0, '2026-10-27 08:59:26'),
(7, NULL, 'e7', 'Barbell Deadlift', 'Back & Posture', 100.00, 5, 'Flat spine, drive through heels.', 0, '2026-10-27 08:59:26'),
(8, NULL, 'e8', 'Lat Pulldown', 'Lats', 50.00, 10, 'Pull bar to upper chest squeezing lats.', 0, '2026-10-27 08:59:26'),
(9, NULL, 'e9', 'Seated Cable Row', 'Mid Back', 45.00, 12, 'Keep torso upright and squeeze shoulder blades.', 0, '2026-10-27 08:59:26'),
(10, NULL, 'e10', 'Dumbbell Bicep Curls', 'Biceps', 14.00, 12, 'Control eccentric negative movement.', 0, '2026-10-27 08:59:26'),
(11, NULL, 'e11', 'Face Pulls', 'Rear Delts', 20.00, 15, 'Pull rope towards forehead, flare elbows.', 0, '2026-10-27 08:59:26'),
(12, NULL, 'e12', 'Barbell Back Squat', 'Quadriceps', 80.00, 8, 'Break at hips, squat to parallel depth.', 0, '2026-10-27 08:59:26'),
(13, NULL, 'e13', 'Romanian Deadlift', 'Hamstrings', 70.00, 10, 'Hinge at hips feeling hamstring stretch.', 0, '2026-10-27 08:59:26'),
(14, NULL, 'e14', 'Leg Press', 'Quadriceps & Glutes', 140.00, 12, 'Controlled depth without lower back rounding.', 0, '2026-10-27 08:59:26'),
(15, NULL, 'e15', 'Standing Calf Raises', 'Calves', 50.00, 15, 'Full stretch at bottom, squeeze at peak.', 0, '2026-10-27 08:59:26'),
(16, 2, 'c_b3a3bb0f', 'Dumbbell Bulgarian Split Squat', 'Quadriceps & Glutes', 16.00, 10, 'Rear foot elevated, upright posture', 1, '2026-10-28 02:44:54'),
(17, 2, 'c_de989761', 'Dumbbell Bulgarian Split Squat', 'Quadriceps & Glutes', 16.00, 10, 'Rear foot elevated, upright posture', 1, '2026-10-28 04:37:17'),
(18, 2, 'c_fad02269', 'Dumbbell Bulgarian Split Squat', 'Quadriceps & Glutes', 16.00, 10, 'Rear foot elevated, upright posture', 1, '2026-10-28 06:39:10');

-- --------------------------------------------------------

--
-- Table structure for table `food_items`
--

CREATE TABLE `food_items` (
  `id` int(10) UNSIGNED NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_items`
--

INSERT INTO `food_items` (`id`, `user_id`, `code`, `name`, `category`, `serving_size_amount`, `serving_unit`, `calories`, `protein_grams`, `carbs_grams`, `fat_grams`, `is_custom`, `created_at`) VALUES
(1, NULL, 'f1', 'Paneer Tikka (Grilled Cottage Cheese)', 'VEG', 100.00, '100g (4 pieces)', 260, 18.00, 6.00, 18.00, 0, '2026-10-27 08:59:26'),
(2, NULL, 'f2', 'Roti / Phulka (Whole Wheat Flatbread)', 'VEG', 30.00, '1 piece (30g)', 80, 2.80, 15.00, 0.80, 0, '2026-10-27 08:59:26'),
(3, NULL, 'f3', 'Chicken Breast Curry (Home Cooked)', 'NON_VEG', 150.00, '150g (1 bowl)', 220, 28.00, 4.00, 10.00, 0, '2026-10-27 08:59:26'),
(4, NULL, 'f4', 'Egg Bhurji (Scrambled Eggs with Spices)', 'EGG', 100.00, '2 eggs (100g)', 170, 13.00, 2.50, 12.00, 0, '2026-10-27 08:59:26'),
(5, NULL, 'f5', 'Dal Tadka (Cooked Yellow Lentils)', 'VEG', 150.00, '150g (1 katori)', 140, 7.50, 18.00, 4.20, 0, '2026-10-27 08:59:26'),
(6, NULL, 'f6', 'Steamed Basmati Rice', 'VEG', 150.00, '150g (1 bowl)', 195, 3.80, 42.00, 0.60, 0, '2026-10-27 08:59:26'),
(7, NULL, 'f7', 'Greek Yogurt (Hung Curd)', 'VEG', 100.00, '100g (1 cup)', 95, 10.00, 4.50, 4.00, 0, '2026-10-27 08:59:26'),
(8, NULL, 'f8', 'Whey Protein Scoop in Water', 'VEG', 30.00, '1 scoop (30g)', 120, 24.00, 2.00, 1.50, 0, '2026-10-27 08:59:26'),
(9, NULL, 'f9', 'Masala Dosa with Chutney', 'VEG', 150.00, '1 dosa (150g)', 320, 6.00, 48.00, 11.00, 0, '2026-10-27 08:59:26'),
(10, NULL, 'f10', 'Idli with Sambar (2 pieces)', 'VEG', 120.00, '2 idlis + sambar', 180, 6.50, 36.00, 1.20, 0, '2026-10-27 08:59:26'),
(11, NULL, 'f11', 'Moong Dal Chilla (Lentil Pancake)', 'VEG', 80.00, '1 chilla (80g)', 130, 8.00, 18.00, 3.00, 0, '2026-10-27 08:59:26'),
(12, NULL, 'f12', 'Chana Masala (Chickpea Curry)', 'VEG', 150.00, '150g (1 bowl)', 210, 9.00, 32.00, 5.00, 0, '2026-10-27 08:59:26'),
(13, 2, 'cf_27b6f42c', 'Homemade Oats Porridge with Almonds', 'VEG', 150.00, '1 bowl (150g)', 240, 9.50, 38.00, 5.50, 1, '2026-10-28 02:45:18'),
(14, 2, 'cf_8e70ac10', 'Homemade Oats Porridge with Almonds', 'VEG', 150.00, '1 bowl (150g)', 240, 9.50, 38.00, 5.50, 1, '2026-10-28 04:37:40'),
(15, 2, 'cf_4a700584', 'Homemade Oats Porridge with Almonds', 'VEG', 150.00, '1 bowl (150g)', 240, 9.50, 38.00, 5.50, 1, '2026-10-28 06:39:36');

-- --------------------------------------------------------

--
-- Table structure for table `health_permissions`
--

CREATE TABLE `health_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `vital_signs` tinyint(1) DEFAULT 1,
  `activity` tinyint(1) DEFAULT 1,
  `sleep` tinyint(1) DEFAULT 1,
  `hydration` tinyint(1) DEFAULT 0,
  `write_health_data` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `health_permissions`
--

INSERT INTO `health_permissions` (`id`, `user_id`, `vital_signs`, `activity`, `sleep`, `hydration`, `write_health_data`, `updated_at`) VALUES
(1, 2, 1, 1, 1, 1, 1, '2026-10-28 02:46:49'),
(4, 1, 1, 1, 1, 1, 1, '2026-10-31 09:03:41'),
(6, 6, 1, 1, 1, 0, 1, '2026-10-31 09:15:12');

-- --------------------------------------------------------

--
-- Table structure for table `hydration_goals`
--

CREATE TABLE `hydration_goals` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `daily_target_ml` int(11) DEFAULT 2000,
  `reminders_enabled` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hydration_goals`
--

INSERT INTO `hydration_goals` (`id`, `user_id`, `daily_target_ml`, `reminders_enabled`, `updated_at`) VALUES
(1, 2, 2500, 1, '2026-10-30 03:28:01'),
(6, 1, 3000, 1, '2026-10-31 08:21:17'),
(8, 9, 3000, 1, '2026-11-02 04:28:03'),
(9, 6, 3000, 1, '2026-11-02 04:39:48');

-- --------------------------------------------------------

--
-- Table structure for table `hydration_logs`
--

CREATE TABLE `hydration_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `volume_ml` int(11) DEFAULT NULL,
  `intake_ml` int(10) UNSIGNED DEFAULT NULL,
  `target_ml` int(10) UNSIGNED NOT NULL DEFAULT 2000,
  `logged_date` date DEFAULT NULL,
  `date_string` date NOT NULL,
  `amount_ml` int(11) NOT NULL,
  `logged_at` datetime NOT NULL DEFAULT current_timestamp(),
  `log_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hydration_logs`
--

INSERT INTO `hydration_logs` (`id`, `user_id`, `volume_ml`, `intake_ml`, `target_ml`, `logged_date`, `date_string`, `amount_ml`, `logged_at`, `log_date`, `created_at`) VALUES
(1, 9, NULL, NULL, 2000, NULL, '2026-10-30', 500, '2026-11-02 09:58:29', NULL, '2026-11-02 04:28:29'),
(2, 6, NULL, NULL, 2000, NULL, '2026-10-30', 500, '2026-11-02 10:09:51', NULL, '2026-11-02 04:39:51'),
(3, 6, NULL, NULL, 2000, NULL, '2026-10-30', 500, '2026-11-02 10:12:45', NULL, '2026-11-02 04:42:45'),
(5, 6, 500, NULL, 2000, NULL, '2026-10-30', 500, '2026-11-02 14:33:27', '2026-10-30', '2026-11-02 09:03:27'),
(6, 6, 750, NULL, 2000, NULL, '2026-10-30', 750, '2026-11-02 14:33:27', '2026-10-30', '2026-11-02 09:03:27'),
(7, 1, 2250, NULL, 2000, NULL, '2026-11-04', 2250, '2026-11-04 10:30:00', '2026-11-04', '2026-11-04 03:09:27'),
(8, 1, 2750, NULL, 2000, NULL, '2026-11-03', 2750, '2026-11-03 10:30:00', '2026-11-03', '2026-11-04 03:09:27'),
(9, 1, 2500, NULL, 2000, NULL, '2026-11-02', 2500, '2026-11-02 10:30:00', '2026-11-02', '2026-11-04 03:09:27'),
(10, 1, 2000, NULL, 2000, NULL, '2026-11-01', 2000, '2026-11-01 10:30:00', '2026-11-01', '2026-11-04 03:09:27'),
(11, 1, 2400, NULL, 2000, NULL, '2026-10-31', 2400, '2026-10-31 10:30:00', '2026-10-31', '2026-11-04 03:09:27'),
(12, 1, 2600, NULL, 2000, NULL, '2026-10-30', 2600, '2026-10-30 10:30:00', '2026-10-30', '2026-11-04 03:09:27'),
(13, 1, 2500, NULL, 2000, NULL, '2026-10-29', 2500, '2026-10-29 10:30:00', '2026-10-29', '2026-11-04 03:09:27'),
(14, 1, 2800, NULL, 2000, NULL, '2026-10-28', 2800, '2026-10-28 10:30:00', '2026-10-28', '2026-11-04 03:09:27'),
(15, 1, 2200, NULL, 2000, NULL, '2026-10-27', 2200, '2026-10-27 10:30:00', '2026-10-27', '2026-11-04 03:09:27'),
(16, 1, 2500, NULL, 2000, NULL, '2026-10-26', 2500, '2026-10-26 10:30:00', '2026-10-26', '2026-11-04 03:09:27'),
(17, 1, 2100, NULL, 2000, NULL, '2026-10-25', 2100, '2026-10-25 10:30:00', '2026-10-25', '2026-11-04 03:09:27'),
(18, 1, 1900, NULL, 2000, NULL, '2026-10-24', 1900, '2026-10-24 10:30:00', '2026-10-24', '2026-11-04 03:09:27'),
(19, 1, 2400, NULL, 2000, NULL, '2026-10-23', 2400, '2026-10-23 10:30:00', '2026-10-23', '2026-11-04 03:09:27'),
(20, 1, 2300, NULL, 2000, NULL, '2026-10-22', 2300, '2026-10-22 10:30:00', '2026-10-22', '2026-11-04 03:09:27'),
(21, 1, 2000, NULL, 2000, NULL, '2026-10-21', 2000, '2026-10-21 10:30:00', '2026-10-21', '2026-11-04 03:09:27'),
(22, 1, 2200, NULL, 2000, NULL, '2026-10-20', 2200, '2026-10-20 10:30:00', '2026-10-20', '2026-11-04 03:09:27'),
(23, 1, 2500, NULL, 2000, NULL, '2026-10-19', 2500, '2026-10-19 10:30:00', '2026-10-19', '2026-11-04 03:09:27'),
(24, 1, 1800, NULL, 2000, NULL, '2026-10-18', 1800, '2026-10-18 10:30:00', '2026-10-18', '2026-11-04 03:09:27'),
(25, 1, 1900, NULL, 2000, NULL, '2026-10-17', 1900, '2026-10-17 10:30:00', '2026-10-17', '2026-11-04 03:09:27'),
(26, 1, 2100, NULL, 2000, NULL, '2026-10-16', 2100, '2026-10-16 10:30:00', '2026-10-16', '2026-11-04 03:09:27'),
(27, 1, 2000, NULL, 2000, NULL, '2026-10-15', 2000, '2026-10-15 10:30:00', '2026-10-15', '2026-11-04 03:09:27'),
(28, 1, 2200, NULL, 2000, NULL, '2026-10-14', 2200, '2026-10-14 10:30:00', '2026-10-14', '2026-11-04 03:09:27'),
(29, 1, 2000, NULL, 2000, NULL, '2026-10-13', 2000, '2026-10-13 10:30:00', '2026-10-13', '2026-11-04 03:09:27'),
(30, 1, 1800, NULL, 2000, NULL, '2026-10-12', 1800, '2026-10-12 10:30:00', '2026-10-12', '2026-11-04 03:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `login_sessions`
--

CREATE TABLE `login_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_os` varchar(50) DEFAULT 'Android',
  `ip_address` varchar(45) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `last_active` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_sessions`
--

INSERT INTO `login_sessions` (`id`, `user_id`, `device_name`, `device_os`, `ip_address`, `location`, `is_current`, `last_active`, `created_at`) VALUES
(1, 2, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-IN) WindowsPowerShell/5.1.26100.9711', 'Android', '127.0.0.1', NULL, 1, '2026-10-27 08:04:15', '2026-10-27 08:04:15'),
(2, 2, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-IN) WindowsPowerShell/5.1.26100.9711', 'Android', '127.0.0.1', NULL, 1, '2026-10-27 08:07:35', '2026-10-27 08:07:35'),
(3, 4, 'Pixel 7 Pro', 'Android', '::1', NULL, 1, '2026-10-27 08:21:58', '2026-10-27 08:21:58'),
(4, 2, 'Pixel 7 Pro', 'Android', '::1', NULL, 1, '2026-10-28 02:49:30', '2026-10-28 02:49:30'),
(5, 2, 'Pixel 7 Pro', 'Android', '::1', NULL, 1, '2026-10-28 04:35:27', '2026-10-28 04:35:27'),
(6, 2, 'Pixel 7 Pro', 'Android', '::1', NULL, 1, '2026-10-28 06:31:26', '2026-10-28 06:31:26'),
(7, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-29 04:07:14', '2026-10-29 04:07:14'),
(8, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-29 04:07:43', '2026-10-29 04:07:43'),
(9, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-10-29 04:31:19', '2026-10-29 04:31:19'),
(10, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-10-29 06:44:44', '2026-10-29 06:44:44'),
(11, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-10-29 07:34:13', '2026-10-29 07:34:13'),
(12, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-10-29 07:41:49', '2026-10-29 07:41:49'),
(13, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-10-29 08:10:42', '2026-10-29 08:10:42'),
(14, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-10-29 08:59:48', '2026-10-29 08:59:48'),
(15, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-30 03:06:30', '2026-10-30 03:06:30'),
(16, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-30 03:41:46', '2026-10-30 03:41:46'),
(19, 2, 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-IN) WindowsPowerShell/5.1.26100.9711', 'Android', '127.0.0.1', NULL, 1, '2026-10-30 05:20:34', '2026-10-30 05:20:34'),
(20, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-30 06:49:23', '2026-10-30 06:49:23'),
(21, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-30 07:13:15', '2026-10-30 07:13:15'),
(22, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-31 04:06:29', '2026-10-31 04:06:29'),
(23, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-31 06:51:10', '2026-10-31 06:51:10'),
(24, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-31 08:04:58', '2026-10-31 08:04:58'),
(25, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-31 08:28:19', '2026-10-31 08:28:19'),
(26, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-31 08:31:37', '2026-10-31 08:31:37'),
(27, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-31 09:07:57', '2026-10-31 09:07:57'),
(28, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-31 09:25:53', '2026-10-31 09:25:53'),
(29, 6, 'Android Device', 'Android', '172.23.49.55', NULL, 1, '2026-10-31 09:30:20', '2026-10-31 09:30:20'),
(30, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-11-02 04:32:00', '2026-11-02 04:32:00'),
(31, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-11-02 04:45:40', '2026-11-02 04:45:40'),
(32, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-11-02 05:00:02', '2026-11-02 05:00:02'),
(33, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-11-02 05:04:58', '2026-11-02 05:04:58'),
(34, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-11-02 05:15:32', '2026-11-02 05:15:32'),
(35, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-11-02 07:04:16', '2026-11-02 07:04:16'),
(36, 6, 'Android Device', 'Android', '172.23.51.62', NULL, 1, '2026-11-03 02:37:41', '2026-11-03 02:37:41'),
(37, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-11-04 03:38:02', '2026-11-04 03:38:02');

-- --------------------------------------------------------

--
-- Table structure for table `meal_entries`
--

CREATE TABLE `meal_entries` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `food_item_id` int(10) UNSIGNED DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` decimal(5,2) NOT NULL DEFAULT 1.00,
  `calculated_calories` int(11) NOT NULL,
  `calculated_protein` decimal(6,2) NOT NULL,
  `calculated_carbs` decimal(6,2) NOT NULL,
  `calculated_fat` decimal(6,2) NOT NULL,
  `date_string` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(50) DEFAULT 'BELL',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_goals`
--

CREATE TABLE `nutrition_goals` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `age` int(11) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `activity_level` varchar(50) DEFAULT NULL,
  `fitness_goal` varchar(50) DEFAULT NULL,
  `dietary_preference` varchar(50) DEFAULT NULL,
  `daily_calorie_target` int(11) DEFAULT NULL,
  `daily_protein_target` decimal(6,2) DEFAULT NULL,
  `daily_carbs_target` decimal(6,2) DEFAULT NULL,
  `daily_fat_target` decimal(6,2) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nutrition_goals`
--

INSERT INTO `nutrition_goals` (`id`, `user_id`, `age`, `weight_kg`, `height_cm`, `activity_level`, `fitness_goal`, `dietary_preference`, `daily_calorie_target`, `daily_protein_target`, `daily_carbs_target`, `daily_fat_target`, `updated_at`) VALUES
(1, 2, 28, 70.00, 175.00, 'Moderately Active', 'MUSCLE_GAIN', 'NON_VEGETARIAN', 2350, 145.00, 260.00, 70.00, '2026-10-29 08:08:55');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(100) NOT NULL,
  `time` varchar(20) NOT NULL,
  `frequency` varchar(50) DEFAULT 'Every day',
  `repeat_days` varchar(50) DEFAULT '0,1,2,3,4,5,6',
  `sound_vibration` tinyint(1) DEFAULT 1,
  `is_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sleep_goals`
--

CREATE TABLE `sleep_goals` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `target_minutes` int(11) NOT NULL DEFAULT 480,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sleep_logs`
--

CREATE TABLE `sleep_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `bedtime` varchar(50) NOT NULL DEFAULT '23:00',
  `wake_time` varchar(50) NOT NULL DEFAULT '07:00',
  `duration_minutes` int(10) UNSIGNED NOT NULL DEFAULT 480,
  `quality_score` int(11) DEFAULT NULL,
  `logged_at` date DEFAULT NULL,
  `status_color` varchar(20) NOT NULL DEFAULT 'optimal',
  `logged_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sleep_logs`
--

INSERT INTO `sleep_logs` (`id`, `user_id`, `start_time`, `end_time`, `bedtime`, `wake_time`, `duration_minutes`, `quality_score`, `logged_at`, `status_color`, `logged_date`, `created_at`) VALUES
(1, 1, '2026-11-04 22:30:00', '2026-11-05 06:45:00', '10:30 PM', '06:45 AM', 495, 88, '2026-11-04', 'optimal', '2026-11-04', '2026-11-04 03:23:19'),
(2, 1, '2026-11-03 22:30:00', '2026-11-04 06:15:00', '10:45 PM', '06:30 AM', 465, 86, '2026-11-03', 'optimal', '2026-11-03', '2026-11-04 03:23:19'),
(3, 1, '2026-11-02 22:30:00', '2026-11-03 05:00:00', '11:45 PM', '06:15 AM', 390, 74, '2026-11-02', 'moderate', '2026-11-02', '2026-11-04 03:23:19'),
(4, 1, '2026-11-01 22:30:00', '2026-11-02 03:50:00', '01:00 AM', '06:20 AM', 320, 62, '2026-11-01', 'deficit', '2026-11-01', '2026-11-04 03:23:19'),
(5, 1, '2026-10-31 22:30:00', '2026-11-01 06:20:00', '10:30 PM', '06:20 AM', 470, 89, '2026-10-31', 'optimal', '2026-10-31', '2026-11-04 03:23:19'),
(6, 1, '2026-10-30 22:30:00', '2026-10-31 08:15:00', '10:00 PM', '07:45 AM', 585, 91, '2026-10-30', 'optimal', '2026-10-30', '2026-11-04 03:23:19'),
(7, 1, '2026-10-29 22:30:00', '2026-10-30 05:15:00', '11:15 PM', '06:00 AM', 405, 76, '2026-10-29', 'moderate', '2026-10-29', '2026-11-04 03:23:19');

-- --------------------------------------------------------

--
-- Table structure for table `sleep_records`
--

CREATE TABLE `sleep_records` (
  `id` int(10) UNSIGNED NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sleep_records`
--

INSERT INTO `sleep_records` (`id`, `user_id`, `date_string`, `duration_minutes`, `sleep_score`, `quality_label`, `bedtime`, `wake_time`, `deep_sleep_minutes`, `light_sleep_minutes`, `rem_sleep_minutes`, `awake_minutes`, `created_at`) VALUES
(1, 1, '2026-11-04', 495, 88, 'Restorative', '10:30 PM', '06:45 AM', 110, 260, 100, 25, '2026-11-04 03:23:19'),
(2, 1, '2026-11-03', 465, 86, 'Restorative', '10:45 PM', '06:30 AM', 105, 245, 95, 20, '2026-11-04 03:23:19'),
(3, 1, '2026-11-02', 390, 74, 'Fair', '11:45 PM', '06:15 AM', 75, 215, 75, 25, '2026-11-04 03:23:19'),
(4, 1, '2026-11-01', 320, 62, 'Sleep Debt', '01:00 AM', '06:20 AM', 50, 180, 60, 30, '2026-11-04 03:23:19'),
(5, 1, '2026-10-31', 470, 89, 'Restorative', '10:30 PM', '06:20 AM', 115, 240, 95, 20, '2026-11-04 03:23:19'),
(6, 1, '2026-10-30', 585, 91, 'Long Sleep', '10:00 PM', '07:45 AM', 135, 300, 120, 30, '2026-11-04 03:23:19'),
(7, 1, '2026-10-29', 405, 76, 'Fair', '11:15 PM', '06:00 AM', 80, 225, 80, 20, '2026-11-04 03:23:19');

-- --------------------------------------------------------

--
-- Table structure for table `steps_logs`
--

CREATE TABLE `steps_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `step_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `target_steps` int(10) UNSIGNED NOT NULL DEFAULT 8000,
  `distance_km` decimal(6,2) NOT NULL DEFAULT 0.00,
  `calories_kcal` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `active_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `logged_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(50) DEFAULT 'open',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_id`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 1, 'Test Question', 'How do I pair my BLE sensor?', 'open', '2026-10-31 14:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `dob` varchar(50) DEFAULT NULL,
  `age` int(3) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `activity_level` varchar(50) DEFAULT NULL,
  `primary_goal` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `name`, `email`, `phone`, `dob`, `age`, `gender`, `height`, `weight`, `activity_level`, `primary_goal`, `password_hash`, `created_at`, `updated_at`, `profile_image`) VALUES
(1, 'Nitesh Kumar', 'nitesh', 'nitesvgho132@gmail.com', '+919876543210', NULL, 25, 'Male', 178.50, 72.00, NULL, 'Build Muscle', '$2y$10$YYNtZrphCXPCvJtPuWMRB.4VqL2NCQwQy6NaRjbZ2R0wO4w757ybm', '2026-10-26 04:38:17', '2026-11-03 03:20:15', 'uploads/profiles/user_1_0d5dfe7246e3b986.jpg'),
(2, 'nitesh Mv', 'nitesh', 'alex.morgan@email.com', '+1 (202) 555-0147', '14/10/2007', NULL, 'Male', NULL, NULL, 'Moderately active', 'Better Sleep', '$2y$10$enG2E.skKd5jVHJ7B3PKne.JC16O19SM5bfC1JlKJj6csl7ByQ7bq', '2026-10-27 08:03:22', '2026-11-02 03:01:52', 'uploads/profiles/user_2_f967d4e414fdcf0c.jpg'),
(3, 'Alex Morgan', 'Alex Morgan', 'alex.morgan.20261027@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$DJdm.OTwpUYIptmC2FF6yeRMkIBcXVJuSbivfdbJSuanB592Jh/CW', '2026-10-27 08:20:15', '2026-11-02 03:01:52', NULL),
(4, 'Alex Morgan', 'Alex Morgan', 'alex.morgan.t0820z7@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$8r2fBWD1uLygo4hmTvlR/uDKJMxEWMcJTLBUPgc0WyawF8NN7Er4O', '2026-10-27 08:21:15', '2026-11-02 03:01:52', NULL),
(5, 'Alex Morgan', 'Alex Morgan', 'alex.morgan22222222222@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$Cwg8nA.0X5p9kj1DL.83zetk9jGCctkeznsQzb6dpUapqLcIzQ1S.', '2026-10-28 02:49:25', '2026-11-02 03:01:52', NULL),
(6, 'nitesh', 'nitesh Mv', 'niteshmanjula886@gmail.com', '8825780695', '14/10/2007', 18, 'male', 175.00, 80.00, NULL, 'Weight Loss', '$2y$10$w5ZKRQZaxWLEH5MUeiIqb.G0qOXkq34uA7CREKONaktAVjuzw3gTC', '2026-10-29 04:03:11', '2026-11-04 03:41:06', NULL),
(7, 'DAKSH', 'DAKSH', 'daksh@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$azbbkGA5D6XH164ZJPsiSu1Lwvp/IE8oJbjnsN4fVz87IRWh9KhjO', '2026-10-29 04:08:41', '2026-11-02 03:01:52', NULL),
(9, 'nitesh', NULL, 'niteshghost@141gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Fitness', '$2y$10$FXYRuFWUZRcsYXCZbBmkuOSrG8EmB18XReFsRMICm2VTgDIZJxDzW', '2026-11-02 03:20:47', '2026-11-02 03:20:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_app_settings`
--

CREATE TABLE `user_app_settings` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `push_notifications` tinyint(1) DEFAULT 1,
  `vital_alerts` tinyint(1) DEFAULT 1,
  `daily_reminders` tinyint(1) DEFAULT 1,
  `metric_units` varchar(10) DEFAULT 'metric',
  `dark_mode` tinyint(1) DEFAULT 0,
  `data_sync_interval` int(11) DEFAULT 15,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ai_insights` tinyint(1) DEFAULT 1,
  `usage_analytics` tinyint(1) DEFAULT 0,
  `ai_insights_enabled` tinyint(1) DEFAULT 1,
  `user_analytics_enabled` tinyint(1) DEFAULT 1,
  `reminder_water_interval` int(11) DEFAULT 120,
  `reminder_workout_enabled` tinyint(1) DEFAULT 1,
  `reminder_sleep_enabled` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_app_settings`
--

INSERT INTO `user_app_settings` (`user_id`, `push_notifications`, `vital_alerts`, `daily_reminders`, `metric_units`, `dark_mode`, `data_sync_interval`, `updated_at`, `ai_insights`, `usage_analytics`, `ai_insights_enabled`, `user_analytics_enabled`, `reminder_water_interval`, `reminder_workout_enabled`, `reminder_sleep_enabled`) VALUES
(1, 1, 1, 1, '0', 1, 10, '2026-10-31 13:51:17', 1, 0, 1, 1, 120, 1, 1),
(2, 1, 1, 1, 'metric', 0, 15, '2026-10-31 13:47:37', 1, 0, 1, 1, 120, 1, 1),
(3, 1, 1, 1, 'metric', 0, 15, '2026-10-31 13:47:37', 1, 0, 1, 1, 120, 1, 1),
(4, 1, 1, 1, 'metric', 0, 15, '2026-10-31 13:47:37', 1, 0, 1, 1, 120, 1, 1),
(5, 1, 1, 1, 'metric', 0, 15, '2026-10-31 13:47:37', 1, 0, 1, 1, 120, 1, 1),
(6, 1, 1, 0, 'metric', 0, 15, '2026-11-02 14:19:55', 1, 1, 1, 1, 120, 1, 1),
(7, 1, 1, 1, 'metric', 0, 15, '2026-10-31 13:47:37', 1, 0, 1, 1, 120, 1, 1),
(9, 1, 1, 1, 'metric', 0, 15, '2026-11-02 08:50:47', 1, 0, 1, 1, 120, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_devices`
--

CREATE TABLE `user_devices` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `mac_address` varchar(100) NOT NULL,
  `device_category` varchar(100) NOT NULL DEFAULT 'SMARTWATCH',
  `is_connected` tinyint(1) NOT NULL DEFAULT 1,
  `battery_level` int(11) DEFAULT NULL,
  `last_synced_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_devices`
--

INSERT INTO `user_devices` (`id`, `user_id`, `device_name`, `mac_address`, `device_category`, `is_connected`, `battery_level`, `last_synced_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Galaxy Watch 6 Pro', 'AA:BB:CC:11:22:33', 'SMARTWATCH', 1, 82, '2026-10-31 12:20:03', '2026-10-31 06:48:40', '2026-10-31 06:50:03'),
(2, 1, 'Galaxy Watch Ultra', 'AA:BB:CC:DD:EE:01', 'SMARTWATCH', 1, 88, '2026-10-31 12:33:56', '2026-10-31 07:03:56', '2026-10-31 07:03:56'),
(3, 1, 'Fitbit Charge 6', 'F0:12:34:56:78:9A', 'smartwatch', 1, 85, '2026-10-31 13:07:35', '2026-10-31 07:37:34', '2026-10-31 07:37:35'),
(4, 2, 'HP Track', 'E6:95:EC:FE:16:D2', 'smartwatch', 1, NULL, '2026-10-31 13:24:22', '2026-10-31 07:37:38', '2026-10-31 07:54:22'),
(5, 1, 'Pixel Watch 3', 'E4:5F:01:23:45:67', 'smartwatch', 1, 90, '2026-10-31 13:10:24', '2026-10-31 07:38:47', '2026-10-31 07:40:24');

-- --------------------------------------------------------

--
-- Table structure for table `user_health_goals`
--

CREATE TABLE `user_health_goals` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `primary_goal` varchar(100) DEFAULT NULL,
  `target_steps` int(11) DEFAULT NULL,
  `target_sleep_hours` decimal(3,1) DEFAULT NULL,
  `target_water_ml` int(11) DEFAULT NULL,
  `target_weight_kg` decimal(5,1) DEFAULT NULL,
  `target_calories` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_health_goals`
--

INSERT INTO `user_health_goals` (`user_id`, `primary_goal`, `target_steps`, `target_sleep_hours`, `target_water_ml`, `target_weight_kg`, `target_calories`, `updated_at`) VALUES
(1, 'Build Muscle', 12000, 8.5, 3000, 68.0, 2100, '2026-11-03 08:50:15'),
(2, 'Better Sleep', NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(3, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(4, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(5, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(6, 'Weight Loss', 10000, 8.0, 2500, NULL, 2200, '2026-11-04 09:11:06'),
(7, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(9, 'Fitness', NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:50:47');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `height_cm` decimal(5,1) DEFAULT NULL,
  `weight_kg` decimal(5,1) DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`user_id`, `full_name`, `age`, `gender`, `height_cm`, `weight_kg`, `blood_group`, `phone`, `avatar_url`, `updated_at`) VALUES
(1, 'Nitesh Kumar', 25, 'Male', 178.5, 72.0, 'O+', '+919876543210', NULL, '2026-10-31 13:51:17'),
(2, 'nitesh Mv', NULL, 'Male', NULL, NULL, NULL, '+1 (202) 555-0147', NULL, '2026-11-02 08:25:19'),
(3, 'Alex Morgan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(4, 'Alex Morgan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(5, 'Alex Morgan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(6, 'nitesh', 18, 'male', 175.0, 80.0, NULL, '8825780695', NULL, '2026-11-03 08:09:04'),
(7, 'DAKSH', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(9, 'nitesh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:50:47');

-- --------------------------------------------------------

--
-- Table structure for table `vitals_logs`
--

CREATE TABLE `vitals_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `device_id` int(10) UNSIGNED DEFAULT NULL,
  `heart_rate` int(10) UNSIGNED DEFAULT NULL,
  `spo2` int(10) UNSIGNED DEFAULT NULL,
  `systolic_bp` int(10) UNSIGNED DEFAULT NULL,
  `diastolic_bp` int(10) UNSIGNED DEFAULT NULL,
  `source` varchar(100) NOT NULL DEFAULT 'bluetooth',
  `logged_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vitals_readings`
--

CREATE TABLE `vitals_readings` (
  `id` int(10) UNSIGNED NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vitals_readings`
--

INSERT INTO `vitals_readings` (`id`, `user_id`, `reading_type`, `value_numeric`, `value_secondary`, `value_text`, `unit`, `status_label`, `source`, `notes`, `recorded_at`, `created_at`) VALUES
(1, 6, 'bmi', 18.40, NULL, '18.4', 'bmi', 'Healthy Range', 'Manual Entry', NULL, '2026-11-02 09:10:22', '2026-11-02 08:10:22'),
(2, 6, 'bmi', 49.00, NULL, '49.0', 'bmi', 'Healthy Range', 'Manual Entry', NULL, '2026-11-02 09:11:09', '2026-11-02 08:11:09'),
(3, 6, 'bmi', 66.70, NULL, '66.7', 'bmi', 'Healthy Range', 'Manual Entry', NULL, '2026-11-02 09:11:30', '2026-11-02 08:11:30'),
(4, 6, 'bmi', 26.10, NULL, '26.1', 'bmi', 'Overweight', 'Manual Entry', NULL, '2026-11-02 10:01:18', '2026-11-02 09:01:18'),
(5, 6, 'bmi', 26.10, NULL, '26.1', 'bmi', 'Overweight', 'Manual Entry', NULL, '2026-11-02 10:02:38', '2026-11-02 09:02:38');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_health_reports`
--

CREATE TABLE `weekly_health_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `wellness_score` int(11) NOT NULL,
  `performance_category` enum('normal','mid','low') NOT NULL,
  `heart_rate_score` int(11) NOT NULL,
  `sleep_score` int(11) NOT NULL,
  `hydration_score` int(11) NOT NULL,
  `activity_score` int(11) NOT NULL,
  `summary_text` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `weekly_health_reports`
--

INSERT INTO `weekly_health_reports` (`id`, `user_id`, `week_start_date`, `week_end_date`, `wellness_score`, `performance_category`, `heart_rate_score`, `sleep_score`, `hydration_score`, `activity_score`, `summary_text`, `created_at`) VALUES
(1, 1, '2026-11-02', '2026-11-08', 50, 'mid', 0, 0, 0, 50, 'Weekly clinical performance: Physical activity averaged 1,867 steps/day (Low). Wellness grade: MID (50/100).', '2026-11-02 14:51:05'),
(2, 6, '2026-11-02', '2026-11-08', 53, 'mid', 0, 0, 55, 50, 'Weekly clinical performance: Hydration averaged 563 ml daily (Low). Physical activity averaged 4 steps/day (Low). Wellness grade: MID (53/100).', '2026-11-03 08:10:43');

-- --------------------------------------------------------

--
-- Table structure for table `workouts`
--

CREATE TABLE `workouts` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `workout_type` varchar(100) NOT NULL DEFAULT 'General Workout',
  `status` varchar(50) NOT NULL DEFAULT 'COMPLETED',
  `duration_seconds` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `calories_burned` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workout_exercise_sets`
--

CREATE TABLE `workout_exercise_sets` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_exercise_id` int(10) UNSIGNED NOT NULL,
  `set_number` int(11) NOT NULL,
  `reps` int(11) NOT NULL,
  `weight_kg` decimal(6,2) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `workout_sessions`
--

CREATE TABLE `workout_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `session_uuid` varchar(64) DEFAULT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT 0,
  `target_type` enum('time','distance') DEFAULT 'time',
  `target_value` int(11) DEFAULT 45,
  `distance_meters` decimal(8,2) DEFAULT 0.00,
  `steps` int(11) DEFAULT 0,
  `distance_km` decimal(6,2) DEFAULT 0.00,
  `avg_pace` varchar(20) DEFAULT '00:00 /km',
  `calories` int(11) DEFAULT 0,
  `tracking_source` varchar(30) DEFAULT 'phone_sensor',
  `avg_heart_rate` int(11) DEFAULT NULL,
  `session_name` varchar(150) NOT NULL,
  `split_type` varchar(50) NOT NULL,
  `date_string` date NOT NULL,
  `timestamp_formatted` varchar(100) NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 45,
  `total_volume_kg` decimal(8,2) DEFAULT 0.00,
  `estimated_calories_burned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workout_sessions`
--

INSERT INTO `workout_sessions` (`id`, `user_id`, `session_uuid`, `activity_type`, `start_time`, `end_time`, `duration_seconds`, `target_type`, `target_value`, `distance_meters`, `steps`, `distance_km`, `avg_pace`, `calories`, `tracking_source`, `avg_heart_rate`, `session_name`, `split_type`, `date_string`, `timestamp_formatted`, `duration_minutes`, `total_volume_kg`, `estimated_calories_burned`, `created_at`) VALUES
(1, 6, 'b06ea3bc-fd74-4a28-9e71-f7bd2b5b27f7', 'Walking', '2026-11-02 08:41:50', '2026-11-02 08:42:06', 16, 'time', 15, 0.00, 15, 0.01, '11:45 /km', 1, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-02 07:41:50'),
(2, 6, '6095c579-82b5-4639-a1ef-3598c5bc9a81', 'Cycling', '2026-11-02 08:42:30', '2026-11-02 08:42:41', 11, 'time', 30, 0.00, 0, 0.06, '2:52 /km', 1, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-02 07:42:30'),
(3, 1, 'test-session-cli-1793606461', 'Walking', '2026-11-02 08:31:01', '2026-11-02 09:01:01', 1800, 'time', 30, 0.00, 2800, 2.15, '13:57 /km', 125, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-02 08:01:01'),
(4, 1, 'test-session-cli-1793606509', 'Walking', '2026-11-02 08:31:49', '2026-11-02 09:01:49', 1800, 'time', 30, 0.00, 2800, 2.15, '13:57 /km', 125, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-02 08:01:49'),
(5, 1, 'cycling-test-1793607285', 'Cycling', '2026-11-02 08:49:45', '2026-11-02 09:14:45', 1500, 'distance', 10, 0.00, 0, 8.45, '20.3 km/h', 210, 'gps', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-02 08:14:45'),
(6, 6, 'b4fdba9f-b5bc-47ee-8fa3-bae320b7f116', 'Cycling', '2026-11-02 09:22:55', '2026-11-02 09:24:55', 120, 'time', 30, 0.00, 0, 0.02, '0.7 km/h', 35, 'gps', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-02 08:22:55'),
(7, 6, '7c3b1cff-ab99-40e2-b0bb-41b9548bfc4c', 'Cycling', '2026-11-02 10:14:28', '2026-11-02 10:14:32', 4, 'time', 30, 0.00, 0, 0.02, '20.5 km/h', 0, 'gps', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-02 09:14:28');

-- --------------------------------------------------------

--
-- Table structure for table `workout_session_exercises`
--

CREATE TABLE `workout_session_exercises` (
  `id` int(10) UNSIGNED NOT NULL,
  `workout_session_id` int(10) UNSIGNED NOT NULL,
  `exercise_id` int(10) UNSIGNED DEFAULT NULL,
  `exercise_name` varchar(150) NOT NULL,
  `target_muscle` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_goals`
--
ALTER TABLE `activity_goals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `activity_sessions`
--
ALTER TABLE `activity_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_user_time` (`user_id`,`start_time`);

--
-- Indexes for table `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ai_user` (`user_id`,`created_at`);

--
-- Indexes for table `ai_daily_insights`
--
ALTER TABLE `ai_daily_insights`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_date_cat` (`user_id`,`insight_date`,`category`),
  ADD KEY `idx_user_date` (`user_id`,`insight_date`);

--
-- Indexes for table `ai_knowledge_base`
--
ALTER TABLE `ai_knowledge_base`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `ai_knowledge_base` ADD FULLTEXT KEY `idx_ft_knowledge` (`keywords`,`content`);
ALTER TABLE `ai_knowledge_base` ADD FULLTEXT KEY `idx_ft_all` (`topic`,`keywords`,`content`);

--
-- Indexes for table `connected_devices`
--
ALTER TABLE `connected_devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_devices_user` (`user_id`);

--
-- Indexes for table `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exercise_user` (`user_id`);

--
-- Indexes for table `food_items`
--
ALTER TABLE `food_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_food_user` (`user_id`);

--
-- Indexes for table `health_permissions`
--
ALTER TABLE `health_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `hydration_goals`
--
ALTER TABLE `hydration_goals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `hydration_logs`
--
ALTER TABLE `hydration_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hydration_user_date` (`user_id`,`date_string`),
  ADD KEY `idx_user_water` (`user_id`,`log_date`),
  ADD KEY `idx_user_hydration_date` (`user_id`,`log_date`);

--
-- Indexes for table `login_sessions`
--
ALTER TABLE `login_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `meal_entries`
--
ALTER TABLE `meal_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_meal_user_date` (`user_id`,`date_string`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`,`is_read`);

--
-- Indexes for table `nutrition_goals`
--
ALTER TABLE `nutrition_goals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sleep_goals`
--
ALTER TABLE `sleep_goals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `sleep_logs`
--
ALTER TABLE `sleep_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_sleep_date` (`user_id`,`logged_date`),
  ADD KEY `idx_user_sleep` (`user_id`,`logged_at`);

--
-- Indexes for table `sleep_records`
--
ALTER TABLE `sleep_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_sleep_user_date` (`user_id`,`date_string`);

--
-- Indexes for table `steps_logs`
--
ALTER TABLE `steps_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_step_date` (`user_id`,`logged_date`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_app_settings`
--
ALTER TABLE `user_app_settings`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_devices`
--
ALTER TABLE `user_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_mac` (`user_id`,`mac_address`),
  ADD KEY `idx_user_devices_conn` (`user_id`,`is_connected`);

--
-- Indexes for table `user_health_goals`
--
ALTER TABLE `user_health_goals`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `vitals_logs`
--
ALTER TABLE `vitals_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_vitals_date` (`user_id`,`logged_at`),
  ADD KEY `fk_vitals_logs_device` (`device_id`),
  ADD KEY `idx_user_time` (`user_id`,`logged_at`),
  ADD KEY `idx_user_hr` (`user_id`,`heart_rate`);

--
-- Indexes for table `vitals_readings`
--
ALTER TABLE `vitals_readings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vitals_user_type` (`user_id`,`reading_type`),
  ADD KEY `idx_vitals_recorded` (`recorded_at`);

--
-- Indexes for table `weekly_health_reports`
--
ALTER TABLE `weekly_health_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_week` (`user_id`,`week_start_date`);

--
-- Indexes for table `workouts`
--
ALTER TABLE `workouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_workout` (`user_id`,`started_at`);

--
-- Indexes for table `workout_exercise_sets`
--
ALTER TABLE `workout_exercise_sets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_set_ex` (`session_exercise_id`);

--
-- Indexes for table `workout_sessions`
--
ALTER TABLE `workout_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workout_user` (`user_id`,`date_string`),
  ADD KEY `idx_user_workout` (`user_id`,`start_time`);

--
-- Indexes for table `workout_session_exercises`
--
ALTER TABLE `workout_session_exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_ex` (`workout_session_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_goals`
--
ALTER TABLE `activity_goals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `activity_sessions`
--
ALTER TABLE `activity_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `ai_daily_insights`
--
ALTER TABLE `ai_daily_insights`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ai_knowledge_base`
--
ALTER TABLE `ai_knowledge_base`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `connected_devices`
--
ALTER TABLE `connected_devices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `food_items`
--
ALTER TABLE `food_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `health_permissions`
--
ALTER TABLE `health_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `hydration_goals`
--
ALTER TABLE `hydration_goals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `hydration_logs`
--
ALTER TABLE `hydration_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `login_sessions`
--
ALTER TABLE `login_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `meal_entries`
--
ALTER TABLE `meal_entries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nutrition_goals`
--
ALTER TABLE `nutrition_goals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sleep_goals`
--
ALTER TABLE `sleep_goals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sleep_logs`
--
ALTER TABLE `sleep_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sleep_records`
--
ALTER TABLE `sleep_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `steps_logs`
--
ALTER TABLE `steps_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `vitals_logs`
--
ALTER TABLE `vitals_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vitals_readings`
--
ALTER TABLE `vitals_readings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `weekly_health_reports`
--
ALTER TABLE `weekly_health_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `workouts`
--
ALTER TABLE `workouts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workout_exercise_sets`
--
ALTER TABLE `workout_exercise_sets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `workout_sessions`
--
ALTER TABLE `workout_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `workout_session_exercises`
--
ALTER TABLE `workout_session_exercises`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_goals`
--
ALTER TABLE `activity_goals`
  ADD CONSTRAINT `fk_activity_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_sessions`
--
ALTER TABLE `activity_sessions`
  ADD CONSTRAINT `fk_activity_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  ADD CONSTRAINT `fk_ai_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `connected_devices`
--
ALTER TABLE `connected_devices`
  ADD CONSTRAINT `fk_devices_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exercises`
--
ALTER TABLE `exercises`
  ADD CONSTRAINT `fk_exercises_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `food_items`
--
ALTER TABLE `food_items`
  ADD CONSTRAINT `fk_food_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `health_permissions`
--
ALTER TABLE `health_permissions`
  ADD CONSTRAINT `fk_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hydration_goals`
--
ALTER TABLE `hydration_goals`
  ADD CONSTRAINT `fk_hydration_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hydration_logs`
--
ALTER TABLE `hydration_logs`
  ADD CONSTRAINT `fk_hydration_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_sessions`
--
ALTER TABLE `login_sessions`
  ADD CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meal_entries`
--
ALTER TABLE `meal_entries`
  ADD CONSTRAINT `fk_meal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nutrition_goals`
--
ALTER TABLE `nutrition_goals`
  ADD CONSTRAINT `fk_nutrition_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `fk_reminder_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sleep_goals`
--
ALTER TABLE `sleep_goals`
  ADD CONSTRAINT `sleep_goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sleep_logs`
--
ALTER TABLE `sleep_logs`
  ADD CONSTRAINT `sleep_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sleep_records`
--
ALTER TABLE `sleep_records`
  ADD CONSTRAINT `fk_sleep_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `steps_logs`
--
ALTER TABLE `steps_logs`
  ADD CONSTRAINT `steps_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_app_settings`
--
ALTER TABLE `user_app_settings`
  ADD CONSTRAINT `user_app_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_devices`
--
ALTER TABLE `user_devices`
  ADD CONSTRAINT `user_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_health_goals`
--
ALTER TABLE `user_health_goals`
  ADD CONSTRAINT `user_health_goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vitals_logs`
--
ALTER TABLE `vitals_logs`
  ADD CONSTRAINT `fk_vitals_logs_device` FOREIGN KEY (`device_id`) REFERENCES `user_devices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vitals_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vitals_readings`
--
ALTER TABLE `vitals_readings`
  ADD CONSTRAINT `fk_vitals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `weekly_health_reports`
--
ALTER TABLE `weekly_health_reports`
  ADD CONSTRAINT `weekly_health_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workouts`
--
ALTER TABLE `workouts`
  ADD CONSTRAINT `workouts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_exercise_sets`
--
ALTER TABLE `workout_exercise_sets`
  ADD CONSTRAINT `fk_set_session_ex` FOREIGN KEY (`session_exercise_id`) REFERENCES `workout_session_exercises` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_sessions`
--
ALTER TABLE `workout_sessions`
  ADD CONSTRAINT `fk_workout_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workout_session_exercises`
--
ALTER TABLE `workout_session_exercises`
  ADD CONSTRAINT `fk_session_ex` FOREIGN KEY (`workout_session_id`) REFERENCES `workout_sessions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
