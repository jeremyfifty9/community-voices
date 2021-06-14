<?php

namespace CommunityVoices\App\Api\Controller;

use CommunityVoices\Model\Component\MapperFactory;
use CommunityVoices\Model\Entity;
use CommunityVoices\Model\Service;
use CommunityVoices\Model\Exception;
use CommunityVoices\App\Api\Component;
use CommunityVoices\App\Api\Component\FileProcessor;
use Exception as GlobalException;

class Image extends Component\Controller
{
    protected $imageLookup;
    protected $imageManagement;
    protected $tagLookup;

    const ALL_FIELDS = ['url','file','title','description', 'dateTaken', 'photographer', 'organization', 'approved', 'tags'];
    
    public function __construct(
        Component\SecureContainer $secureContainer,
        Component\RecognitionAdapter $recognitionAdapter,
        Service\ImageLookup $imageLookup,
        Service\ImageManagement $imageManagement,
        Service\TagLookup $tagLookup
    ) {
        parent::__construct($secureContainer);

        $this->recognitionAdapter = $recognitionAdapter;
        $this->imageLookup = $imageLookup;
        $this->imageManagement = $imageManagement;
        $this->tagLookup = $tagLookup;
    }

    protected function sendImage($request)
    {
        $imageId = $request->attributes->get('id');

        $this->imageLookup->printById((int) $imageId);
    }

    /**
     * image lookup by id
     */
    protected function getImage($request)
    {
        $imageId = $request->attributes->get('id');

        try {
            $this->imageLookup->findById((int) $imageId);
        } catch (Exception\IdentityNotFound $e) {
            $this->send404();
        }
    }

    protected function getAllImage($request)
    {
        $search = (string) $request->query->get('search');
        $tags = $request->query->get('tags');
        $photographers = $request->query->get('photographers');
        $orgs = $request->query->get('orgs');
        $order = (string) $request->query->get('order');
        $status = $request->query->get('status');
        $status = ($status == null) ? ["approved","pending","rejected"] : explode(',', $status);

        $page = (int) $request->query->get('page');
        $page = ($page > 0) ? $page - 1 : 0; // current page, make page 0-based
        $limit = (int) $request->query->get('per_page');
        $limit = ($limit > 0) ? $limit : 15; // number of items per page
        $offset = $limit * $page;
        $only_unused = (int) $request->query->get('unused');
        $only_unused = ($only_unused === 0 || $only_unused === 1) ? $only_unused : 0;

        $this->imageLookup->findAll($page, $limit, $offset, $order, $only_unused, $search, $tags, $photographers, $orgs, $status);
    }

    protected function postImageUpload($request)
    {
        $identity = $this->recognitionAdapter->identify();

        $files = $request->files->get('file') ?? $request->request->get('url') ?? [];
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $dateTaken = $request->request->get('dateTaken');
        $photographer = $request->request->get('photographer');
        $organization = $request->request->get('organization');
        $approved = $request->request->get('approved');
        $tags = $request->request->get('tags');
        $metaData = $request->request->get('metadata') ?? [];

        $this->imageManagement->upload(
            $files,
            $title,
            $description,
            $dateTaken,
            $photographer,
            $organization,
            $identity,
            $approved,
            $tags,
            $metaData
      );
    }

    protected function postImageBatchUpload($request) 
    {
        try {
            $identity = $this->recognitionAdapter->identify();

            $files = $request->files->get('file');


            if (@count($files) != 1) {
                return false; // @TODO more graceful error handling
            }

            $fileProcessor = new Component\FileProcessor();
            $imagesAsAssociativeArray = $fileProcessor->csvToAssociativeArray($files->getPathname());

            foreach($imagesAsAssociativeArray as $image) {
                $url = $image['url'] ?? "";
                $title = $image['title'] ?? "";
                $description = $image['description'] ?? "";
                $dateTaken = $image['dateTaken'] ?? "";
                $photographer = $image['photographer'] ?? "";
                $organization = $image['organization'] ?? "";
                $approved = true; // since only admins will have access, this should always be true though this should be confirmed.

                // this section is pretty brutal LOL, this is just getting the value of all tags and changing the array structure
                // to only include id and label
                $tagsStateObserver = $this->tagLookup->findAll(true);

                $tagCollection = $tagsStateObserver->getEntry("tag")[0]->toArray()["tagCollection"];

                $labels = array_map(function($value){
                    return $value['tag']['label'];
                },$tagCollection);

                $ids = array_map(function($value){
                    return $value['tag']['id'];
                },$tagCollection);

                // get all tag fields based on a) if they have "tag" in their name and b) if their value is actually a tag    
                $allValidTagLabels = array_filter($image, function($value,$key) use ($labels) {
                    return substr($key,0,3) == 'tag' && in_array($value,$labels);
                }, ARRAY_FILTER_USE_BOTH);

                $allValidTagIds = array_values(array_map(function($tag) use ($labels,$ids){
                    return $ids[array_search($tag,$labels)];
                },$allValidTagLabels));

                $metaData = array_filter($image, function($key){
                    return ! (in_array($key,self::ALL_FIELDS) || (substr($key,0,3) == 'tag'));
                },ARRAY_FILTER_USE_KEY);

                // everything that is not a default field (in all_fields) must be metadata.

                $this->imageManagement->upload([$url],$title,$description,$dateTaken,$photographer,$organization,$identity,$approved,$allValidTagIds,$metaData);
            }   
        } catch (\Exception $e) {
            $this->send404();
        }
    }

    protected function postImageUpdate($request)
    {
        $id = (int) $request->attributes->get('id');
        if ($id === 0) {
            $id = (int) $request->request->get('id');
        }

        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $dateTaken = $request->request->get('dateTaken');
        $photographer = $request->request->get('photographer');
        $organization = $request->request->get('organization');
        $crop_x = $request->request->get('crop_x');
        $crop_y = $request->request->get('crop_y');
        $crop_width = $request->request->get('crop_width');
        $crop_height = $request->request->get('crop_height');
        $status = ($request->request->get('approve') === '1') ? Entity\Media::STATUS_APPROVED : Entity\Media::STATUS_PENDING;
        $tags = $request->request->get('tags') ?? [];

        $this->imageManagement->update(
            $id,
            $title,
            $description,
            $dateTaken,
            $photographer,
            $organization,
            ['x' => $crop_x, 'y' => $crop_y, 'width' => $crop_width, 'height' => $crop_height],
            $tags,
            $status
        );
    }

    protected function postImageDelete($request)
    {
        $id = (int) $request->attributes->get('id');

        $this->imageManagement->delete($id);
    }

    protected function postImageUnpair($request)
    {
        $image_id = (int) $request->attributes->get('image');
        $slide_id = (int) $request->attributes->get('slide');

        $this->imageManagement->unpair($image_id, $slide_id);
    }

    protected function getImageRelatedSlide($request)
    {
        $id = $request->attributes->get('id');
        $this->imageLookup->relatedSlide2($id);
    }

    protected function getImagePrevImage($request)
    {
        $id = $request->attributes->get('id');
        $this->imageLookup->prevImage2($id);
    }

    protected function getImageNextImage($request)
    {
        $id = $request->attributes->get('id');
        $this->imageLookup->nextImage2($id);
    }

    protected function postMetaDataFields($request) {

        try {
            $this->imageManagement->createNewBatchUploadFields($request->request->get('fields'));
        } catch (\Exception $e) {
            $this->send404();
        }
    }

    protected function getMetaDataFields($request) {
        $this->imageLookup->metaDataFields();
    }
}
