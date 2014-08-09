CREATE TABLE `holding_cages` (
  `id` int(11) NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '1',
  `set_up_on` date NOT NULL,
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  USING BTREE (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

