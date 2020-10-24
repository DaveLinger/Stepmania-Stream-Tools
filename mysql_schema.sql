/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Dumping database structure for SMsonglist
CREATE DATABASE IF NOT EXISTS `SMsonglist` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_bin */;
USE `SMsonglist`;

-- Dumping structure for table SMsonglist.sm_grade_tiers
CREATE TABLE IF NOT EXISTS `sm_grade_tiers` (
  `percentdp` double(7,2) DEFAULT NULL,
  `ddr_tier` text DEFAULT NULL,
  `ddr_grade` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `itg_tier` text DEFAULT NULL,
  `itg_grade` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Dumping data for table SMsonglist.sm_grade_tiers: ~28 rows (approximately)
/*!40000 ALTER TABLE `sm_grade_tiers` DISABLE KEYS */;
INSERT INTO `sm_grade_tiers` (`percentdp`, `ddr_tier`, `ddr_grade`, `itg_tier`, `itg_grade`) VALUES
	(1.00, 'Tier01', 'AAA+', 'Tier01', '★★★★'),
	(0.99, 'Tier02', 'AAA', 'Tier02', '★★★'),
	(0.98, NULL, 'AA+', 'Tier03', '★★'),
	(0.96, NULL, 'AA+', 'Tier04', '★'),
	(0.95, 'Tier03', 'AA+', NULL, 'S+'),
	(0.94, NULL, 'AA', 'Tier05', 'S+'),
	(0.92, NULL, 'AA', 'Tier06', 'S'),
	(0.90, 'Tier04', 'AA', NULL, 'S-'),
	(0.89, 'Tier05', 'AA-', 'Tier07', 'S-'),
	(0.86, NULL, 'A+', 'Tier08', 'A+'),
	(0.85, 'Tier06', 'A+', NULL, 'A'),
	(0.83, NULL, 'A', 'Tier09', 'A'),
	(0.80, 'Tier07', 'A', 'Tier10', 'A-'),
	(0.79, 'Tier08', 'A-', NULL, 'B+'),
	(0.76, NULL, 'B+', 'Tier11', 'B+'),
	(0.75, 'Tier09', 'B+', NULL, 'B'),
	(0.72, NULL, 'B', 'Tier12', 'B'),
	(0.70, 'Tier10', 'B', NULL, 'B-'),
	(0.69, 'Tier11', 'B-', 'Tier13', 'B-'),
	(0.68, NULL, 'C+', NULL, 'C+'),
	(0.65, 'Tier12', 'C+', NULL, 'C+'),
	(0.64, NULL, 'C', 'Tier14', 'C+'),
	(0.60, 'Tier13', 'C', 'Tier15', 'C-'),
	(0.59, 'Tier14', 'C-', NULL, 'C-'),
	(0.55, 'Tier15', 'D+', 'Tier16', 'C-'),
	(0.00, 'Tier17', 'D', NULL, 'D'),
	(-99999.00, NULL, 'FAILED', 'Tier17', 'D'),
	(0.50, 'Tier16', 'D', NULL, 'D');
/*!40000 ALTER TABLE `sm_grade_tiers` ENABLE KEYS */;

-- Dumping structure for table SMsonglist.sm_notedata
CREATE TABLE IF NOT EXISTS `sm_notedata` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `song_id` mediumint(9) DEFAULT NULL,
  `song_dir` mediumtext DEFAULT NULL,
  `chart_name` text DEFAULT NULL,
  `stepstype` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `chartstyle` text DEFAULT NULL,
  `difficulty` text DEFAULT NULL,
  `meter` int(11) DEFAULT NULL,
  `radar_values` text DEFAULT NULL,
  `credit` text DEFAULT NULL,
  `display_bpm` varchar(50) DEFAULT NULL,
  `stepfile_name` mediumtext DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `song_id` (`song_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- Data exporting was unselected.

-- Dumping structure for table SMsonglist.sm_requestors
CREATE TABLE IF NOT EXISTS `sm_requestors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `twitchid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `whitelisted` enum('true','false') DEFAULT 'false',
  `banned` enum('true','false') DEFAULT 'false',
  `dateadded` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

-- Dumping structure for table SMsonglist.sm_requests
CREATE TABLE IF NOT EXISTS `sm_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) DEFAULT NULL,
  `request_time` datetime DEFAULT NULL,
  `requestor` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `twitch_tier` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `broadcaster` tinytext DEFAULT NULL,
  `state` enum('requested','canceled','completed','skipped') CHARACTER SET utf8 COLLATE utf8_bin DEFAULT 'requested',
  `request_type` enum('normal','random') DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `song_id` (`song_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.

-- Dumping structure for table SMsonglist.sm_scores
CREATE TABLE IF NOT EXISTS `sm_scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_dir` text COLLATE utf8_bin DEFAULT NULL,
  `song_id` int(11) DEFAULT NULL,
  `title` text COLLATE utf8_bin DEFAULT NULL,
  `pack` text COLLATE utf8_bin DEFAULT NULL,
  `stepstype` mediumtext COLLATE utf8_bin DEFAULT NULL,
  `difficulty` text COLLATE utf8_bin DEFAULT NULL,
  `username` tinytext COLLATE utf8_bin DEFAULT NULL,
  `grade` tinytext COLLATE utf8_bin DEFAULT NULL,
  `score` bigint(20) DEFAULT NULL,
  `percentdp` decimal(10,6) DEFAULT NULL,
  `modifiers` text COLLATE utf8_bin DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `survive_seconds` decimal(10,6) DEFAULT NULL,
  `life_remaining_seconds` decimal(10,6) DEFAULT NULL,
  `disqualified` tinyint(4) DEFAULT NULL,
  `max_combo` smallint(6) DEFAULT NULL,
  `stage_award` text COLLATE utf8_bin DEFAULT NULL,
  `peak_combo_award` text COLLATE utf8_bin DEFAULT NULL,
  `player_guid` text COLLATE utf8_bin DEFAULT NULL,
  `machine_guid` text COLLATE utf8_bin DEFAULT NULL,
  `hit_mine` smallint(6) DEFAULT NULL,
  `avoid_mine` smallint(6) DEFAULT NULL,
  `checkpoint_miss` smallint(6) DEFAULT NULL,
  `miss` smallint(6) DEFAULT NULL,
  `w5` smallint(6) DEFAULT NULL,
  `w4` smallint(6) DEFAULT NULL,
  `w3` smallint(6) DEFAULT NULL,
  `w2` smallint(6) DEFAULT NULL,
  `w1` smallint(6) DEFAULT NULL,
  `checkpoint_hit` smallint(6) DEFAULT NULL,
  `let_go` smallint(6) DEFAULT NULL,
  `held` smallint(6) DEFAULT NULL,
  `missed_hold` smallint(6) DEFAULT NULL,
  `stream` decimal(10,6) DEFAULT NULL,
  `voltage` decimal(10,6) DEFAULT NULL,
  `air` decimal(10,6) DEFAULT NULL,
  `freeze` decimal(10,6) DEFAULT NULL,
  `chaos` decimal(10,6) DEFAULT NULL,
  `notes` smallint(6) DEFAULT NULL,
  `taps_holds` smallint(6) DEFAULT NULL,
  `jumps` smallint(6) DEFAULT NULL,
  `holds` smallint(6) DEFAULT NULL,
  `mines` smallint(6) DEFAULT NULL,
  `hands` smallint(6) DEFAULT NULL,
  `rolls` smallint(6) DEFAULT NULL,
  `lifts` smallint(6) DEFAULT NULL,
  `fakes` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin ROW_FORMAT=DYNAMIC;

-- Data exporting was unselected.

-- Dumping structure for table SMsonglist.sm_songs
CREATE TABLE IF NOT EXISTS `sm_songs` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `title` mediumtext DEFAULT NULL,
  `subtitle` mediumtext DEFAULT NULL,
  `artist` mediumtext DEFAULT NULL,
  `pack` mediumtext DEFAULT NULL,
  `strippedtitle` mediumtext DEFAULT NULL,
  `strippedsubtitle` mediumtext DEFAULT NULL,
  `strippedartist` mediumtext DEFAULT NULL,
  `song_dir` mediumtext DEFAULT NULL,
  `credit` text DEFAULT NULL,
  `display_bpm` varchar(50) DEFAULT NULL,
  `music_length` decimal(10,0) DEFAULT NULL,
  `bga` bit(1) DEFAULT NULL,
  `installed` bit(1) DEFAULT NULL,
  `banned` bit(1) DEFAULT b'0',
  `added` datetime DEFAULT NULL,
  `checksum` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- Data exporting was unselected.

-- Dumping structure for table SMsonglist.sm_songsplayed
CREATE TABLE IF NOT EXISTS `sm_songsplayed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) DEFAULT NULL,
  `song_dir` text DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `stepstype` text DEFAULT NULL,
  `difficulty` text DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `numplayed` int(11) DEFAULT NULL,
  `lastplayed` datetime DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
