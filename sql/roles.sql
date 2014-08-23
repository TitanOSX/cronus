DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` int(11) not null auto_increment,
    `serial` varchar(255) not null,
    PRIMARY KEY (`id`)
) Engine=InnoDB;