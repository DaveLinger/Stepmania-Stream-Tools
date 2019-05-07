SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for sm_songs
-- ----------------------------
DROP TABLE IF EXISTS `sm_songs`;
CREATE TABLE `sm_songs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `pack` varchar(255) DEFAULT NULL,
  `added` datetime DEFAULT NULL,
  `strippedtitle` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS = 1;
