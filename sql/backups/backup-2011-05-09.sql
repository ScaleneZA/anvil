-- phpMyAdmin SQL Dump
-- version 3.2.0.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 06, 2011 at 10:35 PM
-- Server version: 5.1.37
-- PHP Version: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `anvil`
--

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE IF NOT EXISTS `company` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `name` varchar(50) NOT NULL,
  `company_information` text NOT NULL,
  `current_working_project_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`id`, `email`, `name`, `company_information`, `current_working_project_id`) VALUES
(1, 'lukesmb@gmail.com', 'Company X', 'Company X is a company of great potential. We seek to improve our development with the use of this online software', 4),
(2, 'lukeb@cti.co.za', 'CTI', 'balls', 5),
(3, 'james@gmail.com', 'James inc', 'aibfiasb', 7),
(4, 'kirk@abc.net', 'ABC Industries', 'ABC Industries is a software company that is looking to expand into the corporate environment. Need good tools to get off the mark.', 8);

-- --------------------------------------------------------

--
-- Table structure for table `feature`
--

CREATE TABLE IF NOT EXISTS `feature` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL DEFAULT 'Story X',
  `description` varchar(250) NOT NULL DEFAULT 'Feature description',
  `status` enum('Not Started','In Progress','Impeded','Done') NOT NULL DEFAULT 'Not Started',
  `priority` int(11) NOT NULL DEFAULT '5',
  `project_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

--
-- Dumping data for table `feature`
--

INSERT INTO `feature` (`id`, `title`, `description`, `status`, `priority`, `project_id`) VALUES
(1, 'aaaaaaaaaaaaaa', 'a', '', 5, 3),
(2, 'z', 'z', '', 5, 3),
(3, 'zz', 'zz', 'Impeded', 3, 4),
(4, 'aa', 'aa', 'In Progress', 5, 4),
(5, 'ss', 'ss', 'Done', 1, 4),
(6, 'gg', 'gg', 'Done', 1, 4),
(7, 'new story', 'aa', 'In Progress', 5, 5),
(8, 'Feature X', 'asdasd', 'In Progress', 1, 7),
(9, 'sad', 'asd', 'Impeded', 5, 7),
(10, 'Login Feature', 'The users need to be able to log in.', 'In Progress', 1, 8),
(11, 'Rent video', 'A user needs to be able to rent a video from the store.', 'In Progress', 2, 8),
(12, 'User feedback', 'User feedback is important to know how to deliver better service.', 'Not Started', 3, 8),
(13, 'Feature X', 'oaasd', 'In Progress', 5, 5);

-- --------------------------------------------------------

--
-- Table structure for table `impediment`
--

CREATE TABLE IF NOT EXISTS `impediment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL DEFAULT 'Impediment X',
  `description` varchar(250) NOT NULL DEFAULT 'Impediment Description',
  `user_email` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `impediment`
--

INSERT INTO `impediment` (`id`, `title`, `description`, `user_email`) VALUES
(1, 'My chair is broken!', 'My office chair broke yesterday, and it is hindering me from doing my work', 'kirk@abc.net');

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

CREATE TABLE IF NOT EXISTS `project` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL DEFAULT 'Project X',
  `description` varchar(250) NOT NULL DEFAULT 'This is a project',
  `status` enum('Not Started','In Progress','Impeded','Done') NOT NULL DEFAULT 'Not Started',
  `company_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `project`
--

INSERT INTO `project` (`id`, `title`, `description`, `status`, `company_id`, `start_date`) VALUES
(4, 'zz', 'zz', 'Not Started', 1, '2011-02-20'),
(5, 'zz', 'zz', 'Not Started', 2, '2011-02-22'),
(6, 'aa', 'aa', 'Not Started', 2, '2011-02-22'),
(7, 'Project X', 'aaaasda', 'Not Started', 3, '2011-03-19'),
(8, 'Project X', 'Project X is a top secret project involving the development of an online video rental store.', 'Not Started', 4, '2011-03-20');

-- --------------------------------------------------------

--
-- Table structure for table `quip`
--

CREATE TABLE IF NOT EXISTS `quip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `quip`
--


-- --------------------------------------------------------

--
-- Table structure for table `task`
--

CREATE TABLE IF NOT EXISTS `task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL DEFAULT 'Task X',
  `description` varchar(250) NOT NULL DEFAULT 'Task description',
  `status` enum('Not Started','In Progress','Impeded','Done') NOT NULL DEFAULT 'Not Started',
  `priority` int(11) NOT NULL,
  `feature_id` int(11) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=30 ;

