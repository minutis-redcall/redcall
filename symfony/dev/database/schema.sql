CREATE TABLE `import` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created_at_ts` bigint(11) NOT NULL,
  `updated_at_ts` bigint(11) DEFAULT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `import_structure` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `import_id` int(11) NOT NULL,
  `identifier` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `parent_structure` varchar(255) DEFAULT NULL,
  `enabled` varchar(255) NOT NULL DEFAULT '',
  `president` varchar(255) NOT NULL DEFAULT '',
  `imported` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
