<?php

namespace CommunityVoices\App\Api\View;

use CommunityVoices\Model\Component\MapperFactory;
use CommunityVoices\App\Api\Component;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpFoundation\Response;

class Image extends Component\View
{
    public function __construct(
        Component\SecureContainer $secureContainer,
        MapperFactory $mapperFactory
    ) {
        parent::__construct($secureContainer, $mapperFactory);
    }

    protected function sendImage()
    {
        // wut
    }

    protected function getImage()
    {
        $clientState = $this->mapperFactory->createClientStateMapper();
        $stateObserver = $clientState->retrieve();

        $stateObserver->setSubject('imageLookup');
        $image = $stateObserver->getEntry('image')[0];

        $response = new HttpFoundation\JsonResponse($image->toArray());

        return $response;
    }

    protected function getAllImage()
    {
        $clientState = $this->mapperFactory->createClientStateMapper();
        $stateObserver = $clientState->retrieve();

        $stateObserver->setSubject('imageFindAll');
        $imageCollection = $stateObserver->getEntry('imageCollection')[0]->toArray();

        $imageCollection['imageCollectionPhotographers'] = $stateObserver->getEntry('imageCollectionPhotographers')[0];
        $imageCollection['imageCollectionOrgs'] = $stateObserver->getEntry('imageCollectionOrgs')[0];

        $stateObserver->setSubject('tagLookup');
        $imageCollection['tags'] = $stateObserver->getEntry('tag')[0]->toArray();

        $response = new HttpFoundation\JsonResponse($imageCollection);

        return $response;
    }

    protected function postImageUpload()
    {
        // intentionally blank
    }

    protected function postImageBatchUpload() 
    {
        return new HttpFoundation\JsonResponse(true);
    }

    protected function getImageRelatedSlide()
    {
        $clientState = $this->mapperFactory->createClientStateMapper();
        $stateObserver = $clientState->retrieve();

        $stateObserver->setSubject('imageLookup');
        $slides = $stateObserver->getEntry('relatedSlide')[0];

        $response = new HttpFoundation\JsonResponse($slides);

        return $response;
    }

    protected function getImagePrevImage()
    {
        $clientState = $this->mapperFactory->createClientStateMapper();
        $stateObserver = $clientState->retrieve();

        $stateObserver->setSubject('imageLookup');
        $slides = $stateObserver->getEntry('prevImage')[0];

        $response = new HttpFoundation\JsonResponse($slides);

        return $response;
    }

    protected function getImageNextImage()
    {
        $clientState = $this->mapperFactory->createClientStateMapper();
        $stateObserver = $clientState->retrieve();

        $stateObserver->setSubject('imageLookup');
        $slides = $stateObserver->getEntry('nextImage')[0];

        $response = new HttpFoundation\JsonResponse($slides);

        return $response;
    }
    protected function postImageUpdate()
    {
        return new HttpFoundation\JsonResponse(true);
    }
    protected function postImageUnpair()
    {
        return new HttpFoundation\JsonResponse(true);

    }

    protected function postMetaDataFields() {

        return new HttpFoundation\JsonResponse(true);
        
    }

    protected function getMetaDataFields() {
        $clientState = $this->mapperFactory->createClientStateMapper();
        $stateObserver = $clientState->retrieve();

        $stateObserver->setSubject('imageLookup');
        $metaData = $stateObserver->getEntry('metaDataFields')[0];
 
        $response = new HttpFoundation\JsonResponse($metaData);

        return $response;
    }
}
