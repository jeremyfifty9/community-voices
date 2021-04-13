<?php

$checkConstraintExists = "SELECT CONSTRAINT_NAME
                       FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                       WHERE CONSTRAINT_NAME = 'community-voices_images_fkMetadata'";

// check for existance of constraint so we don't drop it unless it exists, avoiding an error


$statement = $dbHandler->prepare($checkConstraintExists);
$statement->execute();
$result = $statement->fetch();


$removeFK = "ALTER TABLE `community-voices_images`
                DROP FOREIGN KEY `community-voices_images_fkMetadata`";



$removeMetaDataColumn = "ALTER TABLE `community-voices_images`
                                DROP COLUMN metadata_id";


$dropTable = "DROP TABLE IF EXISTS `community-voices_image_metadata`";

if(!empty($result)) {
    $dbHandler->exec($removeFK);
    $dbHandler->exec($removeMetaDataColumn);
}

$dbHandler->exec($dropTable);