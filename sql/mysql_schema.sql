/*
 Navicat Premium Data Transfer

 Source Server Type    : MySQL
 Source Server Version : 50562

 Target Server Type    : MySQL
 Target Server Version : 50562
 File Encoding         : 65001

 Date: 16/07/2020 09:23:41
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for sm_requestors
-- ----------------------------
DROP TABLE IF EXISTS `sm_requestors`;
CREATE TABLE `sm_requestors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `twitchid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `whitelisted` enum('true','false') DEFAULT 'false',
  `banned` enum('true','false') DEFAULT 'false',
  `dateadded` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for sm_requests
-- ----------------------------
DROP TABLE IF EXISTS `sm_requests`;
CREATE TABLE `sm_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) DEFAULT NULL,
  `request_time` datetime DEFAULT NULL,
  `requestor` varchar(255) DEFAULT NULL,
  `state` enum('requested','canceled') DEFAULT 'requested',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2881 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for sm_songs
-- ----------------------------
DROP TABLE IF EXISTS `sm_songs`;
CREATE TABLE `sm_songs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `artist` varchar(255) DEFAULT NULL,
  `pack` varchar(255) DEFAULT NULL,
  `added` datetime DEFAULT NULL,
  `strippedtitle` varchar(255) DEFAULT NULL,
  `strippedartist` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7402 DEFAULT CHARSET=latin1;
INSERT INTO `sm_songs` VALUES (1, '', '', '', NOW(), '', '');

-- ----------------------------
-- Table structure for sm_songsplayed
-- ----------------------------
DROP TABLE IF EXISTS `sm_songsplayed`;
CREATE TABLE `sm_songsplayed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `songid` int(11) DEFAULT NULL,
  `requestid` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `artist` varchar(255) DEFAULT NULL,
  `pack` varchar(255) DEFAULT NULL,
  `played` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5152 DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS = 1;
