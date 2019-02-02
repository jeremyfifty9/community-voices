<?php

require __DIR__ . '/../../../../vendor/autoload.php';

use CommunityVoices\Model;
use Jenssegers\ImageHash;

/**
 * Injector
 */

$injector = new Auryn\Injector;

/**
 * Db handler configuration
 */

require 'db.php';

$injector->share($dbHandler);

/**
 * Create and share mapper factories
 */

$uploadsDirectory = "/var/www/uploads/CV_Media/images/";

$mapperFactory = new Model\Component\MapperFactory($dbHandler, $uploadsDirectory);

$injector->share($mapperFactory);

/**
 * Fetch all images
 */

$imageCollection = new Model\Entity\ImageCollection;

$imageCollectionMapper = $mapperFactory->createDataMapper(Model\Mapper\ImageCollection::class);
$imageCollectionMapper->fetch($imageCollection);

$hasher = new ImageHash\ImageHash(new ImageHash\Implementations\PerceptualHash());

$imageMapper = $mapperFactory->createDataMapper(Model\Mapper\Image::class);

$i = 0;

foreach ($imageCollection as $image) {
    $i++;

    if ($i > 1) {
        exit;
    }
    
    $image->setPerceptualHash($hasher->hash($image->getFilename()));

    $imageMapper->save($image);
}