--
-- Dumping data for table `task`
--

INSERT INTO `task` (`id`, `title`, `description`, `status`, `priority`, `feature_id`, `user_email`) VALUES
(1, 'zz', 'zz', 'Impeded', 1, 3, 'lukesmb@gmail.com'),
(2, 'aa', 'aa', 'Not Started', 2, 3, 'lukesmb@gmail.com'),
(3, 'zz', 'z', 'Done', 1, 4, 'lukesmb@gmail.com'),
(4, 'aa', 'aa', 'In Progress', 2, 4, 'lukesmb@gmail.com'),
(5, 'zz', 'zz', 'Done', 3, 3, 'lukesmb@gmail.com'),
(6, 'aa', 'aa', 'In Progress', 3, 4, ''),
(7, 'z', 'z', 'Done', 1, 5, 'lukesmb@gmail.com'),
(9, 'z', 'z', 'Done', 1, 6, 'lukesmb@gmail.com'),
(10, 'sd', 'asda', 'Impeded', 4, 4, 'lukesmb@gmail.com'),
(11, 'asd', 'asdas', 'Done', 5, 4, 'lukesmb@gmail.com'),
(12, 'asdasd', 'asdasd', 'Not Started', 6, 4, 'lukesmb@gmail.com'),
(13, 'asdasd', 'asdasd', 'Done', 7, 4, 'lukesmb@gmail.com'),
(14, 'asdasd', 'sadasdas', 'In Progress', 8, 4, 'lukesmb@gmail.com'),
(15, 'yes', 'yes', 'Impeded', 1, 7, 'yes@gmail.com'),
(16, 'no', 'no', 'Done', 2, 7, 'lukeb@cti.co.za'),
(17, 'AlterDB', 'asd', 'Not Started', 1, 8, 'james@gmail.com'),
(18, 'asda', 'sda', 'Impeded', 2, 8, 'james@gmail.com'),
(19, 'teassafafdsgaas asdas', 'asd', 'Not Started', 1, 9, 'james@gmail.com'),
(20, 'Login HTML ', 'The HTML for the login page', 'Done', 2, 10, 'kirk@abc.net'),
(21, 'Database check', 'A check against the users in the database needs to be done, to see if the user exists.', 'Not Started', 1, 10, 'kirk@abc.net'),
(22, 'Register a new user', 'Registering a new user is still apart of the logging page. functionality needs to be put in place for this.', 'Not Started', 3, 10, ''),
(23, 'Database access', 'Database access needs to be put in place for listing the videos available. ', 'In Progress', 1, 11, 'kirk@abc.net'),
(24, 'User tracking', 'A way to track users and which videos they have outstanding needs to be put into place. A list of users etc.', 'Not Started', 2, 11, ''),
(25, 'Page to book future videos', 'A user may book a video in advance that they want to rent out.', 'Not Started', 3, 11, ''),
(26, 'Feedback page', 'A page to provide feedback that gets emailed to the admin of ABC.', 'Not Started', 1, 12, ''),
(27, 'zz', 'zz', 'Not Started', 4, 10, 'kirk@abc.net'),
(28, 'asda', 'asdas', 'Impeded', 1, 13, 'lukeb@cti.co.za'),
(29, 'asdsa', 'asda', 'In Progress', 2, 13, 'lukeb@cti.co.za');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `email` varchar(100) NOT NULL,
  `password` varchar(50) NOT NULL,
  `display_name` varchar(50) NOT NULL,
  `is_admin` binary(1) NOT NULL DEFAULT '0',
  `company_id` int(11) NOT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`email`, `password`, `display_name`, `is_admin`, `company_id`) VALUES
('abc@abc.net', 'password', 'New Company Admin', '1', 4),
('james@gmail.com', 'pppppp', 'James inc Admin', '0', 3),
('kirk@abc.net', 'password', 'ABC Industries Admin', '1', 4),
('lukeb@cti.co.za', 'password', 'CTI Admin', '1', 2),
('lukesmb@gmail.com', 'password', 'Company X Admin', '1', 1),
('trekke@abc.com', 'password', 'Trek!', '0', 4),
('yes@gmail.com', 'password', 'YES!', '0', 2);
