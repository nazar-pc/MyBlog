CREATE TABLE IF NOT EXISTS `[prefix]myblog_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` int(10) unsigned NOT NULL,
  `title` varchar(1024) NOT NULL,
  `text` text NOT NULL,
  `date` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;