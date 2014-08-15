CREATE TABLE `task_user_hours_done` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`hours` INT NOT NULL DEFAULT '0',
`date` DATE NOT NULL ,
`user_email` VARCHAR( 100 ) NOT NULL ,
`task_id` INT NOT NULL ,
INDEX ( `user_email` , `task_id` )
) ENGINE = InnoDB;

ALTER TABLE `task` ADD `time_stamp` TIMESTAMP NOT NULL ,
ADD `estimated_hours` INT NOT NULL DEFAULT '0';

ALTER TABLE `feature` ADD `effort_rating` INT NOT NULL DEFAULT '0' AFTER `priority` ;

