<?php

namespace CommunityVoices\App\Website\View;

use \SimpleXMLElement;
use \DOMDocument;
use \XSLTProcessor;

use CommunityVoices\App\Api;
use CommunityVoices\App\Website\Component;
use CommunityVoices\Model\Service;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Routing\Generator\UrlGenerator;

class Image extends Component\View
{
    // protected $imageAPIView;
    // protected $imageLookup;
    // protected $tagLookup;
    protected $urlGenerator;

    public function __construct(
        Component\MapperFactory $mapperFactory,
        Component\Transcriber $transcriber,
        //Api\View\Identification $identificationAPIView,
        Component\ApiProvider $apiProvider,
        // Api\View\Image $imageAPIView,
        // Service\ImageLookup $imageLookup,
        // Service\TagLookup $tagLookup,
        UrlGenerator $urlGenerator
    ) {
        parent::__construct($mapperFactory, $transcriber, $apiProvider);

        // $this->imageAPIView = $imageAPIView;
        // $this->imageLookup = $imageLookup;
        // $this->tagLookup = $tagLookup;
        $this->urlGenerator = $urlGenerator;
    }

    public function sendImage($request)
    {
        // wut
    }

    public function getImage($request)
    {
        /**
         * Gather image information
         */
        $id = $request->attributes->get('id');
        $json = $this->apiProvider->getJson("/images/{$id}", $request);
        $imageXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($json)
        );

        /**
         * image XML Package
         */
        $imagePackageElement = new Helper\SimpleXMLElementExtension('<package/>');

        $packagedimage = $imagePackageElement->addChild('domain');
        $packagedimage->adopt($imageXMLElement);
        $packagedimage->adopt(new SimpleXMLElement(
            $this->transcriber->toXml(['slideId' => $this->apiProvider->getJson("/images/{$id}/slide", $request)])
        ));
        $packagedimage->adopt(new SimpleXMLElement(
            $this->transcriber->toXml(['prevId' => $this->apiProvider->getJson("/images/{$id}/prev", $request)])
        ));
        $packagedimage->adopt(new SimpleXMLElement(
            $this->transcriber->toXml(['nextId' => $this->apiProvider->getJson("/images/{$id}/next", $request)])
        ));

        $packagedIdentity = $imagePackageElement->addChild('identity');
        $packagedIdentity->adopt($this->identityXMLElement($request));

        /**
         * Generate image module
         */
        $imageModule = new Component\Presenter('Module/Image');
        $imageModuleXML = $imageModule->generate($imagePackageElement);

        /**
         * Get base URL
         */
        //$urlGenerator = new UrlGenerator($routes, $context);
        //$baseUrl = $urlGenerator->generate('root');

        /**
         * Prepare template
         */
        $domainXMLElement = new Helper\SimpleXMLElementExtension('<domain/>');

        $domainXMLElement->addChild('main-pane', $imageModuleXML);
        //$domainXMLElement->addChild('baseUrl', $baseUrl);
        $domainXMLElement->addChild(
            'title',
            "Community Voices: Image ".
            $imageXMLElement->id
        );


        $domainIdentity = $domainXMLElement->addChild('identity');
        $domainIdentity->adopt($this->identityXMLElement($request));

