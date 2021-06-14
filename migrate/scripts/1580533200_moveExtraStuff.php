<?php

// #1

use CommunityVoices\Model\Mapper;
use CommunityVoices\Model\Entity;

$quoteMapper = new Mapper\Quote($dbHandler);
$quoteEntity = new Entity\Quote;
$allExtraStuffQuery = "SELECT * FROM `community-voices_quotes` WHERE extra_stuff is not null";
$addEndUseColumnQuery = "ALTER TABLE `community-voices_slides`
                        ADD COLUMN end_use varchar(255) DEFAULT NULL";

$statement = $dbHandler->prepare($addEndUseColumnQuery);
$statement->execute();

$catRow = $dbHandler->query($allExtraStuffQuery);
$allEndUses = [];
foreach($catRow as $e) {
    $id = $e['media_id'];
    $decoded = json_decode($e['extra_stuff'],true); // create associative array
    if($decoded['interviewer']) {
        $quote = new Entity\Quote;
        $quote->setId($id);
        $quoteMapper->fetch($quote);
        $quote->setInterviewer($decoded['interviewer']);
        $quoteMapper->save($quote);
    }
    $endUse = $decoded['endUse'];
    if ($endUse) {
        if(array_key_exists($endUse,$allEndUses))
            array_push($allEndUses[$endUse],$id);
        else
            $allEndUses[$endUse] = [$id];
    }
}

foreach($allEndUses as $endUse => $valuesArr) {
    $inStatement = '('. implode(",",$valuesArr) . ')';
    $addEndUseDataQuery = "UPDATE
                                `community-voices_slides`
                            SET
                                end_use = :end_use
                            WHERE
                                quote_id IN $inStatement";

    $statement = $dbHandler->prepare($addEndUseDataQuery);
    $statement->bindValue(':end_use', $endUse);
    $statement->execute();
}

// now that all data (besides prob which is useless) is transferred out of column we can delete extra_stuff.

$deleteExtraStuffColumnQuery = "ALTER TABLE `community-voices_quotes`
                                DROP COLUMN extra_stuff";
$statement = $dbHandler->prepare($deleteExtraStuffColumnQuery);
$statement->execute();
