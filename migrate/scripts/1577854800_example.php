<?php

// #0

use CommunityVoices\Model\Mapper;
use CommunityVoices\Model\Entity;

// Using a mapper to fill in an object's fields.
$contentCategoryMapper = new Mapper\ContentCategory($dbHandler);
$cc = new Entity\ContentCategory;
$cc->setGroupId(1);

$contentCategoryMapper->fetch($cc);
var_dump($cc);

// Writing a raw SQL query and filling in fields on own.
$contentCategoryQuery = 'SELECT * FROM `community-voices_content-categories` WHERE group_id = 1';

// This is a silly way to get the first row, maybe there's a more official way.
$catRow = iterator_to_array($dbHandler->query($contentCategoryQuery))[0];
$cc2 = new Entity\ContentCategory;
$cc2->setGroupId($catRow['group_id']);
$cc2->setColor($catRow['color']);

var_dump($cc2);

// Or, combine SQL and the Mapper.
$cc3 = new Entity\ContentCategory;
$contentCategoryMapper->populateEntity($cc3, $catRow);

var_dump($cc3);
