CREATE TABLE `news` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`created_at` TIMESTAMP NULL DEFAULT current_timestamp(),
	`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
	`date_start` DATETIME NULL DEFAULT NULL,
	`date_end` DATETIME NULL DEFAULT NULL,
	`status` TINYINT(4) NOT NULL DEFAULT 1,
	`title` VARCHAR(32) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`body` TEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
	PRIMARY KEY (`id`)
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
;
INSERT INTO `news` (`id`, `created_at`, `updated_at`, `date_start`, `date_end`, `status`, `title`, `body`) VALUES (1, '2020-12-04 21:37:41', '2020-12-04 21:57:19', '2020-12-04 21:37:38', '2020-12-04 21:37:39', 1, 'Test', 'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat.');
