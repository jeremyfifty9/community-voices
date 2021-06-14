<?php

namespace CommunityVoices\App\Api\Controller;

use CommunityVoices\Model\Component\MapperFactory;
use CommunityVoices\Model\Entity;
use CommunityVoices\Model\Service;
use CommunityVoices\Model\Exception;
use CommunityVoices\App\Api\Component;

class Quote extends Component\Controller
{
    protected $recognitionAdapter;
    protected $quoteLookup;
    protected $quoteManagement;

    // for future usage of this pattern: the value is the default value
    const FORM_ATTRIBUTES = [
        'text',
        'originalText',
        'interviewer',
        'attribution',
        'subAttribution',
        'quotationMarks' => false,
        'dateRecorded',
        'status' => Entity\Media::STATUS_PENDING,
        'tags' => [],
        'contentCategories' => []
    ];

    const META_DATA_FIELDS = [
        'sourcetype',
        'intervieweeorsourcedocument',
        'organization',
        'sponsororganization',
        'topicthemeofinterview',
        'urlsourcedocument',
        'intervieweeemail',
        'intervieweetelephone',
        'urlinterviewconsent',
        'urlt1survey',
        'urlt2survey',
        'urlinterviewtransription',
        'urlinterviewarticle',
        'dateapprovedbyinterviewee',
        'urlphotographinterviewee',
        'suggestedphotosource',
        'suggestedphotoincv',
        'createaslide'
    ];
    const WRONG_NUM_FILES = "You have the wrong number of files, please reupload."; // for batch upload
    const WRONG_NAMES_FILES =  "Your files are improperly named, please reupload."; // for batch upload

    public function __construct(
        Component\SecureContainer $secureContainer,
        Component\RecognitionAdapter $recognitionAdapter,
        Service\QuoteLookup $quoteLookup,
        Service\QuoteManagement $quoteManagement
    ) {
        parent::__construct($secureContainer);

        $this->recognitionAdapter = $recognitionAdapter;
        $this->quoteLookup = $quoteLookup;
        $this->quoteManagement = $quoteManagement;
    }

    /**
     * Quote lookup by id
     */
    protected function getQuote($request)
    {
        $quoteId = (int) $request->attributes->get('id');

        try {
            $this->quoteLookup->findById($quoteId);
        } catch (Exception\IdentityNotFound $e) {
            /**
           * @todo This is not necessarily the way to handle 404s
           */
            $this->send404();
        }
    }

    /**
     * Look up quotes that boundary queried quote
     */
    protected function getBoundaryQuotes($request)
    {
        $quoteId = (int) $request->attributes->get('id');

        $this->quoteLookup->findBoundaryQuotesById($quoteId);
    }

    protected function getAllQuote($request)
    {
        $identity = $this->recognitionAdapter->identify();

        /**
         * Grab parameters from bag
         */

        $search = $request->query->get('search');
        $tags = $request->query->get('tags');
        $contentCategories = $request->query->get('contentCategories');
        $attributions = $request->query->get('attributions');
        $subattributions = $request->query->get('subattributions');
        $creatorIDs = $request->attributes->get('creatorIDs');
        $status = $request->query->get('status');
        $order = (string) $request->query->get('order');
        $page = (int) $request->query->get('page');
        $limit = (int) $request->query->get('per_page');
        $only_unused = (int) $request->query->get('unused');

        /**
         * Pre-process parameters
         */
        $status = ($status == null) ? ["approved", "pending", "rejected"] : explode(',', $status);

        /**
         * @todo
         *
         * RBAC usually be in the ACL, not here (i.e., two different View-
         * Controller pairs, arbitrated by a secure container)
         */
        if ($identity->getRole() <= 2) {
            $status = ["approved"];
        }

        /**
         * Current page, indexed at zero
         * @var int
         */
        $page = ($page > 0) ? $page - 1 : 0;

        /**
         * Items per page
         * @var int
         */
        $limit = ($limit > 0) ? $limit : 25; // number of items per page
        $offset = $limit * $page;

        $only_unused = !!$only_unused;

        $this->quoteLookup->findAll($page, $limit, $offset, $order, $only_unused, $search, $tags, $contentCategories, $attributions, $subattributions, $creatorIDs, $status);
    }

    protected function getQuoteUpload()
    {
        // intentionally blank
    }

    protected function postQuoteUpload($request)
    {
        $identity = $this->recognitionAdapter->identify();

        // this needs to be in QuoteManagement->save,
        // but can be saved for when user roles are worked on
        // if ($identity->getRole() <= 2) {
        //     $approved = null;
        // }

        return $this->quoteManagement->save(
            null,
            $this->getFormAttributes($request, self::FORM_ATTRIBUTES),
            $identity
        );
    }

    protected function postBatchDraft($request)
    {
        $files = $request->files->get('file');
        if (sizeof($files) != 2) {
            return [[],[],["item" => self::WRONG_NUM_FILES],[],[]]; // the third array returned to the api/view is errors with upload. This just lets the frontend know that there are errors.
        }

        // there may be a better way to do this, for now we are just relying on file names to indicate which document
        foreach($request->files->get('file') as $file) {
            if (str_contains(strtolower($file->getClientOriginalName()), "source")) $source = $file;
            else if (str_contains(strtolower($file->getClientOriginalName()), "quote")) $quote = $file;
        }
        
        // the third array returned to the api/view is errors with upload. This just lets the frontend know that there are errors.
        if (! (isset($source) && (isset($quote)))) return [[],[],["item" => self::WRONG_NAMES_FILES],[],[]];
        else {
            $fp = new Component\FileProcessor();
            return $fp->parseQuoteBatchUpload($source->getPathname(),$quote->getPathname());
        }
    }

    protected function postBatchUpload($request)
    {
        $identity = $this->recognitionAdapter->identify();
        foreach($request->request->get('attribution') as $key => $value) { // attributions is arbitrary, just need something to specify which source/quote pair it is
            $identifier = $key;
            $this->quoteManagement->save(
                null,
                $this->getFormAttributes($request, self::FORM_ATTRIBUTES,true,$identifier),
                $identity,
                $this->getFormAttributes($request, self::META_DATA_FIELDS,true,$identifier)
            );
        }
    }

    protected function getQuoteUpdate($request)
    {
        // In order to autofill some form values,
        // we need to get the current quote's data.
        $this->getQuote($request);
    }

    protected function postQuoteUpdate($request)
    {
        return $this->quoteManagement->save(
            $this->getId($request),
            $this->getFormAttributes($request, self::FORM_ATTRIBUTES, $request->request->get('defaults'))
        );
    }

    protected function postQuoteDelete($request)
    {
        $id = (int) $request->attributes->get('id');

        $this->quoteManagement->delete($id);
    }

    protected function postQuoteUnpair($request)
    {
        $quote_id = (int) $request->attributes->get('quote');
        $slide_id = (int) $request->attributes->get('slide');

        $this->quoteManagement->unpair($quote_id, $slide_id);
    }

    public function getQuoteRelatedSlide($request)
    {
        $id = $request->attributes->get('id');
        $this->quoteLookup->relatedSlide2($id);
    }

    public function getQuotePrevQuote($request)
    {
        $id = $request->attributes->get('id');
        $this->quoteLookup->prevQuote2($id);
    }

    public function getQuoteNextQuote($request)
    {
        $id = $request->attributes->get('id');
        $this->quoteLookup->nextQuote2($id);
    }
}
