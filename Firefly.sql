# ************************************************************
# Sequel Pro SQL dump
# Version 4096
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: localhost (MySQL 5.6.13)
# Database: Firefly
# Generation Time: 2014-02-01 14:47:25 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table accounts
# ------------------------------------------------------------

CREATE TABLE `accounts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `name` varchar(300) NOT NULL DEFAULT '',
  `openingbalance` decimal(10,2) NOT NULL,
  `openingbalancedate` date NOT NULL,
  `currentbalance` decimal(10,2) NOT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fireflyuser_id` (`user_id`),
  CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table balancemodifiers
# ------------------------------------------------------------

CREATE TABLE `balancemodifiers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_id_2` (`account_id`,`date`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `balancemodifiers_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table cache
# ------------------------------------------------------------

CREATE TABLE `cache` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `expiration` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table component_transaction
# ------------------------------------------------------------

CREATE TABLE `component_transaction` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) unsigned NOT NULL,
  `component_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`,`component_id`),
  KEY `component_id` (`component_id`),
  CONSTRAINT `component_transaction_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `component_transaction_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table components
# ------------------------------------------------------------

CREATE TABLE `components` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `parent_component_id` int(10) unsigned DEFAULT NULL,
  `type` enum('category','beneficiary','budget') NOT NULL DEFAULT 'category',
  `name` varchar(500) NOT NULL DEFAULT '',
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `components_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table limits
# ------------------------------------------------------------

CREATE TABLE `limits` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `component_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `component_id` (`component_id`),
  CONSTRAINT `componentlimits_ibfk_1` FOREIGN KEY (`component_id`) REFERENCES `components` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table piggybanks
# ------------------------------------------------------------

CREATE TABLE `piggybanks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(500) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `target` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `piggybanks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table settings
# ------------------------------------------------------------

CREATE TABLE `settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `type` enum('date','float','string','int') NOT NULL DEFAULT 'date',
  `name` varchar(255) NOT NULL,
  `date` date DEFAULT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_2` (`user_id`,`name`,`date`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table transactions
# ------------------------------------------------------------

CREATE TABLE `transactions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `account_id` int(11) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `description` varchar(500) NOT NULL DEFAULT '',
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `ignore` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `mark` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fireflyuser_id` (`user_id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table transfers
# ------------------------------------------------------------

CREATE TABLE `transfers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `accountfrom_id` int(11) unsigned NOT NULL,
  `accountto_id` int(11) unsigned NOT NULL,
  `description` varchar(500) NOT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `account_from` (`accountfrom_id`),
  KEY `account_to` (`accountto_id`),
  KEY `fireflyuser_id` (`user_id`),
  CONSTRAINT `transfers_ibfk_1` FOREIGN KEY (`accountfrom_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `transfers_ibfk_2` FOREIGN KEY (`accountto_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `transfers_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table users
# ------------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `email` varchar(150) NOT NULL DEFAULT '',
  `password` varchar(60) NOT NULL,
  `activation` varchar(64) DEFAULT NULL,
  `reset` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
