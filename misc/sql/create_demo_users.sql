DELETE FROM `users`;
ALTER TABLE `users` AUTO_INCREMENT = 1;

INSERT INTO `users` (`username`, `password`, `role_id`, `active`) VALUES
('admin', md5('admin'), 1, 1),
('user', md5('user'), 1, 1);
