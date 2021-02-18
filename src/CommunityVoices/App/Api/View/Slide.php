<?php

namespace CommunityVoices\App\Api\View;

use CommunityVoices\Model\Component\MapperFactory;
use CommunityVoices\App\Api\Component;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpFoundation\Response;

class Slide extends Component\View
{
    public function __construct(
        Component\SecureContainer $secureContainer,
        MapperFactory $mapperFactory
    ) {
        parent::__construct($secureContainer, $mapperFactory);
    }

    protected function getAllSlide()
    {
        $clientState = $this->mapperFactory->createClientStateMapper();
        $stateObserver = $clientState->retrieve();

        $stateObserver->setSubject('slideFindAll');
        $slideCollection = $stateObserver->getEntry('slideCollection')[0]->toArray();

        $stateObserver->setSubject('tagLookup');
        $tag = $stateObserver->getEntry('tag')[0]->toArray();

        $stateObserver->setSubject('quoteLookup');
        $quote_attributions['attributionCollection'] = $stateObserver->getEntry('attribution')[0]->attributionCollection;

        $stateObserver->setSubject('imageLookup');
        $image_photographers['PhotographerCollection'] = $stateObserver->getEntry('photographer')[0]->photographerCollection;
        $image_orgs['OrgCollection'] = $stateObserver->getEntry('org')[0]->orgCollection;

        $response = new HttpFoundation\JsonResponse(array_merge($this->convert_from_latin1_to_utf8_recursively($slideCollection), $tag, $quote_attributions, $image_photographers, $image_orgs));
        return $response;
    }

    protected function getSlide()
    {
        $clientState = $this->mapperFactory->createClientStateMapper();
        $stateObserver = $clientState->retrieve();

        $stateObserver->setSubject('slideLookup');
        $slide = $stateObserver->getEntry('slide')[0];

        $response = new HttpFoundation\JsonResponse($slide->toArray());

        return $response;
    }

    protected function getSlideUpload()
    {
        $clientState = $this->mapperFactory->createClientStateMapper();
        $stateObserver = $clientState->retrieve();

        $stateObserver->setSubject('tagLookup');
        $tag = $stateObserver->getEntry('tag')[0]->toArray();

        $stateObserver->setSubject('quoteLookup');
        $quote_attributions['attributionCollection'] = $stateObserver->getEntry('attribution')[0]->attributionCollection;

        $stateObserver->setSubject('imageLookup');
        $image_photographers['PhotographerCollection'] = $stateObserver->getEntry('photographer')[0]->photographerCollection;
        $image_orgs['OrgCollection'] = $stateObserver->getEntry('org')[0]->orgCollection;

        $response = new HttpFoundation\JsonResponse(array_merge($tag, $quote_attributions, $image_photographers, $image_orgs));

        return $response;
    }

    protected function postSlideUpload()
    {
        $clientStateMapper = $this->mapperFactory->createClientStateMapper();
        $clientStateObserver = $clientStateMapper->retrieve();

        // In the case that we have retrieved errors, we will send them along.
        // Otherwise, our errors array will be an empty array.
        $errors = ($clientStateObserver && $clientStateObserver->hasSubjectEntries('slideUpload'))
            ? $clientStateObserver->getEntriesBySubject('slideUpload') : [];

        $response = new HttpFoundation\JsonResponse(['errors' => $errors]);

        return $response;
    }

    protected function getSlideUpdate()
    {
        $clientState = $this->mapperFactory->createClientStateMapper();
        $stateObserver = $clientState->retrieve();

        $stateObserver->setSubject('slideLookup');
        $slide = $stateObserver->getEntry('slide')[0]->toArray();

        $stateObserver->setSubject('tagLookup');
        $tag = $stateObserver->getEntry('tag')[0]->toArray();

        $stateObserver->setSubject('locLookup');
        $loc = $stateObserver->getEntry('loc')[0]->toArray();

        // $stateObserver->setSubject('locLookup');
        $selectedLoc = ['selectedLocs' => $stateObserver->getEntry('selectedLoc')[0]];

        $stateObserver->setSubject('quoteLookup');
        $quote_attributions['attributionCollection'] = $stateObserver->getEntry('attribution')[0]->attributionCollection;

        $stateObserver->setSubject('imageLookup');
        $image_photographers['PhotographerCollection'] = $stateObserver->getEntry('photographer')[0]->photographerCollection;
        $image_orgs['OrgCollection'] = $stateObserver->getEntry('org')[0]->orgCollection;

        $response = new HttpFoundation\JsonResponse(array_merge($slide, $tag, $loc, $quote_attributions, $image_photographers, $image_orgs, $selectedLoc));

        return $response;
    }

    protected function postSlideUpdate()
    {
        return $this->errorsResponse("slideUpdate");
    }

    private function convert_from_latin1_to_utf8_recursively($dat)
    { // TODO: fix!
        if (is_string($dat)) {
            return $dat;
        } elseif (is_array($dat)) {
            $ret = [];
            foreach ($dat as $i => $d) {
                $ret[ $i ] = self::convert_from_latin1_to_utf8_recursively($d);
            }

            return $ret;
        } elseif (is_object($dat)) {
            foreach ($dat as $i => $d) {
                $dat->$i = self::convert_from_latin1_to_utf8_recursively($d);
            }

            return $dat;
        } else {
            return $dat;
        }
    }
}
