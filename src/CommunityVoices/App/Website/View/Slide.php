<?php

namespace CommunityVoices\App\Website\View;

use \SimpleXMLElement;
use \DOMDocument;
use \XSLTProcessor;

use CommunityVoices\App\Api;
use CommunityVoices\App\Website\Component;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Routing\Generator\UrlGenerator;

class Slide extends Component\View
{
    // protected $slideAPIView;
    // protected $contentCategoryAPIView;

    public function __construct(
        Component\MapperFactory $mapperFactory,
        Component\Transcriber $transcriber,
        //Api\View\Identification $identificationAPIView,
        Component\ApiProvider $apiProvider
        // Api\View\Slide $slideAPIView,
        // Api\View\ContentCategory $contentCategoryAPIView
    ) {
        parent::__construct($mapperFactory, $transcriber, $apiProvider);

        // $this->slideAPIView = $slideAPIView;
        // $this->contentCategoryAPIView = $contentCategoryAPIView;
    }

    public function getAllSlide($request)
    {
        parse_str($_SERVER['QUERY_STRING'], $qs);

        /**
         * Gather slide information
         */
        // var_dump($slideAPIView->getAllSlide()->getContent());die;
        $json = $this->apiProvider->getQueriedJson('/slides', $request);
        // var_dump($json->slideCollection);die;
        $obj = new \stdClass();
        $obj->slideCollection = (array) $json->slideCollection;
        $count = $obj->slideCollection['count'];
        $limit = $obj->slideCollection['limit'];
        $page = $obj->slideCollection['page'];
        unset($obj->slideCollection['count']);
        unset($obj->slideCollection['limit']);
        unset($obj->slideCollection['page']);
        foreach ($obj->slideCollection as $key => $slide) {
            $slide->slide->quote->quote->text = $slide->slide->quote->quote->text;
            $slide->slide->quote->quote->attribution = $slide->slide->quote->quote->attribution;
            $slide->slide->quote->quote->subAttribution = $slide->slide->quote->quote->subAttribution;
            $slide->slide->quote->quote->attribution = $slide->slide->quote->quote->attribution;
            $slide->slide->quote->quote->text = $slide->slide->quote->quote->text;
            $slide->slide->image->image->title = $slide->slide->image->image->title;
        }
        $obj->slideCollection = array_values($obj->slideCollection);

        $slideXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $pagination = new \stdClass();
        $pagination->div = $this->paginationHTML($qs, $count, $limit, $page);
        $paginationXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($pagination)
        );