        $presentation = new Component\Presenter('SinglePane');

        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));

        $this->finalize($response);
        return $response;
    }

    public function getAllImage($request)
    {
        parse_str($_SERVER['QUERY_STRING'], $qs);

        /**
         * Gather image information
         */
        $json = $this->apiProvider->getQueriedJson('/images', $request);
        
        $isAdmin = false;
        
        if($this->identityXMLElement($request)->role=='administrator') {
            $isAdmin = true;
            $jsonMetaData = $this->apiProvider->getQueriedJson('/images/metadata/all', $request);
        }
        
        $obj = new \stdClass();
        $obj->imageCollection = $json->imageCollection;
        $count = $obj->imageCollection->count;
        $limit = $obj->imageCollection->limit;
        $page = $obj->imageCollection->page;
        unset($obj->imageCollection->count); // TODO: fix!
        unset($obj->imageCollection->limit);
        unset($obj->imageCollection->page);
        $obj->imageCollection = array_values((array) $obj->imageCollection);
        // add csv of tags so checkboxes can be checked with xslt
        foreach ($obj->imageCollection as $item) {
            $selectedTags = [];
            foreach ($item->image->tagCollection->groupCollection as $group) {
                $selectedTags[] = $group->group->id;
            }
            $item->image->selectedTagString = ',' . implode(',', $selectedTags) . ',';
        }

        $imageXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        // Get all photographers for menu -- should this be done a different way?
        $photographers = $json->imageCollectionPhotographers;
        $photographerXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($photographers)
        );

        $orgs = $json->imageCollectionOrgs;
        $orgXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($orgs)
        );

        $tags = $json->tags;
        usort($tags->tagCollection, function ($a, $b) {
            $a = $a->tag->label;
            $b = $b->tag->label;
            return strcmp($a, $b);
        });
        $tagXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($tags)
        );

        // TODO fix
        $pagination = new \stdClass();
        $pagination->div = $this->paginationHTML($qs, $count, $limit, $page);
        $paginationXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($pagination)
        );

        if($isAdmin) {
            $metaDataXMLElement = new SimpleXMLElement(
                $this->transcriber->toXml($jsonMetaData)
            );
        }

        /**
         * image XML Package
         */
        $imagePackageElement = new Helper\SimpleXMLElementExtension('<package/>');

        $packagedImage = $imagePackageElement->addChild('domain');
        $packagedImage->adopt($imageXMLElement);
        $packagedImage->adopt($photographerXMLElement);
        $packagedImage->adopt($orgXMLElement);
        $packagedImage->adopt($paginationXMLElement);
        $packagedImage->adopt($tagXMLElement);
        if($isAdmin) {
            $packagedImage->adopt($metaDataXMLElement);
        }

        foreach ($qs as $key => $value) {
            if ($key === 'search' || $key === 'order' || $key === 'unused') {
                $packagedImage->addChild($key, $value);
            } else {
                $packagedImage->addChild($key, (is_array($value)) ? ','.implode(',', $value).',' : ','.$value.',');
            }
        }

        $packagedIdentity = $imagePackageElement->addChild('identity');
        $packagedIdentity->adopt($this->identityXMLElement($request));

        /**
         * Generate image module
         */
        $imageModule = new Component\Presenter('Module/ImageCollection');
        $imageModuleXML = $imageModule->generate($imagePackageElement);

        /**
         * Get base URL
         */
        //$urlGenerator = new UrlGenerator($routes, $context);
        //$baseUrl = $urlGenerator->generate('root');

        /**
         * Prepare template
         */
        $domainXMLElement = new Helper\SimpleXMLElementExtension('<domain/>');

        $domainXMLElement->addChild('main-pane', $imageModuleXML);
        //$domainXMLElement->addChild('baseUrl', $baseUrl);
        $domainXMLElement->addChild('title', "Community Voices: All Images");
        $domainXMLElement->addChild('extraJS', "https://cdn.jsdelivr.net/npm/exif-js image-collection");
        $domainXMLElement->addChild('extraCSS', "image-collection");
        $domainXMLElement->addChild('metaDescription', "Searchable database of photos used for Community Voices communication technology to promote environmental, social and economic sustainability in diverse communities.");
        $domainIdentity = $domainXMLElement->addChild('identity');
        $domainIdentity->adopt($this->identityXMLElement($request));

        $presentation = new Component\Presenter('SinglePane');

        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));

        $this->finalize($response);
        return $response;
    }

    public function postImageUpload($request)
    {
        $response = new HttpFoundation\RedirectResponse(
            $this->urlGenerator->generate('getAllImage')
        );

        $this->finalize($response);
        return $response;
    }

    public function postImageUnpair($request)
    {
        $response = new HttpFoundation\RedirectResponse(
            $request->headers->get('referer')
        );

        $this->finalize($response);
        return $response;
    }
}
