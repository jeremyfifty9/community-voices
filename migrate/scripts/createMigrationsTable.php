<?php

$createMigrationsTable = "CREATE TABLE IF NOT EXISTS `community-voices_migrations` (
    `id` int(21) NOT NULL AUTO_INCREMENT,
    `script_name` VARCHAR(250) NOT NULL,
    `arguments` TEXT DEFAULT NULL,
    `successful` BOOLEAN NOT NULL,
    `datetime_executed` DATETIME NOT NULL,
    `exception_message` TEXT DEFAULT NULL,
    `exception_trace` TEXT DEFAULT NULL,
     PRIMARY KEY (id)
  ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
  $dbHandler->exec($createMigrationsTable);