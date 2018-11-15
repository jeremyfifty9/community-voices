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
    protected $recognitionAdapter;
    protected $imageAPIView;
    protected $secureContainer;
    protected $transcriber;

    public function __construct(
        Component\RecognitionAdapter $recognitionAdapter,
        Component\MapperFactory $mapperFactory,
        Component\Transcriber $transcriber,
        Api\Component\SecureContainer $secureContainer,
        Api\View\Image $imageAPIView,
        Service\ImageLookup $imageLookup,
        Service\TagLookup $tagLookup
    ) {
        $this->recognitionAdapter = $recognitionAdapter;
        $this->mapperFactory = $mapperFactory;
        $this->transcriber = $transcriber;
        $this->secureContainer = $secureContainer;
        $this->imageAPIView = $imageAPIView;
        $this->imageLookup = $imageLookup;
        $this->tagLookup = $tagLookup;
    }

    public function sendImage($request)
    {
        // wut
    }

    public function getImage($request)
    {
        /**
         * Gather identity information
         */
        $identity = $this->recognitionAdapter->identify();

        $identityXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($identity->toArray())
        );

        /**
         * Gather image information
         */
        $imageAPIView = $this->secureContainer->contain($this->imageAPIView);
        $json = json_decode($imageAPIView->getImage()->getContent());
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
            $this->transcriber->toXml(['slideId' => $this->imageLookup->relatedSlide($json->image->id)])
        ));
        $packagedimage->adopt(new SimpleXMLElement(
            $this->transcriber->toXml(['prevId' => $this->imageLookup->prevImage($json->image->id)])
        ));
        $packagedimage->adopt(new SimpleXMLElement(
            $this->transcriber->toXml(['nextId' => $this->imageLookup->nextImage($json->image->id)])
        ));

        $packagedIdentity = $imagePackageElement->addChild('identity');
        $packagedIdentity->adopt($identityXMLElement);

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
        $domainIdentity->adopt($identityXMLElement);

        $presentation = new Component\Presenter('SinglePane');

        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));

        $this->finalize($response);
        return $response;
    }

    public function getAllImage($request)
    {
        parse_str($_SERVER['QUERY_STRING'], $qs);
        /**
         * Gather identity information
         */
        $identity = $this->recognitionAdapter->identify();

        $identityXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($identity->toArray())
        );

        /**
         * Gather image information
         */
        $imageAPIView = $this->secureContainer->contain($this->imageAPIView);
        $json = json_decode($imageAPIView->getAllImage()->getContent());
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
            $item->image->relatedSlide = $this->imageLookup->relatedSlide($item->image->id);
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
        usort($tags->groupCollection, function ($a, $b) {
            $a = $a->group->label;
            $b = $b->group->label;
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

        foreach ($qs as $key => $value) {
            if ($key === 'search' || $key === 'order' || $key === 'unused') {
                $packagedImage->addChild($key, $value);
            } else {
                $packagedImage->addChild($key, (is_array($value)) ? ','.implode(',', $value).',' : ','.$value.',');
            }
        }

        $packagedIdentity = $imagePackageElement->addChild('identity');
        $packagedIdentity->adopt($identityXMLElement);

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
        $domainXMLElement->addChild('extraJS', "image-collection");
        $domainXMLElement->addChild('extraCSS', "image-collection");
        $domainXMLElement->addChild('metaDescription', "Searchable database of photos used for Community Voices communication technology to promote environmental, social and economic sustainability in diverse communities.");
        $domainIdentity = $domainXMLElement->addChild('identity');
        $domainIdentity->adopt($identityXMLElement);

        $presentation = new Component\Presenter('SinglePane');

        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));

        $this->finalize($response);
        return $response;
    }

    public function getImageUpload($request)
    {
        $identity = $this->recognitionAdapter->identify();

        $identityXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($identity->toArray())
        );

        $imageAPIView = $this->secureContainer->contain($this->imageAPIView);

        $imageXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml(json_decode(
                $imageAPIView->getImageUpload()->getContent()
            ))
        );

        $imagePackageElement = new Helper\SimpleXMLElementExtension('<package/>');
        $packagedImage = $imagePackageElement->addChild('domain');
        $packagedImage->adopt($imageXMLElement);
        $packagedIdentity = $imagePackageElement->addChild('identity');
        $packagedIdentity->adopt($identityXMLElement);
        $imageModule = new Component\Presenter('Module/Form/ImageUpload');
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
        $domainXMLElement->addChild('title', "Community Voices: Image Upload");
        $domainIdentity = $domainXMLElement->addChild('identity');
        $domainIdentity->adopt($identityXMLElement);
        $presentation = new Component\Presenter('SinglePane');
        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));
        $this->finalize($response);
        return $response;
    }

    public function postImageUpload($request)
    {
        $this->success('https://environmentaldashboard.org/community-voices/images');

        /*
        $identity = $this->recognitionAdapter->identify();
        $identityXMLElement = new SimpleXMLElement(
          $this->transcriber->toXml($identity->toArray())
        );
        $domainXMLElement = new Helper\SimpleXMLElementExtension('<domain/>');
        $domainXMLElement->addChild('main-pane', '<p>Success.</p>');
        $domainXMLElement->addChild(
          'title',
          "Community Voices"
        );
        $domainIdentity = $domainXMLElement->addChild('identity');
        $domainIdentity->adopt($identityXMLElement);
        $presentation = new Component\Presenter('SinglePane');
        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));
        $this->finalize($response);
        return $response;
        */
    }

    public function getImageUpdate($request)
    {
        $paramXML = new Helper\SimpleXMLElementExtension('<form/>');

        /**
         * Gather image information
         */
        $imageAPIView = $this->secureContainer->contain($this->imageAPIView);

        $image = json_decode($imageAPIView->getImage()->getContent());
        $imageXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($image)
        );

        $tags = $this->tagLookup->findAll(true);
        $tagXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($tags->getEntry('tag')[0]->toArray())
        );

        $selectedTagString = ',';
        foreach ($image->image->tagCollection->groupCollection as $group) {
            $selectedTagString .= "{$group->group->id},";
        }
        $selectedTagXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml(['selectedTags' => [$selectedTagString]])
        );

        $packagedImage = $paramXML->addChild('domain');
        $packagedImage->adopt($imageXMLElement);
        $packagedImage->adopt($tagXMLElement);
        $packagedImage->adopt($selectedTagXMLElement);

        $formModule = new Component\Presenter('Module/Form/ImageUpdate');
        $formModuleXML = $formModule->generate($paramXML);

        $identity = $this->recognitionAdapter->identify();

        $identityXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($identity->toArray())
        );

        /**
         * Get base URL
         */
        //$urlGenerator = new UrlGenerator($routes, $context);
        //$baseUrl = $urlGenerator->generate('root');

        //

        $domainXMLElement = new Helper\SimpleXMLElementExtension('<domain/>');

        $domainXMLElement->addChild('main-pane', $formModuleXML);
        //$domainXMLElement->addChild('baseUrl', $baseUrl);
        $domainXMLElement->addChild('title', "Community Voices: Image Update");
        $domainXMLElement->addChild('extraJS', "image-update");


        $domainIdentity = $domainXMLElement->addChild('identity');
        $domainIdentity->adopt($identityXMLElement);

        // var_dump($domainIdentity);

        $presentation = new Component\Presenter('SinglePane');

        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));

        $this->finalize($response);
        return $response;
    }

    public function postImageUpdate($request)
    {
        $this->success();

        /*
        $identity = $this->recognitionAdapter->identify();
        $identityXMLElement = new SimpleXMLElement(
          $this->transcriber->toXml($identity->toArray())
        );
        $domainXMLElement = new Helper\SimpleXMLElementExtension('<domain/>');
        $domainXMLElement->addChild('main-pane', '<p>Success.</p>');
        $domainXMLElement->addChild(
          'title',
          "Community Voices"
        );
        $domainIdentity = $domainXMLElement->addChild('identity');
        $domainIdentity->adopt($identityXMLElement);
        $presentation = new Component\Presenter('SinglePane');
        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));
        $this->finalize($response);
        return $response;
        */
    }

    public function postImageUnpair($request)
    {
        exit; // nothing to show to user
    }
}
