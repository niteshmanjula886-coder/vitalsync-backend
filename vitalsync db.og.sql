-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2026 at 04:08 AM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.3.36

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
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Bookmarks';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Settings related to Designer';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- Table structure for table `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Favorite tables';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='SQL history for phpMyAdmin';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Recently accessed tables';

--
-- Dumping data for table `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"vitalsync\",\"table\":\"users\"}]');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Relation table';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Saved searches';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Table information for phpMyAdmin';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Tables'' UI preferences';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Dumping data for table `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2026-11-16 03:08:00', '{\"Console\\/Mode\":\"collapse\"}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Users and their assignments to user groups';

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
  `sender` enum('user','ai') NOT NULL,
  `message` text NOT NULL,
  `message_text` text DEFAULT NULL,
  `time_text` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `model_used` varchar(50) DEFAULT 'qwen2.5:1.5b',
  `tokens_used` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_chat_messages`
--

INSERT INTO `ai_chat_messages` (`id`, `user_id`, `sender`, `message`, `message_text`, `time_text`, `created_at`, `model_used`, `tokens_used`) VALUES
(1, 2, 'user', 'How is my resting heart rate today?', 'How is my resting heart rate today?', '10:09 AM', '2026-10-27 09:09:10', 'llama3.2:3b', 0),
(2, 2, 'ai', 'Your resting heart rate averages around 64–76 BPM, which is in the healthy normal range. Maintaining moderate aerobic activity and staying well-hydrated helps keep your cardiovascular rhythm balanced. Note: VitalSync provides wellness insights, not clinical medical advice.', 'Your resting heart rate averages around 64–76 BPM, which is in the healthy normal range. Maintaining moderate aerobic activity and staying well-hydrated helps keep your cardiovascular rhythm balanced. Note: VitalSync provides wellness insights, not clinical medical advice.', '10:09 AM', '2026-10-27 09:09:10', 'llama3.2:3b', 0),
(3, 2, 'user', 'What should my post-workout meal look like today?', 'What should my post-workout meal look like today?', '03:45 AM', '2026-10-28 02:45:52', 'llama3.2:3b', 0),
(4, 2, 'ai', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', '03:45 AM', '2026-10-28 02:45:52', 'llama3.2:3b', 0),
(5, 2, 'user', 'What should my post-workout meal look like today?', 'What should my post-workout meal look like today?', '05:38 AM', '2026-10-28 04:38:15', 'llama3.2:3b', 0),
(6, 2, 'ai', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', '05:38 AM', '2026-10-28 04:38:15', 'llama3.2:3b', 0),
(7, 2, 'user', 'What should my post-workout meal look like today?', 'What should my post-workout meal look like today?', '07:39 AM', '2026-10-28 06:39:58', 'llama3.2:3b', 0),
(8, 2, 'ai', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', '07:39 AM', '2026-10-28 06:39:58', 'llama3.2:3b', 0),
(9, 2, 'user', 'How is my heart rate and sleep?', 'How is my heart rate and sleep?', '06:23 AM', '2026-10-30 05:23:50', 'llama3.2:3b', 0),
(10, 2, 'ai', 'Your resting heart rate averages around 64–76 BPM, which is in the healthy normal range. Maintaining moderate aerobic activity and staying well-hydrated helps keep your cardiovascular rhythm balanced. Note: VitalSync provides wellness insights, not clinical medical advice.', 'Your resting heart rate averages around 64–76 BPM, which is in the healthy normal range. Maintaining moderate aerobic activity and staying well-hydrated helps keep your cardiovascular rhythm balanced. Note: VitalSync provides wellness insights, not clinical medical advice.', '06:23 AM', '2026-10-30 05:23:50', 'llama3.2:3b', 0),
(11, 1, 'user', 'Hi Doctor, how is my resting heart rate today?', 'Hi Doctor, how is my resting heart rate today?', '05:05 AM', '2026-11-03 04:05:14', 'llama3.2:3b', 0),
(12, 1, 'ai', 'Hello nitesh, your latest resting heart rate is recorded at 74 BPM, which falls comfortably within the healthy clinical range (60–100 BPM). Your cardiovascular rhythm indicates steady autonomic balance. Maintaining structured cardiovascular pacing and adequate hydration will keep your heart rate variability optimal.', 'Hello nitesh, your latest resting heart rate is recorded at 74 BPM, which falls comfortably within the healthy clinical range (60–100 BPM). Your cardiovascular rhythm indicates steady autonomic balance. Maintaining structured cardiovascular pacing and adequate hydration will keep your heart rate variability optimal.', '05:05 AM', '2026-11-03 04:05:18', 'llama3.2:fallback', 0),
(24, 1, 'user', 'Who directed Inception and what is it about?', 'Who directed Inception and what is it about?', '06:09 AM', '2026-11-03 05:09:49', 'llama3.2:3b', 0),
(25, 1, 'ai', 'Here is verified information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\n', 'Here is verified information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\n', '06:09 AM', '2026-11-03 05:09:54', 'rag:verified-kb', 0),
(26, 1, 'user', 'What are the best high protein sources for building muscle?', 'What are the best high protein sources for building muscle?', '06:09 AM', '2026-11-03 05:09:54', 'llama3.2:3b', 0),
(27, 1, 'ai', 'Here is verified information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, note that **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', 'Here is verified information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, note that **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '06:09 AM', '2026-11-03 05:09:58', 'rag:verified-kb', 0),
(28, 1, 'user', 'How is my resting heart rate today?', 'How is my resting heart rate today?', '06:09 AM', '2026-11-03 05:09:58', 'llama3.2:3b', 0),
(29, 1, 'ai', 'Here is verified information regarding **Resting Heart Rate Metrics**:\n\nClinical norms: Normal resting heart rate for adults ranges between 60-100 BPM. Well-conditioned athletes frequently exhibit 40-60 BPM (physiological bradycardia). Sustained resting HR > 100 BPM without physical exertion is tachycardia and warrants observation if paired with dizziness, palpitations, or shortness of breath.\n\nAdditionally, note that **Fever, Dehydration & Vitals**: Physiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate ...', 'Here is verified information regarding **Resting Heart Rate Metrics**:\n\nClinical norms: Normal resting heart rate for adults ranges between 60-100 BPM. Well-conditioned athletes frequently exhibit 40-60 BPM (physiological bradycardia). Sustained resting HR > 100 BPM without physical exertion is tachycardia and warrants observation if paired with dizziness, palpitations, or shortness of breath.\n\nAdditionally, note that **Fever, Dehydration & Vitals**: Physiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate ...', '06:09 AM', '2026-11-03 05:10:03', 'rag:verified-kb', 0),
(30, 1, 'user', 'Who directed Inception and what is it about?', 'Who directed Inception and what is it about?', '06:13 AM', '2026-11-03 05:13:07', 'llama3.2:3b', 0),
(31, 1, 'ai', '**Inception (2010)** was written and directed by **Christopher Nolan**! It stars Leonardo DiCaprio as Dom Cobb, an extractor who steals secrets from deep within the subconscious during dream states. With mind-bending practical effects, Hans Zimmer\'s iconic score, and an ambiguous spinning totem ending, it remains one of cinema\'s greatest sci-fi masterpieces.', '**Inception (2010)** was written and directed by **Christopher Nolan**! It stars Leonardo DiCaprio as Dom Cobb, an extractor who steals secrets from deep within the subconscious during dream states. With mind-bending practical effects, Hans Zimmer\'s iconic score, and an ambiguous spinning totem ending, it remains one of cinema\'s greatest sci-fi masterpieces.', '06:13 AM', '2026-11-03 05:13:11', 'llama3.2:fallback', 0),
(32, 1, 'user', 'What are the best high protein sources for building muscle?', 'What are the best high protein sources for building muscle?', '06:13 AM', '2026-11-03 05:13:12', 'llama3.2:3b', 0),
(33, 1, 'ai', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '06:13 AM', '2026-11-03 05:13:16', 'rag:verified-kb', 0),
(34, 1, 'user', 'How is my resting heart rate today?', 'How is my resting heart rate today?', '06:13 AM', '2026-11-03 05:13:16', 'llama3.2:3b', 0),
(35, 1, 'ai', 'Here is verified reference information regarding **Resting Heart Rate Metrics**:\n\nClinical norms: Normal resting heart rate for adults ranges between 60-100 BPM. Well-conditioned athletes frequently exhibit 40-60 BPM (physiological bradycardia). Sustained resting HR > 100 BPM without physical exertion is tachycardia and warrants observation if paired with dizziness, palpitations, or shortness of breath.\n\nAdditionally, regarding **Fever, Dehydration & Vitals**: Physiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate ...', 'Here is verified reference information regarding **Resting Heart Rate Metrics**:\n\nClinical norms: Normal resting heart rate for adults ranges between 60-100 BPM. Well-conditioned athletes frequently exhibit 40-60 BPM (physiological bradycardia). Sustained resting HR > 100 BPM without physical exertion is tachycardia and warrants observation if paired with dizziness, palpitations, or shortness of breath.\n\nAdditionally, regarding **Fever, Dehydration & Vitals**: Physiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate ...', '06:13 AM', '2026-11-03 05:13:20', 'rag:verified-kb', 0),
(36, 1, 'user', 'Who directed Inception and what is it about?', 'Who directed Inception and what is it about?', '06:14 AM', '2026-11-03 05:14:50', 'llama3.2:3b', 0),
(37, 1, 'ai', '**Inception (2010)** was written and directed by **Christopher Nolan**! It stars Leonardo DiCaprio as Dom Cobb, an extractor who steals secrets from deep within the subconscious during dream states. With mind-bending practical effects, Hans Zimmer\'s iconic score, and an ambiguous spinning totem ending, it remains one of cinema\'s greatest sci-fi masterpieces.', '**Inception (2010)** was written and directed by **Christopher Nolan**! It stars Leonardo DiCaprio as Dom Cobb, an extractor who steals secrets from deep within the subconscious during dream states. With mind-bending practical effects, Hans Zimmer\'s iconic score, and an ambiguous spinning totem ending, it remains one of cinema\'s greatest sci-fi masterpieces.', '06:14 AM', '2026-11-03 05:14:54', 'llama3.2:fallback', 0),
(38, 1, 'user', 'What are the best high protein sources for building muscle?', 'What are the best high protein sources for building muscle?', '06:14 AM', '2026-11-03 05:14:54', 'llama3.2:3b', 0),
(39, 1, 'ai', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '06:14 AM', '2026-11-03 05:14:59', 'rag:verified-kb', 0),
(40, 1, 'user', 'How is my resting heart rate today?', 'How is my resting heart rate today?', '06:14 AM', '2026-11-03 05:14:59', 'llama3.2:3b', 0),
(41, 1, 'ai', 'Your latest resting heart rate is **74 BPM**, which is in the healthy clinical range (60–100 BPM). Your cardiovascular trends reflect balanced autonomic regulation.', 'Your latest resting heart rate is **74 BPM**, which is in the healthy clinical range (60–100 BPM). Your cardiovascular trends reflect balanced autonomic regulation.', '06:14 AM', '2026-11-03 05:15:03', 'vitals:personal-sync', 0),
(42, 6, 'ai', 'Hello nitesh Mv! I am your VitalSync AI assistant powered by Llama 3.2. I\'m here for anything—from daily health, sleep, and workouts, to general knowledge, movies, tech, and everyday curiosity. How can I help you today?', 'Hello nitesh Mv! I am your VitalSync AI assistant powered by Llama 3.2. I\'m here for anything—from daily health, sleep, and workouts, to general knowledge, movies, tech, and everyday curiosity. How can I help you today?', '06:20 AM', '2026-11-03 05:20:24', 'llama3.2:3b', 0),
(43, 6, 'user', 'how many days to take build muscles', 'how many days to take build muscles', '06:22 AM', '2026-11-03 05:22:05', 'llama3.2:3b', 0),
(44, 6, 'ai', 'That\'s an interesting question! Whether we\'re exploring ideas, analyzing concepts, working through a problem, or discussing health and training, I\'m here to provide clear, actionable, and engaging answers. Feel free to give me more details on what you\'d like to dive into!', 'That\'s an interesting question! Whether we\'re exploring ideas, analyzing concepts, working through a problem, or discussing health and training, I\'m here to provide clear, actionable, and engaging answers. Feel free to give me more details on what you\'d like to dive into!', '06:22 AM', '2026-11-03 05:22:09', 'llama3.2:fallback', 0),
(45, 1, 'user', 'List top 3 protein sources in one line.', 'List top 3 protein sources in one line.', '07:37 AM', '2026-11-03 06:37:18', 'qwen2.5:1.5b', 0),
(46, 1, 'ai', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Hypertrophy and Muscle Growth Principles**: Hypertrophy Science: Optimal muscle growth requires progressive tension overload, 10-20 tough working sets per muscle group per week, and working within 1-3 Reps in Reserve (RIR). ...', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Hypertrophy and Muscle Growth Principles**: Hypertrophy Science: Optimal muscle growth requires progressive tension overload, 10-20 tough working sets per muscle group per week, and working within 1-3 Reps in Reserve (RIR). ...', '07:37 AM', '2026-11-03 06:37:22', 'rag:verified-kb', 0),
(47, 6, 'user', 'hey', 'hey', '07:40 AM', '2026-11-03 06:40:23', 'qwen2.5:1.5b', 0),
(48, 6, 'ai', 'Hello! I am your VitalSync AI assistant. I\'m here to chat about anything you\'d like—from technology, movies, and philosophy to workout recovery, nutrition, and daily wellness. How can I help you today?', 'Hello! I am your VitalSync AI assistant. I\'m here to chat about anything you\'d like—from technology, movies, and philosophy to workout recovery, nutrition, and daily wellness. How can I help you today?', '07:40 AM', '2026-11-03 06:40:28', 'qwen2.5:fallback', 0),
(49, 6, 'user', 'I need to focus', 'I need to focus', '07:40 AM', '2026-11-03 06:40:42', 'qwen2.5:1.5b', 0),
(50, 6, 'ai', 'That\'s an interesting question! Whether we\'re exploring ideas, analyzing concepts, working through a problem, or discussing health and training, I\'m here to provide clear, actionable, and engaging answers. Feel free to give me more details on what you\'d like to dive into!', 'That\'s an interesting question! Whether we\'re exploring ideas, analyzing concepts, working through a problem, or discussing health and training, I\'m here to provide clear, actionable, and engaging answers. Feel free to give me more details on what you\'d like to dive into!', '07:40 AM', '2026-11-03 06:40:46', 'qwen2.5:fallback', 0),
(51, 6, 'user', 'how to maintain my diet', 'how to maintain my diet', '07:40 AM', '2026-11-03 06:40:56', 'qwen2.5:1.5b', 0),
(52, 6, 'ai', 'Here is verified reference information regarding **Blood Pressure Stages & Guidelines**:\n\nAHA BP Guidelines: Normal: < 120/< 80 mmHg. Elevated: 120-129/< 80 mmHg. Stage 1 Hypertension: 130-139 / 80-89 mmHg. Stage 2 Hypertension: >= 140 or >= 90 mmHg. Hypertensive Crisis: > 180 and/or > 120 mmHg. Lifestyle modifications: DASH diet, sodium reduction (< 2,300 mg/day), regular aerobic exercise, stress reduction.', 'Here is verified reference information regarding **Blood Pressure Stages & Guidelines**:\n\nAHA BP Guidelines: Normal: < 120/< 80 mmHg. Elevated: 120-129/< 80 mmHg. Stage 1 Hypertension: 130-139 / 80-89 mmHg. Stage 2 Hypertension: >= 140 or >= 90 mmHg. Hypertensive Crisis: > 180 and/or > 120 mmHg. Lifestyle modifications: DASH diet, sodium reduction (< 2,300 mg/day), regular aerobic exercise, stress reduction.', '07:40 AM', '2026-11-03 06:41:00', 'rag:verified-kb', 0),
(53, 6, 'user', 'Chennai best hospital', 'Chennai best hospital', '07:41 AM', '2026-11-03 06:41:31', 'qwen2.5:1.5b', 0),
(54, 6, 'ai', 'Technology and software engineering thrive on modular architecture, clean abstractions, and continuous iteration. Whether you\'re working with REST APIs, state management in modern frameworks, or database optimization, feel free to ask any specific coding question!', 'Technology and software engineering thrive on modular architecture, clean abstractions, and continuous iteration. Whether you\'re working with REST APIs, state management in modern frameworks, or database optimization, feel free to ask any specific coding question!', '07:41 AM', '2026-11-03 06:41:35', 'qwen2.5:fallback', 0),
(55, 6, 'user', 'i have fever', 'i have fever', '07:41 AM', '2026-11-03 06:41:59', 'qwen2.5:1.5b', 0),
(56, 6, 'ai', 'Here is verified reference information regarding **Fever, Dehydration & Vitals**:\n\nPhysiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate is elevated alongside low hydration, prioritize electrolyte rehydration (ORS, coconut water). Warning signs: persistent fever > 103┬░F (39.4┬░C), stiff neck, or dyspnea require immediate medical evaluation.', 'Here is verified reference information regarding **Fever, Dehydration & Vitals**:\n\nPhysiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate is elevated alongside low hydration, prioritize electrolyte rehydration (ORS, coconut water). Warning signs: persistent fever > 103┬░F (39.4┬░C), stiff neck, or dyspnea require immediate medical evaluation.', '07:41 AM', '2026-11-03 06:42:04', 'rag:verified-kb', 0),
(57, 6, 'user', 'give any python cod3', 'give any python cod3', '07:46 AM', '2026-11-03 06:46:52', 'qwen2.5:1.5b', 0),
(58, 6, 'ai', '**Python** is renowned for its clean syntax, readability, and massive ecosystem. Core strengths:\n\n- **AI & Machine Learning:** PyTorch, TensorFlow, HuggingFace, Scikit-Learn\n- **Web Development:** FastAPI, Django, Flask\n- **Data Analysis & Scripting:** Pandas, NumPy, Requests\n\nIs there a specific project or concept in Python you\'d like to build?', '**Python** is renowned for its clean syntax, readability, and massive ecosystem. Core strengths:\n\n- **AI & Machine Learning:** PyTorch, TensorFlow, HuggingFace, Scikit-Learn\n- **Web Development:** FastAPI, Django, Flask\n- **Data Analysis & Scripting:** Pandas, NumPy, Requests\n\nIs there a specific project or concept in Python you\'d like to build?', '07:46 AM', '2026-11-03 06:46:57', 'qwen2.5:fallback', 0),
(59, 6, 'user', 'What are the best high protein sources for building muscle?', 'What are the best high protein sources for building muscle?', '08:32 AM', '2026-11-03 07:32:41', 'qwen2.5:1.5b', 0),
(60, 6, 'ai', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '08:32 AM', '2026-11-03 07:32:45', 'rag:verified-kb', 0),
(61, 1, 'user', 'Hi VitalSync, can you recommend 3 high-protein foods?', 'Hi VitalSync, can you recommend 3 high-protein foods?', '10:15 AM', '2026-11-03 09:15:08', 'qwen2.5:1.5b', 0),
(62, 1, 'ai', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '10:15 AM', '2026-11-03 09:16:38', 'rag:verified-kb', 0),
(63, 1, 'user', 'Hi VitalSync, can you recommend 3 high-protein foods?', 'Hi VitalSync, can you recommend 3 high-protein foods?', '10:21 AM', '2026-11-03 09:21:53', 'qwen2.5:1.5b', 0),
(64, 1, 'ai', 'Here are 3 **high-protein foods** that work well for most diets (with verified protein content per 100g or standard serving):\n\n1. **Chicken breast** (31g protein/100g) – Lean, versatile, and great for quick meals.  \n2. **Greek yogurt** (10g protein/100g) – High in protein, adds richness to smoothies or meals.  \n3. **Whole eggs** (6–7g protein/egg) – Affordable, nutrient-dense, and perfect for breakfast or snacks.  \n\n💡 *Why these work*: They cover different dietary preferences (animal-based, dairy, and whole foods), are widely', 'Here are 3 **high-protein foods** that work well for most diets (with verified protein content per 100g or standard serving):\n\n1. **Chicken breast** (31g protein/100g) – Lean, versatile, and great for quick meals.  \n2. **Greek yogurt** (10g protein/100g) – High in protein, adds richness to smoothies or meals.  \n3. **Whole eggs** (6–7g protein/egg) – Affordable, nutrient-dense, and perfect for breakfast or snacks.  \n\n💡 *Why these work*: They cover different dietary preferences (animal-based, dairy, and whole foods), are widely', '10:21 AM', '2026-11-03 09:23:29', 'qwen3:4b', 600),
(65, 6, 'user', 'how i can improve my die', 'how i can improve my die', '10:28 AM', '2026-11-03 09:28:34', 'qwen2.5:1.5b', 0),
(66, 6, 'ai', 'Okay, the user just asked \"how i can improve my die\" twice. That\'s... interesting. First, I need to figure out what they meant. \"Die\" could be a typo. Let me think about common typos people make.\n\nIn English, \"die\" is a verb meaning to stop living, but they wrote \"die\" instead of \"diet\" or \"diet\". Wait, no—they might have meant \"diet\" but typed \"die\". Or maybe they meant \"daily\" but wrote \"die\". Hmm. Another possibility: in gaming terms, \"die\" can mean to fail a roll, like in D&D. But the user has been asking about high protein sources for muscle building, so probably not gaming.\n\nWait, looking back at the conversation history: the user previously asked about high protein sources for building muscle. Then they typed \"how i can improve my die\" twice. Given the context, they likely meant \"how can I improve my diet\". The typo \"die\" instead of \"diet\" is common when typing quickly. \n\nSo the user is probably asking about improving their diet for muscle building, given their previous question. They might have made a typo. Let me check the spelling. In English, \"diet\" vs \"die\"—yes, the \'t\' and \'i\' are swapped. So it\'s a typo.\n\nI should respond by clarifying that they likely meant \"diet\" and then provide actionable advice based on their interest in muscle building. Since they previously asked about high protein sources, they might want more detailed dietary strategies.\n\nAlso, the user wrote \"how i\" with lowercase \'i\', which is informal, so they\'re probably a casual user, not a professional. I should keep the response simple and practical.\n\nPossible response structure:\n1. Clarify the typo: \"You might have meant \'diet\'—common typo!\"\n2. Provide concise, actionable tips for improving diet for muscle building, linking back to their previous interest in protein sources.\n3. Avoid medical jargon since they\'re focused on fitness, not health issues.\n4. Keep it encouraging and positive.\n\nAlso, check if there\'s any other possible meaning of \"die\" in context. For example, in computing, \"die\" isn\'t a standard term, but in gaming, as mentioned. But given the history, it\'s unlikely. The user is into muscle building, so diet is the most probable.\n\nI should make sure not to overcomplicate it. The user might be in a hurry or typing on mobile, so the response should be short and to the point.\n\nLet me draft the response:\n\n\"Hey! I think you meant \'diet\' (common typo 😄). For improving your diet to build muscle, here\'s what works:\n\n1. **Prioritize protein**: 1.6-2.2g/kg body weight (e.g., 100g chicken breast = 31g protein)\n2.', 'Okay, the user just asked \"how i can improve my die\" twice. That\'s... interesting. First, I need to figure out what they meant. \"Die\" could be a typo. Let me think about common typos people make.\n\nIn English, \"die\" is a verb meaning to stop living, but they wrote \"die\" instead of \"diet\" or \"diet\". Wait, no—they might have meant \"diet\" but typed \"die\". Or maybe they meant \"daily\" but wrote \"die\". Hmm. Another possibility: in gaming terms, \"die\" can mean to fail a roll, like in D&D. But the user has been asking about high protein sources for muscle building, so probably not gaming.\n\nWait, looking back at the conversation history: the user previously asked about high protein sources for building muscle. Then they typed \"how i can improve my die\" twice. Given the context, they likely meant \"how can I improve my diet\". The typo \"die\" instead of \"diet\" is common when typing quickly. \n\nSo the user is probably asking about improving their diet for muscle building, given their previous question. They might have made a typo. Let me check the spelling. In English, \"diet\" vs \"die\"—yes, the \'t\' and \'i\' are swapped. So it\'s a typo.\n\nI should respond by clarifying that they likely meant \"diet\" and then provide actionable advice based on their interest in muscle building. Since they previously asked about high protein sources, they might want more detailed dietary strategies.\n\nAlso, the user wrote \"how i\" with lowercase \'i\', which is informal, so they\'re probably a casual user, not a professional. I should keep the response simple and practical.\n\nPossible response structure:\n1. Clarify the typo: \"You might have meant \'diet\'—common typo!\"\n2. Provide concise, actionable tips for improving diet for muscle building, linking back to their previous interest in protein sources.\n3. Avoid medical jargon since they\'re focused on fitness, not health issues.\n4. Keep it encouraging and positive.\n\nAlso, check if there\'s any other possible meaning of \"die\" in context. For example, in computing, \"die\" isn\'t a standard term, but in gaming, as mentioned. But given the history, it\'s unlikely. The user is into muscle building, so diet is the most probable.\n\nI should make sure not to overcomplicate it. The user might be in a hurry or typing on mobile, so the response should be short and to the point.\n\nLet me draft the response:\n\n\"Hey! I think you meant \'diet\' (common typo 😄). For improving your diet to build muscle, here\'s what works:\n\n1. **Prioritize protein**: 1.6-2.2g/kg body weight (e.g., 100g chicken breast = 31g protein)\n2.', '10:28 AM', '2026-11-03 09:30:01', 'qwen3:4b', 600),
(67, 6, 'user', 'how are you', 'how are you', '04:53 AM', '2026-11-04 03:53:32', 'qwen2.5:1.5b', 0),
(68, 6, 'ai', 'I am **VitalSync Core AI**, a custom-engineered clinical wellness and general-purpose intelligence engine built natively for VitalSync. I run directly inside this workspace without third-party dependencies, grounding my insights in your authenticated health telemetry (heart rate, sleep stages, hydration, steps) alongside verified clinical knowledge bases.', 'I am **VitalSync Core AI**, a custom-engineered clinical wellness and general-purpose intelligence engine built natively for VitalSync. I run directly inside this workspace without third-party dependencies, grounding my insights in your authenticated health telemetry (heart rate, sleep stages, hydration, steps) alongside verified clinical knowledge bases.', '04:53 AM', '2026-11-04 03:53:32', 'VitalSync-Core-AI-v1', 45),
(69, 6, 'user', 'how i can maintain my sleep', 'how i can maintain my sleep', '04:54 AM', '2026-11-04 03:54:08', 'qwen2.5:1.5b', 0),
(70, 6, 'user', 'give me diet plan', 'give me diet plan', '04:57 AM', '2026-11-04 03:57:55', 'qwen2.5:1.5b', 0),
(71, 6, 'user', 'Can you review my resting heart rate and cardiovascular trends today?', 'Can you review my resting heart rate and cardiovascular trends today?', '04:58 AM', '2026-11-04 03:58:03', 'qwen2.5:1.5b', 0),
(72, 6, 'user', 'what is normal\' blood pressure', 'what is normal\' blood pressure', '05:03 AM', '2026-11-04 04:03:12', 'qwen2.5:1.5b', 0),
(73, 6, 'user', 'what is normal\' blood pressure', 'what is normal\' blood pressure', '05:05 AM', '2026-11-04 04:05:06', 'qwen2.5:1.5b', 0),
(74, 6, 'user', 'good morning', 'good morning', '05:08 AM', '2026-11-04 04:08:53', 'qwen2.5:1.5b', 0),
(75, 6, 'ai', 'Hello there! I am your VitalSync Core AI medical & wellness assistant. How can I help you today with health questions, symptoms, vitals, or workout recovery?', 'Hello there! I am your VitalSync Core AI medical & wellness assistant. How can I help you today with health questions, symptoms, vitals, or workout recovery?', '05:08 AM', '2026-11-04 04:08:53', 'VitalSync-Core-AI-v1', 25),
(76, 6, 'user', 'give me diet plan', 'give me diet plan', '05:09 AM', '2026-11-04 04:09:07', 'qwen2.5:1.5b', 0),
(77, 6, 'user', 'weight', 'weight', '05:09 AM', '2026-11-04 04:09:18', 'qwen2.5:1.5b', 0),
(78, 6, 'ai', 'Hey there! Ready to assist you with medical questions, vitals analytics, or daily recovery. What\'s on your mind?', 'Hey there! Ready to assist you with medical questions, vitals analytics, or daily recovery. What\'s on your mind?', '05:09 AM', '2026-11-04 04:09:18', 'VitalSync-Core-AI-v1', 18),
(79, 1, 'user', 'what is normal\' blood pressure', 'what is normal\' blood pressure', '05:10 AM', '2026-11-04 04:10:25', 'qwen2.5:1.5b', 0),
(80, 1, 'user', 'what is normal\' blood pressure', 'what is normal\' blood pressure', '05:14 AM', '2026-11-04 04:14:37', 'qwen2.5:1.5b', 0),
(81, 1, 'ai', 'Blood pressure measures arterial resistance during cardiac contraction (systolic) and relaxation (diastolic). Your VitalSync profile logs your recorded pressure at **120/80 mmHg**. Consistent systolic pressure ≥130 mmHg indicates hypertension requiring dietary sodium reduction, regular cardio, and physician consultation.', 'Blood pressure measures arterial resistance during cardiac contraction (systolic) and relaxation (diastolic). Your VitalSync profile logs your recorded pressure at **120/80 mmHg**. Consistent systolic pressure ≥130 mmHg indicates hypertension requiring dietary sodium reduction, regular cardio, and physician consultation.', '05:14 AM', '2026-11-04 04:14:37', 'VitalSync-Core-AI-v1', 36),
(82, 1, 'user', 'Can you review my resting heart rate and cardiovascular trends today?', 'Can you review my resting heart rate and cardiovascular trends today?', '05:15 AM', '2026-11-04 04:15:34', 'qwen2.5:1.5b', 0),
(83, 1, 'ai', 'Cardiovascular Status: Your heart rate is holding at **72 BPM**. Blood pressure sits at **120/80 mmHg** with SpO2 at **98%**.', 'Cardiovascular Status: Your heart rate is holding at **72 BPM**. Blood pressure sits at **120/80 mmHg** with SpO2 at **98%**.', '05:15 AM', '2026-11-04 04:15:34', 'VitalSync-Core-AI-v1', 17),
(84, 6, 'user', 'How was my activity and sleep quality this week?', 'How was my activity and sleep quality this week?', '05:21 AM', '2026-11-04 04:21:37', 'qwen2.5:1.5b', 0),
(85, 6, 'ai', 'Your last recorded sleep session was **7h 30m** with a quality score of **84/100**. For your goal (General Wellness), adequate deep and REM sleep is crucial for cellular repair and mental clarity.', 'Your last recorded sleep session was **7h 30m** with a quality score of **84/100**. For your goal (General Wellness), adequate deep and REM sleep is crucial for cellular repair and mental clarity.', '05:21 AM', '2026-11-04 04:21:37', 'VitalSync-Core-AI-v1', 31);

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
(2, 'nitesh Mv', 'nitesh', 'alex.morgan@email.com', '+1 (202) 555-0147', '14/10/2007', NULL, 'Male', NULL, NULL, 'Moderately active', 'Better Sleep', '$2y$10$Rxlj/fgP9WJyoBKjSionwugLS3fAJ8ZGWuv41lzBNOYekf038Pn52', '2026-10-27 08:03:22', '2026-11-06 05:21:03', 'uploads/profiles/user_2_f967d4e414fdcf0c.jpg'),
(3, 'Alex Morgan', 'Alex Morgan', 'alex.morgan.20261027@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$DJdm.OTwpUYIptmC2FF6yeRMkIBcXVJuSbivfdbJSuanB592Jh/CW', '2026-10-27 08:20:15', '2026-11-02 03:01:52', NULL),
(4, 'Alex Morgan', 'Alex Morgan', 'alex.morgan.t0820z7@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$8r2fBWD1uLygo4hmTvlR/uDKJMxEWMcJTLBUPgc0WyawF8NN7Er4O', '2026-10-27 08:21:15', '2026-11-02 03:01:52', NULL),
(5, 'Alex Morgan', 'Alex Morgan', 'alex.morgan22222222222@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$Cwg8nA.0X5p9kj1DL.83zetk9jGCctkeznsQzb6dpUapqLcIzQ1S.', '2026-10-28 02:49:25', '2026-11-02 03:01:52', NULL),
(6, 'nitesh', 'nitesh Mv', 'niteshmanjula886@gmail.com', '8825780695', '14/10/2007', 18, 'male', 175.00, 80.00, NULL, 'Weight Loss', '$2y$10$w5ZKRQZaxWLEH5MUeiIqb.G0qOXkq34uA7CREKONaktAVjuzw3gTC', '2026-10-29 04:03:11', '2026-11-04 03:41:06', NULL),
(7, 'DAKSH', 'DAKSH', 'daksh@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$azbbkGA5D6XH164ZJPsiSu1Lwvp/IE8oJbjnsN4fVz87IRWh9KhjO', '2026-10-29 04:08:41', '2026-11-02 03:01:52', NULL),
(9, 'nitesh', NULL, 'niteshghost@141gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Fitness', '$2y$10$FXYRuFWUZRcsYXCZbBmkuOSrG8EmB18XReFsRMICm2VTgDIZJxDzW', '2026-11-02 03:20:47', '2026-11-02 03:20:47', NULL),
(10, 'Test User', 'Test User', 'test@vitalsync.com', '+1 555-0199', '1995-05-15', 30, 'Male', 175.00, 70.00, 'Moderate', 'Improve Fitness', '$2y$10$Rxlj/fgP9WJyoBKjSionwugLS3fAJ8ZGWuv41lzBNOYekf038Pn52', '2026-11-06 05:21:03', '2026-11-06 05:21:03', NULL);

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
(9, 1, 1, 1, 'metric', 0, 15, '2026-11-02 08:50:47', 1, 0, 1, 1, 120, 1, 1),
(10, 1, 1, 1, 'metric', 0, 15, '2026-11-06 10:51:03', 1, 0, 1, 1, 120, 1, 1);

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
(9, 'Fitness', NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:50:47'),
(10, 'Improve Fitness', 10000, 8.0, 2500, 68.0, 2200, '2026-11-06 10:51:03');

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
(9, 'nitesh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:50:47'),
(10, 'Test User', 30, 'Male', 175.0, 70.0, 'O+', '+1 555-0199', NULL, '2026-11-06 10:51:03');

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
--
-- Database: `vitalsync_db`
--
CREATE DATABASE IF NOT EXISTS `vitalsync_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vitalsync_db`;

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
(6, 1, 12000, 500, 45, '2026-11-10 09:19:45', 3.00, 5.00),
(9, 10, 8000, 500, 45, '2026-11-06 10:08:49', 3.00, 5.00),
(11, 6, 12000, 500, 45, '2026-11-11 13:51:40', 3.00, 5.00),
(27, 16, 8000, 500, 45, '2026-11-12 16:58:24', 3.00, 5.00),
(28, 18, 12000, 500, 45, '2026-11-12 17:18:44', 3.00, 5.00);

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
(6, 6, 'CYCLING', 4, 0.02, 0, '20.5 km/h', 138, 0, NULL, '2026-11-02 10:14:28', '2026-11-02 10:14:32', '2026-11-02 09:14:28'),
(7, 6, 'WALKING', 34, 0.00, 2, '11:45 /km', 108, 0, NULL, '2026-11-06 10:39:23', '2026-11-06 10:39:57', '2026-11-06 09:39:23'),
(8, 6, 'WALKING', 10, 0.00, 0, '11:45 /km', 108, 0, NULL, '2026-11-06 10:40:43', '2026-11-06 10:40:53', '2026-11-06 09:40:43'),
(9, 6, 'WALKING', 39, 0.04, 3, '18:11 /km', 108, 47, NULL, '2026-11-06 12:03:35', '2026-11-06 12:04:14', '2026-11-06 11:03:35'),
(10, 6, 'WALKING', 9, 0.01, 0, '13:09 /km', 108, 15, NULL, '2026-11-10 07:52:09', '2026-11-10 07:52:18', '2026-11-10 06:52:09'),
(11, 6, 'WALKING', 29, 0.04, 2, '11:21 /km', 108, 56, NULL, '2026-11-10 10:38:38', '2026-11-10 10:39:07', '2026-11-10 09:38:38'),
(12, 6, 'WALKING', 4, 0.00, 0, '11:45 /km', 108, 5, NULL, '2026-11-12 16:39:51', '2026-11-12 16:39:55', '2026-11-12 15:39:51'),
(13, 16, 'WALKING', 5, 0.00, 0, '11:45 /km', 108, 4, NULL, '2026-11-12 17:59:32', '2026-11-12 17:59:37', '2026-11-12 16:59:32'),
(14, 18, 'WALKING', 1, 0.00, 0, '11:45 /km', 108, 0, NULL, '2026-11-12 18:19:31', '2026-11-12 18:19:32', '2026-11-12 17:19:31'),
(15, 18, 'WALKING', 10, 0.01, 0, '16:52 /km', 108, 13, NULL, '2026-11-12 18:19:43', '2026-11-12 18:19:53', '2026-11-12 17:19:43'),
(16, 6, 'WALKING', 4, 0.00, 0, '11:45 /km', 108, 2, NULL, '2026-11-13 17:40:27', '2026-11-13 17:40:31', '2026-11-13 16:40:27'),
(17, 6, 'WALKING', 7, 0.01, 0, '09:35 /km', 108, 16, NULL, '2026-11-13 17:41:03', '2026-11-13 17:41:10', '2026-11-13 16:41:03'),
(18, 6, 'WALKING', 7, 0.01, 0, '11:48 /km', 108, 13, NULL, '2026-11-15 06:54:56', '2026-11-15 06:55:03', '2026-11-15 05:54:56'),
(19, 6, 'WALKING', 3, 0.01, 0, '03:39 /km', 108, 18, NULL, '2026-11-15 09:41:09', '2026-11-15 09:41:12', '2026-11-15 08:41:09'),
(20, 6, 'WALKING', 9, 0.01, 0, '24:40 /km', 108, 8, NULL, '2026-11-15 16:43:29', '2026-11-15 16:43:38', '2026-11-15 15:43:29'),
(21, 6, 'WALKING', 6, 0.01, 0, '10:07 /km', 108, 13, NULL, '2026-11-15 16:43:40', '2026-11-15 16:43:46', '2026-11-15 15:43:40'),
(22, 6, 'WALKING', 13, 0.01, 1, '25:55 /km', 108, 11, NULL, '2026-11-15 17:39:53', '2026-11-15 17:40:06', '2026-11-15 16:39:53'),
(23, 6, 'WALKING', 6, 0.01, 0, '09:23 /km', 108, 14, NULL, '2026-11-15 17:40:26', '2026-11-15 17:40:32', '2026-11-15 16:40:26'),
(24, 6, 'WALKING', 5, 0.01, 0, '10:57 /km', 108, 10, NULL, '2026-11-15 17:55:43', '2026-11-15 17:55:48', '2026-11-15 16:55:43');

-- --------------------------------------------------------

--
-- Table structure for table `ai_chat_messages`
--

CREATE TABLE `ai_chat_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sender` enum('user','ai') NOT NULL,
  `message` text DEFAULT NULL,
  `message_text` text DEFAULT NULL,
  `time_text` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `model_used` varchar(50) DEFAULT 'qwen2.5:1.5b',
  `tokens_used` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_chat_messages`
