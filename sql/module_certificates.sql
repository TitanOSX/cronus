DROP TABLE IF EXISTS `module_certificates`;
CREATE TABLE `module_certificates` (
    `id` int(11) not null auto_increment,
    `device_id` int(11) not null,
    `name` varchar(255) not null,
    `unixtime` varchar(32) null,
    `fingerprint` text null,
    `type` varchar(255) null,
    `date` varchar(32) null,
    `date_created` bigint(13),
    `date_modified` bigint(13),    
    PRIMARY KEY (`id`)
) Engine=InnoDB;