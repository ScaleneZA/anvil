-- phpMyAdmin SQL Dump
-- version 3.3.8.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 28, 2011 at 11:16 AM
-- Server version: 5.1.56
-- 

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET 

@OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: 
DROP DATABASE anvil;
CREATE DATABASE anvil;
USE ANVIL;
--

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE IF NOT EXISTS `company` (
  

`id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `company_information` text NOT NULL,
  `status` varchar

(10) NOT NULL DEFAULT 'pending',
  `key` int(11) NOT NULL,
  `super_team_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `super_team_id` (`super_team_id`),
  KEY 

`email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=59 ;


CREATE TABLE IF NOT EXISTS `feature` (
  `id` int(11) NOT NULL 

AUTO_INCREMENT,
  `title` varchar(250) NOT NULL DEFAULT 'Story X',
  `description` text NOT NULL,
  `status` enum('Not Started','In Progress','Impeded','Done') NOT 

NULL DEFAULT 'Not Started',
  `priority` int(11) NOT NULL DEFAULT '5',
  `project_id` int(11) NOT NULL,
  `release_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  

KEY `project_id` (`project_id`),
  KEY `release_id` (`release_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=33 ;


--
-- Table structure for table 

`impediment`
--

CREATE TABLE IF NOT EXISTS `impediment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL DEFAULT 'Impediment X',
  

`description` text NOT NULL,
  `user_email` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_email` (`user_email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ;



CREATE TABLE IF NOT EXISTS `log` (
  `id` 

int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=77 ;

-- --------------------------------------------------------

--
-- Table structure for 

table `pending_email`
--

CREATE TABLE IF NOT EXISTS `pending_email` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to` varchar(150) NOT NULL,
  `from` varchar(150) NOT 

NULL,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT 

CHARSET=latin1 AUTO_INCREMENT=2 ;


CREATE TABLE IF NOT EXISTS `project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL DEFAULT 'Project X',
  `description` 

text NOT NULL,
  `status` enum('Not Started','In Progress','Impeded','Done') NOT NULL DEFAULT 'Not Started',
  `company_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=23 ;


UPDATE user set is_admin = 1;


CREATE TABLE IF NOT EXISTS `quip` (
  `id` int(11) NOT NULL 

AUTO_INCREMENT,
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `release` (
  `id` int(11) NOT NULL 

AUTO_INCREMENT,
  `title` varchar(250) NOT NULL,
  `start_date` date NOT NULL,
  `estimated_completion_date` date NOT NULL,
  `project_id` int(11) NOT NULL,
  

`task_time_stamp` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=17 ;


-- 

--------------------------------------------------------

--
-- Table structure for table `task`
--

CREATE TABLE IF NOT EXISTS `task` (
  `id` int(11) NOT NULL 

AUTO_INCREMENT,
  `title` varchar(250) NOT NULL DEFAULT 'Task X',
  `description` text NOT NULL,
  `status` enum('Not Started','In Progress','Impeded','Done') NOT 

NULL DEFAULT 'Not Started',
  `priority` int(11) NOT NULL,
  `feature_id` int(11) NOT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `added_by` varchar(100) 

DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feature_id` (`feature_id`),
  KEY `user_email` (`user_email`),
  KEY `added_by` (`added_by`)
) ENGINE=InnoDB  DEFAULT 

CHARSET=latin1 AUTO_INCREMENT=85 ;


CREATE TABLE IF NOT EXISTS `team` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `description` text,
  `company_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=25 ;

--------------------------------------------------------

--
-- Table structure for table `team_project`
--

CREATE TABLE IF NOT EXISTS `team_project` (
  `team_id` int

(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  PRIMARY KEY (`team_id`,`project_id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `theme`
--

CREATE TABLE IF NOT EXISTS `theme` (
  `id` int(11) NOT NULL 

AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- 

Dumping data for table `theme`
--

INSERT INTO `theme` (`id`, `name`, `description`) VALUES
(1, 'brown', 'The default brown theme. Developer''s choice.'),
(2, 'black', 'Black theme. For the darker side of Anvil.\r\nUi-Darkness (JQuery UI Themeroller)'),
(5, 'mint-choc', 'mint-choc from JQuery UI');

-- 

--------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `email` varchar(100) NOT NULL,
 

 `password` varchar(50) NOT NULL,
  `display_name` varchar(50) NOT NULL,
  `is_admin` binary(1) NOT NULL DEFAULT '0',
  `company_id` int(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `theme_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`email`),
  KEY `company_id` (`company_id`),
  KEY `team_id` (`team_id`),
  KEY 

`theme_id` (`theme_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `user_preference` (
  `id` 

int(11) NOT NULL AUTO_INCREMENT,
  `controller` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `key` varchar(50) NOT NULL,
  `value` varchar(50) NOT NULL,
  

`user_email` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;
