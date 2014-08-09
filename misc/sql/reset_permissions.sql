-- USE mousedb;

DELETE FROM `roles`;
ALTER TABLE `roles` AUTO_INCREMENT = 1;

INSERT INTO `roles` VALUES
(1,'Administrator',NULL),
(2,'Read-only',NULL),
(3,'Read-write',2),
(4,'Read-write-delete',3);

DELETE FROM `acl_resources`;
ALTER TABLE `acl_resources` AUTO_INCREMENT = 1;

INSERT INTO `acl_resources` VALUES ('admin','index'),
('admin','phpinfo'),
('default','breeding-cage'),
('default','cage'),
('default','comment'),
('default','genotype'),
('default','holding-cage'),
('default','index'),
('default','protocol'),
('default','search'),
('default','strains'),
('default','tag'),
('default','transfer'),
('default','user'),
('default','weaning-cage');

DELETE FROM `permissions`;
ALTER TABLE `permissions` AUTO_INCREMENT = 1;

INSERT INTO `permissions`(`role_id`, `module`, `controller`, `action`, `is_allowed`) VALUES
(1,NULL,NULL,NULL,1),
(2,'default','breeding-cage','index',1),
(2,'default','breeding-cage','search',1),
(2,'default','breeding-cage','suggest',1),
(2,'default','breeding-cage','view',1),
(3,'default','breeding-cage','new',1),
(3,'default','breeding-cage','save',1),
(3,'default','breeding-cage','transfer',1),
(3,'default','breeding-cage','unassignstud',1),
(4,'default','breeding-cage','delete',1),
(2,'default','holding-cage','index',1),
(2,'default','holding-cage','search',1),
(2,'default','holding-cage','suggest',1),
(2,'default','holding-cage','view',1),
(3,'default','holding-cage','new',1),
(3,'default','holding-cage','save',1),
(3,'default','holding-cage','transfer',1),
(3,'default','holding-cage','unassignstud',1),
(4,'default','holding-cage','delete',1),
(2,'default','cage','list',1),
(2,'default','cage','view',1),
(2,'default','comment',NULL,1),
(2,'default','comment','delete_other',0),
(2,'default','index',NULL,1),
(2,'default','litter','index',1),
(2,'default','litter','printweancards',1),
(2,'default','litter','search',1),
(2,'default','litter','view',1),
(2,'default','litter','weanlist',1),
(3,'default','litter','editparents',1),
(3,'default','litter','new',1),
(3,'default','litter','save',1),
(3,'default','litter','unwean',1),
(3,'default','litter','wean',1),
(4,'default','litter','delete',1),
(2,'default','mouse','index',1),
(2,'default','mouse','list',1),
(2,'default','mouse','search',1),
(2,'default','mouse','suggest',1),
(2,'default','mouse','view',1),
(3,'default','mouse','modifyselected',1),
(3,'default','mouse','new',1),
(3,'default','mouse','revive',1),
(3,'default','mouse','sacrifice',1),
(3,'default','mouse','save',1),
(3,'default','mouse','transfer',1),
(4,'default','mouse','delete',1),
(2,'default','protocol','list',1),
(2,'default','protocol','view',1),
(2,'default','search','go',1),
(2,'default','search','index',1),
(2,'default','search','listgo',1),
(2,'default','search','view',1),
(3,'default','search','delete',1),
(3,'default','search','save',1),
(2,'default','strain','index',1),
(2,'default','strain','list',1),
(2,'default','strain','search',1),
(2,'default','strain','suggest',1),
(2,'default','strain','view',1),
(3,'default','strain','new',1),
(3,'default','strain','save',1),
(4,'default','strain','delete',1),
(2,'default','tag','index',1),
(2,'default','tag','list',1),
(2,'default','tag','tagged',1),
(2,'default','tag','view',1),
(3,'default','tag','add',1),
(3,'default','tag','addselected',1),
(3,'default','tag','remove',1),
(2,'default','transfer','view',1),
(3,'default','transfer','new',1),
(3,'default','transfer','save',1),
(4,'default','transfer','delete',1),
(2,'default','user','list',1),
(2,'default','user','logout',1),
(2,'default','user','settings',1),
(2,'default','weaning-cage','index',1),
(2,'default','weaning-cage','search',1),
(2,'default','weaning-cage','view',1),
(3,'default','weaning-cage','save',1);