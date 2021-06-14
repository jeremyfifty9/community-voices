<?php

namespace CommunityVoices\Model\Service;

/**
 * @overview Handles lookup functionality for image entities.
 */

use Palladium;
use CommunityVoices\Model\Entity;
use CommunityVoices\Model\Component;
use CommunityVoices\Model\Mapper;
use CommunityVoices\Model\Exception;

class ImageLookup
{
    private $mapperFactory;

    private $stateObserver;

    /**
     * @param ComponentMapperFactory $mapperFactory Factory for creating mappers
     */
    public function __construct(
        Component\MapperFactory $mapperFactory,
        Component\StateObserver $stateObserver
    ) {
        $this->mapperFactory = $mapperFactory;
        $this->stateObserver = $stateObserver;
    }

    public function printById(int $imageId)
    {
        $image = new Entity\Image;
        $image->setId($imageId);

        $imageMapper = $this->mapperFactory->createDataMapper(Mapper\Image::class);
        $imageMapper->fetch($image);
        $rect = $image->getCropRect();

        if (!$image->getId()) {
            throw new Exception\IdentityNotFound;
        }
        $fn = $image->getFilename();
        if (!file_exists($fn)) {
            throw new Exception\IdentityNotFound;
        }

        $type = mime_content_type($fn);
        header('Content-type: ' . $type);

        $dimensions = getimagesize($fn);
        $max_width = (isset($_GET['max_width']) && is_numeric($_GET['max_width'])) ? (int) $_GET['max_width'] : false;

        if (!isset($_GET['nocrop']) && ($rect['x'] > 0 || $rect['y'] > 0 || $rect['height'] > 0 || $rect['width'] > 0)) { // crop
            $img = $this->GDcreate($fn, $type);
            $img = $this->imagecrop($img, $rect); // custom imagecrop method used because the built-in PHP imagecrop() method prints: Warning: imagecrop(): one parameter to a memory allocation multiplication is negative or zero, failing operation gracefully
            if ($max_width && $rect['width'] > $max_width) { // also resize
                $img = $this->GDresize($img, $rect['width'], $rect['height'], $max_width);
            }
            $this->GDdisplay($img, $type);
        } else { // no crop
            if ($max_width && $dimensions[0] > $max_width) { // resize
                $img = $this->GDcreate($fn, $type);
                $img = $this->GDresize($img, $dimensions[0], $dimensions[1], $max_width);
                $this->GDdisplay($img, $type);
            } else { // no resize
                readfile($fn);
            }
        }
        exit;
    }

