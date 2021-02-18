-- MySQL dump 10.13  Distrib 5.7.32, for Linux (x86_64)
--
-- Host: cv-mysql    Database: community_voices
-- ------------------------------------------------------
-- Server version	5.7.31

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `community-voices_articles`
--

DROP TABLE IF EXISTS `community-voices_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_articles` (
  `media_id` int(21) NOT NULL,
  `image_id` int(21) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `text` text,
  `author` varchar(255) DEFAULT NULL,
  `date_recorded` datetime DEFAULT NULL,
  PRIMARY KEY (`media_id`),
  UNIQUE KEY `image_id` (`image_id`),
  CONSTRAINT `community-voices_articles_fk0` FOREIGN KEY (`media_id`) REFERENCES `community-voices_media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `community-voices_articles_fk1` FOREIGN KEY (`image_id`) REFERENCES `community-voices_images` (`media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_content-categories`
--

DROP TABLE IF EXISTS `community-voices_content-categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_content-categories` (
  `group_id` int(21) NOT NULL,
  `image_id` int(21) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`group_id`),
  CONSTRAINT `community-voices_content-categories_fk0` FOREIGN KEY (`group_id`) REFERENCES `community-voices_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_groups`
--

DROP TABLE IF EXISTS `community-voices_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `type` enum('tag','org-category','content-category') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_identities`
--

DROP TABLE IF EXISTS `community-voices_identities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_identities` (
  `identity_id` int(21) NOT NULL AUTO_INCREMENT,
  `parent_id` int(21) DEFAULT NULL,
  `account_id` int(21) DEFAULT NULL,
  `type` int(1) NOT NULL,
  `identifier` text NOT NULL,
  `fingerprint` char(96) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
  `hash` text,
  `created_on` int(11) NOT NULL,
  `used_on` int(11) DEFAULT NULL,
  `expires_on` int(11) DEFAULT NULL,
  `token` char(32) DEFAULT NULL,
  `token_expires_on` int(11) DEFAULT NULL,
  `token_action` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`identity_id`),
  KEY `community-voices_identities_fk1` (`account_id`),
  KEY `parent_id` (`parent_id`,`account_id`,`type`,`fingerprint`,`status`,`expires_on`,`token`,`token_expires_on`,`token_action`),
  CONSTRAINT `community-voices_identities_fk0` FOREIGN KEY (`parent_id`) REFERENCES `community-voices_identities` (`identity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `community-voices_identities_fk1` FOREIGN KEY (`account_id`) REFERENCES `community-voices_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=698 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_images`
--

DROP TABLE IF EXISTS `community-voices_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_images` (
  `media_id` int(21) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `generated_tags` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `date_taken` datetime DEFAULT NULL,
  `photographer` varchar(255) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `exif` text,
  `perceptual_hash` bigint(20) unsigned DEFAULT NULL,
  `crop_x` int(11) NOT NULL DEFAULT '0',
  `crop_y` int(11) NOT NULL DEFAULT '0',
  `crop_height` int(11) NOT NULL DEFAULT '0',
  `crop_width` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`media_id`),
  CONSTRAINT `community-voices_images_fk0` FOREIGN KEY (`media_id`) REFERENCES `community-voices_media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_location-category-map`
--

DROP TABLE IF EXISTS `community-voices_location-category-map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_location-category-map` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `location_id` int(21) NOT NULL,
  `group_id` int(21) NOT NULL,
  `probability` int(21) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `community-voices_location-category-map_fk0` (`location_id`),
  KEY `community-voices_location-category-map_fk1` (`group_id`),
  CONSTRAINT `community-voices_location-category-map_fk0` FOREIGN KEY (`location_id`) REFERENCES `community-voices_locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `community-voices_location-category-map_fk1` FOREIGN KEY (`group_id`) REFERENCES `community-voices_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_locations`
--

DROP TABLE IF EXISTS `community-voices_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_locations` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `end_use` enum('city','college') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_media`
--

DROP TABLE IF EXISTS `community-voices_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_media` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `added_by` int(11) DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` enum('slide','image','quote','article') NOT NULL,
  `status` enum('pending','rejected','approved') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `community-voices_media_fk0` (`added_by`),
  CONSTRAINT `community-voices_media_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `community-voices_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5855 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_media-article-map`
--

DROP TABLE IF EXISTS `community-voices_media-article-map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_media-article-map` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `article_id` int(21) NOT NULL,
  `media_id` int(21) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_media-group-map`
--

DROP TABLE IF EXISTS `community-voices_media-group-map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_media-group-map` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `media_id` int(21) NOT NULL,
  `group_id` int(21) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `community-voices_media-group-map_fk0` (`media_id`),
  KEY `community-voices_media-group-map_fk1` (`group_id`),
  CONSTRAINT `community-voices_media-group-map_fk0` FOREIGN KEY (`media_id`) REFERENCES `community-voices_media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `community-voices_media-group-map_fk1` FOREIGN KEY (`group_id`) REFERENCES `community-voices_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13183 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_media-location-map`
--

DROP TABLE IF EXISTS `community-voices_media-location-map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_media-location-map` (
  `id` int(21) NOT NULL AUTO_INCREMENT,
  `media_id` int(21) NOT NULL,
  `loc_id` int(21) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `media_id` (`media_id`),
  KEY `loc_id` (`loc_id`)
) ENGINE=InnoDB AUTO_INCREMENT=41309 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_organization-categories`
--

DROP TABLE IF EXISTS `community-voices_organization-categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_organization-categories` (
  `group_id` int(21) NOT NULL,
  PRIMARY KEY (`group_id`),
  CONSTRAINT `community-voices_organization-categories_fk0` FOREIGN KEY (`group_id`) REFERENCES `community-voices_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_quotes`
--

DROP TABLE IF EXISTS `community-voices_quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_quotes` (
  `media_id` int(21) NOT NULL,
  `text` text,
  `original_text` text,
  `interviewer` varchar(255) DEFAULT NULL,
  `attribution` varchar(255) DEFAULT NULL,
  `sub_attribution` varchar(255) DEFAULT NULL,
  `quotation_marks` tinyint(1) NOT NULL DEFAULT '1',
  `date_recorded` datetime DEFAULT NULL,
  `public_document_link` varchar(255) DEFAULT NULL,
  `source_document_link` varchar(255) DEFAULT NULL,
  `extra_stuff` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`media_id`),
  CONSTRAINT `community-voices_quotes_fk0` FOREIGN KEY (`media_id`) REFERENCES `community-voices_media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_slides`
--

DROP TABLE IF EXISTS `community-voices_slides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_slides` (
  `media_id` int(21) NOT NULL,
  `content_category_id` int(21) DEFAULT NULL,
  `image_id` int(21) DEFAULT NULL,
  `logo_id` int(21) DEFAULT NULL,
  `quote_id` int(21) DEFAULT NULL,
  `formatted_text` text,
  `probability` int(21) NOT NULL,
  `decay_percent` int(21) DEFAULT NULL,
  `decay_start` datetime DEFAULT NULL,
  `decay_end` datetime DEFAULT NULL,
  PRIMARY KEY (`media_id`),
  UNIQUE KEY `image_id` (`image_id`,`quote_id`),
  UNIQUE KEY `image_id_2` (`image_id`,`quote_id`),
  KEY `community-voices_slides_fk1` (`content_category_id`),
  KEY `community-voices_slides_fk2` (`image_id`),
  KEY `community-voices_slides_fk3` (`quote_id`),
  KEY `logo_id` (`logo_id`),
  CONSTRAINT `community-voices_slides_fk0` FOREIGN KEY (`media_id`) REFERENCES `community-voices_media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `community-voices_slides_fk1` FOREIGN KEY (`content_category_id`) REFERENCES `community-voices_content-categories` (`group_id`) ON UPDATE CASCADE,
  CONSTRAINT `community-voices_slides_fk2` FOREIGN KEY (`image_id`) REFERENCES `community-voices_images` (`media_id`) ON UPDATE CASCADE,
  CONSTRAINT `community-voices_slides_fk3` FOREIGN KEY (`quote_id`) REFERENCES `community-voices_quotes` (`media_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_tags`
--

DROP TABLE IF EXISTS `community-voices_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_tags` (
  `group_id` int(21) NOT NULL,
  PRIMARY KEY (`group_id`),
  CONSTRAINT `community-voices_tags_fk0` FOREIGN KEY (`group_id`) REFERENCES `community-voices_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_tokens`
--

DROP TABLE IF EXISTS `community-voices_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `role` tinyint(4) NOT NULL DEFAULT '0',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `community-voices_users`
--

DROP TABLE IF EXISTS `community-voices_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community-voices_users` (
  `id` int(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `fname` varchar(100) DEFAULT NULL,
  `role` enum('unverified','user','manager','admin') NOT NULL DEFAULT 'unverified',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-02-15 11:28:45
