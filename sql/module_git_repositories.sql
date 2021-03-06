DROP TABLE IF EXISTS `module_git_repositories`;
CREATE TABLE `module_git_repositories` (
    `id` int(11) not null auto_increment,
    `device_id` int(11) not null,
    `name` varchar(255) not null,
    `commit_date` varchar(32) null,
    `remotes` text null,
    `last_commit` text null,
    `path` text null,
    `audit_date` varchar(32) not null,
    `date_created` bigint(13),
    `date_modified` bigint(13),        
    PRIMARY KEY (`id`)
) Engine=InnoDB;