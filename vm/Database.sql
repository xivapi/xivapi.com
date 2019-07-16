CREATE DATABASE dalamud;
CREATE USER dalamud@localhost IDENTIFIED BY 'dalamud';
GRANT ALL PRIVILEGES ON *.* TO dalamud@'%' IDENTIFIED BY 'dalamud';
GRANT ALL PRIVILEGES ON *.* TO dalamud@localhost IDENTIFIED BY 'dalamud';
FLUSH PRIVILEGES;

-- Adminer 4.6.3 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

CREATE DATABASE `dalamud` /*!40100 DEFAULT CHARACTER SET latin1 */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `dalamud`;

DROP VIEW IF EXISTS `companion patron usage`;
CREATE TABLE `companion patron usage` (`TOTAL DPS ALERTS` bigint(21), `TOTAL DPS ITEMS` bigint(22), `TOTAL MINUTES` decimal(23,0), `TOTAL MINUTES BY QUEUE` decimal(23,0), `TIME REMAINING` decimal(24,0), `STILL OK?` varchar(3));


DROP VIEW IF EXISTS `companion queues`;
CREATE TABLE `companion queues` (`normal_queue` int(11), `total_items` bigint(21), `total_minutes` decimal(22,0), `total_hours` decimal(22,0));


DROP VIEW IF EXISTS `companion stats`;
CREATE TABLE `companion stats` (`TotalUpdated` bigint(21), `characters_online` bigint(21), `time` datetime, `time_last_update` datetime, `total_entries` varchar(61), `total_errors` varchar(61));


SET NAMES utf8mb4;

