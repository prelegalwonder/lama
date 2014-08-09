CREATE TABLE `acl_resources` (
  `module` varchar(35) NOT NULL,
  `controller` varchar(35) NOT NULL,
  PRIMARY KEY  (`module`,`controller`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `breeding_cages` (
  `id` int(11) NOT NULL default '0',
  `set_up_on` date NOT NULL,
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `breeding_type` varchar(45) default NULL,
  `mating_type` varchar(45) default NULL,
  `assigned_stud_id` int(11) default NULL,
  `active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  USING BTREE (`id`),
  KEY `stud` (`assigned_stud_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `cages` (
  `id` int(11) NOT NULL auto_increment,
  `cagetype` varchar(45) default NULL,
  `assigned_id` varchar(45) default NULL,
  `user_id` int(11) default NULL,
  `protocol_id` int(11) default NULL,
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `assigned_id` (`assigned_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `comments` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) default NULL,
  `comment` text,
  `ref_item_id` int(11) default NULL,
  `ref_table` varchar(45) default NULL,
  `modified_on` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  USING BTREE (`id`),
  KEY `new_index` (`ref_table`,`ref_item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `default_user_prefs` (
  `preference` varchar(100) NOT NULL,
  `value` text,
  PRIMARY KEY  (`preference`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `global_prefs` (
  `preference` varchar(100) NOT NULL,
  `value` text,
  PRIMARY KEY  (`preference`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `holding_cages` (
  `id` int(11) NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '1',
  `set_up_on` date NOT NULL,
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  USING BTREE (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `litters` (
  `id` int(11) NOT NULL auto_increment,
  `assigned_id` varchar(45) default NULL,
  `breeding_cage_id` int(11) default NULL,
  `user_id` int(11) default NULL,
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `generation` varchar(45) default NULL,
  `father_id` int(11) NOT NULL,
  `mother_id` int(11) NOT NULL,
  `mother2_id` int(11) default NULL,
  `mother3_id` int(11) default NULL,
  `strain_id` int(11) default NULL,
  `protocol_id` int(11) default NULL,
  `weaned_on` date default NULL,
  `born_on` date default NULL,
  `total_pups` int(11) default NULL,
  `alive_pups` int(11) default NULL,
  `weaned_male_count` int(11) default NULL,
  `weaned_female_count` int(11) default NULL,
  `is_embryo` tinyint(1) NOT NULL default '0',
  `sacrificed_male_count` int(11) default NULL,
  `sacrificed_female_count` int(11) default NULL,
  `sacrificed_nosex_count` int(11) default NULL,
  `holding_male_count` int(11) default NULL,
  `holding_female_count` int(11) default NULL,
  `not_viable` tinyint(1) default NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `assigned_id` (`assigned_id`),
  KEY `breeding_cage_id` (`breeding_cage_id`),
  KEY `parents` (`father_id`,`mother_id`,`mother2_id`,`mother3_id`),
  KEY `generation` (`generation`),
  FULLTEXT KEY `fulltextindex` (`assigned_id`,`generation`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `mice` (
  `id` int(11) NOT NULL auto_increment,
  `assigned_id` varchar(45) NOT NULL,
  `sex` char(1) default NULL,
  `is_alive` tinyint(1) NOT NULL default '1',
  `cage_id` int(11) default NULL,
  `strain_id` int(11) default NULL,
  `user_id` int(11) default NULL,
  `litter_id` int(11) default NULL,
  `protocol_id` int(11) default NULL,
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `born_on` date default NULL,
  `pcr_on` date default NULL,
  `weaned_on` date default NULL,
  `terminated_on` date default NULL,
  `status` varchar(45) default NULL,
  `ear_mark` varchar(45) default NULL,
  `genotype` varchar(45) default NULL,
  `generation` varchar(45) default NULL,
  `chip` varchar(45) default NULL,
  `is_chimera` tinyint(1) NOT NULL default '0',
  `chimera_is_germline` tinyint(1) default NULL,
  `chimera_is_founderline` tinyint(1) default NULL,
  `chimera_perc_esc` float default NULL,
  `chimera_perc_escblast` float default NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `assigned_id` (`assigned_id`),
  KEY `cage_id` (`cage_id`),
  KEY `litter_id` (`litter_id`),
  KEY `strain_id` (`strain_id`),
  KEY `status` (`status`),
  KEY `genotype` (`genotype`),
  KEY `generation` (`generation`),
  KEY `ear_mark` (`ear_mark`),
  FULLTEXT KEY `fulltext` (`assigned_id`,`status`,`genotype`,`generation`,`chip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `permissions` (
  `role_id` int(11) default NULL,
  `module` varchar(32) default NULL,
  `controller` varchar(32) default NULL,
  `action` varchar(32) default NULL,
  `is_allowed` tinyint(1) NOT NULL default '1',
  `id` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `perms` (`role_id`,`module`,`controller`,`action`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `protocols` (
  `id` int(11) NOT NULL auto_increment,
  `protocol_name` varchar(45) NOT NULL,
  `user_id` int(11) default NULL,
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `protocol_name` (`protocol_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `roles` (
  `id` int(11) NOT NULL auto_increment,
  `role_name` varchar(32) default NULL,
  `parent_role_id` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `acl_role_name` (`role_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `searches` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `public` tinyint(1) NOT NULL default '1',
  `subject` varchar(45) NOT NULL,
  `type` varchar(45) NOT NULL default 'columns',
  `params` text NOT NULL,
  `output_fields` text,
  `user_id` int(11) NOT NULL,
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `limit` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `user` (`user_id`,`type`,`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `strains` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) default NULL,
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `strain_name` varchar(45) NOT NULL,
  `pems` varchar(45) default NULL,
  `bems` varchar(45) default NULL,
  `promoter` varchar(45) default NULL,
  `esc_line` varchar(45) default NULL,
  `backbone_pems` varchar(45) default NULL,
  `reporter` varchar(45) default NULL,
  `jax_strain_name` varchar(45) default NULL,
  `jax_store_number` varchar(45) default NULL,
  `jax_generation` varchar(45) default NULL,
  `jax_genotype` varchar(45) default NULL,
  `jax_url` varchar(45) default NULL,
  `description` text,
  `assigned_user_id` int(11) default NULL,
  `grant` varchar(45) default NULL,
  `location` varchar(45) default NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `strain_name` (`strain_name`),
  FULLTEXT KEY `fulltext` (`strain_name`,`pems`,`promoter`,`esc_line`,`backbone_pems`,`reporter`,`jax_strain_name`,`jax_store_number`,`jax_generation`,`jax_genotype`,`jax_url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `tags` (
  `ref_table` varchar(45) NOT NULL,
  `ref_item_id` int(11) NOT NULL,
  `tag` varchar(45) NOT NULL,
  `user_id` int(11) default NULL,
  PRIMARY KEY  (`ref_table`,`ref_item_id`,`tag`),
  KEY `tag` (`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `transfers` (
  `id` int(11) NOT NULL auto_increment,
  `mouse_id` int(11) NOT NULL,
  `user_id` int(11) default NULL,
  `transferred_on` date NOT NULL,
  `notes` text,
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `from_cage_id` int(11) default NULL,
  `to_cage_id` int(11) default NULL,
  PRIMARY KEY  USING BTREE (`id`),
  KEY `from_cage` (`from_cage_id`),
  KEY `to_cage` (`to_cage_id`),
  KEY `mouse` (`mouse_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `user_prefs` (
  `user_id` int(11) NOT NULL,
  `preference` varchar(100) NOT NULL,
  `value` text,
  PRIMARY KEY  (`user_id`,`preference`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(45) NOT NULL,
  `email` varchar(45) default NULL,
  `password` varchar(45) default NULL,
  `active` tinyint(1) NOT NULL default '0',
  `email_verified` tinyint(1) NOT NULL default '0',
  `role_id` int(11) NOT NULL default '2',
  `last_seen` timestamp NOT NULL default '0000-00-00 00:00:00',
  `last_ip` varchar(45) default NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `username` (`username`),
  FULLTEXT KEY `fulltxt` (`username`,`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `weaning_cages` (
  `id` int(11) NOT NULL default '0',
  `litter_id` int(11) default NULL,
  `sex` char(1) default NULL,
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  USING BTREE (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
