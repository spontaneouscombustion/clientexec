CREATE TABLE `email` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `userid` INT NOT NULL,
    `subject` TEXT NOT NULL,
    `content` TEXT NOT NULL,
    `date` DATETIME NOT NULL,
    `to` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX (`userid`)
);