--

INSERT INTO `ai_chat_messages` (`id`, `user_id`, `session_id`, `sender`, `message`, `message_text`, `time_text`, `created_at`, `model_used`, `tokens_used`) VALUES
(1, 2, NULL, 'user', 'How is my resting heart rate today?', 'How is my resting heart rate today?', '10:09 AM', '2026-10-27 09:09:10', 'llama3.2:3b', 0),
(2, 2, NULL, 'ai', 'Your resting heart rate averages around 64–76 BPM, which is in the healthy normal range. Maintaining moderate aerobic activity and staying well-hydrated helps keep your cardiovascular rhythm balanced. Note: VitalSync provides wellness insights, not clinical medical advice.', 'Your resting heart rate averages around 64–76 BPM, which is in the healthy normal range. Maintaining moderate aerobic activity and staying well-hydrated helps keep your cardiovascular rhythm balanced. Note: VitalSync provides wellness insights, not clinical medical advice.', '10:09 AM', '2026-10-27 09:09:10', 'llama3.2:3b', 0),
(3, 2, NULL, 'user', 'What should my post-workout meal look like today?', 'What should my post-workout meal look like today?', '03:45 AM', '2026-10-28 02:45:52', 'llama3.2:3b', 0),
(4, 2, NULL, 'ai', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', '03:45 AM', '2026-10-28 02:45:52', 'llama3.2:3b', 0),
(5, 2, NULL, 'user', 'What should my post-workout meal look like today?', 'What should my post-workout meal look like today?', '05:38 AM', '2026-10-28 04:38:15', 'llama3.2:3b', 0),
(6, 2, NULL, 'ai', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', '05:38 AM', '2026-10-28 04:38:15', 'llama3.2:3b', 0),
(7, 2, NULL, 'user', 'What should my post-workout meal look like today?', 'What should my post-workout meal look like today?', '07:39 AM', '2026-10-28 06:39:58', 'llama3.2:3b', 0),
(8, 2, NULL, 'ai', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', 'Consistent progress! When following a Push/Pull/Legs split, ensure you hit your daily protein target (approx. 130g) and allow 48 hours recovery between working the same muscle groups.', '07:39 AM', '2026-10-28 06:39:58', 'llama3.2:3b', 0),
(9, 2, NULL, 'user', 'How is my heart rate and sleep?', 'How is my heart rate and sleep?', '06:23 AM', '2026-10-30 05:23:50', 'llama3.2:3b', 0),
(10, 2, NULL, 'ai', 'Your resting heart rate averages around 64–76 BPM, which is in the healthy normal range. Maintaining moderate aerobic activity and staying well-hydrated helps keep your cardiovascular rhythm balanced. Note: VitalSync provides wellness insights, not clinical medical advice.', 'Your resting heart rate averages around 64–76 BPM, which is in the healthy normal range. Maintaining moderate aerobic activity and staying well-hydrated helps keep your cardiovascular rhythm balanced. Note: VitalSync provides wellness insights, not clinical medical advice.', '06:23 AM', '2026-10-30 05:23:50', 'llama3.2:3b', 0),
(11, 1, 1, 'user', 'Hi Doctor, how is my resting heart rate today?', 'Hi Doctor, how is my resting heart rate today?', '05:05 AM', '2026-11-03 04:05:14', 'llama3.2:3b', 0),
(12, 1, 1, 'ai', 'Hello nitesh, your latest resting heart rate is recorded at 74 BPM, which falls comfortably within the healthy clinical range (60–100 BPM). Your cardiovascular rhythm indicates steady autonomic balance. Maintaining structured cardiovascular pacing and adequate hydration will keep your heart rate variability optimal.', 'Hello nitesh, your latest resting heart rate is recorded at 74 BPM, which falls comfortably within the healthy clinical range (60–100 BPM). Your cardiovascular rhythm indicates steady autonomic balance. Maintaining structured cardiovascular pacing and adequate hydration will keep your heart rate variability optimal.', '05:05 AM', '2026-11-03 04:05:18', 'llama3.2:fallback', 0),
(24, 1, 1, 'user', 'Who directed Inception and what is it about?', 'Who directed Inception and what is it about?', '06:09 AM', '2026-11-03 05:09:49', 'llama3.2:3b', 0),
(25, 1, 1, 'ai', 'Here is verified information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\n', 'Here is verified information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\n', '06:09 AM', '2026-11-03 05:09:54', 'rag:verified-kb', 0),
(26, 1, 1, 'user', 'What are the best high protein sources for building muscle?', 'What are the best high protein sources for building muscle?', '06:09 AM', '2026-11-03 05:09:54', 'llama3.2:3b', 0),
(27, 1, 1, 'ai', 'Here is verified information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, note that **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', 'Here is verified information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, note that **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '06:09 AM', '2026-11-03 05:09:58', 'rag:verified-kb', 0),
(28, 1, 1, 'user', 'How is my resting heart rate today?', 'How is my resting heart rate today?', '06:09 AM', '2026-11-03 05:09:58', 'llama3.2:3b', 0),
(29, 1, 1, 'ai', 'Here is verified information regarding **Resting Heart Rate Metrics**:\n\nClinical norms: Normal resting heart rate for adults ranges between 60-100 BPM. Well-conditioned athletes frequently exhibit 40-60 BPM (physiological bradycardia). Sustained resting HR > 100 BPM without physical exertion is tachycardia and warrants observation if paired with dizziness, palpitations, or shortness of breath.\n\nAdditionally, note that **Fever, Dehydration & Vitals**: Physiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate ...', 'Here is verified information regarding **Resting Heart Rate Metrics**:\n\nClinical norms: Normal resting heart rate for adults ranges between 60-100 BPM. Well-conditioned athletes frequently exhibit 40-60 BPM (physiological bradycardia). Sustained resting HR > 100 BPM without physical exertion is tachycardia and warrants observation if paired with dizziness, palpitations, or shortness of breath.\n\nAdditionally, note that **Fever, Dehydration & Vitals**: Physiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate ...', '06:09 AM', '2026-11-03 05:10:03', 'rag:verified-kb', 0),
(30, 1, 1, 'user', 'Who directed Inception and what is it about?', 'Who directed Inception and what is it about?', '06:13 AM', '2026-11-03 05:13:07', 'llama3.2:3b', 0),
(31, 1, 1, 'ai', '**Inception (2010)** was written and directed by **Christopher Nolan**! It stars Leonardo DiCaprio as Dom Cobb, an extractor who steals secrets from deep within the subconscious during dream states. With mind-bending practical effects, Hans Zimmer\'s iconic score, and an ambiguous spinning totem ending, it remains one of cinema\'s greatest sci-fi masterpieces.', '**Inception (2010)** was written and directed by **Christopher Nolan**! It stars Leonardo DiCaprio as Dom Cobb, an extractor who steals secrets from deep within the subconscious during dream states. With mind-bending practical effects, Hans Zimmer\'s iconic score, and an ambiguous spinning totem ending, it remains one of cinema\'s greatest sci-fi masterpieces.', '06:13 AM', '2026-11-03 05:13:11', 'llama3.2:fallback', 0),
(32, 1, 1, 'user', 'What are the best high protein sources for building muscle?', 'What are the best high protein sources for building muscle?', '06:13 AM', '2026-11-03 05:13:12', 'llama3.2:3b', 0),
(33, 1, 1, 'ai', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '06:13 AM', '2026-11-03 05:13:16', 'rag:verified-kb', 0),
(34, 1, 1, 'user', 'How is my resting heart rate today?', 'How is my resting heart rate today?', '06:13 AM', '2026-11-03 05:13:16', 'llama3.2:3b', 0),
(35, 1, 1, 'ai', 'Here is verified reference information regarding **Resting Heart Rate Metrics**:\n\nClinical norms: Normal resting heart rate for adults ranges between 60-100 BPM. Well-conditioned athletes frequently exhibit 40-60 BPM (physiological bradycardia). Sustained resting HR > 100 BPM without physical exertion is tachycardia and warrants observation if paired with dizziness, palpitations, or shortness of breath.\n\nAdditionally, regarding **Fever, Dehydration & Vitals**: Physiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate ...', 'Here is verified reference information regarding **Resting Heart Rate Metrics**:\n\nClinical norms: Normal resting heart rate for adults ranges between 60-100 BPM. Well-conditioned athletes frequently exhibit 40-60 BPM (physiological bradycardia). Sustained resting HR > 100 BPM without physical exertion is tachycardia and warrants observation if paired with dizziness, palpitations, or shortness of breath.\n\nAdditionally, regarding **Fever, Dehydration & Vitals**: Physiological patterns: Body temperature elevation (fever) naturally increases resting heart rate by ~8-10 BPM per 1┬░C elevation due to higher metabolic demand. If heart rate ...', '06:13 AM', '2026-11-03 05:13:20', 'rag:verified-kb', 0),
(36, 1, 1, 'user', 'Who directed Inception and what is it about?', 'Who directed Inception and what is it about?', '06:14 AM', '2026-11-03 05:14:50', 'llama3.2:3b', 0),
(37, 1, 1, 'ai', '**Inception (2010)** was written and directed by **Christopher Nolan**! It stars Leonardo DiCaprio as Dom Cobb, an extractor who steals secrets from deep within the subconscious during dream states. With mind-bending practical effects, Hans Zimmer\'s iconic score, and an ambiguous spinning totem ending, it remains one of cinema\'s greatest sci-fi masterpieces.', '**Inception (2010)** was written and directed by **Christopher Nolan**! It stars Leonardo DiCaprio as Dom Cobb, an extractor who steals secrets from deep within the subconscious during dream states. With mind-bending practical effects, Hans Zimmer\'s iconic score, and an ambiguous spinning totem ending, it remains one of cinema\'s greatest sci-fi masterpieces.', '06:14 AM', '2026-11-03 05:14:54', 'llama3.2:fallback', 0),
(38, 1, 1, 'user', 'What are the best high protein sources for building muscle?', 'What are the best high protein sources for building muscle?', '06:14 AM', '2026-11-03 05:14:54', 'llama3.2:3b', 0),
(39, 1, 1, 'ai', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '06:14 AM', '2026-11-03 05:14:59', 'rag:verified-kb', 0),
(40, 1, 1, 'user', 'How is my resting heart rate today?', 'How is my resting heart rate today?', '06:14 AM', '2026-11-03 05:14:59', 'llama3.2:3b', 0),
(41, 1, 1, 'ai', 'Your latest resting heart rate is **74 BPM**, which is in the healthy clinical range (60–100 BPM). Your cardiovascular trends reflect balanced autonomic regulation.', 'Your latest resting heart rate is **74 BPM**, which is in the healthy clinical range (60–100 BPM). Your cardiovascular trends reflect balanced autonomic regulation.', '06:14 AM', '2026-11-03 05:15:03', 'vitals:personal-sync', 0),
(45, 1, 1, 'user', 'List top 3 protein sources in one line.', 'List top 3 protein sources in one line.', '07:37 AM', '2026-11-03 06:37:18', 'qwen2.5:1.5b', 0),
(46, 1, 1, 'ai', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Hypertrophy and Muscle Growth Principles**: Hypertrophy Science: Optimal muscle growth requires progressive tension overload, 10-20 tough working sets per muscle group per week, and working within 1-3 Reps in Reserve (RIR). ...', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Hypertrophy and Muscle Growth Principles**: Hypertrophy Science: Optimal muscle growth requires progressive tension overload, 10-20 tough working sets per muscle group per week, and working within 1-3 Reps in Reserve (RIR). ...', '07:37 AM', '2026-11-03 06:37:22', 'rag:verified-kb', 0),
(61, 1, 1, 'user', 'Hi VitalSync, can you recommend 3 high-protein foods?', 'Hi VitalSync, can you recommend 3 high-protein foods?', '10:15 AM', '2026-11-03 09:15:08', 'qwen2.5:1.5b', 0),
(62, 1, 1, 'ai', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', 'Here is verified reference information regarding **High Protein Sources**:\n\nTop dietary protein sources: \n1. Non-Vegetarian: Whole eggs (6-7g/egg), Chicken breast (31g/100g), Fish (Salmon/Tuna, 22-26g/100g), Lean beef.\n2. Vegetarian: Soya chunks (52g/100g dry), Paneer/Cottage cheese (18g/100g), Greek yogurt (10g/100g), Tofu (17g/100g), Lentils/Dal (9g/100g cooked), Chickpeas (19g/100g cooked).\n3. Supplements: Whey protein isolate (24-27g per scoop), Pea/Brown rice blend.\n\nAdditionally, regarding **Stress, Cortisol, and Heart Rate Variability (HRV)**: Autonomic regulation: High Heart Rate Variability (HRV) reflects robust parasympathetic vagal tone and systemic recovery readiness. Chronic stress and elevated cortisol suppress HR...', '10:15 AM', '2026-11-03 09:16:38', 'rag:verified-kb', 0),
(63, 1, 1, 'user', 'Hi VitalSync, can you recommend 3 high-protein foods?', 'Hi VitalSync, can you recommend 3 high-protein foods?', '10:21 AM', '2026-11-03 09:21:53', 'qwen2.5:1.5b', 0),
(64, 1, 1, 'ai', 'Here are 3 **high-protein foods** that work well for most diets (with verified protein content per 100g or standard serving):\n\n1. **Chicken breast** (31g protein/100g) – Lean, versatile, and great for quick meals.  \n2. **Greek yogurt** (10g protein/100g) – High in protein, adds richness to smoothies or meals.  \n3. **Whole eggs** (6–7g protein/egg) – Affordable, nutrient-dense, and perfect for breakfast or snacks.  \n\n💡 *Why these work*: They cover different dietary preferences (animal-based, dairy, and whole foods), are widely', 'Here are 3 **high-protein foods** that work well for most diets (with verified protein content per 100g or standard serving):\n\n1. **Chicken breast** (31g protein/100g) – Lean, versatile, and great for quick meals.  \n2. **Greek yogurt** (10g protein/100g) – High in protein, adds richness to smoothies or meals.  \n3. **Whole eggs** (6–7g protein/egg) – Affordable, nutrient-dense, and perfect for breakfast or snacks.  \n\n💡 *Why these work*: They cover different dietary preferences (animal-based, dairy, and whole foods), are widely', '10:21 AM', '2026-11-03 09:23:29', 'qwen3:4b', 600),
(79, 1, 1, 'user', 'what is normal\' blood pressure', 'what is normal\' blood pressure', '05:10 AM', '2026-11-04 04:10:25', 'qwen2.5:1.5b', 0),
(80, 1, 1, 'user', 'what is normal\' blood pressure', 'what is normal\' blood pressure', '05:14 AM', '2026-11-04 04:14:37', 'qwen2.5:1.5b', 0),
(81, 1, 1, 'ai', 'Blood pressure measures arterial resistance during cardiac contraction (systolic) and relaxation (diastolic). Your VitalSync profile logs your recorded pressure at **120/80 mmHg**. Consistent systolic pressure ≥130 mmHg indicates hypertension requiring dietary sodium reduction, regular cardio, and physician consultation.', 'Blood pressure measures arterial resistance during cardiac contraction (systolic) and relaxation (diastolic). Your VitalSync profile logs your recorded pressure at **120/80 mmHg**. Consistent systolic pressure ≥130 mmHg indicates hypertension requiring dietary sodium reduction, regular cardio, and physician consultation.', '05:14 AM', '2026-11-04 04:14:37', 'VitalSync-Core-AI-v1', 36),
(82, 1, 1, 'user', 'Can you review my resting heart rate and cardiovascular trends today?', 'Can you review my resting heart rate and cardiovascular trends today?', '05:15 AM', '2026-11-04 04:15:34', 'qwen2.5:1.5b', 0),
(83, 1, 1, 'ai', 'Cardiovascular Status: Your heart rate is holding at **72 BPM**. Blood pressure sits at **120/80 mmHg** with SpO2 at **98%**.', 'Cardiovascular Status: Your heart rate is holding at **72 BPM**. Blood pressure sits at **120/80 mmHg** with SpO2 at **98%**.', '05:15 AM', '2026-11-04 04:15:34', 'VitalSync-Core-AI-v1', 17),
(87, 10, 3, 'user', 'What should my heart rate be during light walking?', 'What should my heart rate be during light walking?', '12:36 PM', '2026-11-06 11:36:47', 'qwen2.5:1.5b', 0),
(88, 10, 3, 'ai', 'During light walking, your heart rate should be around 100-120 beats per minute. This is a moderate level of exertion that is suitable for most people, allowing you to maintain a brisk pace without feeling excessively tired.', 'During light walking, your heart rate should be around 100-120 beats per minute. This is a moderate level of exertion that is suitable for most people, allowing you to maintain a brisk pace without feeling excessively tired.', '12:36 PM', '2026-11-06 11:36:47', 'qwen2.5:1.5b', 0),
(91, 1, 1, 'user', 'What is a healthy resting heart rate?', NULL, NULL, '2026-11-06 15:43:54', 'qwen2.5:1.5b', 0),
(92, 1, 1, 'ai', 'A healthy resting heart rate is typically between 60 and 100 beats per minute for an adult. However, this can vary slightly based on age, gender, and physical condition. A rate below 60 beats per minute may indicate bradycardia, while a rate above 100 beats per minute could be considered tachycardia.', NULL, NULL, '2026-11-06 15:43:54', 'qwen2.5:1.5b', 0),
(103, 1, 1, 'user', 'Quick heart rate check', NULL, NULL, '2026-11-10 06:30:35', 'qwen2.5:1.5b', 0),
(104, 1, 1, 'ai', 'Based on your quick heart rate check, it seems within a normal range. This is good news! Keep up the healthy habits and continue monitoring your vitals. If you notice any changes, please feel free to reach out.', NULL, NULL, '2026-11-10 06:30:35', 'qwen2.5:1.5b', 0),
(105, 1, 1, 'user', 'Hi, quick check', NULL, NULL, '2026-11-10 06:43:18', 'qwen2.5:1.5b', 0),
(106, 1, 1, 'ai', 'Hello! Your blood pressure is 120/80, which is within the normal range. Your heart rate is 70 beats per minute, indicating good cardiovascular health. Your oxygen saturation is 98%, which is excellent. You\'re doing well!', NULL, NULL, '2026-11-10 06:43:18', 'qwen2.5:1.5b', 0),
(267, 11, 14, 'user', 'hi', 'hi', '10:25 PM', '2026-11-12 16:55:24', 'qwen2.5:1.5b', 0),
(268, 11, 14, 'ai', 'Hello! How can I help you today?', 'Hello! How can I help you today?', '10:25 PM', '2026-11-12 16:55:24', 'qwen2.5:1.5b', 0),
(269, 11, 14, 'user', 'how can i improve my diet', 'how can i improve my diet', '10:25 PM', '2026-11-12 16:55:36', 'qwen2.5:1.5b', 0),
(270, 11, 14, 'ai', 'Improving your diet is a great step towards better health. Here are some practical tips to help you make healthier choices:\n\n1. **Increase Fruits and Vegetables**: Aim for at least five servings of fruits and vegetables daily. This includes fruits like apples, bananas, and oranges, and vegetables like carrots, broccoli, and spinach.\n\n2. **Eat More Whole Foods**: Choose whole foods over processed foods. This includes whole grains, lean proteins, and fresh fruits and vegetables.\n\n3. **Limit Saturated and Trans Fats**: Reduce the intake of saturated fats and trans fats, which are found in processed foods, fried foods, and some types of meat. Opt for healthier fats like olive oil, avocados, and nuts.\n\n4. **Hydrate Well**: Drink plenty of water throughout the day. Aim for at least 8 glasses (about 2 liters) of water a day.\n\n5. **Limit Added Sugars and Salt**: Reduce your intake of sugary drinks and snacks, and try to avoid adding salt to your food.\n\n6. **Eat Regularly**: Try to eat smaller, more frequent meals throughout the day instead of three large meals. This can help maintain stable blood sugar levels and keep your metabolism active.\n\n7. **Portion Control**: Pay attention to', 'Improving your diet is a great step towards better health. Here are some practical tips to help you make healthier choices:\n\n1. **Increase Fruits and Vegetables**: Aim for at least five servings of fruits and vegetables daily. This includes fruits like apples, bananas, and oranges, and vegetables like carrots, broccoli, and spinach.\n\n2. **Eat More Whole Foods**: Choose whole foods over processed foods. This includes whole grains, lean proteins, and fresh fruits and vegetables.\n\n3. **Limit Saturated and Trans Fats**: Reduce the intake of saturated fats and trans fats, which are found in processed foods, fried foods, and some types of meat. Opt for healthier fats like olive oil, avocados, and nuts.\n\n4. **Hydrate Well**: Drink plenty of water throughout the day. Aim for at least 8 glasses (about 2 liters) of water a day.\n\n5. **Limit Added Sugars and Salt**: Reduce your intake of sugary drinks and snacks, and try to avoid adding salt to your food.\n\n6. **Eat Regularly**: Try to eat smaller, more frequent meals throughout the day instead of three large meals. This can help maintain stable blood sugar levels and keep your metabolism active.\n\n7. **Portion Control**: Pay attention to', '10:25 PM', '2026-11-12 16:55:39', 'qwen2.5:1.5b', 0),
(271, 11, 14, 'user', 'hi', 'hi', '10:30 PM', '2026-11-12 17:00:55', 'qwen2.5:1.5b', 0),
(272, 11, 14, 'ai', 'Hello! How can I help you today?', 'Hello! How can I help you today?', '10:30 PM', '2026-11-12 17:00:55', 'qwen2.5:1.5b', 0),
(285, 6, 15, 'user', 'hi', 'hi', '07:59 PM', '2026-11-13 14:29:47', 'qwen2.5:1.5b', 0),
(286, 6, 15, 'ai', 'Hello! How can I help you today?', 'Hello! How can I help you today?', '07:59 PM', '2026-11-13 14:29:48', 'qwen2.5:1.5b', 0),
(287, 6, 15, 'user', 'dosa cal', 'dosa cal', '09:12 PM', '2026-11-13 15:42:34', 'qwen2.5:1.5b', 0),
(288, 6, 15, 'ai', 'Dosa is a popular Indian dish consisting of a soft, thin crepe made from fermented rice and black gram flour batter. It is typically served with chutney, sambar, and coconut chutney. Enjoy your meal!', 'Dosa is a popular Indian dish consisting of a soft, thin crepe made from fermented rice and black gram flour batter. It is typically served with chutney, sambar, and coconut chutney. Enjoy your meal!', '09:12 PM', '2026-11-13 15:42:34', 'qwen2.5:1.5b', 0),
(289, 6, 15, 'user', 'hi i have mild fever', 'hi i have mild fever', '09:12 PM', '2026-11-13 15:42:50', 'qwen2.5:1.5b', 0),
(290, 6, 15, 'ai', 'It sounds like you\'re dealing with a mild fever. Here are some steps you can take to manage it:\n\n1. **Stay Hydrated**: Drink plenty of fluids to help your body cool down.\n2. **Rest**: Get plenty of rest to help your body fight off the infection.\n3. **Cooling Measures**: Use a cool cloth on your forehead or under your armpits to help reduce fever.\n4. **Comfortable Clothing**: Wear light, loose clothing to help keep your body cool.\n5. **Monitor Symptoms**: Keep an eye on your symptoms. If the fever persists or worsens, or if you experience other concerning symptoms like severe headache, difficulty breathing, or severe pain, contact a healthcare provider.\n\nIf you have any specific questions or need further advice, feel free to ask!', 'It sounds like you\'re dealing with a mild fever. Here are some steps you can take to manage it:\n\n1. **Stay Hydrated**: Drink plenty of fluids to help your body cool down.\n2. **Rest**: Get plenty of rest to help your body fight off the infection.\n3. **Cooling Measures**: Use a cool cloth on your forehead or under your armpits to help reduce fever.\n4. **Comfortable Clothing**: Wear light, loose clothing to help keep your body cool.\n5. **Monitor Symptoms**: Keep an eye on your symptoms. If the fever persists or worsens, or if you experience other concerning symptoms like severe headache, difficulty breathing, or severe pain, contact a healthcare provider.\n\nIf you have any specific questions or need further advice, feel free to ask!', '09:12 PM', '2026-11-13 15:42:52', 'qwen2.5:1.5b', 0),
(291, 6, 15, 'user', 'hi', 'hi', '09:51 PM', '2026-11-13 16:21:29', 'qwen2.5:1.5b', 0),
(292, 6, 15, 'ai', 'Hello! How can I assist you today?', 'Hello! How can I assist you today?', '09:51 PM', '2026-11-13 16:21:29', 'qwen2.5:1.5b', 0),
(293, 6, 15, 'user', 'I\'m struggling to breath', 'I\'m struggling to breath', '09:51 PM', '2026-11-13 16:21:50', 'qwen2.5:1.5b', 0),
(294, 6, 15, 'ai', 'I\'m sorry to hear that you\'re having trouble breathing. This could be due to a variety of reasons, including respiratory infections, asthma, or even anxiety. Here are some steps you can take:\n\n1. **Stay Hydrated**: Drinking plenty of fluids can help keep your airways moist and make breathing easier.\n2. **Stay Warm**: Cold air can make breathing more difficult. If you\'re feeling cold, try wearing a warm blanket or using a hot water bottle.\n3. **Relaxation Techniques**: Deep breathing exercises or relaxation techniques can help calm your breathing.\n4. **Avoid Stimulants**: Caffeine and nicotine can make breathing difficult. If you\'re feeling anxious, try to avoid these substances.\n5. **Seek Medical Attention**: If your breathing problems persist or worsen, or if you experience any other concerning symptoms like chest pain, dizziness, or confusion, it\'s important to seek medical attention.\n\nIf you have any specific questions or need further advice, feel free to ask!', 'I\'m sorry to hear that you\'re having trouble breathing. This could be due to a variety of reasons, including respiratory infections, asthma, or even anxiety. Here are some steps you can take:\n\n1. **Stay Hydrated**: Drinking plenty of fluids can help keep your airways moist and make breathing easier.\n2. **Stay Warm**: Cold air can make breathing more difficult. If you\'re feeling cold, try wearing a warm blanket or using a hot water bottle.\n3. **Relaxation Techniques**: Deep breathing exercises or relaxation techniques can help calm your breathing.\n4. **Avoid Stimulants**: Caffeine and nicotine can make breathing difficult. If you\'re feeling anxious, try to avoid these substances.\n5. **Seek Medical Attention**: If your breathing problems persist or worsen, or if you experience any other concerning symptoms like chest pain, dizziness, or confusion, it\'s important to seek medical attention.\n\nIf you have any specific questions or need further advice, feel free to ask!', '09:51 PM', '2026-11-13 16:21:52', 'qwen2.5:1.5b', 0),
(295, 6, 15, 'user', 'hi', 'hi', '10:11 PM', '2026-11-13 16:41:57', 'qwen2.5:1.5b', 0),
(296, 6, 15, 'ai', 'Hello! How can I assist you today?', 'Hello! How can I assist you today?', '10:11 PM', '2026-11-13 16:41:57', 'qwen2.5:1.5b', 0),
(297, 6, 15, 'user', 'mutton vs beef', 'mutton vs beef', '10:12 PM', '2026-11-13 16:42:05', 'qwen2.5:1.5b', 0),
(298, 6, 15, 'ai', 'Both mutton and beef are types of meat, but they come from different animals. Here\'s a quick comparison:\n\n- **Mutton** comes from sheep.\n- **Beef** comes from cattle.\n\nBoth are high in protein and can be used in many dishes, but they have different textures and flavors. Mutton is generally leaner and has a more gamey flavor, while beef can be more fatty and has a richer, fuller taste.', 'Both mutton and beef are types of meat, but they come from different animals. Here\'s a quick comparison:\n\n- **Mutton** comes from sheep.\n- **Beef** comes from cattle.\n\nBoth are high in protein and can be used in many dishes, but they have different textures and flavors. Mutton is generally leaner and has a more gamey flavor, while beef can be more fatty and has a richer, fuller taste.', '10:12 PM', '2026-11-13 16:42:06', 'qwen2.5:1.5b', 0),
(299, 6, 15, 'user', 'hi', 'hi', '11:25 AM', '2026-11-15 05:55:29', 'qwen2.5:1.5b', 0),
(300, 6, 15, 'ai', 'Hello! How can I assist you today?', 'Hello! How can I assist you today?', '11:25 AM', '2026-11-15 05:55:30', 'qwen2.5:1.5b', 0),
(301, 6, 15, 'user', 'hey', 'hey', '02:13 PM', '2026-11-15 08:43:25', 'qwen2.5:1.5b', 0),
(302, 6, 15, 'ai', 'Hello! How can I help you today?', 'Hello! How can I help you today?', '02:13 PM', '2026-11-15 08:43:25', 'qwen2.5:1.5b', 0),
(303, 6, 15, 'user', 'dog meat is eatable', 'dog meat is eatable', '02:13 PM', '2026-11-15 08:43:39', 'qwen2.5:1.5b', 0),
(304, 6, 15, 'ai', 'I\'m sorry, but I can\'t assist with that.', 'I\'m sorry, but I can\'t assist with that.', '02:13 PM', '2026-11-15 08:43:39', 'qwen2.5:1.5b', 0),
(305, 6, 15, 'user', 'how can i maintain my deit regularly', 'how can i maintain my deit regularly', '02:13 PM', '2026-11-15 08:43:52', 'qwen2.5:1.5b', 0),
(306, 6, 15, 'ai', 'Maintaining a healthy diet involves a balanced intake of nutrients. Here are some general tips to help you manage your diet:\n\n1. **Eat a Variety of Foods**: Ensure that your diet includes a variety of foods from all the food groups.\n2. **Balance Your Meals**: Aim for a balance of carbohydrates, proteins, and fats in each meal.\n3. **Hydration**: Drink plenty of water throughout the day.\n4. **Limit Processed Foods**: Reduce the intake of processed foods and sugary drinks.\n5. **Eat Regularly**: Try to eat at regular intervals to keep your metabolism healthy.\n6. **Monitor Your Portions**: Pay attention to your portion sizes to avoid overeating.\n7. **Exercise**: Regular physical activity helps in maintaining a healthy weight and boosts metabolism.\n\nRemember, consulting with a healthcare professional or a registered dietitian can provide personalized advice based on your specific health needs.', 'Maintaining a healthy diet involves a balanced intake of nutrients. Here are some general tips to help you manage your diet:\n\n1. **Eat a Variety of Foods**: Ensure that your diet includes a variety of foods from all the food groups.\n2. **Balance Your Meals**: Aim for a balance of carbohydrates, proteins, and fats in each meal.\n3. **Hydration**: Drink plenty of water throughout the day.\n4. **Limit Processed Foods**: Reduce the intake of processed foods and sugary drinks.\n5. **Eat Regularly**: Try to eat at regular intervals to keep your metabolism healthy.\n6. **Monitor Your Portions**: Pay attention to your portion sizes to avoid overeating.\n7. **Exercise**: Regular physical activity helps in maintaining a healthy weight and boosts metabolism.\n\nRemember, consulting with a healthcare professional or a registered dietitian can provide personalized advice based on your specific health needs.', '02:13 PM', '2026-11-15 08:43:54', 'qwen2.5:1.5b', 0),
(307, 6, 15, 'user', 'ho', 'ho', '05:51 PM', '2026-11-15 12:21:19', 'qwen2.5:1.5b', 0),
(308, 6, 15, 'ai', 'I\'m sorry, but I can\'t assist with that.', 'I\'m sorry, but I can\'t assist with that.', '05:51 PM', '2026-11-15 12:21:20', 'qwen2.5:1.5b', 0),
(309, 6, 15, 'user', 'hi', 'hi', '05:51 PM', '2026-11-15 12:21:22', 'qwen2.5:1.5b', 0),
(310, 6, 15, 'ai', 'Hello! How can I assist you today?', 'Hello! How can I assist you today?', '05:51 PM', '2026-11-15 12:21:22', 'qwen2.5:1.5b', 0),
(311, 6, 15, 'user', 'maida vs wheat which is better dough', 'maida vs wheat which is better dough', '06:08 PM', '2026-11-15 12:38:38', 'qwen2.5:1.5b', 0),
(312, 6, 15, 'ai', 'Both maize (corn) flour and wheat flour can be used to make dough, and the choice between them often depends on the recipe and personal preference. Here are some considerations:\n\n1. **Nutritional Value:**\n   - **Maize Flour:** It is generally lower in gluten, which means it\'s easier to digest and has fewer calories per unit.\n   - **Wheat Flour:** It contains more gluten, which can make the dough more elastic and easier to handle.\n\n2. **Texture and Baking:**\n   - **Maize Dough:** It usually has a lighter, more tender texture and is often used for dishes that require a delicate texture.\n   - **Wheat Dough:** It provides a more robust texture and is often used for bread and other baked goods that need a chewier consistency.\n\n3. **Availability and Cost:**\n   - **Maize Flour:** It\'s more widely available and can be found in many grocery stores.\n   - **Wheat Flour:** It\'s more commonly used in baking and is often more affordable.\n\n4. **Flavor and Aroma:**\n   - **Maize Dough:** It can impart a slightly sweet and nutty flavor.\n   - **Wheat Dough:** It can have a slightly sweeter and more robust flavor.\n\nUltimately', 'Both maize (corn) flour and wheat flour can be used to make dough, and the choice between them often depends on the recipe and personal preference. Here are some considerations:\n\n1. **Nutritional Value:**\n   - **Maize Flour:** It is generally lower in gluten, which means it\'s easier to digest and has fewer calories per unit.\n   - **Wheat Flour:** It contains more gluten, which can make the dough more elastic and easier to handle.\n\n2. **Texture and Baking:**\n   - **Maize Dough:** It usually has a lighter, more tender texture and is often used for dishes that require a delicate texture.\n   - **Wheat Dough:** It provides a more robust texture and is often used for bread and other baked goods that need a chewier consistency.\n\n3. **Availability and Cost:**\n   - **Maize Flour:** It\'s more widely available and can be found in many grocery stores.\n   - **Wheat Flour:** It\'s more commonly used in baking and is often more affordable.\n\n4. **Flavor and Aroma:**\n   - **Maize Dough:** It can impart a slightly sweet and nutty flavor.\n   - **Wheat Dough:** It can have a slightly sweeter and more robust flavor.\n\nUltimately', '06:08 PM', '2026-11-15 12:38:41', 'qwen2.5:1.5b', 0),
(313, 6, 15, 'user', 'tell me abt this app', 'tell me abt this app', '06:10 PM', '2026-11-15 12:40:42', 'qwen2.5:1.5b', 0),
(314, 6, 15, 'ai', 'I\'m sorry, but I need more information about the app you\'re referring to. Could you please provide details such as the name of the app, its purpose, or any specific features you\'re interested in? This will help me give you a more accurate and helpful response.', 'I\'m sorry, but I need more information about the app you\'re referring to. Could you please provide details such as the name of the app, its purpose, or any specific features you\'re interested in? This will help me give you a more accurate and helpful response.', '06:10 PM', '2026-11-15 12:40:43', 'qwen2.5:1.5b', 0),
(315, 6, 15, 'user', 'vital sync', 'vital sync', '06:11 PM', '2026-11-15 12:41:01', 'qwen2.5:1.5b', 0),
(316, 6, 15, 'ai', 'VitalSync is an app designed to help you track and manage your health metrics. It integrates with various devices to monitor your heart rate, sleep, and other health-related data. The app provides personalized insights and recommendations based on your data, aiming to help you maintain and improve your health. It\'s useful for individuals looking to track their health progress and make informed decisions about their lifestyle.', 'VitalSync is an app designed to help you track and manage your health metrics. It integrates with various devices to monitor your heart rate, sleep, and other health-related data. The app provides personalized insights and recommendations based on your data, aiming to help you maintain and improve your health. It\'s useful for individuals looking to track their health progress and make informed decisions about their lifestyle.', '06:11 PM', '2026-11-15 12:41:02', 'qwen2.5:1.5b', 0),
(317, 6, 15, 'user', 'how many gram of protein does mutton gravy have', 'how many gram of protein does mutton gravy have', '08:00 PM', '2026-11-15 14:30:18', 'qwen2.5:1.5b', 0),
(318, 6, 15, 'ai', 'I\'m sorry, but I can\'t provide that information. The context you\'re referring to is about a food item, specifically a mutton gravy, which is a dish that typically contains meat, such as mutton, and is often seasoned with various herbs and spices. The protein content in such a dish would depend on the type of mutton used, the amount of mutton, and the seasoning. However, as an AI, I don\'t have the capability to calculate the exact protein content of a specific recipe. For accurate nutritional information, it\'s best to consult a nutritionist or use a reliable food database.', 'I\'m sorry, but I can\'t provide that information. The context you\'re referring to is about a food item, specifically a mutton gravy, which is a dish that typically contains meat, such as mutton, and is often seasoned with various herbs and spices. The protein content in such a dish would depend on the type of mutton used, the amount of mutton, and the seasoning. However, as an AI, I don\'t have the capability to calculate the exact protein content of a specific recipe. For accurate nutritional information, it\'s best to consult a nutritionist or use a reliable food database.', '08:00 PM', '2026-11-15 14:30:23', 'qwen2.5:1.5b', 0),
(319, 6, 15, 'user', 'creatine macro', 'creatine macro', '08:07 PM', '2026-11-15 14:37:34', 'qwen2.5:1.5b', 0),
(320, 6, 15, 'ai', 'Creatine is a naturally occurring compound in the body that plays a crucial role in energy metabolism. It helps to increase the production of ATP, the energy molecule used by muscles during exercise. Here are some key points about creatine:\n\n1. **Natural Sources**: Creatine is primarily found in meat, fish, and dairy products. It\'s also synthesized in the body from the amino acids glycine, arginine, and ornithine.\n\n2. **Supplementation**: Creatine monohydrate is the most common form used for supplementation. It\'s often used to enhance athletic performance by increasing muscle strength and power.\n\n3. **Benefits**:\n   - **Muscle Growth**: Promotes the growth of lean muscle mass.\n   - **Performance Enhancement**: Improves strength, power, and speed.\n   - **Aerobic Endurance**: Helps maintain performance during prolonged aerobic activities.\n   - **Inflammation Reduction**: May help reduce muscle soreness and inflammation.\n\n4. **Dosage**: The recommended daily dose for supplementation is typically 3-5 grams per day. It\'s important to start with a small amount and gradually increase as needed.\n\n5. **Safety**: While generally considered safe, high doses can lead to some side effects such as gastrointestinal discomfort, nausea, and headaches', 'Creatine is a naturally occurring compound in the body that plays a crucial role in energy metabolism. It helps to increase the production of ATP, the energy molecule used by muscles during exercise. Here are some key points about creatine:\n\n1. **Natural Sources**: Creatine is primarily found in meat, fish, and dairy products. It\'s also synthesized in the body from the amino acids glycine, arginine, and ornithine.\n\n2. **Supplementation**: Creatine monohydrate is the most common form used for supplementation. It\'s often used to enhance athletic performance by increasing muscle strength and power.\n\n3. **Benefits**:\n   - **Muscle Growth**: Promotes the growth of lean muscle mass.\n   - **Performance Enhancement**: Improves strength, power, and speed.\n   - **Aerobic Endurance**: Helps maintain performance during prolonged aerobic activities.\n   - **Inflammation Reduction**: May help reduce muscle soreness and inflammation.\n\n4. **Dosage**: The recommended daily dose for supplementation is typically 3-5 grams per day. It\'s important to start with a small amount and gradually increase as needed.\n\n5. **Safety**: While generally considered safe, high doses can lead to some side effects such as gastrointestinal discomfort, nausea, and headaches', '08:07 PM', '2026-11-15 14:37:37', 'qwen2.5:1.5b', 0),
(321, 6, 15, 'user', 'like how many protien,kal it have?', 'like how many protien,kal it have?', '08:07 PM', '2026-11-15 14:37:59', 'qwen2.5:1.5b', 0),
(322, 6, 15, 'ai', 'Creatine, a supplement that helps increase muscle strength and power, typically contains about 0.5 grams of protein per gram of creatine monohydrate. The amount of carbohydrates and fats in creatine supplements can vary widely, but these nutrients are not the primary focus of the supplement. For a more accurate understanding of the nutritional content, it\'s best to check the specific product\'s label or consult with a nutritionist or a registered dietitian.', 'Creatine, a supplement that helps increase muscle strength and power, typically contains about 0.5 grams of protein per gram of creatine monohydrate. The amount of carbohydrates and fats in creatine supplements can vary widely, but these nutrients are not the primary focus of the supplement. For a more accurate understanding of the nutritional content, it\'s best to check the specific product\'s label or consult with a nutritionist or a registered dietitian.', '08:07 PM', '2026-11-15 14:38:00', 'qwen2.5:1.5b', 0),
(323, 6, 15, 'user', 'hi', 'hi', '10:14 PM', '2026-11-15 16:44:19', 'qwen2.5:1.5b', 0),
(324, 6, 15, 'ai', 'Hello! How can I help you today?', 'Hello! How can I help you today?', '10:14 PM', '2026-11-15 16:44:19', 'qwen2.5:1.5b', 0),
(325, 6, 15, 'user', 'cake is good or bad', 'cake is good or bad', '10:14 PM', '2026-11-15 16:44:28', 'qwen2.5:1.5b', 0),
(326, 6, 15, 'ai', 'It depends on the context. In general, cake is considered a sweet treat and often enjoyed as part of a balanced diet. It\'s typically high in sugar and calories, so it\'s important to enjoy it in moderation. If you\'re looking for a healthier alternative, consider baking your own cake with whole ingredients or using cake mixes that are lower in sugar and fat.', 'It depends on the context. In general, cake is considered a sweet treat and often enjoyed as part of a balanced diet. It\'s typically high in sugar and calories, so it\'s important to enjoy it in moderation. If you\'re looking for a healthier alternative, consider baking your own cake with whole ingredients or using cake mixes that are lower in sugar and fat.', '10:14 PM', '2026-11-15 16:44:29', 'qwen2.5:1.5b', 0),
(327, 6, 15, 'user', 'hi', 'hi', '10:26 PM', '2026-11-15 16:56:40', 'qwen2.5:1.5b', 0),
(328, 6, 15, 'ai', 'Hello! How can I assist you today?', 'Hello! How can I assist you today?', '10:26 PM', '2026-11-15 16:56:40', 'qwen2.5:1.5b', 0),
(329, 6, 15, 'user', 'I\'m doing good', 'I\'m doing good', '10:26 PM', '2026-11-15 16:56:49', 'qwen2.5:1.5b', 0),
(330, 6, 15, 'ai', 'That\'s great to hear! How can I help you today?', 'That\'s great to hear! How can I help you today?', '10:26 PM', '2026-11-15 16:56:49', 'qwen2.5:1.5b', 0);

-- --------------------------------------------------------

--
-- Table structure for table `ai_chat_sessions`
--

CREATE TABLE `ai_chat_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(120) NOT NULL DEFAULT 'New Chat',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_chat_sessions`
--

INSERT INTO `ai_chat_sessions` (`id`, `user_id`, `title`, `created_at`, `updated_at`) VALUES
(1, 1, 'Hi Doctor, how is my...', '2026-11-10 16:50:14', '2026-11-10 16:50:14'),
(3, 10, 'What should my heart rate...', '2026-11-10 16:51:07', '2026-11-10 16:51:07'),
(14, 11, 'Hi', '2026-11-12 16:55:24', '2026-11-12 17:00:55'),
(15, 6, 'Hi', '2026-11-13 14:29:47', '2026-11-15 16:56:49');

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
(5, 6, '2026-11-04', 'General Wellness', 'Activity & Recovery Balance', 'Your resting heart rate is stable at 72 BPM, and daily movement is on track with 6,420 steps logged today.', 'Hydration is currently at 1500 mL; drinking an additional glass of water will help maintain optimal cardiac output.', 85, 0, 'VitalSync-Core-AI-v1', '2026-11-04 03:49:28'),
(6, 1, '2026-11-06', 'DAILY_OVERVIEW', 'Activity & Recovery Harmony', 'Your resting heart rate is stable at 72 BPM, and activity is progressing well with 6,420 steps logged today.', 'Hydration is at 1500 ml; drinking an additional glass of water will help maintain optimal cardiac output.', 86, 0, 'vitalsync:clinical-engine', '2026-11-06 05:27:49'),
(7, 6, '2026-11-06', 'DAILY_OVERVIEW', 'Activity & Recovery Harmony', 'Your resting heart rate is stable at 72 BPM, and activity is progressing well with 6,420 steps logged today.', 'Hydration is at 1500 ml; drinking an additional glass of water will help maintain optimal cardiac output.', 86, 0, 'vitalsync:clinical-engine', '2026-11-06 09:33:02'),
(8, 1, '2026-11-10', 'DAILY_OVERVIEW', 'Build Muscle Day', 'Today\'s stats: 0/12,000 steps, 0/2,500 mL hydration, 6h 45m sleep. Focus on building muscle today. Pair a wearable device to monitor resting heart rate and recovery metrics in real time.', 'Stay active with 30 minutes of resistance training.', 25, 0, 'qwen2.5:1.5b', '2026-11-10 09:19:14'),
(9, 6, '2026-11-10', 'DAILY_OVERVIEW', 'Weight Loss Boost', 'Resting heart rate remains stable, but daily steps are low, and hydration is adequate. Consider a brisk walk or light exercise to increase step count.', 'Start a 30-minute walk in the evening to boost your daily step count.', 85, 0, 'qwen2.5:1.5b', '2026-11-10 07:11:13'),
(14, 6, '2026-11-11', 'DAILY_OVERVIEW', 'Activity & Hydration Summary', 'You have completed 0 of your 10,000 daily step goal. A 20-minute brisk walk will help you close the gap. Sleep quality is Deep / Restorative at 23h 15m. Water intake is critically low (0 / 2,500 ml). Dehydration can lead to acute mental fatigue, headaches, decreased muscle recovery, and sluggish metabolism. Pair a wearable device to monitor resting heart rate and recovery metrics in real time.', 'Pair a wearable device in Settings > Connected Devices to track heart rate and recovery live.', 30, 0, 'vitalsync:clinical-engine', '2026-11-11 13:38:17'),
(16, 6, '2026-11-12', 'DAILY_OVERVIEW', 'Weight Loss Focus', 'Today, you\'ve achieved 7 hours of sleep, taken 0 steps, and consumed 0 of your daily hydration goal. Your primary goal of weight loss has been partially achieved with 26 points out of 100.', 'Stay consistent with your steps and hydration, and consider a light, nutritious snack to support your weight loss journey.', 26, 0, 'qwen2.5:1.5b', '2026-11-12 15:36:41'),
(17, 11, '2026-11-12', 'DAILY_OVERVIEW', 'Daily Wellness Check', 'Today\'s steps: 0 of 10,000, hydration: 0 of 2,500 mL, sleep: Not logged. Focus on improving your daily steps and hydration, aiming for optimal sleep quality.', 'Log your vital signs to improve your sleep quality.', 0, 0, 'qwen2.5:1.5b', '2026-11-12 16:12:10'),
(18, 16, '2026-11-12', 'DAILY_OVERVIEW', 'Daily Wellness Check', 'Today\'s steps: 0/10,000, hydration: 0/2,500 mL, sleep: Not logged. Needs to aim for 10,000 steps and 2,500 mL of hydration. Consider logging sleep and vital signs for a better understanding of your body.', 'Start tracking sleep and vital signs to see how your body feels.', 0, 0, 'qwen2.5:1.5b', '2026-11-12 16:57:44'),
(19, 18, '2026-11-12', 'DAILY_OVERVIEW', 'Daily Wellness Check', 'Today, you\'ve taken 0 steps, drank 0 of your recommended 2,500 mL of water, and slept less than 6 hours. Your fitness goal is on track, but you could improve your health with better sleep and more physical activity.', 'Consider setting a goal for 10,000 steps and aiming for 8 hours of sleep tonight.', 0, 0, 'qwen2.5:1.5b', '2026-11-12 17:17:20'),
(20, 6, '2026-11-13', 'DAILY_OVERVIEW', 'Weight Loss Focus', 'Today, you\'ve logged 0 steps, 0 of your daily 2,500 mL of water, and 18 hours of sleep. Your primary goal is to lose weight, and while your sleep and hydration are on track, your steps are lacking. Focus on increasing your daily steps to support your weight loss journey.', 'Try to log 1,000 steps by tomorrow to help you reach your weight loss goal.', 30, 0, 'qwen2.5:1.5b', '2026-11-13 14:26:42'),
(22, 6, '2026-11-15', 'DAILY_OVERVIEW', 'Activity & Hydration Summary', 'You have completed 0 of your 12,000 daily step goal. A 20-minute brisk walk will help you close the gap. Sleep quality is Deep / Restorative at 10h 0m. Water intake is critically low (0 / 2,500 ml). Dehydration can lead to acute mental fatigue, headaches, decreased muscle recovery, and sluggish metabolism. Tap \'+ Log Vitals\' on your home dashboard to record Heart Rate, SpO2, or Blood Pressure.', 'Tap \'+ Log Vitals\' on Home to record Heart Rate, SpO2, and Blood Pressure for complete insights.', 30, 0, 'vitalsync:clinical-engine', '2026-11-15 05:53:59'),
(23, 11, '2026-11-15', 'DAILY_OVERVIEW', 'Daily Progress Update', 'Today, you\'ve taken 0 of your recommended 10,000 steps, drank 0 of your recommended 2,500 mL of water, and slept 8 hours. Your sleep quality is Optimal.', 'Try to increase your step count and water intake for better health.', 30, 0, 'qwen2.5:1.5b', '2026-11-15 05:55:57');

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
-- Table structure for table `gym_nutrition_logs`
--

CREATE TABLE `gym_nutrition_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meal_type` varchar(50) NOT NULL,
  `food_description` text NOT NULL,
  `calories` int(11) NOT NULL DEFAULT 0,
  `protein_g` float NOT NULL DEFAULT 0,
  `carbs_g` float NOT NULL DEFAULT 0,
  `fats_g` float NOT NULL DEFAULT 0,
  `ai_feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_nutrition_logs`
--

INSERT INTO `gym_nutrition_logs` (`id`, `user_id`, `meal_type`, `food_description`, `calories`, `protein_g`, `carbs_g`, `fats_g`, `ai_feedback`, `created_at`) VALUES
(52, 6, 'Breakfast', '3 scoop whey', 360, 72, 9, 4.5, 'Fast-digesting whey protein with high leucine content to trigger muscle protein synthesis.', '2026-11-15 14:59:20'),
(58, 6, 'Breakfast', '4 chapatis', 420, 14, 80, 6, 'Whole wheat flatbread providing sustained energy and dietary fiber.', '2026-11-15 15:01:56'),
(59, 6, 'Lunch', 'chicken 65', 165, 31, 0, 3.6, 'Ultra-lean poultry protein (31g per 100g) with near-zero carbs, optimal for lean muscle synthesis.', '2026-11-15 15:41:04'),
(64, 6, 'Lunch', 'chicken breast,3 eggs', 399, 49.9, 1.8, 19.5, 'Meal logged (chicken breast, 3 eggs): 399 kcal, 49.9g protein, 1.8g carbs, 19.5g fats. Gold-standard complete protein source with choline and healthy fats.', '2026-11-15 16:42:42');

-- --------------------------------------------------------

--
-- Table structure for table `gym_workouts`
--

CREATE TABLE `gym_workouts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `split_name` varchar(100) NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 0,
  `total_volume_kg` float NOT NULL DEFAULT 0,
  `total_sets` int(11) NOT NULL DEFAULT 0,
  `calories_burned` int(11) NOT NULL DEFAULT 0,
  `exercises_json` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_workouts`
--

INSERT INTO `gym_workouts` (`id`, `user_id`, `split_name`, `duration_minutes`, `total_volume_kg`, `total_sets`, `calories_burned`, `exercises_json`, `created_at`) VALUES
(15, 6, 'Push', 1, 3900, 11, 8, '[{\"exercise\":{\"defaultReps\":10,\"defaultWeightKg\":60,\"id\":\"push_1\",\"isCustom\":false,\"name\":\"Barbell Bench Press\",\"notes\":\"\",\"targetMuscle\":\"Chest\"},\"notes\":\"\",\"sets\":[{\"isCompleted\":true,\"reps\":10,\"setNumber\":1,\"weightKg\":60},{\"isCompleted\":true,\"reps\":10,\"setNumber\":2,\"weightKg\":60},{\"isCompleted\":true,\"reps\":10,\"setNumber\":3,\"weightKg\":60}]},{\"exercise\":{\"defaultReps\":15,\"defaultWeightKg\":15,\"id\":\"push_4\",\"isCustom\":false,\"name\":\"Cable Chest Fly\",\"notes\":\"\",\"targetMuscle\":\"Inner Chest\"},\"notes\":\"\",\"sets\":[{\"isCompleted\":true,\"reps\":15,\"setNumber\":1,\"weightKg\":15},{\"isCompleted\":true,\"reps\":15,\"setNumber\":2,\"weightKg\":15},{\"isCompleted\":true,\"reps\":15,\"setNumber\":3,\"weightKg\":15},{\"isCompleted\":true,\"reps\":15,\"setNumber\":4,\"weightKg\":15}]},{\"exercise\":{\"defaultReps\":12,\"defaultWeightKg\":25,\"id\":\"push_5\",\"isCustom\":false,\"name\":\"Cable Triceps Pushdown\",\"notes\":\"\",\"targetMuscle\":\"Triceps Lateral Head\"},\"notes\":\"\",\"sets\":[{\"isCompleted\":true,\"reps\":12,\"setNumber\":1,\"weightKg\":25},{\"isCompleted\":true,\"reps\":12,\"setNumber\":2,\"weightKg\":25},{\"isCompleted\":true,\"reps\":12,\"setNumber\":3,\"weightKg\":25},{\"isCompleted\":true,\"reps\":12,\"setNumber\":4,\"weightKg\":25}]}]', '2026-11-15 16:41:57'),
(16, 6, 'Push', 1, 4200, 12, 8, '[{\"exercise\":{\"defaultReps\":10,\"defaultWeightKg\":60,\"id\":\"push_1\",\"isCustom\":false,\"name\":\"Barbell Bench Press\",\"notes\":\"\",\"targetMuscle\":\"Chest\"},\"notes\":\"\",\"sets\":[{\"isCompleted\":true,\"reps\":10,\"setNumber\":1,\"weightKg\":60},{\"isCompleted\":true,\"reps\":10,\"setNumber\":2,\"weightKg\":60},{\"isCompleted\":true,\"reps\":10,\"setNumber\":3,\"weightKg\":60}]},{\"exercise\":{\"defaultReps\":15,\"defaultWeightKg\":15,\"id\":\"push_4\",\"isCustom\":false,\"name\":\"Cable Chest Fly\",\"notes\":\"\",\"targetMuscle\":\"Inner Chest\"},\"notes\":\"\",\"sets\":[{\"isCompleted\":true,\"reps\":15,\"setNumber\":1,\"weightKg\":15},{\"isCompleted\":true,\"reps\":15,\"setNumber\":2,\"weightKg\":15},{\"isCompleted\":true,\"reps\":15,\"setNumber\":3,\"weightKg\":15},{\"isCompleted\":true,\"reps\":15,\"setNumber\":4,\"weightKg\":15}]},{\"exercise\":{\"defaultReps\":12,\"defaultWeightKg\":25,\"id\":\"push_5\",\"isCustom\":false,\"name\":\"Cable Triceps Pushdown\",\"notes\":\"\",\"targetMuscle\":\"Triceps Lateral Head\"},\"notes\":\"\",\"sets\":[{\"isCompleted\":true,\"reps\":12,\"setNumber\":1,\"weightKg\":25},{\"isCompleted\":true,\"reps\":12,\"setNumber\":2,\"weightKg\":25},{\"isCompleted\":true,\"reps\":12,\"setNumber\":3,\"weightKg\":25},{\"isCompleted\":true,\"reps\":12,\"setNumber\":4,\"weightKg\":25},{\"isCompleted\":true,\"reps\":12,\"setNumber\":5,\"weightKg\":25}]}]', '2026-11-15 16:57:04');

-- --------------------------------------------------------

--
-- Table structure for table `gym_workout_sets`
--

CREATE TABLE `gym_workout_sets` (
  `id` int(11) NOT NULL,
  `workout_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `exercise_name` varchar(150) NOT NULL,
  `target_muscle` varchar(100) DEFAULT NULL,
  `set_number` int(11) NOT NULL DEFAULT 1,
  `weight_kg` float NOT NULL DEFAULT 0,
  `reps` int(11) NOT NULL DEFAULT 0,
  `is_completed` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym_workout_sets`
--

INSERT INTO `gym_workout_sets` (`id`, `workout_id`, `user_id`, `exercise_name`, `target_muscle`, `set_number`, `weight_kg`, `reps`, `is_completed`, `created_at`) VALUES
(216, 15, 6, 'Barbell Bench Press', 'Chest', 1, 60, 10, 1, '2026-11-15 16:41:57'),
(217, 15, 6, 'Barbell Bench Press', 'Chest', 2, 60, 10, 1, '2026-11-15 16:41:57'),
(218, 15, 6, 'Barbell Bench Press', 'Chest', 3, 60, 10, 1, '2026-11-15 16:41:57'),
(219, 15, 6, 'Cable Chest Fly', 'Inner Chest', 1, 15, 15, 1, '2026-11-15 16:41:57'),
(220, 15, 6, 'Cable Chest Fly', 'Inner Chest', 2, 15, 15, 1, '2026-11-15 16:41:57'),
(221, 15, 6, 'Cable Chest Fly', 'Inner Chest', 3, 15, 15, 1, '2026-11-15 16:41:57'),
(222, 15, 6, 'Cable Chest Fly', 'Inner Chest', 4, 15, 15, 1, '2026-11-15 16:41:57'),
(223, 15, 6, 'Cable Triceps Pushdown', 'Triceps Lateral Head', 1, 25, 12, 1, '2026-11-15 16:41:57'),
(224, 15, 6, 'Cable Triceps Pushdown', 'Triceps Lateral Head', 2, 25, 12, 1, '2026-11-15 16:41:57'),
(225, 15, 6, 'Cable Triceps Pushdown', 'Triceps Lateral Head', 3, 25, 12, 1, '2026-11-15 16:41:57'),
(226, 15, 6, 'Cable Triceps Pushdown', 'Triceps Lateral Head', 4, 25, 12, 1, '2026-11-15 16:41:57'),
(227, 16, 6, 'Barbell Bench Press', 'Chest', 1, 60, 10, 1, '2026-11-15 16:57:04'),
(228, 16, 6, 'Barbell Bench Press', 'Chest', 2, 60, 10, 1, '2026-11-15 16:57:04'),
(229, 16, 6, 'Barbell Bench Press', 'Chest', 3, 60, 10, 1, '2026-11-15 16:57:04'),
(230, 16, 6, 'Cable Chest Fly', 'Inner Chest', 1, 15, 15, 1, '2026-11-15 16:57:04'),
(231, 16, 6, 'Cable Chest Fly', 'Inner Chest', 2, 15, 15, 1, '2026-11-15 16:57:04'),
(232, 16, 6, 'Cable Chest Fly', 'Inner Chest', 3, 15, 15, 1, '2026-11-15 16:57:04'),
(233, 16, 6, 'Cable Chest Fly', 'Inner Chest', 4, 15, 15, 1, '2026-11-15 16:57:04'),
(234, 16, 6, 'Cable Triceps Pushdown', 'Triceps Lateral Head', 1, 25, 12, 1, '2026-11-15 16:57:04'),
(235, 16, 6, 'Cable Triceps Pushdown', 'Triceps Lateral Head', 2, 25, 12, 1, '2026-11-15 16:57:04'),
(236, 16, 6, 'Cable Triceps Pushdown', 'Triceps Lateral Head', 3, 25, 12, 1, '2026-11-15 16:57:04'),
(237, 16, 6, 'Cable Triceps Pushdown', 'Triceps Lateral Head', 4, 25, 12, 1, '2026-11-15 16:57:04'),
(238, 16, 6, 'Cable Triceps Pushdown', 'Triceps Lateral Head', 5, 25, 12, 1, '2026-11-15 16:57:04');

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
(9, 6, 9008, 1, '2026-11-10 10:18:36'),
(13, 10, 2000, 1, '2026-11-13 14:51:36');

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
(30, 1, 1800, NULL, 2000, NULL, '2026-10-12', 1800, '2026-10-12 10:30:00', '2026-10-12', '2026-11-04 03:09:27'),
(36, 6, NULL, NULL, 2000, NULL, '2026-10-30', 0, '2026-11-10 12:18:06', NULL, '2026-11-10 06:48:06'),
(37, 6, 750, NULL, 2000, NULL, '2026-10-30', 750, '2026-11-10 12:18:35', '2026-10-30', '2026-11-10 06:48:35'),
(38, 6, 750, NULL, 2000, NULL, '2026-10-30', 750, '2026-11-10 12:18:43', '2026-10-30', '2026-11-10 06:48:43'),
(39, 6, 500, NULL, 2000, NULL, '2026-10-30', 500, '2026-11-10 12:19:47', '2026-10-30', '2026-11-10 06:49:47'),
(40, 6, 750, NULL, 2000, NULL, '2026-10-30', 750, '2026-11-10 12:20:55', '2026-10-30', '2026-11-10 06:50:55'),
(41, 6, 500, NULL, 2000, NULL, '2026-10-30', 500, '2026-11-10 12:41:56', '2026-10-30', '2026-11-10 07:11:56'),
(67, 6, NULL, NULL, 2000, NULL, '2026-11-10', 0, '2026-11-10 21:45:05', NULL, '2026-11-10 16:15:05'),
(68, 6, 500, NULL, 2000, NULL, '2026-11-10', 500, '2026-11-10 21:45:07', '2026-11-10', '2026-11-10 16:15:07'),
(69, 6, 500, NULL, 2000, NULL, '2026-11-10', 500, '2026-11-10 21:45:58', '2026-11-10', '2026-11-10 16:15:58'),
(70, 6, 750, NULL, 2000, NULL, '2026-11-10', 750, '2026-11-10 21:45:59', '2026-11-10', '2026-11-10 16:15:59'),
(71, 6, 5000, NULL, 2000, NULL, '2026-11-10', 5000, '2026-11-10 21:46:06', '2026-11-10', '2026-11-10 16:16:06'),
(84, 6, NULL, NULL, 2000, NULL, '2026-11-11', 0, '2026-11-11 22:37:08', NULL, '2026-11-11 17:07:08'),
(85, 16, 500, NULL, 2000, NULL, '2026-11-12', 500, '2026-11-12 22:28:31', '2026-11-12', '2026-11-12 16:58:31'),
(86, 16, 500, NULL, 2000, NULL, '2026-11-12', 500, '2026-11-12 22:29:13', '2026-11-12', '2026-11-12 16:59:13'),
(87, 11, 500, NULL, 2000, NULL, '2026-11-12', 500, '2026-11-12 22:30:38', '2026-11-12', '2026-11-12 17:00:38'),
(88, 11, 750, NULL, 2000, NULL, '2026-11-12', 750, '2026-11-12 22:30:45', '2026-11-12', '2026-11-12 17:00:45'),
(104, 6, NULL, NULL, 2000, NULL, '2026-11-12', 0, '2026-11-12 22:42:41', NULL, '2026-11-12 17:12:41'),
(105, 18, 500, NULL, 2000, NULL, '2026-11-12', 500, '2026-11-12 22:48:54', '2026-11-12', '2026-11-12 17:18:54'),
(106, 6, 500, NULL, 2000, NULL, '2026-11-12', 500, '2026-11-12 22:52:07', '2026-11-12', '2026-11-12 17:22:07'),
(107, 6, 750, NULL, 2000, NULL, '2026-11-12', 750, '2026-11-12 22:52:08', '2026-11-12', '2026-11-12 17:22:08'),
(110, 10, 9000, 9000, 2000, '2026-11-13', '2026-11-13', 9000, '2026-11-13 15:51:36', '2026-11-13', '2026-11-13 14:51:36'),
(120, 6, NULL, NULL, 2000, NULL, '2026-11-13', 0, '2026-11-13 21:54:52', NULL, '2026-11-13 16:24:52'),
(121, 6, 5000, NULL, 2000, NULL, '2026-11-13', 5000, '2026-11-13 21:54:57', '2026-11-13', '2026-11-13 16:24:57'),
(122, 6, 500, NULL, 2000, NULL, '2026-11-13', 500, '2026-11-13 21:56:32', '2026-11-13', '2026-11-13 16:26:32'),
(123, 6, 750, NULL, 2000, NULL, '2026-11-13', 750, '2026-11-13 21:56:33', '2026-11-13', '2026-11-13 16:26:33'),
(124, 6, 750, NULL, 2000, NULL, '2026-11-13', 750, '2026-11-13 21:56:43', '2026-11-13', '2026-11-13 16:26:43'),
(125, 6, 750, NULL, 2000, NULL, '2026-11-13', 750, '2026-11-13 21:56:44', '2026-11-13', '2026-11-13 16:26:44'),
(126, 6, 750, NULL, 2000, NULL, '2026-11-13', 750, '2026-11-13 21:56:44', '2026-11-13', '2026-11-13 16:26:44'),
(127, 6, 750, NULL, 2000, NULL, '2026-11-13', 750, '2026-11-13 21:56:44', '2026-11-13', '2026-11-13 16:26:44'),
(128, 6, 750, NULL, 2000, NULL, '2026-11-13', 750, '2026-11-13 21:59:27', '2026-11-13', '2026-11-13 16:29:27'),
(129, 6, 750, NULL, 2000, NULL, '2026-11-13', 750, '2026-11-13 22:07:39', '2026-11-13', '2026-11-13 16:37:39'),
(145, 6, NULL, NULL, 2000, NULL, '2026-11-15', 0, '2026-11-15 22:09:04', NULL, '2026-11-15 16:39:04'),
(146, 6, 500, NULL, 2000, NULL, '2026-11-15', 500, '2026-11-15 22:09:06', '2026-11-15', '2026-11-15 16:39:06'),
(147, 6, 750, NULL, 2000, NULL, '2026-11-15', 750, '2026-11-15 22:09:07', '2026-11-15', '2026-11-15 16:39:07'),
(148, 6, 750, NULL, 2000, NULL, '2026-11-15', 750, '2026-11-15 22:10:55', '2026-11-15', '2026-11-15 16:40:55'),
(149, 6, 250, NULL, 2000, NULL, '2026-11-15', 250, '2026-11-15 22:10:55', '2026-11-15', '2026-11-15 16:40:55'),
(150, 6, 250, NULL, 2000, NULL, '2026-11-15', 250, '2026-11-15 22:10:56', '2026-11-15', '2026-11-15 16:40:56'),
(151, 6, 750, NULL, 2000, NULL, '2026-11-15', 750, '2026-11-15 22:10:56', '2026-11-15', '2026-11-15 16:40:56'),
(152, 6, 750, NULL, 2000, NULL, '2026-11-15', 750, '2026-11-15 22:10:56', '2026-11-15', '2026-11-15 16:40:56'),
(153, 6, 750, NULL, 2000, NULL, '2026-11-15', 750, '2026-11-15 22:10:56', '2026-11-15', '2026-11-15 16:40:56'),
(154, 6, 750, NULL, 2000, NULL, '2026-11-15', 750, '2026-11-15 22:26:29', '2026-11-15', '2026-11-15 16:56:29'),
(155, 6, 250, NULL, 2000, NULL, '2026-11-15', 250, '2026-11-15 22:26:29', '2026-11-15', '2026-11-15 16:56:29'),
(156, 6, 250, NULL, 2000, NULL, '2026-11-15', 250, '2026-11-15 22:26:29', '2026-11-15', '2026-11-15 16:56:29'),
(157, 6, 250, NULL, 2000, NULL, '2026-11-15', 250, '2026-11-15 22:26:29', '2026-11-15', '2026-11-15 16:56:29'),
(158, 6, 250, NULL, 2000, NULL, '2026-11-15', 250, '2026-11-15 22:26:29', '2026-11-15', '2026-11-15 16:56:29'),
(159, 6, 250, NULL, 2000, NULL, '2026-11-15', 250, '2026-11-15 22:26:29', '2026-11-15', '2026-11-15 16:56:29'),
(160, 6, 250, NULL, 2000, NULL, '2026-11-15', 250, '2026-11-15 22:26:30', '2026-11-15', '2026-11-15 16:56:30'),
(161, 6, 750, NULL, 2000, NULL, '2026-11-15', 750, '2026-11-15 22:26:30', '2026-11-15', '2026-11-15 16:56:30'),
(162, 6, 750, NULL, 2000, NULL, '2026-11-15', 750, '2026-11-15 22:26:30', '2026-11-15', '2026-11-15 16:56:30');

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
(37, 6, 'Android Device', 'Android', '127.0.0.1', NULL, 1, '2026-11-04 03:38:02', '2026-11-04 03:38:02'),
(38, 10, 'curl/8.21.0', 'Android', '::1', NULL, 1, '2026-11-06 05:21:49', '2026-11-06 05:21:49'),
(39, 10, 'curl/8.21.0', 'Android', '::1', NULL, 1, '2026-11-06 05:22:01', '2026-11-06 05:22:01'),
(40, 10, 'curl/8.21.0', 'Android', '::1', NULL, 1, '2026-11-06 05:22:23', '2026-11-06 05:22:23'),
(41, 10, 'curl/8.21.0', 'Android', '10.154.129.52', NULL, 1, '2026-11-06 05:23:01', '2026-11-06 05:23:01'),
(42, 6, 'Android Device', 'Android', '10.154.129.60', NULL, 1, '2026-11-06 05:28:10', '2026-11-06 05:28:10'),
(43, 6, 'Android Device', 'Android', '10.154.129.60', NULL, 1, '2026-11-06 15:59:22', '2026-11-06 15:59:22'),
(44, 6, 'Android Device', 'Android', '10.18.112.127', NULL, 1, '2026-11-10 06:46:10', '2026-11-10 06:46:10'),
(45, 11, 'Android Device', 'Android', '10.18.112.127', NULL, 1, '2026-11-12 16:14:14', '2026-11-12 16:14:14'),
(46, 11, 'Android Device', 'Android', '10.18.112.127', NULL, 1, '2026-11-12 16:15:13', '2026-11-12 16:15:13'),
(51, 6, 'Android Device', 'Android', '10.18.112.127', NULL, 1, '2026-11-12 16:56:10', '2026-11-12 16:56:10'),
(52, 11, 'Android Device', 'Android', '10.18.112.127', NULL, 1, '2026-11-12 16:56:46', '2026-11-12 16:56:46'),
(53, 11, 'Android Device', 'Android', '10.18.112.127', NULL, 1, '2026-11-12 17:00:24', '2026-11-12 17:00:24'),
(54, 6, 'Android Device', 'Android', '10.18.112.127', NULL, 1, '2026-11-12 17:08:24', '2026-11-12 17:08:24'),
(55, 11, 'Android Device', 'Android', '10.18.112.127', NULL, 1, '2026-11-12 17:16:28', '2026-11-12 17:16:28'),
(56, 6, 'Android Device', 'Android', '10.18.112.127', NULL, 1, '2026-11-12 17:20:13', '2026-11-12 17:20:13'),
(57, 6, 'Android Device', 'Android', '10.205.196.94', NULL, 1, '2026-11-15 05:53:54', '2026-11-15 05:53:54'),
(58, 11, 'Android Device', 'Android', '10.205.196.94', NULL, 1, '2026-11-15 05:55:55', '2026-11-15 05:55:55'),
(59, 6, 'Android Device', 'Android', '10.205.196.94', NULL, 1, '2026-11-15 05:56:41', '2026-11-15 05:56:41');

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
(7, 1, '2026-10-29 22:30:00', '2026-10-30 05:15:00', '11:15 PM', '06:00 AM', 405, 76, '2026-10-29', 'moderate', '2026-10-29', '2026-11-04 03:23:19'),
(9, 6, '2026-11-10 06:15:00', '2026-11-11 05:30:00', '06:15 AM', '05:30 AM', 1395, 100, '2026-11-10', 'optimal', '2026-11-10', '2026-11-10 10:17:34'),
(10, 6, '2026-11-11 05:30:00', '2026-11-11 12:30:00', '05:30 AM', '12:30 PM', 420, 75, '2026-11-11', 'optimal', '2026-11-11', '2026-11-11 13:38:23'),
(11, 6, '2026-11-12 11:30:00', '2026-11-13 05:30:00', '11:30 AM', '05:30 AM', 1080, 100, '2026-11-12', 'optimal', '2026-11-12', '2026-11-12 15:44:03'),
(12, 16, '2026-11-12 22:30:00', '2026-11-13 06:30:00', '10:30 PM', '06:30 AM', 480, 85, '0000-00-00', 'optimal', '2026-11-12', '2026-11-12 16:58:27'),
(13, 11, '2026-11-12 22:30:00', '2026-11-13 06:30:00', '10:30 PM', '06:30 AM', 480, 85, '0000-00-00', 'optimal', '2026-11-12', '2026-11-12 17:00:34'),
(14, 18, '2026-11-12 22:30:00', '2026-11-13 06:30:00', '10:30 PM', '06:30 AM', 480, 85, '0000-00-00', 'optimal', '2026-11-12', '2026-11-12 17:18:48'),
(15, 6, '2026-11-13 23:30:00', '2026-11-14 09:30:00', '11:30 PM', '09:30 AM', 600, 100, '2026-11-13', 'optimal', '2026-11-13', '2026-11-13 14:27:57'),
(16, 6, '2026-11-15 12:30:00', '2026-11-15 16:30:00', '12:30 PM', '04:30 PM', 240, 50, '2026-11-15', 'critical', '2026-11-15', '2026-11-15 05:54:37');

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
(7, 1, '2026-10-29', 405, 76, 'Fair', '11:15 PM', '06:00 AM', 80, 225, 80, 20, '2026-11-04 03:23:19'),
(11, 6, '2026-11-10', 1395, 100, 'Excellent', '06:15 AM', '05:30 AM', 307, 767, 251, 70, '2026-11-10 10:17:34'),
(23, 6, '2026-11-11', 420, 75, 'Optimal', '05:30 AM', '12:30 PM', 92, 231, 76, 21, '2026-11-11 13:38:23'),
(39, 6, '2026-11-12', 1080, 100, 'Excellent', '11:30 AM', '05:30 AM', 238, 594, 194, 54, '2026-11-12 15:44:03'),
(40, 16, '2026-11-12', 480, 85, 'Optimal', '10:30 PM', '06:30 AM', 106, 264, 86, 24, '2026-11-12 16:58:27'),
(41, 11, '2026-11-12', 480, 85, 'Optimal', '10:30 PM', '06:30 AM', 106, 264, 86, 24, '2026-11-12 17:00:34'),
(43, 18, '2026-11-12', 480, 85, 'Optimal', '10:30 PM', '06:30 AM', 106, 264, 86, 24, '2026-11-12 17:18:48'),
(47, 6, '2026-11-13', 600, 100, 'Excellent', '11:30 PM', '09:30 AM', 132, 330, 108, 30, '2026-11-13 14:27:57'),
(61, 6, '2026-11-15', 240, 50, 'Poor', '12:30 PM', '04:30 PM', 53, 132, 43, 12, '2026-11-15 05:54:37');

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

--
-- Dumping data for table `steps_logs`
--

INSERT INTO `steps_logs` (`id`, `user_id`, `step_count`, `target_steps`, `distance_km`, `calories_kcal`, `active_minutes`, `logged_date`, `created_at`) VALUES
(1, 10, 250, 8000, 0.19, 10, 3, '2026-11-06', '2026-11-06 10:08:49'),
(2, 6, 1720, 8000, 1.31, 68, 17, '2026-11-06', '2026-11-06 11:03:29'),
(3, 6, 2159, 10000, 1.64, 86, 21, '2026-11-10', '2026-11-10 08:32:50'),
(4, 6, 79, 12000, 0.06, 3, 0, '2026-11-11', '2026-11-11 13:38:00'),
(5, 6, 540, 12000, 0.41, 21, 5, '2026-11-13', '2026-11-13 15:39:19'),
(6, 6, 1273, 12000, 0.97, 50, 12, '2026-11-15', '2026-11-15 08:41:08');

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
(2, 'nitesh Mv', 'nitesh', 'alex.morgan@email.com', '+1 (202) 555-0147', '14/10/2007', NULL, 'Male', NULL, NULL, 'Moderately active', 'Better Sleep', '$2y$10$Rxlj/fgP9WJyoBKjSionwugLS3fAJ8ZGWuv41lzBNOYekf038Pn52', '2026-10-27 08:03:22', '2026-11-06 05:21:03', 'uploads/profiles/user_2_f967d4e414fdcf0c.jpg'),
(3, 'Alex Morgan', 'Alex Morgan', 'alex.morgan.20261027@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$DJdm.OTwpUYIptmC2FF6yeRMkIBcXVJuSbivfdbJSuanB592Jh/CW', '2026-10-27 08:20:15', '2026-11-02 03:01:52', NULL),
(4, 'Alex Morgan', 'Alex Morgan', 'alex.morgan.t0820z7@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$8r2fBWD1uLygo4hmTvlR/uDKJMxEWMcJTLBUPgc0WyawF8NN7Er4O', '2026-10-27 08:21:15', '2026-11-02 03:01:52', NULL),
(5, 'Alex Morgan', 'Alex Morgan', 'alex.morgan22222222222@email.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$Cwg8nA.0X5p9kj1DL.83zetk9jGCctkeznsQzb6dpUapqLcIzQ1S.', '2026-10-28 02:49:25', '2026-11-02 03:01:52', NULL),
(6, 'nitesh', 'nitesh Mv', 'niteshmanjula886@gmail.com', '8825780695', '14/10/2007', 18, 'male', 175.00, 90.00, NULL, 'Build Muscle', '$2y$10$w5ZKRQZaxWLEH5MUeiIqb.G0qOXkq34uA7CREKONaktAVjuzw3gTC', '2026-10-29 04:03:11', '2026-11-15 15:42:55', NULL),
(7, 'DAKSH', 'DAKSH', 'daksh@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$azbbkGA5D6XH164ZJPsiSu1Lwvp/IE8oJbjnsN4fVz87IRWh9KhjO', '2026-10-29 04:08:41', '2026-11-02 03:01:52', NULL),
(9, 'nitesh', NULL, 'niteshghost@141gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Fitness', '$2y$10$FXYRuFWUZRcsYXCZbBmkuOSrG8EmB18XReFsRMICm2VTgDIZJxDzW', '2026-11-02 03:20:47', '2026-11-02 03:20:47', NULL),
(10, 'Test User', 'Test User', 'test@vitalsync.com', '+1 555-0199', '1995-05-15', 30, 'Male', 175.00, 70.00, 'Moderate', 'Improve Fitness', '$2y$10$Rxlj/fgP9WJyoBKjSionwugLS3fAJ8ZGWuv41lzBNOYekf038Pn52', '2026-11-06 05:21:03', '2026-11-06 05:21:03', NULL),
(11, 'sukuna', NULL, 'niteshsukuna@gmail.com', '88257880695', '14/10/2007', 18, 'male', 155.00, 69.00, NULL, 'Fitness', '$2y$10$uOoveXQn.lbFiNkH9oACaOAhhU6cizf2ki4WmlHwqpyodleWQmkDG', '2026-11-12 16:01:13', '2026-11-12 16:55:01', 'uploads/avatars/avatar_11_1794502501.jpg'),
(16, 'nitesh kumar', NULL, 'niteshthedog@gmail.com', NULL, NULL, 25, 'Not Specified', 125.00, 69.00, NULL, 'Fitness', '$2y$10$vKFj0q/Q6M0GW9NUq0yIqu3nqIEDIacgjO2O.tUlr15KcDwTKuXxe', '2026-11-12 16:57:41', '2026-11-12 16:59:52', 'uploads/avatars/avatar_16_1794502792.jpg'),
(18, 'mirage', NULL, 'niteshassasssin@gmail.com', NULL, NULL, 15, 'Male', 180.00, 55.00, 'Sedentary (Little to no exercise)', 'Fitness', '$2y$10$iRdg/8kXDMZxZ8zgxGjd2uuu3Rsz8J7SJUj7xR2e3KCXlpiJW9ct2', '2026-11-12 17:17:19', '2026-11-12 17:18:07', 'uploads/avatars/avatar_18_1794503887.jpg');

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
(9, 1, 1, 1, 'metric', 0, 15, '2026-11-02 08:50:47', 1, 0, 1, 1, 120, 1, 1),
(10, 1, 1, 1, 'metric', 0, 15, '2026-11-06 10:51:03', 1, 0, 1, 1, 120, 1, 1),
(11, 1, 1, 1, 'metric', 0, 15, '2026-11-12 21:31:13', 1, 0, 1, 1, 120, 1, 1),
(16, 1, 1, 1, 'metric', 0, 15, '2026-11-12 22:27:41', 1, 0, 1, 1, 120, 1, 1),
(18, 1, 1, 1, 'metric', 0, 15, '2026-11-12 22:47:19', 1, 0, 1, 1, 120, 1, 1);

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
(1, 'Build Muscle', 12000, 8.5, 3000, 68.0, 2100, '2026-11-10 14:49:45'),
(2, 'Better Sleep', NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(3, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(4, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(5, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(6, 'Build Muscle', 12000, 8.0, 9008, NULL, 2200, '2026-11-13 19:57:04'),
(7, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(9, 'Fitness', NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:50:47'),
(10, 'Improve Fitness', 10000, 8.0, 2500, 68.0, 2200, '2026-11-06 10:51:03'),
(11, 'Fitness', NULL, NULL, NULL, NULL, NULL, '2026-11-12 21:31:13'),
(16, 'Fitness', 8000, NULL, NULL, NULL, NULL, '2026-11-12 22:28:24'),
(18, 'Fitness', 12000, NULL, NULL, NULL, NULL, '2026-11-12 22:48:44');

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
(6, 'nitesh', 18, 'male', 175.0, 90.0, NULL, '8825780695', NULL, '2026-11-15 21:12:55'),
(7, 'DAKSH', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:25:19'),
(9, 'nitesh', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-02 08:50:47'),
(10, 'Test User', 30, 'Male', 175.0, 70.0, 'O+', '+1 555-0199', NULL, '2026-11-06 10:51:03'),
(11, 'sukuna', 18, 'male', 155.0, 69.0, NULL, '88257880695', 'uploads/avatars/avatar_11_1794502501.jpg', '2026-11-12 22:25:01'),
(16, 'nitesh kumar', 25, 'Not Specified', 125.0, 69.0, NULL, NULL, 'uploads/avatars/avatar_16_1794502792.jpg', '2026-11-12 22:29:52'),
(18, 'mirage', 15, 'Male', 180.0, 55.0, NULL, NULL, 'uploads/avatars/avatar_18_1794503887.jpg', '2026-11-12 22:48:07');

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

--
-- Dumping data for table `vitals_logs`
--

INSERT INTO `vitals_logs` (`id`, `user_id`, `device_id`, `heart_rate`, `spo2`, `systolic_bp`, `diastolic_bp`, `source`, `logged_at`, `created_at`) VALUES
(5, 6, NULL, 74, NULL, NULL, NULL, 'Manual Entry', '2026-11-11 20:30:42', '2026-11-11 15:00:42'),
(6, 6, NULL, 74, 80, NULL, NULL, 'Manual Entry', '2026-11-11 20:31:07', '2026-11-11 15:01:07'),
(7, 6, NULL, 74, 80, 110, 90, 'Manual Entry', '2026-11-11 20:31:26', '2026-11-11 15:01:26'),
(8, 6, NULL, 74, 80, 110, 90, 'Manual Entry', '2026-11-11 20:36:33', '2026-11-11 15:06:33'),
(9, 6, NULL, 40, 80, 110, 90, 'Manual Entry', '2026-11-11 20:36:52', '2026-11-11 15:06:52'),
(10, 6, NULL, 40, 80, 110, 90, 'Manual Entry', '2026-11-11 20:37:07', '2026-11-11 15:07:07'),
(11, 6, NULL, 50, 80, 110, 90, 'Manual Entry', '2026-11-11 20:55:46', '2026-11-11 15:25:46'),
(12, 6, NULL, 90, 80, 110, 90, 'Manual Entry', '2026-11-11 20:55:55', '2026-11-11 15:25:55'),
(13, 6, NULL, 90, 80, 120, 90, 'Manual Entry', '2026-11-11 20:56:13', '2026-11-11 15:26:13'),
(17, 6, NULL, 75, NULL, NULL, NULL, 'Manual Entry', '2026-11-11 21:26:29', '2026-11-11 15:56:29'),
(18, 6, NULL, 60, NULL, NULL, NULL, 'Manual Entry', '2026-11-11 21:27:04', '2026-11-11 15:57:04'),
(19, 6, NULL, NULL, 96, NULL, NULL, 'Manual Entry', '2026-11-11 21:27:33', '2026-11-11 15:57:33'),
(20, 6, NULL, NULL, 74, NULL, NULL, 'Manual Entry', '2026-11-11 21:28:13', '2026-11-11 15:58:13'),
(21, 6, NULL, NULL, 85, NULL, NULL, 'Manual Entry', '2026-11-11 21:28:42', '2026-11-11 15:58:42'),
(22, 6, NULL, NULL, NULL, 150, 90, 'Manual Entry', '2026-11-11 21:29:56', '2026-11-11 15:59:56'),
(23, 6, NULL, NULL, NULL, 120, 90, 'Manual Entry', '2026-11-11 21:30:15', '2026-11-11 16:00:15'),
(24, 6, NULL, NULL, NULL, 70, 40, 'Manual Entry', '2026-11-11 21:30:31', '2026-11-11 16:00:31'),
(25, 6, NULL, NULL, NULL, 70, 60, 'Manual Entry', '2026-11-11 21:32:31', '2026-11-11 16:02:31'),
(26, 6, NULL, NULL, NULL, 140, 60, 'Manual Entry', '2026-11-11 21:33:20', '2026-11-11 16:03:20'),
(27, 6, NULL, NULL, 100, NULL, NULL, 'Manual Entry', '2026-11-11 21:33:38', '2026-11-11 16:03:38'),
(28, 6, NULL, NULL, NULL, 70, 47, 'Manual Entry', '2026-11-11 21:34:31', '2026-11-11 16:04:31'),
(29, 6, NULL, NULL, 90, NULL, NULL, 'Manual Entry', '2026-11-11 21:35:40', '2026-11-11 16:05:40'),
(30, 6, NULL, NULL, 96, NULL, NULL, 'Manual Entry', '2026-11-11 21:36:05', '2026-11-11 16:06:05'),
(31, 6, NULL, 40, NULL, NULL, NULL, 'Manual Entry', '2026-11-11 21:37:09', '2026-11-11 16:07:09'),
(32, 6, NULL, 50, NULL, NULL, NULL, 'Manual Entry', '2026-11-11 21:37:30', '2026-11-11 16:07:30'),
(33, 6, NULL, 60, NULL, NULL, NULL, 'Manual Entry', '2026-11-11 21:37:41', '2026-11-11 16:07:41'),
(34, 6, NULL, 128, NULL, NULL, NULL, 'Manual Entry', '2026-11-11 21:37:51', '2026-11-11 16:07:51'),
(35, 6, NULL, 120, NULL, NULL, NULL, 'Manual Entry', '2026-11-11 21:38:05', '2026-11-11 16:08:05'),
(37, 6, NULL, NULL, 75, NULL, NULL, 'Manual Entry', '2026-11-11 21:56:17', '2026-11-11 16:26:17'),
(38, 6, NULL, NULL, NULL, 75, 60, 'Manual Entry', '2026-11-11 21:57:11', '2026-11-11 16:27:11'),
(39, 6, NULL, NULL, NULL, 100, 90, 'Manual Entry', '2026-11-11 21:58:40', '2026-11-11 16:28:40'),
(41, 6, NULL, NULL, NULL, 70, 40, 'Manual Entry', '2026-11-11 22:25:29', '2026-11-11 16:55:29'),
(42, 6, NULL, NULL, NULL, 80, 60, 'Manual Entry', '2026-11-11 22:25:46', '2026-11-11 16:55:46'),
(43, 6, NULL, NULL, NULL, 110, 90, 'Manual Entry', '2026-11-11 22:25:58', '2026-11-11 16:55:58'),
(44, 6, NULL, NULL, NULL, 120, 60, 'Manual Entry', '2026-11-11 22:26:13', '2026-11-11 16:56:13'),
(45, 6, NULL, NULL, NULL, 120, 80, 'Manual Entry', '2026-11-11 22:26:24', '2026-11-11 16:56:24'),
(46, 6, NULL, NULL, NULL, 110, 80, 'Manual Entry', '2026-11-11 22:26:49', '2026-11-11 16:56:49'),
(47, 6, NULL, NULL, NULL, 120, 70, 'Manual Entry', '2026-11-11 22:32:30', '2026-11-11 17:02:30'),
(48, 6, NULL, NULL, NULL, 120, 78, 'Manual Entry', '2026-11-11 22:32:54', '2026-11-11 17:02:54'),
(49, 6, NULL, 45, NULL, NULL, NULL, 'Manual Entry', '2026-11-11 22:34:59', '2026-11-11 17:04:59'),
(50, 6, NULL, 50, NULL, NULL, NULL, 'Manual Entry', '2026-11-11 22:35:08', '2026-11-11 17:05:08'),
(51, 6, NULL, 65, NULL, NULL, NULL, 'Manual Entry', '2026-11-11 22:35:18', '2026-11-11 17:05:18'),
(52, 6, NULL, 40, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 21:07:37', '2026-11-12 15:37:37'),
(53, 6, NULL, 35, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 21:07:58', '2026-11-12 15:37:58'),
(54, 6, NULL, NULL, NULL, 150, 90, 'Manual Entry', '2026-11-12 21:08:19', '2026-11-12 15:38:19'),
(55, 6, NULL, NULL, 96, NULL, NULL, 'Manual Entry', '2026-11-12 21:08:44', '2026-11-12 15:38:44'),
(56, 6, NULL, 80, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 21:09:20', '2026-11-12 15:39:20'),
(57, 6, NULL, 150, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 21:09:27', '2026-11-12 15:39:27'),
(58, 6, NULL, 90, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 21:10:24', '2026-11-12 15:40:24'),
(59, 6, NULL, 35, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 21:11:56', '2026-11-12 15:41:56'),
(60, 6, NULL, 60, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 21:12:42', '2026-11-12 15:42:42'),
(61, 6, NULL, NULL, NULL, 120, 90, 'Manual Entry', '2026-11-12 21:13:02', '2026-11-12 15:43:02'),
(62, 6, NULL, NULL, NULL, 130, 90, 'Manual Entry', '2026-11-12 21:13:34', '2026-11-12 15:43:34'),
(63, 6, NULL, 100, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 21:29:38', '2026-11-12 15:59:38'),
(64, 6, NULL, 130, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 21:29:53', '2026-11-12 15:59:53'),
(65, 11, NULL, 50, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 21:43:18', '2026-11-12 16:13:18'),
(66, 11, NULL, NULL, 96, NULL, NULL, 'Manual Entry', '2026-11-12 21:43:30', '2026-11-12 16:13:30'),
(67, 11, NULL, NULL, NULL, 150, 85, 'Manual Entry', '2026-11-12 21:43:41', '2026-11-12 16:13:41'),
(68, 11, NULL, 90, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 22:25:10', '2026-11-12 16:55:10'),
(69, 16, NULL, 50, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 22:28:04', '2026-11-12 16:58:04'),
(70, 16, NULL, NULL, 80, NULL, NULL, 'Manual Entry', '2026-11-12 22:28:11', '2026-11-12 16:58:11'),
(71, 16, NULL, NULL, NULL, 150, 90, 'Manual Entry', '2026-11-12 22:28:18', '2026-11-12 16:58:18'),
(72, 16, NULL, 180, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 22:28:43', '2026-11-12 16:58:43'),
(73, 16, NULL, NULL, NULL, 120, 90, 'Manual Entry', '2026-11-12 22:28:54', '2026-11-12 16:58:54'),
(74, 16, NULL, NULL, NULL, 120, 40, 'Manual Entry', '2026-11-12 22:29:00', '2026-11-12 16:59:00'),
(75, 18, NULL, 50, NULL, NULL, NULL, 'Manual Entry', '2026-11-12 22:48:17', '2026-11-12 17:18:17'),
(76, 18, NULL, NULL, 96, NULL, NULL, 'Manual Entry', '2026-11-12 22:48:24', '2026-11-12 17:18:24'),
(77, 18, NULL, NULL, NULL, 150, 80, 'Manual Entry', '2026-11-12 22:48:35', '2026-11-12 17:18:35'),
(78, 6, NULL, 50, NULL, NULL, NULL, 'Manual Entry', '2026-11-13 19:57:17', '2026-11-13 14:27:17'),
(79, 6, NULL, NULL, 98, NULL, NULL, 'Manual Entry', '2026-11-13 19:57:28', '2026-11-13 14:27:28'),
(80, 6, NULL, NULL, NULL, 140, 40, 'Manual Entry', '2026-11-13 19:57:40', '2026-11-13 14:27:40'),
(81, 6, NULL, 80, NULL, NULL, NULL, 'Manual Entry', '2026-11-13 21:11:41', '2026-11-13 15:41:41'),
(82, 6, NULL, 65, NULL, NULL, NULL, 'Manual Entry', '2026-11-13 21:49:49', '2026-11-13 16:19:49'),
(83, 6, NULL, 55, NULL, NULL, NULL, 'Manual Entry', '2026-11-13 21:52:08', '2026-11-13 16:22:08'),
(84, 6, NULL, 90, NULL, NULL, NULL, 'Manual Entry', '2026-11-13 21:52:20', '2026-11-13 16:22:20'),
(85, 6, NULL, 45, NULL, NULL, NULL, 'Manual Entry', '2026-11-13 21:52:34', '2026-11-13 16:22:34'),
(86, 6, NULL, 90, NULL, NULL, NULL, 'Manual Entry', '2026-11-13 21:53:40', '2026-11-13 16:23:40'),
(87, 6, NULL, 50, NULL, NULL, NULL, 'Manual Entry', '2026-11-13 21:53:53', '2026-11-13 16:23:53'),
(88, 6, NULL, 87, NULL, NULL, NULL, 'Manual Entry', '2026-11-13 21:55:16', '2026-11-13 16:25:16'),
(89, 6, NULL, NULL, 100, NULL, NULL, 'Manual Entry', '2026-11-13 21:55:41', '2026-11-13 16:25:41'),
(90, 6, NULL, NULL, NULL, 110, 40, 'Manual Entry', '2026-11-13 21:56:06', '2026-11-13 16:26:06'),
(91, 6, NULL, NULL, 80, NULL, NULL, 'Manual Entry', '2026-11-13 21:56:54', '2026-11-13 16:26:54'),
(92, 6, NULL, NULL, 98, NULL, NULL, 'Manual Entry', '2026-11-13 21:57:12', '2026-11-13 16:27:12'),
(93, 6, NULL, NULL, 97, NULL, NULL, 'Manual Entry', '2026-11-13 22:07:58', '2026-11-13 16:37:58'),
(94, 6, NULL, NULL, 99, NULL, NULL, 'Manual Entry', '2026-11-13 22:08:11', '2026-11-13 16:38:11'),
(95, 6, NULL, NULL, 96, NULL, NULL, 'Manual Entry', '2026-11-13 22:09:16', '2026-11-13 16:39:16'),
(96, 6, NULL, 42, NULL, NULL, NULL, 'Manual Entry', '2026-11-13 22:09:44', '2026-11-13 16:39:44'),
(97, 6, NULL, 80, NULL, NULL, NULL, 'Manual Entry', '2026-11-15 11:24:04', '2026-11-15 05:54:04'),
(98, 6, NULL, NULL, 98, NULL, NULL, 'Manual Entry', '2026-11-15 11:24:16', '2026-11-15 05:54:16'),
(99, 6, NULL, NULL, NULL, 150, 90, 'Manual Entry', '2026-11-15 11:24:26', '2026-11-15 05:54:26'),
(100, 6, NULL, 80, NULL, NULL, NULL, 'Manual Entry', '2026-11-15 11:36:08', '2026-11-15 06:06:08'),
(101, 6, NULL, 56, NULL, NULL, NULL, 'Manual Entry', '2026-11-15 11:36:12', '2026-11-15 06:06:12'),
(102, 6, NULL, NULL, 80, NULL, NULL, 'Manual Entry', '2026-11-15 12:02:03', '2026-11-15 06:32:03'),
(103, 6, NULL, 98, NULL, NULL, NULL, 'Manual Entry', '2026-11-15 12:04:06', '2026-11-15 06:34:06'),
(104, 6, NULL, NULL, 95, NULL, NULL, 'Manual Entry', '2026-11-15 12:04:36', '2026-11-15 06:34:36'),
(105, 6, NULL, 80, NULL, NULL, NULL, 'Manual Entry', '2026-11-15 14:08:58', '2026-11-15 08:38:58'),
(106, 6, NULL, 45, NULL, NULL, NULL, 'Manual Entry', '2026-11-15 14:09:09', '2026-11-15 08:39:09'),
(107, 6, NULL, NULL, 99, NULL, NULL, 'Manual Entry', '2026-11-15 14:12:23', '2026-11-15 08:42:23'),
(108, 6, NULL, NULL, 100, NULL, NULL, 'Manual Entry', '2026-11-15 14:12:37', '2026-11-15 08:42:37'),
(109, 6, NULL, 90, NULL, NULL, NULL, 'Manual Entry', '2026-11-15 17:50:51', '2026-11-15 12:20:51'),
(110, 6, NULL, 47, NULL, NULL, NULL, 'Manual Entry', '2026-11-15 22:09:23', '2026-11-15 16:39:23');

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
(5, 6, 'bmi', 26.10, NULL, '26.1', 'bmi', 'Overweight', 'Manual Entry', NULL, '2026-11-02 10:02:38', '2026-11-02 09:02:38'),
(6, 10, 'steps', 150.00, NULL, '150 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-06 15:38:49', '2026-11-06 10:08:49'),
(7, 10, 'steps', 250.00, NULL, '250 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-06 15:41:39', '2026-11-06 10:11:39'),
(8, 6, 'steps', 25.00, NULL, '25 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-06 16:33:29', '2026-11-06 11:03:29'),
(9, 6, 'steps', 1720.00, NULL, '1,720 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-06 20:59:17', '2026-11-06 15:29:17'),
(10, 6, 'steps', 49.00, NULL, '49 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-10 14:02:50', '2026-11-10 08:32:50'),
(11, 6, 'steps', 65.00, NULL, '65 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-10 15:08:25', '2026-11-10 09:38:25'),
(12, 6, 'steps', 102.00, NULL, '102 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-10 15:45:29', '2026-11-10 10:15:29'),
(13, 6, 'steps', 2159.00, NULL, '2,159 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-10 21:42:44', '2026-11-10 16:12:44'),
(14, 6, 'steps', 71.00, NULL, '71 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-11 19:08:00', '2026-11-11 13:38:00'),
(27, 6, 'heart_rate', 74.00, NULL, '74 BPM', 'bpm', 'Normal Resting', 'Manual Entry', NULL, '2026-11-11 20:30:42', '2026-11-11 15:00:42'),
(28, 6, 'heart_rate', 74.00, NULL, '74 BPM', 'bpm', 'Normal Resting', 'Manual Entry', NULL, '2026-11-11 20:31:07', '2026-11-11 15:01:07'),
(29, 6, 'spo2', 80.00, NULL, '80%', '%', 'Low SpO2', 'Manual Entry', NULL, '2026-11-11 20:31:07', '2026-11-11 15:01:07'),
(30, 6, 'heart_rate', 74.00, NULL, '74 BPM', 'bpm', 'Normal Resting', 'Manual Entry', NULL, '2026-11-11 20:31:26', '2026-11-11 15:01:26'),
(31, 6, 'spo2', 80.00, NULL, '80%', '%', 'Low SpO2', 'Manual Entry', NULL, '2026-11-11 20:31:26', '2026-11-11 15:01:26'),
(32, 6, 'blood_pressure', 110.00, 90.00, '110/90 mmHg', 'mmHg', 'Stage 2 HTN', 'Manual Entry', NULL, '2026-11-11 20:31:26', '2026-11-11 15:01:26'),
(33, 6, 'heart_rate', 74.00, NULL, '74 BPM', 'bpm', 'Normal Resting', 'Manual Entry', NULL, '2026-11-11 20:36:33', '2026-11-11 15:06:33'),
(34, 6, 'spo2', 80.00, NULL, '80%', '%', 'Low SpO2', 'Manual Entry', NULL, '2026-11-11 20:36:33', '2026-11-11 15:06:33'),
(35, 6, 'blood_pressure', 110.00, 90.00, '110/90 mmHg', 'mmHg', 'Stage 2 HTN', 'Manual Entry', NULL, '2026-11-11 20:36:33', '2026-11-11 15:06:33'),
(36, 6, 'heart_rate', 40.00, NULL, '40 BPM', 'bpm', 'Low / Athletic', 'Manual Entry', NULL, '2026-11-11 20:36:52', '2026-11-11 15:06:52'),
(37, 6, 'spo2', 80.00, NULL, '80%', '%', 'Low SpO2', 'Manual Entry', NULL, '2026-11-11 20:36:52', '2026-11-11 15:06:52'),
(38, 6, 'blood_pressure', 110.00, 90.00, '110/90 mmHg', 'mmHg', 'Stage 2 HTN', 'Manual Entry', NULL, '2026-11-11 20:36:52', '2026-11-11 15:06:52'),
(39, 6, 'heart_rate', 40.00, NULL, '40 BPM', 'bpm', 'Low / Athletic', 'Manual Entry', NULL, '2026-11-11 20:37:07', '2026-11-11 15:07:07'),
(40, 6, 'spo2', 80.00, NULL, '80%', '%', 'Low SpO2', 'Manual Entry', NULL, '2026-11-11 20:37:07', '2026-11-11 15:07:07'),
(41, 6, 'blood_pressure', 110.00, 90.00, '110/90 mmHg', 'mmHg', 'Stage 2 HTN', 'Manual Entry', NULL, '2026-11-11 20:37:07', '2026-11-11 15:07:07'),
(42, 6, 'heart_rate', 50.00, NULL, '50 BPM', 'bpm', 'Low / Athletic', 'Manual Entry', NULL, '2026-11-11 20:55:46', '2026-11-11 15:25:46'),
(43, 6, 'spo2', 80.00, NULL, '80%', '%', 'Low SpO2', 'Manual Entry', NULL, '2026-11-11 20:55:46', '2026-11-11 15:25:46'),
(44, 6, 'blood_pressure', 110.00, 90.00, '110/90 mmHg', 'mmHg', 'Stage 2 HTN', 'Manual Entry', NULL, '2026-11-11 20:55:46', '2026-11-11 15:25:46'),
(45, 6, 'heart_rate', 90.00, NULL, '90 BPM', 'bpm', 'Normal Resting', 'Manual Entry', NULL, '2026-11-11 20:55:55', '2026-11-11 15:25:55'),
(46, 6, 'spo2', 80.00, NULL, '80%', '%', 'Low SpO2', 'Manual Entry', NULL, '2026-11-11 20:55:55', '2026-11-11 15:25:55'),
(47, 6, 'blood_pressure', 110.00, 90.00, '110/90 mmHg', 'mmHg', 'Stage 2 HTN', 'Manual Entry', NULL, '2026-11-11 20:55:55', '2026-11-11 15:25:55'),
(48, 6, 'heart_rate', 90.00, NULL, '90 BPM', 'bpm', 'Normal Resting', 'Manual Entry', NULL, '2026-11-11 20:56:13', '2026-11-11 15:26:13'),
(49, 6, 'spo2', 80.00, NULL, '80%', '%', 'Low SpO2', 'Manual Entry', NULL, '2026-11-11 20:56:13', '2026-11-11 15:26:13'),
(50, 6, 'blood_pressure', 120.00, 90.00, '120/90 mmHg', 'mmHg', 'Stage 2 HTN', 'Manual Entry', NULL, '2026-11-11 20:56:13', '2026-11-11 15:26:13'),
(54, 6, 'heart_rate', 75.00, NULL, '75 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', 'before cardio', '2026-11-11 21:26:29', '2026-11-11 15:56:29'),
(55, 6, 'heart_rate', 60.00, NULL, '60 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-11 21:27:04', '2026-11-11 15:57:04'),
(56, 6, 'spo2', 96.00, NULL, '96%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-11 21:27:33', '2026-11-11 15:57:33'),
(57, 6, 'spo2', 74.00, NULL, '74%', '%', 'Low Oxygen / Consult Doctor', 'Manual Entry', NULL, '2026-11-11 21:28:13', '2026-11-11 15:58:13'),
(58, 6, 'spo2', 85.00, NULL, '85%', '%', 'Low Oxygen / Consult Doctor', 'Manual Entry', NULL, '2026-11-11 21:28:42', '2026-11-11 15:58:42'),
(59, 6, 'blood_pressure', 150.00, 90.00, '150/90 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-11 21:29:56', '2026-11-11 15:59:56'),
(60, 6, 'blood_pressure', 120.00, 90.00, '120/90 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-11 21:30:15', '2026-11-11 16:00:15'),
(61, 6, 'blood_pressure', 70.00, 40.00, '70/40 mmHg', 'mmHg', 'Normal Blood Pressure', 'Manual Entry', NULL, '2026-11-11 21:30:31', '2026-11-11 16:00:31'),
(62, 6, 'blood_pressure', 70.00, 60.00, '70/60 mmHg', 'mmHg', 'Normal Blood Pressure', 'Manual Entry', NULL, '2026-11-11 21:32:31', '2026-11-11 16:02:31'),
(63, 6, 'blood_pressure', 140.00, 60.00, '140/60 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-11 21:33:20', '2026-11-11 16:03:20'),
(64, 6, 'spo2', 100.00, NULL, '100%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-11 21:33:38', '2026-11-11 16:03:38'),
(65, 6, 'blood_pressure', 70.00, 47.00, '70/47 mmHg', 'mmHg', 'Normal Blood Pressure', 'Manual Entry', NULL, '2026-11-11 21:34:31', '2026-11-11 16:04:31'),
(66, 6, 'spo2', 90.00, NULL, '90%', '%', 'Mild Hypoxia', 'Manual Entry', NULL, '2026-11-11 21:35:40', '2026-11-11 16:05:40'),
(67, 6, 'spo2', 96.00, NULL, '96%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-11 21:36:05', '2026-11-11 16:06:05'),
(68, 6, 'heart_rate', 40.00, NULL, '40 BPM', 'bpm', 'Severe Bradycardia', 'Manual Entry', NULL, '2026-11-11 21:37:09', '2026-11-11 16:07:09'),
(69, 6, 'heart_rate', 50.00, NULL, '50 BPM', 'bpm', 'Athletic / Low Resting', 'Manual Entry', NULL, '2026-11-11 21:37:30', '2026-11-11 16:07:30'),
(70, 6, 'heart_rate', 60.00, NULL, '60 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-11 21:37:41', '2026-11-11 16:07:41'),
(71, 6, 'heart_rate', 128.00, NULL, '128 BPM', 'bpm', 'Elevated', 'Manual Entry', NULL, '2026-11-11 21:37:51', '2026-11-11 16:07:51'),
(72, 6, 'heart_rate', 120.00, NULL, '120 BPM', 'bpm', 'Elevated', 'Manual Entry', NULL, '2026-11-11 21:38:05', '2026-11-11 16:08:05'),
(74, 6, 'spo2', 75.00, NULL, '75%', '%', 'Low Oxygen / Consult Doctor', 'Manual Entry', NULL, '2026-11-11 21:56:17', '2026-11-11 16:26:17'),
(75, 6, 'blood_pressure', 75.00, 60.00, '75/60 mmHg', 'mmHg', 'Optimal Blood Pressure', 'Manual Entry', NULL, '2026-11-11 21:57:11', '2026-11-11 16:27:11'),
(76, 6, 'blood_pressure', 100.00, 90.00, '100/90 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-11 21:58:40', '2026-11-11 16:28:40'),
(78, 6, 'blood_pressure', 70.00, 40.00, '70/40 mmHg', 'mmHg', 'Low Blood Pressure (Hypotension)', 'Manual Entry', NULL, '2026-11-11 22:25:29', '2026-11-11 16:55:29'),
(79, 6, 'blood_pressure', 80.00, 60.00, '80/60 mmHg', 'mmHg', 'Low Blood Pressure (Hypotension)', 'Manual Entry', NULL, '2026-11-11 22:25:46', '2026-11-11 16:55:46'),
(80, 6, 'blood_pressure', 110.00, 90.00, '110/90 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-11 22:25:58', '2026-11-11 16:55:58'),
(81, 6, 'blood_pressure', 120.00, 60.00, '120/60 mmHg', 'mmHg', 'Elevated Blood Pressure', 'Manual Entry', NULL, '2026-11-11 22:26:13', '2026-11-11 16:56:13'),
(82, 6, 'blood_pressure', 120.00, 80.00, '120/80 mmHg', 'mmHg', 'Stage 1 Hypertension', 'Manual Entry', NULL, '2026-11-11 22:26:24', '2026-11-11 16:56:24'),
(83, 6, 'blood_pressure', 110.00, 80.00, '110/80 mmHg', 'mmHg', 'Stage 1 Hypertension', 'Manual Entry', NULL, '2026-11-11 22:26:49', '2026-11-11 16:56:49'),
(84, 6, 'blood_pressure', 120.00, 70.00, '120/70 mmHg', 'mmHg', 'Elevated Blood Pressure', 'Manual Entry', NULL, '2026-11-11 22:32:30', '2026-11-11 17:02:30'),
(85, 6, 'blood_pressure', 120.00, 78.00, '120/78 mmHg', 'mmHg', 'Elevated Blood Pressure', 'Manual Entry', NULL, '2026-11-11 22:32:54', '2026-11-11 17:02:54'),
(86, 6, 'steps', 79.00, NULL, '79 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-11 22:34:16', '2026-11-11 17:04:16'),
(87, 6, 'heart_rate', 45.00, NULL, '45 BPM', 'bpm', 'Severe Bradycardia', 'Manual Entry', NULL, '2026-11-11 22:34:59', '2026-11-11 17:04:59'),
(88, 6, 'heart_rate', 50.00, NULL, '50 BPM', 'bpm', 'Athletic / Low Resting', 'Manual Entry', NULL, '2026-11-11 22:35:08', '2026-11-11 17:05:08'),
(89, 6, 'heart_rate', 65.00, NULL, '65 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-11 22:35:18', '2026-11-11 17:05:18'),
(90, 6, 'heart_rate', 40.00, NULL, '40 BPM', 'bpm', 'Severe Bradycardia', 'Manual Entry', NULL, '2026-11-12 21:07:37', '2026-11-12 15:37:37'),
(91, 6, 'heart_rate', 35.00, NULL, '35 BPM', 'bpm', 'Severe Bradycardia', 'Manual Entry', NULL, '2026-11-12 21:07:58', '2026-11-12 15:37:58'),
(92, 6, 'blood_pressure', 150.00, 90.00, '150/90 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-12 21:08:19', '2026-11-12 15:38:19'),
(93, 6, 'spo2', 96.00, NULL, '96%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-12 21:08:44', '2026-11-12 15:38:44'),
(94, 6, 'heart_rate', 80.00, NULL, '80 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-12 21:09:20', '2026-11-12 15:39:20'),
(95, 6, 'heart_rate', 150.00, NULL, '150 BPM', 'bpm', 'Tachycardia / High', 'Manual Entry', NULL, '2026-11-12 21:09:27', '2026-11-12 15:39:27'),
(96, 6, 'heart_rate', 90.00, NULL, '90 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-12 21:10:24', '2026-11-12 15:40:24'),
(97, 6, 'heart_rate', 35.00, NULL, '35 BPM', 'bpm', 'Severe Bradycardia', 'Manual Entry', NULL, '2026-11-12 21:11:56', '2026-11-12 15:41:56'),
(98, 6, 'heart_rate', 60.00, NULL, '60 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-12 21:12:42', '2026-11-12 15:42:42'),
(99, 6, 'blood_pressure', 120.00, 90.00, '120/90 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-12 21:13:02', '2026-11-12 15:43:02'),
(100, 6, 'blood_pressure', 130.00, 90.00, '130/90 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-12 21:13:34', '2026-11-12 15:43:34'),
(101, 6, 'heart_rate', 100.00, NULL, '100 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-12 21:29:38', '2026-11-12 15:59:38'),
(102, 6, 'heart_rate', 130.00, NULL, '130 BPM', 'bpm', 'Tachycardia / High', 'Manual Entry', NULL, '2026-11-12 21:29:53', '2026-11-12 15:59:53'),
(103, 11, 'heart_rate', 50.00, NULL, '50 BPM', 'bpm', 'Athletic / Low Resting', 'Manual Entry', NULL, '2026-11-12 21:43:18', '2026-11-12 16:13:18'),
(104, 11, 'spo2', 96.00, NULL, '96%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-12 21:43:30', '2026-11-12 16:13:30'),
(105, 11, 'blood_pressure', 150.00, 85.00, '150/85 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-12 21:43:41', '2026-11-12 16:13:41'),
(106, 11, 'heart_rate', 90.00, NULL, '90 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-12 22:25:10', '2026-11-12 16:55:10'),
(107, 16, 'heart_rate', 50.00, NULL, '50 BPM', 'bpm', 'Athletic / Low Resting', 'Manual Entry', NULL, '2026-11-12 22:28:04', '2026-11-12 16:58:04'),
(108, 16, 'spo2', 80.00, NULL, '80%', '%', 'Low Oxygen / Consult Doctor', 'Manual Entry', NULL, '2026-11-12 22:28:11', '2026-11-12 16:58:11'),
(109, 16, 'blood_pressure', 150.00, 90.00, '150/90 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-12 22:28:18', '2026-11-12 16:58:18'),
(110, 16, 'heart_rate', 180.00, NULL, '180 BPM', 'bpm', 'Tachycardia / High', 'Manual Entry', NULL, '2026-11-12 22:28:43', '2026-11-12 16:58:43'),
(111, 16, 'blood_pressure', 120.00, 90.00, '120/90 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-12 22:28:54', '2026-11-12 16:58:54'),
(112, 16, 'blood_pressure', 120.00, 40.00, '120/40 mmHg', 'mmHg', 'Low Blood Pressure (Hypotension)', 'Manual Entry', NULL, '2026-11-12 22:29:00', '2026-11-12 16:59:00'),
(113, 18, 'heart_rate', 50.00, NULL, '50 BPM', 'bpm', 'Athletic / Low Resting', 'Manual Entry', NULL, '2026-11-12 22:48:17', '2026-11-12 17:18:17'),
(114, 18, 'spo2', 96.00, NULL, '96%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-12 22:48:24', '2026-11-12 17:18:24'),
(115, 18, 'blood_pressure', 150.00, 80.00, '150/80 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-12 22:48:35', '2026-11-12 17:18:35'),
(116, 6, 'heart_rate', 50.00, NULL, '50 BPM', 'bpm', 'Athletic / Low Resting', 'Manual Entry', NULL, '2026-11-13 19:57:17', '2026-11-13 14:27:17'),
(117, 6, 'spo2', 98.00, NULL, '98%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-13 19:57:28', '2026-11-13 14:27:28'),
(118, 6, 'blood_pressure', 140.00, 40.00, '140/40 mmHg', 'mmHg', 'Low Blood Pressure (Hypotension)', 'Manual Entry', NULL, '2026-11-13 19:57:40', '2026-11-13 14:27:40'),
(119, 6, 'steps', 532.00, NULL, '532 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-13 21:09:19', '2026-11-13 15:39:19'),
(120, 6, 'heart_rate', 80.00, NULL, '80 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-13 21:11:41', '2026-11-13 15:41:41'),
(121, 6, 'heart_rate', 65.00, NULL, '65 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-13 21:49:49', '2026-11-13 16:19:49'),
(122, 6, 'heart_rate', 55.00, NULL, '55 BPM', 'bpm', 'Athletic / Low Resting', 'Manual Entry', NULL, '2026-11-13 21:52:08', '2026-11-13 16:22:08'),
(123, 6, 'heart_rate', 90.00, NULL, '90 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-13 21:52:20', '2026-11-13 16:22:20'),
(124, 6, 'heart_rate', 45.00, NULL, '45 BPM', 'bpm', 'Severe Bradycardia', 'Manual Entry', NULL, '2026-11-13 21:52:34', '2026-11-13 16:22:34'),
(125, 6, 'heart_rate', 90.00, NULL, '90 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-13 21:53:40', '2026-11-13 16:23:40'),
(126, 6, 'heart_rate', 50.00, NULL, '50 BPM', 'bpm', 'Athletic / Low Resting', 'Manual Entry', NULL, '2026-11-13 21:53:53', '2026-11-13 16:23:53'),
(127, 6, 'heart_rate', 87.00, NULL, '87 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-13 21:55:16', '2026-11-13 16:25:16'),
(128, 6, 'spo2', 100.00, NULL, '100%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-13 21:55:41', '2026-11-13 16:25:41'),
(129, 6, 'blood_pressure', 110.00, 40.00, '110/40 mmHg', 'mmHg', 'Low Blood Pressure (Hypotension)', 'Manual Entry', NULL, '2026-11-13 21:56:06', '2026-11-13 16:26:06'),
(130, 6, 'spo2', 80.00, NULL, '80%', '%', 'Low Oxygen / Consult Doctor', 'Manual Entry', NULL, '2026-11-13 21:56:54', '2026-11-13 16:26:54'),
(131, 6, 'spo2', 98.00, NULL, '98%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-13 21:57:12', '2026-11-13 16:27:12'),
(132, 6, 'spo2', 97.00, NULL, '97%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-13 22:07:58', '2026-11-13 16:37:58'),
(133, 6, 'spo2', 99.00, NULL, '99%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-13 22:08:11', '2026-11-13 16:38:11'),
(134, 6, 'spo2', 96.00, NULL, '96%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-13 22:09:16', '2026-11-13 16:39:16'),
(135, 6, 'heart_rate', 42.00, NULL, '42 BPM', 'bpm', 'Severe Bradycardia', 'Manual Entry', NULL, '2026-11-13 22:09:44', '2026-11-13 16:39:44'),
(136, 6, 'steps', 540.00, NULL, '540 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-13 22:10:59', '2026-11-13 16:40:59'),
(137, 6, 'heart_rate', 80.00, NULL, '80 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-15 11:24:04', '2026-11-15 05:54:04'),
(138, 6, 'spo2', 98.00, NULL, '98%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-15 11:24:16', '2026-11-15 05:54:16'),
(139, 6, 'blood_pressure', 150.00, 90.00, '150/90 mmHg', 'mmHg', 'Stage 2 Hypertension', 'Manual Entry', NULL, '2026-11-15 11:24:26', '2026-11-15 05:54:26'),
(140, 6, 'heart_rate', 80.00, NULL, '80 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-15 11:36:08', '2026-11-15 06:06:08'),
(141, 6, 'heart_rate', 56.00, NULL, '56 BPM', 'bpm', 'Athletic / Low Resting', 'Manual Entry', NULL, '2026-11-15 11:36:12', '2026-11-15 06:06:12'),
(142, 6, 'spo2', 80.00, NULL, '80%', '%', 'Low Oxygen / Consult Doctor', 'Manual Entry', NULL, '2026-11-15 12:02:03', '2026-11-15 06:32:03'),
(143, 6, 'heart_rate', 98.00, NULL, '98 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-15 12:04:06', '2026-11-15 06:34:06'),
(144, 6, 'spo2', 95.00, NULL, '95%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-15 12:04:36', '2026-11-15 06:34:36'),
(145, 6, 'heart_rate', 80.00, NULL, '80 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-15 14:08:58', '2026-11-15 08:38:58'),
(146, 6, 'heart_rate', 45.00, NULL, '45 BPM', 'bpm', 'Severe Bradycardia', 'Manual Entry', NULL, '2026-11-15 14:09:09', '2026-11-15 08:39:09'),
(147, 6, 'steps', 576.00, NULL, '576 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-15 14:11:08', '2026-11-15 08:41:08'),
(148, 6, 'spo2', 99.00, NULL, '99%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-15 14:12:23', '2026-11-15 08:42:23'),
(149, 6, 'spo2', 100.00, NULL, '100%', '%', 'Optimal Oxygenation', 'Manual Entry', NULL, '2026-11-15 14:12:37', '2026-11-15 08:42:37'),
(150, 6, 'steps', 1092.00, NULL, '1,092 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-15 17:50:38', '2026-11-15 12:20:38'),
(151, 6, 'heart_rate', 90.00, NULL, '90 BPM', 'bpm', 'Optimal Resting', 'Manual Entry', NULL, '2026-11-15 17:50:51', '2026-11-15 12:20:51'),
(152, 6, 'steps', 1162.00, NULL, '1,162 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-15 21:07:34', '2026-11-15 15:37:34'),
(153, 6, 'bmi', 29.40, NULL, '29.4', 'bmi', 'Overweight', 'Manual Entry', NULL, '2026-11-15 16:42:55', '2026-11-15 15:42:55'),
(154, 6, 'heart_rate', 47.00, NULL, '47 BPM', 'bpm', 'Severe Bradycardia', 'Manual Entry', NULL, '2026-11-15 22:09:23', '2026-11-15 16:39:23'),
(155, 6, 'steps', 1196.00, NULL, '1,196 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-15 22:10:22', '2026-11-15 16:40:22'),
(156, 6, 'steps', 1273.00, NULL, '1,273 steps', 'steps', 'Active', 'Phone Sensor', NULL, '2026-11-15 22:25:20', '2026-11-15 16:55:20');

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
(2, 6, '2026-11-02', '2026-11-08', 53, 'mid', 0, 0, 55, 50, 'Weekly clinical performance: Hydration averaged 563 ml daily (Low). Physical activity averaged 9 steps/day (Low). Wellness grade: MID (53/100).', '2026-11-03 08:10:43'),
(7, 1, '2026-11-09', '2026-11-15', 0, 'low', 0, 0, 0, 0, 'No weekly vital logs recorded yet. Start tracking or connect a smartwatch to view clinical observations.', '2026-11-10 11:51:57'),
(10, 6, '2026-11-09', '2026-11-15', 71, 'mid', 95, 75, 80, 50, 'Executive Summary: Overall 7-day weekly health scored 71/100 (Good Progress). Resting heart rate averaged 71 BPM (Normal Resting) and blood pressure averaged 111/75 mmHg (Optimal BP). Respiratory oxygenation averaged 89% SpO2 (Low Oxygenation). Weekly lifestyle metrics recorded 8,901 total steps (1,780 steps/day, 11% of goal), 30,500 ml weekly hydration (7,625 ml/day, 48% of goal), and 12h 27m avg/night (Total: 62.3 hrs) (Good / Fair Weekly Avg).', '2026-11-10 12:21:30'),
(17, 1, '2026-11-04', '2026-11-10', 94, 'normal', 0, 88, 100, 0, 'Weekly clinical performance: Sleep averaged 8h 15m nightly (Optimal). Hydration averaged 2250 ml daily (Normal). Wellness grade: NORMAL (94/100).', '2026-11-10 13:57:20'),
(21, 6, '2026-11-04', '2026-11-10', 83, 'normal', 0, 100, 100, 50, 'Weekly clinical performance: Sleep averaged 23h 15m nightly (Excellent). Hydration averaged 10000 ml daily (Normal). Physical activity averaged 554 steps/day (Low). Wellness grade: NORMAL (83/100).', '2026-11-10 14:02:53'),
(104, 2, '2026-11-04', '2026-11-10', 0, 'low', 0, 0, 0, 0, 'No weekly vital logs recorded yet. Start tracking or connect a smartwatch to view clinical observations.', '2026-11-10 15:20:38'),
(344, 6, '2026-11-05', '2026-11-11', 83, 'normal', 95, 88, 100, 50, 'Weekly clinical performance: Resting heart rate averaged 68 BPM (Normal). Sleep averaged 15h 8m nightly (Excellent). Hydration averaged 10000 ml daily (Normal). Physical activity averaged 565 steps/day (Low). Wellness grade: NORMAL (83/100).', '2026-11-11 19:08:00'),
(346, 1, '2026-11-05', '2026-11-11', 0, 'low', 0, 0, 0, 0, 'No weekly vital logs recorded yet. Start tracking or connect a smartwatch to view clinical observations.', '2026-11-11 19:08:00'),
(697, 6, '2026-11-06', '2026-11-12', 84, 'normal', 95, 92, 100, 50, 'Weekly clinical performance: Resting heart rate averaged 72 BPM (Normal). Sleep averaged 16h 5m nightly (Excellent). Hydration averaged 5625 ml daily (Normal). Physical activity averaged 565 steps/day (Low). Wellness grade: NORMAL (84/100).', '2026-11-12 21:06:34'),
(698, 1, '2026-11-06', '2026-11-12', 0, 'low', 0, 0, 0, 0, 'No weekly vital logs recorded yet. Start tracking or connect a smartwatch to view clinical observations.', '2026-11-12 21:06:35'),
(789, 11, '2026-11-06', '2026-11-12', 78, 'mid', 95, 85, 55, 0, 'Weekly clinical performance: Resting heart rate averaged 70 BPM (Normal). Sleep averaged 8h 0m nightly (Optimal). Hydration averaged 1250 ml daily (Low). Wellness grade: MID (78/100).', '2026-11-12 21:31:14'),
(874, 16, '2026-11-06', '2026-11-12', 66, 'mid', 75, 85, 55, 50, 'Weekly clinical performance: Resting heart rate averaged 115 BPM (Mid). Sleep averaged 8h 0m nightly (Optimal). Hydration averaged 1000 ml daily (Low). Physical activity averaged 1 steps/day (Low). Wellness grade: MID (66/100).', '2026-11-12 22:27:41'),
(977, 18, '2026-11-06', '2026-11-12', 66, 'mid', 75, 85, 55, 50, 'Weekly clinical performance: Resting heart rate averaged 50 BPM (Mid). Sleep averaged 8h 0m nightly (Optimal). Hydration averaged 500 ml daily (Low). Physical activity averaged 2 steps/day (Low). Wellness grade: MID (66/100).', '2026-11-12 22:47:19'),
(1047, 6, '2026-11-07', '2026-11-13', 77, 'mid', 95, 94, 100, 50, 'Executive Summary: Overall weekly health scored 77/100 (Mid). Cardiovascular vitals show resting heart rate at 70 BPM (Normal) and blood pressure at 110/74 mmHg (Normal). Respiratory oxygenation sits at 87.8% SpO2 (Low). Lifestyle metrics recorded 2,778 steps (3% of goal), 22,000 ml hydration (35% of goal), and 14h 34m sleep (100% of target).', '2026-11-13 19:56:34'),
(1104, 1, '2026-11-07', '2026-11-13', 0, 'low', 0, 0, 0, 0, 'Executive Summary: Overall weekly health scored 0/100 (Low). Cardiovascular vitals show resting heart rate at -- BPM (No Data) and blood pressure at --/-- mmHg (No Data). Respiratory oxygenation sits at --% SpO2 (No Data). Lifestyle metrics recorded 0 steps (0% of goal), 0 ml hydration (0% of goal), and 0h 0m sleep (0% of target).', '2026-11-13 20:46:44'),
(1112, 11, '2026-11-07', '2026-11-13', 76, 'mid', 95, 85, 55, 0, 'Executive Summary: Overall weekly health scored 76/100 (Mid). Cardiovascular vitals show resting heart rate at 70 BPM (Normal) and blood pressure at 150/85 mmHg (Low). Respiratory oxygenation sits at 96% SpO2 (Normal). Lifestyle metrics recorded 0 steps (0% of goal), 1,250 ml hydration (9% of goal), and 8h 0m sleep (100% of target).', '2026-11-13 21:47:39'),
(1113, 16, '2026-11-07', '2026-11-13', 61, 'mid', 75, 85, 55, 50, 'Executive Summary: Overall weekly health scored 61/100 (Mid). Cardiovascular vitals show resting heart rate at 115 BPM (Mid) and blood pressure at 130/73 mmHg (Mid). Respiratory oxygenation sits at 80% SpO2 (Low). Lifestyle metrics recorded 4 steps (0% of goal), 1,000 ml hydration (7% of goal), and 8h 0m sleep (100% of target).', '2026-11-13 21:47:39'),
(1114, 18, '2026-11-07', '2026-11-13', 68, 'mid', 75, 85, 55, 50, 'Executive Summary: Overall weekly health scored 68/100 (Mid). Cardiovascular vitals show resting heart rate at 50 BPM (Mid) and blood pressure at 150/80 mmHg (Low). Respiratory oxygenation sits at 96% SpO2 (Normal). Lifestyle metrics recorded 13 steps (0% of goal), 500 ml hydration (4% of goal), and 8h 0m sleep (100% of target).', '2026-11-13 21:47:39');

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
(7, 6, '7c3b1cff-ab99-40e2-b0bb-41b9548bfc4c', 'Cycling', '2026-11-02 10:14:28', '2026-11-02 10:14:32', 4, 'time', 30, 0.00, 0, 0.02, '20.5 km/h', 0, 'gps', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-02 09:14:28'),
(8, 6, '3b79eecf-76c9-41bb-ad01-7c936cc2a1a8', 'Walking', '2026-11-06 10:39:23', '2026-11-06 10:39:57', 34, 'time', 30, 0.00, 0, 0.00, '11:45 /km', 2, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-06 09:39:23'),
(9, 6, '93c0f83e-5fcc-4ea5-af0f-44ed5d171faf', 'Walking', '2026-11-06 10:40:43', '2026-11-06 10:40:53', 10, 'time', 30, 0.00, 0, 0.00, '11:45 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-06 09:40:43'),
(10, 6, '8af4b0c0-d979-4795-bd79-04796ee7de88', 'Walking', '2026-11-06 12:03:35', '2026-11-06 12:04:14', 39, 'time', 30, 0.00, 47, 0.04, '18:11 /km', 3, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-06 11:03:35'),
(11, 6, '9d2594be-fd05-4c3d-8966-94bf6e6c7762', 'Walking', '2026-11-10 07:52:09', '2026-11-10 07:52:18', 9, 'time', 30, 0.00, 15, 0.01, '13:09 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-10 06:52:09'),
(12, 6, '9daebdfb-6681-4ebb-a1ba-fbb28ccdfde3', 'Walking', '2026-11-10 10:38:38', '2026-11-10 10:39:07', 29, 'time', 30, 0.00, 56, 0.04, '11:21 /km', 2, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-10 09:38:38'),
(13, 6, 'a2f6b60e-4205-49c1-b0fe-fafb59610f95', 'Walking', '2026-11-12 16:39:51', '2026-11-12 16:39:55', 4, 'time', 30, 0.00, 5, 0.00, '11:45 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-12 15:39:51'),
(14, 16, '2d60485c-2190-4e92-b659-9d427b217f75', 'Walking', '2026-11-12 17:59:32', '2026-11-12 17:59:37', 5, 'time', 30, 0.00, 4, 0.00, '11:45 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-12 16:59:32'),
(15, 18, '0be6b328-3739-48fa-b33d-3a238ab65170', 'Walking', '2026-11-12 18:19:31', '2026-11-12 18:19:32', 1, 'time', 30, 0.00, 0, 0.00, '11:45 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-12 17:19:31'),
(16, 18, 'bc547d52-1fd2-4737-b4a1-91c01a2dfcf6', 'Walking', '2026-11-12 18:19:43', '2026-11-12 18:19:53', 10, 'time', 30, 0.00, 13, 0.01, '16:52 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-12 17:19:43'),
(17, 6, '2f2a42d3-a9fa-4bf0-b1ae-6f63bf821e0a', 'Walking', '2026-11-13 17:40:27', '2026-11-13 17:40:31', 4, 'time', 30, 0.00, 2, 0.00, '11:45 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-13 16:40:27'),
(18, 6, '8f3eb97e-2229-4369-80d7-e77d4eeb4269', 'Walking', '2026-11-13 17:41:03', '2026-11-13 17:41:10', 7, 'time', 30, 0.00, 16, 0.01, '09:35 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-13 16:41:03'),
(19, 6, '32c3eb29-1437-452d-aee5-77f3ed4d5fad', 'Walking', '2026-11-15 06:54:56', '2026-11-15 06:55:03', 7, 'time', 30, 0.00, 13, 0.01, '11:48 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-15 05:54:56'),
(20, 6, '05720b4a-aa90-43e4-ba6a-3c88da46620b', 'Walking', '2026-11-15 09:41:09', '2026-11-15 09:41:12', 3, 'time', 30, 0.00, 18, 0.01, '03:39 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-15 08:41:09'),
(21, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '03:22 PM', 1, 3780.00, 82, '2026-11-15 14:22:53'),
(22, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '04:02 PM', 1, 1780.00, 42, '2026-11-15 15:02:55'),
(23, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '04:05 PM', 1, 4455.00, 95, '2026-11-15 15:05:20'),
(24, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '04:07 PM', 1, 4410.00, 94, '2026-11-15 15:07:00'),
(25, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '04:07 PM', 1, 4410.00, 94, '2026-11-15 15:07:40'),
(26, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '04:10 PM', 1, 450.00, 15, '2026-11-15 15:10:28'),
(27, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '04:13 PM', 1, 2030.00, 47, '2026-11-15 15:13:14'),
(28, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '04:38 PM', 1, 2140.00, 7, '2026-11-15 15:38:41'),
(29, 6, 'a7a5abad-58f6-4958-b8fa-08e89a0fe212', 'Walking', '2026-11-15 16:43:29', '2026-11-15 16:43:38', 9, 'time', 30, 0.00, 8, 0.01, '24:40 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-15 15:43:29'),
(30, 6, '192be3ef-fc1f-4919-a33b-c29a223c21fc', 'Walking', '2026-11-15 16:43:40', '2026-11-15 16:43:46', 6, 'time', 30, 0.00, 13, 0.01, '10:07 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-15 15:43:40'),
(31, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '05:02 PM', 1, 3780.00, 8, '2026-11-15 16:02:51'),
(32, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '05:04 PM', 1, 3375.00, 8, '2026-11-15 16:04:56'),
(33, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '05:05 PM', 1, 2100.00, 8, '2026-11-15 16:05:48'),
(34, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '05:38 PM', 1, 2100.00, 8, '2026-11-15 16:38:33'),
(35, 6, 'b224a1af-0622-4853-bb97-5976508d4a70', 'Walking', '2026-11-15 17:39:53', '2026-11-15 17:40:06', 13, 'time', 30, 0.00, 11, 0.01, '25:55 /km', 1, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-15 16:39:53'),
(36, 6, 'e6488093-26fa-4964-965e-d6cedbaa211c', 'Walking', '2026-11-15 17:40:26', '2026-11-15 17:40:32', 6, 'time', 30, 0.00, 14, 0.01, '09:23 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-15 16:40:26'),
(37, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '05:41 PM', 1, 3000.00, 8, '2026-11-15 16:41:16'),
(38, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '05:41 PM', 1, 3300.00, 8, '2026-11-15 16:41:42'),
(39, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '05:41 PM', 1, 3900.00, 8, '2026-11-15 16:41:57'),
(40, 6, '8775f0d1-1b07-4f7e-85cf-fc36d9af61f8', 'Walking', '2026-11-15 17:55:43', '2026-11-15 17:55:48', 5, 'time', 30, 0.00, 10, 0.01, '10:57 /km', 0, 'phone_sensor', NULL, '', '', '0000-00-00', '', 45, 0.00, 0, '2026-11-15 16:55:43'),
(41, 6, NULL, NULL, NULL, NULL, 0, 'time', 45, 0.00, 0, 0.00, '00:00 /km', 0, 'phone_sensor', NULL, 'Push Routine', 'Push', '2026-11-15', '05:57 PM', 1, 4200.00, 8, '2026-11-15 16:57:04');

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
  ADD KEY `idx_ai_user` (`user_id`,`created_at`),
  ADD KEY `idx_ai_chat_session` (`user_id`,`session_id`,`created_at`);

--
-- Indexes for table `ai_chat_sessions`
--
ALTER TABLE `ai_chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ai_sessions_user` (`user_id`,`updated_at`);

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
-- Indexes for table `gym_nutrition_logs`
--
ALTER TABLE `gym_nutrition_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `gym_workouts`
--
ALTER TABLE `gym_workouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `gym_workout_sets`
--
ALTER TABLE `gym_workout_sets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workout_id` (`workout_id`),
  ADD KEY `idx_user_id` (`user_id`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `activity_sessions`
--
ALTER TABLE `activity_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=331;

--
-- AUTO_INCREMENT for table `ai_chat_sessions`
--
ALTER TABLE `ai_chat_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `ai_daily_insights`
--
ALTER TABLE `ai_daily_insights`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
-- AUTO_INCREMENT for table `gym_nutrition_logs`
--
ALTER TABLE `gym_nutrition_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `gym_workouts`
--
ALTER TABLE `gym_workouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `gym_workout_sets`
--
ALTER TABLE `gym_workout_sets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=239;

--
-- AUTO_INCREMENT for table `health_permissions`
--
ALTER TABLE `health_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `hydration_goals`
--
ALTER TABLE `hydration_goals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `hydration_logs`
--
ALTER TABLE `hydration_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `login_sessions`
--
ALTER TABLE `login_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sleep_records`
--
ALTER TABLE `sleep_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `steps_logs`
--
ALTER TABLE `steps_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `vitals_logs`
--
ALTER TABLE `vitals_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `vitals_readings`
--
ALTER TABLE `vitals_readings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- AUTO_INCREMENT for table `weekly_health_reports`
--
ALTER TABLE `weekly_health_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1727;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

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
-- Constraints for table `ai_chat_sessions`
--
ALTER TABLE `ai_chat_sessions`
  ADD CONSTRAINT `fk_ai_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