    private function GDresize($src, $cur_width, $cur_height, $max_width)
    {
        $r = $cur_width / $cur_height;
        $width = $max_width;
        $height = $max_width / $r;
        $dst = imagecreatetruecolor($width, $height);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $cur_width, $cur_height);
        return $dst;
    }

    private function GDcreate($fn, $type)
    {
        switch ($type) {
            case 'image/png':
                return imagecreatefrompng($fn);
            case 'image/gif':
                return imagecreatefromgif($fn);
            default:
                return imagecreatefromjpeg($fn);
        }
    }

    private function GDdisplay($img, $type)
    {
        switch ($type) {
            case 'image/png':
                return imagepng($img);
            case 'image/gif':
                return imagegif($img);
            default:
                return imagejpeg($img);
        }
    }

    /**
     * GD implementation of PHP's imagecrop method (which uses too much memory)
     */
    private function imagecrop($img, $rect)
    {
        if ($rect['width'] > 0 && $rect['height'] > 0) {
            $dst = imagecreatetruecolor($rect['width'], $rect['height']);
            imagecopyresampled($dst, $img, 0, 0, $rect['x'], $rect['y'], $rect['width'], $rect['height'], $rect['width'], $rect['height']);
        } else {
            $dst = $img;
        }
        return $dst;
    }

    /**
     * Lookup image by id
     *
     * @param  int $imageId
     *
     * @throws CommunityVoices\Model\Exception\IdentityNotFound
     *
     * @return CommunityVoices\Model\Entity\Image
     */
    public function findById(int $imageId)
    {
        $image = new Entity\Image;
        $image->setId($imageId);

        $tags = new Entity\GroupCollection;
        $tags->forGroupType(1);
        $tags->forParent($image);
        $image->setTagCollection($tags);

        $imageMapper = $this->mapperFactory->createDataMapper(Mapper\Image::class);
        $imageMapper->fetch($image);

        if (!$image->getId()) {
            throw new Exception\IdentityNotFound;
        }

        $userMapper = $this->mapperFactory->createDataMapper(Mapper\User::class);
        $userMapper->fetch($image->getAddedBy());

        $groupCollectionMapper = $this->mapperFactory->createDataMapper(Mapper\GroupCollection::class);
        $groupCollectionMapper->fetch($image->getTagCollection());

        $this->stateObserver->setSubject('imageLookup');
        $this->stateObserver->addEntry('image', $image);

        $clientState = $this->mapperFactory->createClientStateMapper(Mapper\ClientState::class);
        $clientState->save($this->stateObserver);
    }

    /**
     * Grab all the images
     *
     * @return CommunityVoices\Model\Entity\ImageCollection
     */
    public function findAll(int $page, int $limit, int $offset, string $order, int $only_unused, string $search, $tags, $photographers, $orgs, $status)
    {
        $imageCollection = new Entity\ImageCollection;
        $imageCollection->setPage($page);
        $imageCollection->setLimit($limit);
        $imageCollection->status = $status;
        $imageCollectionPhotographers = new \stdClass();
        $imageCollectionOrgs = new \stdClass();

        $groupCollectionMapper = $this->mapperFactory->createDataMapper(Mapper\GroupCollection::class);

        $imageCollectionMapper = $this->mapperFactory->createDataMapper(Mapper\ImageCollection::class);
        $imageCollectionMapper->fetch($imageCollection, $only_unused, $search, $tags, $photographers, $orgs, $limit, $offset, $order);
        $imageCollectionMapper->photographers($imageCollectionPhotographers);
        $imageCollectionMapper->orgs($imageCollectionOrgs);
        foreach ($imageCollection as $item) { // is this the right place to fetch tags for each image?
            $tags = new Entity\GroupCollection;
            $tags->forGroupType(1);
            $tags->forParent($item);
            $item->setTagCollection($tags);
            $groupCollectionMapper->fetch($item->getTagCollection());
        }

        $tagLookup = new TagLookup($this->mapperFactory, $this->stateObserver);
        $tagLookup->findAll();

        // I am uncertain about this
        $this->stateObserver->setSubject('imageFindAll');
        $this->stateObserver->addEntry('imageCollection', $imageCollection);
        $this->stateObserver->addEntry('imageCollectionPhotographers', $imageCollectionPhotographers);
        $this->stateObserver->addEntry('imageCollectionOrgs', $imageCollectionOrgs);

        // This should probably be done within the mapper, but just a quick speed refactor.
        $mapper = $this->mapperFactory->createDataMapper(Mapper\Image::class);
        foreach ($imageCollection as $image) {
            $image->setRelatedSlide($mapper->relatedSlideId($image->getId()));
        }

        $clientState = $this->mapperFactory->createClientStateMapper(Mapper\ClientState::class);
        $clientState->save($this->stateObserver);
    }

    /**
     * Find images by creator
     *
     * @param ID of user who added the images $creator
     *
     * @throws CommunityVoices\Model\Exception\IdentityNotFound
     *
     * @return CommunityVoices\Model\Entity\ImageCollection
     */
    public function findByCreator(int $creator)
    {
        $imageCollection = new Entity\ImageCollection;

        // instantiate and map data to new User entity
        $user = new Entity\User;
        $user->setId($creator);

        $userMapper = $this->mapperFactory->createDataMapper(Mapper\User::class);
        $userMapper->fetch($user);

        // no valid User
        if (!$user->getId()) {
            throw new Exception\IdentityNotFound;
        }

        $imageCollectionMapper = $this->mapperFactory->createDataMapper(Mapper\ImageCollection::class);
        // map data
        // check whether collection empty, do something

        // do we really want to grab all tag collections ???
        // if we choose not to, we can make a different toArray() method

        // stateObserver stuff

        // clientState stuff
    }

    /**
     * Lookup images by Group (e.g. tag, content category)
     *
     * @param  int $groupID
     *
     * @throws CommunityVoices\Model\Exception\IdentityNotFound
     *
     * @return CommunityVoices\Model\Entity\ImageCollection
     */
    public function findByGroup(int $groupID)
    {
        $imageCollection = new Entity\ImageCollection;

        // instantiate and map data to new Group entity
        $group = new Entity\Group;
        $group->setId($groupID);

        $groupMapper = $this->mapperFactory->createDataMapper(Mapper\Group::class);
        $groupMapper->fetch($group);

        // no valid Group
        if (!$group->getId()) {
            throw new Exception\IdentityNotFound;
        }

        $imageCollectionMapper = $this->mapperFactory->createDataMapper(Mapper\ImageCollection::class);
        // map data
        // check whether collection empty, do something

        // do we really want to grab all tag collections ???
        // if we choose not to, we can make a different toArray() method

        // stateObserver stuff

        // clientState stuff
    }

    public function photographers($stateObserver, $return = false)
    {
        $attributionCollection = new \stdClass;
        $attributionMapper = $this->mapperFactory->createDataMapper(Mapper\ImageCollection::class);
        $attributionMapper->photographers($attributionCollection);
        $stateObserver->setSubject('imageLookup');
        $stateObserver->addEntry('photographer', $attributionCollection);
        if ($return) {
            return $stateObserver;
        }
        $clientState = $this->mapperFactory->createClientStateMapper(Mapper\ClientState::class);
        $clientState->save($stateObserver);
    }

    public function orgs($stateObserver, $return = false)
    {
        $attributionCollection = new \stdClass;
        $attributionMapper = $this->mapperFactory->createDataMapper(Mapper\ImageCollection::class);
        $attributionMapper->orgs($attributionCollection);
        $stateObserver->setSubject('imageLookup');
        $stateObserver->addEntry('org', $attributionCollection);
        if ($return) {
            return $stateObserver;
        }
        $clientState = $this->mapperFactory->createClientStateMapper(Mapper\ClientState::class);
        $clientState->save($stateObserver);
    }

    public function relatedSlide(int $image_id)
    {
        $mapper = $this->mapperFactory->createDataMapper(Mapper\Image::class);
        $id = $mapper->relatedSlideId($image_id);
        return $id;
        // $slide = new Entity\Slide;
        // $slide->setId($id);
        // $mapper = $this->mapperFactory->createDataMapper(Mapper\Slide::class);
        // $mapper->fetch($slide);
        // return $slide;
    }

    public function relatedSlide2(int $image_id)
    {
        $mapper = $this->mapperFactory->createDataMapper(Mapper\Image::class);
        $id = $mapper->relatedSlideId($image_id);

        $this->stateObserver->setSubject('imageLookup');
        $this->stateObserver->addEntry('relatedSlide', $id);

        $clientState = $this->mapperFactory->createClientStateMapper(Mapper\ClientState::class);
        $clientState->save($this->stateObserver);
    }

    public function prevImage(int $image_id)
    {
        $mapper = $this->mapperFactory->createDataMapper(Mapper\Image::class);
        $id = $mapper->prevImage($image_id);
        return $id;
    }

    public function prevImage2(int $image_id)
    {
        $mapper = $this->mapperFactory->createDataMapper(Mapper\Image::class);
        $id = $mapper->relatedSlideId($image_id);

        $this->stateObserver->setSubject('imageLookup');
        $this->stateObserver->addEntry('prevImage', $id);

        $clientState = $this->mapperFactory->createClientStateMapper(Mapper\ClientState::class);
        $clientState->save($this->stateObserver);
    }

    public function nextImage(int $image_id)
    {
        $mapper = $this->mapperFactory->createDataMapper(Mapper\Image::class);
        $id = $mapper->nextImage($image_id);
        return $id;
    }

    public function nextImage2(int $image_id)
    {
        $mapper = $this->mapperFactory->createDataMapper(Mapper\Image::class);
        $id = $mapper->relatedSlideId($image_id);

        $this->stateObserver->setSubject('imageLookup');
        $this->stateObserver->addEntry('nextImage', $id);

        $clientState = $this->mapperFactory->createClientStateMapper(Mapper\ClientState::class);
        $clientState->save($this->stateObserver);
    }

    public function metaDataFields()
    {
        $mapper = $this->mapperFactory->createDataMapper(Mapper\Image::class);
        $metaDataFields = $mapper->getMetaDataFields();

        if(empty($metaDataFields) && is_array($metaDataFields)) $metaDataFields = "empty";
        // distinguish between empty array and false

        $this->stateObserver->setSubject('imageLookup');
        $this->stateObserver->addEntry('metaDataFields', ['metadata' => $metaDataFields]);

        $clientState = $this->mapperFactory->createClientStateMapper(Mapper\ClientState::class);
        $clientState->save($this->stateObserver);
    }
}
