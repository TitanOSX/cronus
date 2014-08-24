DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles` (
    `id` int(11) not null auto_increment,
    `user_id` int(11) not null,
    `role_id` int(11) not null,
    UNIQUE KEY `user_per_role` (`user_id`, `role_id`),
    PRIMARY KEY (`id`)
) Engine=InnoDB;