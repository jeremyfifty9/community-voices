<?php

namespace CommunityVoices\App\Website\View;

use \SimpleXMLElement;
use \DOMDocument;
use \XSLTProcessor;

use CommunityVoices\App\Api;
use CommunityVoices\App\Website\Component;
use Symfony\Component\HttpFoundation;

class DisplayError extends Component\View
{
    public function __construct(
        Component\MapperFactory $mapperFactory,
        Component\Transcriber $transcriber,
        //Api\View\Identification $identificationAPIView
        Component\ApiProvider $apiProvider
    ) {
        parent::__construct($mapperFactory, $transcriber, $apiProvider);
    }

    public function getError($request)
    {
        /**
         * DisplayError XML Package
         */
        $displayErrorPackageElement = new Helper\SimpleXMLElementExtension('<package/>');

        // Check if we are receiving a 404 error so that we can display the 404 page.
        // Also check for an access denied error to display the access denied page.
        $error = $request->attributes->get("error");
        if ($error === "Symfony\\Component\\Routing\\Exception\\ResourceNotFoundException") {
            $displayErrorPackageElement->addAttribute("error", "404");
        } else if ($error === "CommunityVoices\\App\\Api\\Component\\Exception\\AccessDenied") {
            $displayErrorPackageElement->addAttribute("error", "AccessDenied");
        }

        $identity = $displayErrorPackageElement->addChild('identity');
        $identity->adopt($this->identityXMLElement($request));

        /**
         * Generate DisplayError module
         */
        $displayErrorModule = new Component\Presenter('Module/DisplayError');
        $displayErrorModuleXML = $displayErrorModule->generate($displayErrorPackageElement);

        /**
         * Get base URL
         */
        //$urlGenerator = new UrlGenerator($routes, $context);
        //$baseUrl = $urlGenerator->generate('root');

        /**
         * Prepare template
         */
        $domainXMLElement = new Helper\SimpleXMLElementExtension('<domain/>');

        $domainXMLElement->addChild('main-pane', $displayErrorModuleXML);

        $presentation = new Component\Presenter('SinglePane');

        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));

        $this->finalize($response);
        return $response;
    }
    public function getErrors($request) {

        $errorsPackageElement = new Helper\SimpleXMLElementExtension('<package/>');

        $packagedErrors = $errorsPackageElement->addChild('domain');

        $packagedIdentity = $errorsPackageElement->addChild('identity');
        $identity = $this->identityXMLElement($request);

        $packagedIdentity->adopt($this->identityXMLElement($request));

        $errorsModule = new Component\Presenter('Module/Errors');
        $errorsModuleXML = $errorsModule->generate($errorsPackageElement);

        $domainXMLElement = new Helper\SimpleXMLElementExtension('<domain/>');
        $domainXMLElement->addChild('main-pane', $errorsModuleXML);
        $domainXMLElement->addChild('extraJS', "https://cdn.jsdelivr.net/momentjs/latest/moment.min.js https://cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js error-log-collection");

        $presentation = new Component\Presenter('SinglePane');
        $response = new HttpFoundation\Response($presentation->generate($domainXMLElement));

        $this->finalize($response);
        return $response;
    }
}