DROP TABLE IF EXISTS `companion_characters`;
CREATE TABLE `companion_characters` (
  `id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lodestone_id` int(11) DEFAULT NULL,
  `updated` int(11) NOT NULL,
  `added` int(11) NOT NULL,
  `server` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `lodestone_id` (`lodestone_id`),
  KEY `added` (`added`),
  KEY `updated` (`updated`),
  KEY `server` (`server`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `companion_errors`;
CREATE TABLE `companion_errors` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `added` int(11) NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `added` (`added`),
  KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `companion_items`;
CREATE TABLE `companion_items` (
  `item_id` int(11) NOT NULL,
  `last_visit` int(11) DEFAULT NULL,
  `normal_queue` int(11) DEFAULT NULL,
  UNIQUE KEY `item_id` (`item_id`),
  KEY `last_visit` (`last_visit`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `companion_market_item_queue`;
CREATE TABLE `companion_market_item_queue` (
  `id` int(11) NOT NULL,
  `item` int(11) NOT NULL,
  `server` int(11) NOT NULL,
  `queue` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`item`),
  KEY `server` (`server`),
  KEY `queue` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `companion_market_item_source`;
CREATE TABLE `companion_market_item_source` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item` int(11) NOT NULL,
  `data` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_A6130D121F1B251E` (`item`),
  KEY `item` (`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `companion_market_items`;
CREATE TABLE `companion_market_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `updated` int(11) NOT NULL,
  `item` int(11) NOT NULL,
  `server` int(11) NOT NULL,
  `normal_queue` int(11) NOT NULL,
  `patreon_queue` int(11) DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  `manual_queue` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_item` (`item`,`server`),
  KEY `updated` (`updated`),
  KEY `item` (`item`),
  KEY `normal_queue` (`normal_queue`),
  KEY `patreon_queue` (`patreon_queue`),
  KEY `server` (`server`),
  KEY `state` (`state`),
  KEY `manual_queue` (`normal_queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `companion_retainers`;
CREATE TABLE `companion_retainers` (
  `id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated` int(11) NOT NULL,
  `added` int(11) NOT NULL,
  `server` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `added` (`added`),
  KEY `updated` (`updated`),
  KEY `server` (`server`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `companion_tokens`;
CREATE TABLE `companion_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `character_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `server` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiring` int(11) NOT NULL,
  `online` tinyint(1) NOT NULL,
  `message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_7F4E10C41136BE75` (`character_id`),
  KEY `account` (`account`),
  KEY `character_id` (`character_id`),
  KEY `server` (`server`),
  KEY `expiring` (`expiring`),
  KEY `online` (`online`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `companion_updates`;
CREATE TABLE `companion_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `added` int(11) NOT NULL,
  `pass` tinyint(4) NOT NULL,
  `message` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `added` (`added`),
  KEY `pass` (`pass`),
  KEY `queue` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `item_icons`;
CREATE TABLE `item_icons` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `item` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `lodestone_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lodestone_icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item` (`item`),
  KEY `status` (`status`),
  KEY `lodestone_id` (`lodestone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `item_views`;
CREATE TABLE `item_views` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `added` int(11) NOT NULL,
  `lastview` int(11) NOT NULL,
  `item` int(11) NOT NULL,
  `previous_queue` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_3B5787571F1B251E` (`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `items_popularity`;
CREATE TABLE `items_popularity` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `added` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `item` int(11) NOT NULL,
  `count` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_25DF8FBE1F1B251E` (`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `lodestone_character`;
CREATE TABLE `lodestone_character` (
  `id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `not_found_checks` int(11) NOT NULL DEFAULT '0',
  `achievements_private_checks` int(11) NOT NULL DEFAULT '0',
  `last_request` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  KEY `updated` (`updated`),
  KEY `priority` (`priority`),
  KEY `notFoundChecks` (`not_found_checks`),
  KEY `achievementsPrivateChecks` (`achievements_private_checks`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `lodestone_character_achievements`;
CREATE TABLE `lodestone_character_achievements` (
  `id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `not_found_checks` int(11) NOT NULL DEFAULT '0',
  `achievements_private_checks` int(11) NOT NULL DEFAULT '0',
  `last_request` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  KEY `updated` (`updated`),
  KEY `priority` (`priority`),
  KEY `notFoundChecks` (`not_found_checks`),
  KEY `achievementsPrivateChecks` (`achievements_private_checks`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `lodestone_character_friends`;
CREATE TABLE `lodestone_character_friends` (
  `id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `not_found_checks` int(11) NOT NULL DEFAULT '0',
  `achievements_private_checks` int(11) NOT NULL DEFAULT '0',
  `last_request` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  KEY `updated` (`updated`),
  KEY `priority` (`priority`),
  KEY `notFoundChecks` (`not_found_checks`),
  KEY `achievementsPrivateChecks` (`achievements_private_checks`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `lodestone_freecompany`;
CREATE TABLE `lodestone_freecompany` (
  `id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `not_found_checks` int(11) NOT NULL DEFAULT '0',
  `achievements_private_checks` int(11) NOT NULL DEFAULT '0',
  `last_request` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  KEY `updated` (`updated`),
  KEY `priority` (`priority`),
  KEY `notFoundChecks` (`not_found_checks`),
  KEY `achievementsPrivateChecks` (`achievements_private_checks`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `lodestone_linkshell`;
CREATE TABLE `lodestone_linkshell` (
  `id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `not_found_checks` int(11) NOT NULL DEFAULT '0',
  `achievements_private_checks` int(11) NOT NULL DEFAULT '0',
  `last_request` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  KEY `updated` (`updated`),
  KEY `priority` (`priority`),
  KEY `notFoundChecks` (`not_found_checks`),
  KEY `achievementsPrivateChecks` (`achievements_private_checks`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `lodestone_pvpteam`;
CREATE TABLE `lodestone_pvpteam` (
  `id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `priority` int(11) NOT NULL DEFAULT '0',
  `not_found_checks` int(11) NOT NULL DEFAULT '0',
  `achievements_private_checks` int(11) NOT NULL DEFAULT '0',
  `last_request` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  KEY `updated` (`updated`),
  KEY `priority` (`priority`),
  KEY `notFoundChecks` (`not_found_checks`),
  KEY `achievementsPrivateChecks` (`achievements_private_checks`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `lodestone_queue_status`;
CREATE TABLE `lodestone_queue_status` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `active` tinyint(1) NOT NULL,
  `message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `lodestone_statistic`;
CREATE TABLE `lodestone_statistic` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `added` int(11) NOT NULL,
  `queue` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` double NOT NULL,
  `request_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `added` (`added`),
  KEY `queue` (`queue`),
  KEY `duration` (`duration`),
  KEY `method` (`method`),
  KEY `request_id` (`request_id`),
  KEY `count` (`count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `maintenance`;
CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mogboard` int(11) NOT NULL,
  `xivapi` int(11) NOT NULL,
  `game` int(11) NOT NULL,
  `lodestone` int(11) NOT NULL,
  `companion` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `map_positions`;
CREATE TABLE `map_positions` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_index` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `enpc_resident_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bnpc_name_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bnpc_base_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `map_id` int(11) NOT NULL,
  `map_index` int(11) NOT NULL,
  `map_territory_id` int(11) NOT NULL,
  `place_name_id` int(11) NOT NULL,
  `coordinate_x` double NOT NULL,
  `coordinate_y` double NOT NULL,
  `coordinate_z` double NOT NULL,
  `pos_x` double NOT NULL,
  `pos_y` double NOT NULL,
  `pixel_x` int(11) NOT NULL,
  `pixel_y` int(11) NOT NULL,
  `added` int(11) NOT NULL,
  `managed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_73F7A221D1B862B8` (`hash`),
  KEY `map_id` (`map_id`),
  KEY `map_index` (`map_index`),
  KEY `map_territory_id` (`map_territory_id`),
  KEY `place_name_id` (`place_name_id`),
  KEY `content_index` (`content_index`),
  KEY `added` (`added`),
  KEY `managed` (`managed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `map_positions_completed`;
CREATE TABLE `map_positions_completed` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `updated` int(11) NOT NULL,
  `map_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `complete` tinyint(1) NOT NULL,
  `notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_3587E81253C55F64` (`map_id`),
  KEY `updated` (`updated`),
  KEY `map_id` (`map_id`),
  KEY `complete` (`complete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `memory_data`;
CREATE TABLE `memory_data` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_index` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `enpc_resident_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bnpc_name_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bnpc_base_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` int(11) NOT NULL,
  `race` int(11) NOT NULL,
  `hpmax` int(11) NOT NULL,
  `mpmax` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  `fate_id` int(11) NOT NULL,
  `event_object_type_id` int(11) NOT NULL,
  `gathering_invisible` int(11) NOT NULL,
  `gathering_status` int(11) NOT NULL,
  `hit_box_radius` int(11) NOT NULL,
  `is_gm` tinyint(1) NOT NULL,
  `added` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_2C0986DD1B862B8` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `added` int(11) NOT NULL,
  `sso` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_banned` tinyint(1) NOT NULL DEFAULT '0',
  `notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `api_public_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_analytics_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_rate_limit` int(11) NOT NULL DEFAULT '0',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `patron` int(11) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sso_discord_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sso_discord_avatar` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sso_discord_token_expires` int(11) DEFAULT NULL,
  `sso_discord_token_access` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sso_discord_token_refresh` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alerts_max` int(11) NOT NULL,
  `alerts_expiry` int(11) NOT NULL,
  `alerts_update` tinyint(1) NOT NULL,
  `last_online` int(11) NOT NULL,
  `patron_benefit_user` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `added` (`added`),
  KEY `is_banned` (`is_banned`),
  KEY `sso` (`sso`),
  KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `api_public_key` (`api_public_key`),
  KEY `sso_discord_id` (`sso_discord_id`),
  KEY `sso_discord_avatar` (`sso_discord_avatar`),
  KEY `sso_discord_token_expires` (`sso_discord_token_expires`),
  KEY `sso_discord_token_access` (`sso_discord_token_access`),
  KEY `sso_discord_token_refresh` (`sso_discord_token_refresh`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `users_alerts`;
CREATE TABLE `users_alerts` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `user_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(DC2Type:guid)',
  `uniq` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_id` int(11) NOT NULL,
  `added` int(11) NOT NULL,
  `last_checked` int(11) NOT NULL DEFAULT '0',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `server` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry` int(11) NOT NULL,
  `trigger_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `trigger_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger_last_sent` int(11) NOT NULL,
  `triggers_sent` int(11) NOT NULL,
  `trigger_action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger_data_center` tinyint(1) NOT NULL DEFAULT '0',
  `trigger_hq` tinyint(1) NOT NULL DEFAULT '0',
  `trigger_nq` tinyint(1) NOT NULL DEFAULT '0',
  `trigger_active` tinyint(1) NOT NULL DEFAULT '1',
  `notified_via_email` tinyint(1) NOT NULL DEFAULT '0',
  `notified_via_discord` tinyint(1) NOT NULL DEFAULT '0',
  `keep_updated` tinyint(1) NOT NULL DEFAULT '0',
  `active_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_F89D4EFBACD1F8DC` (`uniq`),
  KEY `IDX_F89D4EFBA76ED395` (`user_id`),
  KEY `uniq` (`uniq`),
  KEY `item_id` (`item_id`),
  KEY `last_checked` (`last_checked`),
  KEY `server` (`server`),
  CONSTRAINT `FK_F89D4EFBA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `users_alerts_events`;
CREATE TABLE `users_alerts_events` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `event_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(DC2Type:guid)',
  `user_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `added` int(11) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_93DB681F71F7E88B` (`event_id`),
  CONSTRAINT `FK_93DB681F71F7E88B` FOREIGN KEY (`event_id`) REFERENCES `users_alerts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `users_characters`;
CREATE TABLE `users_characters` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `user_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(DC2Type:guid)',
  `lodestone_id` int(11) NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `server` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `main` tinyint(1) NOT NULL DEFAULT '0',
  `confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `updated` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_ED42EE9AA76ED395` (`user_id`),
  CONSTRAINT `FK_ED42EE9AA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `users_lists`;
CREATE TABLE `users_lists` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `user_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(DC2Type:guid)',
  `added` int(11) NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `custom` tinyint(1) NOT NULL DEFAULT '0',
  `custom_type` int(11) DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `updated` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EF513BECA76ED395` (`user_id`),
  CONSTRAINT `FK_EF513BECA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `users_reports`;
CREATE TABLE `users_reports` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `user_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(DC2Type:guid)',
  `added` int(11) NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  KEY `IDX_11FD38FA76ED395` (`user_id`),
  CONSTRAINT `FK_11FD38FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `users_retainers`;
CREATE TABLE `users_retainers` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `user_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(DC2Type:guid)',
  `uniq` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `server` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `confirm_item` int(11) NOT NULL,
  `confirm_price` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  `added` int(11) NOT NULL,
  `api_retainer_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C867A431A76ED395` (`user_id`),
  CONSTRAINT `FK_C867A431A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `users_sessions`;
CREATE TABLE `users_sessions` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `user_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(DC2Type:guid)',
  `session` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_active` int(11) NOT NULL,
  `site` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_E121B6C9D044D5D4` (`session`),
  KEY `IDX_E121B6C9A76ED395` (`user_id`),
  KEY `session` (`session`),
  CONSTRAINT `FK_E121B6C9A76ED395` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP VIEW IF EXISTS `view_lodestone_methods`;
CREATE TABLE `view_lodestone_methods` (`total` bigint(21), `avg_duration` double(22,2), `max_duration` double, `avg_count` decimal(13,2), `max_count` int(11), `method` varchar(64));


DROP VIEW IF EXISTS `view_lodestone_queues`;
CREATE TABLE `view_lodestone_queues` (`total` bigint(21), `avg_duration` double(22,2), `max_duration` double, `avg_count` decimal(13,2), `max_count` int(11), `queue` varchar(64));


DROP TABLE IF EXISTS `companion patron usage`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `companion patron usage` AS select count(0) AS `TOTAL DPS ALERTS`,(count(0) * 8) AS `TOTAL DPS ITEMS`,ceiling(((count(0) * 8) / 15)) AS `TOTAL MINUTES`,ceiling((((count(0) * 8) / 15) / 5)) AS `TOTAL MINUTES BY QUEUE`,ceiling(((((count(0) * 8) / 15) / 5) - 15)) AS `TIME REMAINING`,if(ceiling(((((count(0) * 8) / 15) / 5) - 15)),'YES','NO') AS `STILL OK?` from `users_alerts` where (`users_alerts`.`keep_updated` = 1) group by `users_alerts`.`keep_updated`;

DROP TABLE IF EXISTS `companion queues`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `companion queues` AS select `companion_market_items`.`normal_queue` AS `normal_queue`,count(0) AS `total_items`,ceiling((count(0) / 15)) AS `total_minutes`,ceiling(((count(0) / 15) / 60)) AS `total_hours` from `companion_market_items` group by `companion_market_items`.`normal_queue` order by `companion_market_items`.`normal_queue`;

DROP TABLE IF EXISTS `companion stats`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `companion stats` AS select (select count(0) AS `TotalUpdated` from `companion_market_items` where (`companion_market_items`.`updated` > '1556542562')) AS `TotalUpdated`,(select count(0) AS `total` from `companion_tokens` where (`companion_tokens`.`online` = 1)) AS `characters_online`,now() AS `time`,from_unixtime(max(`companion_market_items`.`updated`)) AS `time_last_update`,format(count(`companion_market_items`.`id`),0) AS `total_entries`,format((select count(0) from `companion_errors`),0) AS `total_errors` from `companion_market_items`;

DROP TABLE IF EXISTS `view_lodestone_methods`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `view_lodestone_methods` AS select count(0) AS `total`,round(avg(`lodestone_statistic`.`duration`),2) AS `avg_duration`,max(`lodestone_statistic`.`duration`) AS `max_duration`,round(avg(`lodestone_statistic`.`count`),2) AS `avg_count`,max(`lodestone_statistic`.`count`) AS `max_count`,`lodestone_statistic`.`method` AS `method` from `lodestone_statistic` group by `lodestone_statistic`.`method` order by `lodestone_statistic`.`method`;

DROP TABLE IF EXISTS `view_lodestone_queues`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `view_lodestone_queues` AS select count(0) AS `total`,round(avg(`lodestone_statistic`.`duration`),2) AS `avg_duration`,max(`lodestone_statistic`.`duration`) AS `max_duration`,round(avg(`lodestone_statistic`.`count`),2) AS `avg_count`,max(`lodestone_statistic`.`count`) AS `max_count`,`lodestone_statistic`.`queue` AS `queue` from `lodestone_statistic` group by `lodestone_statistic`.`queue` order by `lodestone_statistic`.`queue`;

-- 2019-07-16 13:53:03

