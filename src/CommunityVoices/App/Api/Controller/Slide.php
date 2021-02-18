<?php

namespace CommunityVoices\App\Api\Controller;

use CommunityVoices\Model\Component\MapperFactory;
use CommunityVoices\Model\Entity;
use CommunityVoices\Model\Service;
use CommunityVoices\Model\Exception;
use CommunityVoices\App\Api\Component;

class Slide extends Component\Controller
{
    protected $recognitionAdapter;
    protected $slideLookup;
    protected $slideManagement;
    protected $tagLookup;
    protected $quoteLookup;
    protected $imageLookup;

    public function __construct(
        Component\SecureContainer $secureContainer,
        Component\RecognitionAdapter $recognitionAdapter,
        Service\SlideLookup $slideLookup,
        Service\SlideManagement $slideManagement,
        Service\TagLookup $tagLookup,
        Service\QuoteLookup $quoteLookup,
        Service\ImageLookup $imageLookup
    ) {
        parent::__construct($secureContainer);

        $this->recognitionAdapter = $recognitionAdapter;
        $this->slideLookup = $slideLookup;
        $this->slideManagement = $slideManagement;
        $this->tagLookup = $tagLookup;
        $this->quoteLookup = $quoteLookup;
        $this->imageLookup = $imageLookup;
    }

    /**
     * Grabs all slides from databbase
     * @param  Request $request A request from the client's machine
     * @return SlideCollection  A collection of all slides in the database
     */
    public function getAllSlide($request)
    {
      $identity = $this->recognitionAdapter->identify();

        $search = (string) $request->query->get('search');
        $tags = $request->query->get('tags');
        $photographers = $request->query->get('photographers');
        $orgs = $request->query->get('orgs');
        $order = (string) $request->query->get('order');
        $attributions = $request->query->get('attributions');

        $status = $request->query->get('status');

        if ($status == null) {
          if ($identity->getRole() >= 3) {
            $status = ['approved', 'pending', 'rejected'];
          } else {
            $status = ['approved'];
          }
        } else {
          $status = explode(',', $status);
        }

        $page = (int) $request->query->get('page');
        $page = ($page > 0) ? $page - 1 : 0; // current page, make page 0-based
        $limit = (int) $request->query->get('per_page');
        if ($limit < 1) {
            $limit = 12; // number of items per page
        }
        $offset = $limit * $page;
        $cc = (is_array($request->query->get('content_category'))) ? $request->query->get('content_category') : [];
        $stateObserver = $this->tagLookup->findAll(true);
        $stateObserver = $this->slideLookup->findAll($page, $limit, $offset, $order, $search, $tags, $photographers, $orgs, $attributions, $cc, $status, $stateObserver);
        $stateObserver = $this->quoteLookup->attributions($stateObserver, true);
        $stateObserver = $this->imageLookup->photographers($stateObserver, true);
        $this->imageLookup->orgs($stateObserver);
    }

    /**
     * Slide lookup by id
     */
    public function getSlide($request)
    {
        $slideId = (int) $request->attributes->get('id');

        try {
            $this->slideLookup->findById($slideId);
        } catch (Exception\IdentityNotFound $e) {
            $this->send404();
        }
    }

    public function getSlideUpload()
    {
        $stateObserver = $this->tagLookup->findAll(true);
        $stateObserver = $this->imageLookup->photographers($stateObserver, true);
        $stateObserver = $this->imageLookup->orgs($stateObserver, true);
        $this->quoteLookup->attributions($stateObserver);
    }

    public function postSlideUpload($request)
    {
        $identity = $this->recognitionAdapter->identify();

        $imageId = $request->request->get('image_id');
        $quoteId = $request->request->get('quote_id');

        $logo = $request->request->get('logo_id');
        if (!is_null($logo))
            $logo = (int)($logo);

        $contentCategory = $request->request->get('content_category');
        $screens = (array) $request->request->get('screens');
        $dateRecorded = 'now';
        $approved = null;//$request->request->get('approved');

        $decay_percent = (int) $request->request->get('decay_percent');
        $probability = (float) $request->request->get('probability');
        $decay_start = (string) $request->request->get('decay_start');
        $decay_end = (string) $request->request->get('decay_end');

        // if ($identity->getRole() <= 2){
        //   $approved = null;
        // }

        $this->slideManagement->upload($quoteId, $imageId, $contentCategory, $logo, $screens, $dateRecorded, $decay_percent, $probability, $decay_start, $decay_end, $approved, $identity);
    }

    public function getSlideUpdate($request)
    {
        $slideId = (int) $request->attributes->get('id');

        $stateObserver = $this->tagLookup->findAll(true);
        $stateObserver = $this->imageLookup->photographers($stateObserver, true);
        $stateObserver = $this->imageLookup->orgs($stateObserver, true);
        $stateObserver = $this->quoteLookup->attributions($stateObserver, true);
        try {
            $this->slideLookup->findById($slideId, $stateObserver);
        } catch (Exception\IdentityNotFound $e) {
            $this->send404();
        }
    }

    public function postSlideUpdate($request)
    {
        $identity = $this->recognitionAdapter->identify();

        $imageId = (int) $request->request->get('image_id');
        $quoteId = (int) $request->request->get('quote_id');
        $contentCategory = (int) $request->request->get('content_category');

        $logo = $request->request->get('logo_id');
        if (!is_null($logo))
            $logo = (int)($logo);

        $screens = (array) $request->request->get('screens');
        $decay_percent = (int) $request->request->get('decay_percent');
        $probability = (float) $request->request->get('probability');
        $decay_start = (string) $request->request->get('decay_start');
        $decay_end = (string) $request->request->get('decay_end');
        $id = (int) $request->attributes->get('id');
        $status = ($request->request->get('approve') === '1') ? Entity\Media::STATUS_APPROVED : Entity\Media::STATUS_PENDING;

        $this->slideManagement->update($id, $imageId, $quoteId, $contentCategory, $logo, $screens, $decay_percent, $probability, $decay_start, $decay_end, $status, $identity);
    }

    public function postSlideDelete($request)
    {
        $id = (int) $request->attributes->get('id');

        $this->slideManagement->delete($id);
    }
}