        $obj = new \stdClass;
        $obj->tagCollection = $json->tagCollection;
        $tagXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $obj = new \stdClass;
        $obj->attributionCollection = $json->attributionCollection;
        $attrXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $obj = new \stdClass;
        $obj->PhotographerCollection = $json->PhotographerCollection;
        $photoXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $obj = new \stdClass;
        $obj->OrgCollection = $json->OrgCollection;
        $orgXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $contentCategoryXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml(
                $this->apiProvider->getJson('/content-categories', $request)
            )
        );

        /**
         * Slide XML Package
         */
        $slidePackageElement = new Helper\SimpleXMLElementExtension('<package/>');

        $packagedSlide = $slidePackageElement->addChild('domain');
        $packagedSlide->adopt($slideXMLElement);
        $packagedSlide->adopt($paginationXMLElement);
        $packagedSlide->adopt($attrXMLElement);
        $packagedSlide->adopt($photoXMLElement);
        $packagedSlide->adopt($orgXMLElement);
        $packagedSlide->adopt($contentCategoryXMLElement);
        $packagedSlide->adopt($tagXMLElement);
        foreach ($qs as $key => $value) {
            if ($key === 'search' || $key === 'order') {
                $packagedSlide->addChild($key, $value);
            } else {
                $packagedSlide->addChild($key, (is_array($value)) ? ','.implode(',', $value).',' : ','.$value.',');
            }
        }
        // var_dump($packagedSlide);die;

        $packagedIdentity = $slidePackageElement->addChild('identity');
        $packagedIdentity->adopt($this->identityXMLElement($request));

        /**
         * Generate slide module
         */
        // var_dump($slidePackageElement->domain->slideCollection->slide[0]);exit;
        $slideModule = new Component\Presenter('Module/SlideCollection');
        $slideModuleXML = $slideModule->generate($slidePackageElement);

        /**
         * Get base URL
         */
        //$urlGenerator = new UrlGenerator($routes, $context);
        //$baseUrl = $urlGenerator->generate('root');

        /**
         * Prepare template
         */
        $domainXMLElement = new Helper\SimpleXMLElementExtension('<domain/>');

        $domainXMLElement->addChild('main-pane', $slideModuleXML);
        //$domainXMLElement->addChild('baseUrl', $baseUrl);
        $domainXMLElement->addChild(
            'title',
            "Community Voices: All Slides".
            $slideXMLElement->id
        );
        $domainXMLElement->addChild('extraJS', "slide-collection");
        $domainXMLElement->addChild('metaDescription', "Searchable database of content for Community Voices communication technology combining images and words to advance sustainability in diverse communities.");

        $domainIdentity = $domainXMLElement->addChild('identity');
        $domainIdentity->adopt($this->identityXMLElement($request));

        $presentation = new Component\Presenter('SinglePane');

        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));

        $this->finalize($response);
        return $response;
    }

    public function getSlide($request)
    {
        /**
         * Gather slide information
         */
        $id = $request->attributes->get('id');
        $json = $this->apiProvider->getJson("/slides/{$id}", $request);
        $json->slide->quote->quote->text = $json->slide->quote->quote->text;
        $json->slide->quote->quote->attribution = $json->slide->quote->quote->attribution;
        $json->slide->quote->quote->subAttribution = $json->slide->quote->quote->subAttribution;
        $json->slide->image->image->title = $json->slide->image->image->title;
        $json->slide->image->image->description = $json->slide->image->image->description;


        $dimensions = (file_exists($json->slide->image->image->filename)) ? getimagesize($json->slide->image->image->filename) : [16, 12];
        $aspect_ratio = $dimensions[0] / $dimensions[1];
        if ($aspect_ratio > 1.5) {
            $aspect_ratio = 1.5;
        }
        $scaled_ar = 2 - (($aspect_ratio ** 4) / 5);
        $strlen = strlen($json->slide->quote->quote->text);
        $scaled_len = 1 - ((($strlen/500) ** 2));
        $font_size = 0.5 + $scaled_ar + $scaled_len;//($aspect_ratio * ($strlen/100))
        $json->slide->font_size = $font_size;

        $slideXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($json)
        );

        /**
         * Slide XML Package
         */
        $slidePackageElement = new Helper\SimpleXMLElementExtension('<package/>');

        $height = (isset($_GET['height']) && intval($_GET['height']) > 0) ? (int) $_GET['height'] : 1080;
        $width = (isset($_GET['width']) && intval($_GET['width'])) > 0 ? (int) $_GET['width'] : 1920;
        $slidePackageElement->addChild('height', $height);
        $slidePackageElement->addChild('width', $width);

        $packagedSlide = $slidePackageElement->addChild('domain');
        $packagedSlide->adopt($slideXMLElement);

        $packagedIdentity = $slidePackageElement->addChild('identity');
        $packagedIdentity->adopt($this->identityXMLElement($request));

        /**
         * Generate slide module
         */
        $slideModule = new Component\Presenter('Module/Slide');
        $slideModuleXML = $slideModule->generate($slidePackageElement);

        /**
         * Get base URL
         */
        //$urlGenerator = new UrlGenerator($routes, $context);
        //$baseUrl = $urlGenerator->generate('root');

        /**
         * Prepare template
         */
        $domainXMLElement = new Helper\SimpleXMLElementExtension('<domain/>');

        $domainXMLElement->addChild('main-pane', $slideModuleXML);
        //$domainXMLElement->addChild('baseUrl', $baseUrl);
        $domainXMLElement->addChild(
            'title',
            "Community Voices: Slide ".
            $slideXMLElement->id
        );

        $domainIdentity = $domainXMLElement->addChild('identity');
        $domainIdentity->adopt($this->identityXMLElement($request));

        $presentation = new Component\Presenter('Blank');

        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));

        $this->finalize($response);

        return $response;
    }

    public function getSlideUpload($request)
    {
        parse_str($_SERVER['QUERY_STRING'], $qs);

        $json = $this->apiProvider->getJson('/slides/new', $request);

        $obj = new \stdClass;
        $obj->tagCollection = $json->tagCollection;
        $slideXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $obj = new \stdClass;
        $obj->attributionCollection = $json->attributionCollection;
        $attrXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $obj = new \stdClass;
        $obj->PhotographerCollection = $json->PhotographerCollection;
        $photoXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $obj = new \stdClass;
        $obj->OrgCollection = $json->OrgCollection;
        $orgXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $contentCategoryXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml(
                $this->apiProvider->getJson('/content-categories', $request)
            )
        );

        $paramXML = new SimpleXMLElement('<form/>');
        $formModule = new Component\Presenter('Module/Form/SlideUpload');
        $formModuleXML = $formModule->generate($paramXML);
        // var_dump($formModuleXML);die;

        $slidePackageElement = new Helper\SimpleXMLElementExtension('<form/>');
        $packagedSlide = $slidePackageElement->addChild('domain');
        $packagedSlide->adopt($slideXMLElement);
        $packagedSlide->adopt($attrXMLElement);
        $packagedSlide->adopt($photoXMLElement);
        $packagedSlide->adopt($orgXMLElement);
        $packagedSlide->adopt($contentCategoryXMLElement);
        $packagedIdentity = $slidePackageElement->addChild('identity');
        $packagedIdentity->adopt($this->identityXMLElement($request));
        $slideModule = new Component\Presenter('Module/Form/SlideUpload');
        $slideModuleXML = $slideModule->generate($slidePackageElement);
        // var_dump($slideModuleXML);die;

        /**
         * Get base URL
         */
        //$urlGenerator = new UrlGenerator($routes, $context);
        //$baseUrl = $urlGenerator->generate('root');

        //
        $domainXMLElement = new Helper\SimpleXMLElementExtension('<domain/>');
        $domainXMLElement->addChild('main-pane', $slideModuleXML);
        //$domainXMLElement->addChild('baseUrl', $baseUrl);
        $domainXMLElement->addChild(
            'title',
            "Community Voices: Slide Upload"
        );
        $domainXMLElement->addChild('extraJS', "create-slide");
        $domainXMLElement->addChild('extraCSS', "create-slide");
        $domainXMLElement->addChild('comfortaa', "1");

        foreach ($qs as $key => $value) {
            if ($key === 'search') {
                $domainXMLElement->addChild($key, $value);
            } else {
                $domainXMLElement->addChild($key, (is_array($value)) ? ','.implode(',', $value).',' : ','.$value.',');
            }
        }


        $presentation = new Component\Presenter('SinglePane');

        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));

        $this->finalize($response);
        return $response;
    }

    public function postSlideUpload($request)
    {
        $response = new HttpFoundation\RedirectResponse(
            $request->headers->get('referer')
        );

        $this->finalize($response);
        return $response;
    }

    public function getSlideUpdate($request)
    {
        parse_str($_SERVER['QUERY_STRING'], $qs);

        $id = $request->attributes->get('id');
        $json = $this->apiProvider->getJson("/slides/{$id}/edit", $request);

        $obj = new \stdClass;
        $obj->slide = $json->slide;
        $slideXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $obj = new \stdClass;
        $obj->tagCollection = $json->tagCollection;
        $tagXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $obj = new \stdClass;
        $obj->attributionCollection = $json->attributionCollection;
        $attrXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $obj = new \stdClass;
        $obj->PhotographerCollection = $json->PhotographerCollection;
        $photoXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $obj = new \stdClass;
        $obj->OrgCollection = $json->OrgCollection;
        $orgXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $obj = new \stdClass;
        $obj->locCollection = $json->locCollection;
        $locXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $obj = new \stdClass;
        $obj->selectedLocs = ','.implode(',', $json->selectedLocs).',';
        $selectedLocXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml($obj)
        );

        $contentCategoryXMLElement = new SimpleXMLElement(
            $this->transcriber->toXml(
                $this->apiProvider->getJson('/content-categories', $request)
            )
        );

        $paramXML = new SimpleXMLElement('<form/>');
        $formModule = new Component\Presenter('Module/Form/SlideUpload');
        $formModuleXML = $formModule->generate($paramXML);

        $slidePackageElement = new Helper\SimpleXMLElementExtension('<form/>');
        $packagedSlide = $slidePackageElement->addChild('domain');
        $packagedSlide->adopt($slideXMLElement);
        $packagedSlide->adopt($tagXMLElement);
        $packagedSlide->adopt($attrXMLElement);
        $packagedSlide->adopt($photoXMLElement);
        $packagedSlide->adopt($orgXMLElement);
        $packagedSlide->adopt($locXMLElement);
        $packagedSlide->adopt($selectedLocXMLElement);
        $packagedSlide->adopt($contentCategoryXMLElement);

        // var_dump($packagedSlide);die;

        $packagedIdentity = $slidePackageElement->addChild('identity');
        $packagedIdentity->adopt($this->identityXMLElement($request));
        $slideModule = new Component\Presenter('Module/Form/SlideUpload');
        $slideModuleXML = $slideModule->generate($slidePackageElement);
        // var_dump($packagedSlide->slide);die;

        /**
         * Get base URL
         */
        //$urlGenerator = new UrlGenerator($routes, $context);
        //$baseUrl = $urlGenerator->generate('root');

        //
        $domainXMLElement = new Helper\SimpleXMLElementExtension('<domain/>');
        $domainXMLElement->addChild('main-pane', $slideModuleXML);
        //$domainXMLElement->addChild('baseUrl', $baseUrl);
        $domainXMLElement->addChild(
            'title',
            "Community Voices: Slide Upload"
        );
        $domainXMLElement->addChild('extraJS', "create-slide");
        $domainXMLElement->addChild('extraCSS', "create-slide");
        $domainXMLElement->addChild('comfortaa', "1");

        foreach ($qs as $key => $value) {
            if ($key === 'search') {
                $domainXMLElement->addChild($key, $value);
            } else {
                $domainXMLElement->addChild($key, (is_array($value)) ? ','.implode(',', $value).',' : ','.$value.',');
            }
        }


        $presentation = new Component\Presenter('SinglePane');

        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));

        $this->finalize($response);
        return $response;
    }

    public function postSlideUpdate($request)
    {
        $response = new HttpFoundation\RedirectResponse(
            $request->headers->get('referer')
        );

        $this->finalize($response);
        return $response;
    }
}
