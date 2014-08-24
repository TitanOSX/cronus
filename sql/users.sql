DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` int(11) not null auto_increment,
    `username` varchar(64) not null,
    `password` varchar(255) not null,
    `meta` text null,
    `date_created` bigint(13),
    `date_modified` bigint(13),    
    PRIMARY KEY (`id`)
) Engine=InnoDB;