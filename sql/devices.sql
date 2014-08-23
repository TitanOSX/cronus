DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
    `id` int(11) not null auto_increment,
    `serial` varchar(255) not null,
    `uuid` varchar(255),
    `make` varchar(255),
    `model` varchar(255),
    `cpu_type` varchar(255),
    `cpu_speed` varchar(255),
    `physical_memory` varchar(255),
    `os_version` varchar(255),
    `os_build` varchar(255),
    `date_created` bigint(13),
    `date_modified` bigint(13),
    UNIQUE KEY `device_serial` (`serial`),
    PRIMARY KEY (`id`)
) Engine=InnoDB;