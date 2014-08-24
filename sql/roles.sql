DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `id` int(11) not null auto_increment,
    `name` varchar(255) not null,
    `permission` int(3) not null,
    PRIMARY KEY (`id`)
) Engine=InnoDB;