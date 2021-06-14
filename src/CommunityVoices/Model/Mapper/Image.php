<?php

namespace CommunityVoices\Model\Mapper;

use PDO;
use CommunityVoices\Model\Entity;

class Image extends Media
{
    public function relatedSlideId(int $image_id)
    {
        $query = "SELECT media_id FROM `community-voices_slides` WHERE image_id = :id";
        $statement = $this->conn->prepare($query);
        $statement->bindValue(':id', $image_id);
        $statement->execute();
        return $statement->fetchColumn();
    }

    public function prevImage(int $image_id)
    {
        $query = "SELECT media_id FROM `community-voices_images` WHERE media_id < :id ORDER BY media_id DESC LIMIT 1";
        $statement = $this->conn->prepare($query);
        $statement->bindValue(':id', $image_id);
        $statement->execute();
        return $statement->fetchColumn();
    }

    public function nextImage(int $image_id)
    {
        $query = "SELECT media_id FROM `community-voices_images` WHERE media_id > :id ORDER BY media_id ASC LIMIT 1";
        $statement = $this->conn->prepare($query);
        $statement->bindValue(':id', $image_id);
        $statement->execute();
        return $statement->fetchColumn();
    }

    /**
     * @uses Image::fetchById
     */
    public function fetch(Entity\Media $image)
    {
        $this->fetchById($image);
    }

    /**
     * Fetches an Image entity by the ID assigned on the instance. If the
     * instance ID isn't found, the ID is overwriten as null.
     *
     * @param Media $image Image entity to fetch & map
     */
    private function fetchById(Entity\Media $image)
    {
        $query = "SELECT
                        parent.id                       AS id,
                        parent.added_by                 AS addedBy,
                        CAST(parent.type AS UNSIGNED)   AS type,
                        CAST(parent.status AS UNSIGNED) AS status,
                        child.date_taken                AS dateTaken,
                        child.filename                  AS filename,
                        child.generated_tags            AS generatedTags,
                        child.title                     AS title,
                        child.description               AS description,
                        child.date_taken                AS dateTaken,
                        child.photographer              AS photographer,
                        child.organization              AS organization,
                        CONCAT(child.crop_x, ',', child.crop_y, ',', child.crop_height, ',', child.crop_width) AS cropRect
                    FROM
                        `community-voices_media` parent
                    JOIN
                        `community-voices_images` child
                        ON parent.id = child.media_id
                    WHERE
                        parent.id = :id";

        $statement = $this->conn->prepare($query);

        $statement->bindValue(':id', $image->getId());

        $statement->execute();

        $results = $statement->fetch(PDO::FETCH_ASSOC);

        if ($results) {
            $convertedParams = $this->convertRelations($this->relations, $results);

            $this->populateEntity($image, array_merge($results, $convertedParams));
        } else {
            $image->setId(null);
        }
    }

    /**
     * Save an Image entity to the database by either: updating a current record
     * if the ID exists or creating a new record.
     *
     * @param Media $image Image entity to save to database.
     */
    public function save(Entity\Media $image)
    {
        if ($image->getId()) {
            $this->update($image);
            return;
        }

        $this->create($image);
    }

    protected function update(Entity\Media $image)
    {
        parent::update($image);
        $rect = $image->getCropRect();
        $update_cropping = ($rect['x'] !== null && $rect['y'] !== null && $rect['height'] !== null && $rect['width'] !== null);
        $query = ($update_cropping) ? "UPDATE
                        `community-voices_images`
                    SET
                        filename = :filename,
                        generated_tags = :generated_tags,
                        title = :title,
                        description = :description,
                        date_taken = :date_taken,
                        photographer = :photographer,
                        organization = :organization,
                        crop_x = :crop_x,
                        crop_y = :crop_y,
                        crop_height = :crop_height,
                        crop_width = :crop_width
                    WHERE
                        media_id = :media_id" :
                    "UPDATE
                        `community-voices_images`
                    SET
                        filename = :filename,
                        generated_tags = :generated_tags,
                        title = :title,
                        description = :description,
                        date_taken = :date_taken,
                        photographer = :photographer,
                        organization = :organization
                    WHERE
                        media_id = :media_id";

        $statement = $this->conn->prepare($query);

        $statement->bindValue(':media_id', $image->getId());
        $statement->bindValue(':filename', $image->getFilename());
        $statement->bindValue(':generated_tags', $image->getGeneratedTags());
        $statement->bindValue(':title', $image->getTitle());
        $statement->bindValue(':description', $image->getDescription());
        $statement->bindValue(':date_taken', date('Y-m-d H:i:s', $image->getDateTaken()));
        $statement->bindValue(':photographer', $image->getPhotographer());
        $statement->bindValue(':organization', $image->getOrganization());
        if ($update_cropping) {
            $rect = array_map('intval', $rect);
            $statement->bindValue(':crop_x', $rect['x']);
            $statement->bindValue(':crop_y', $rect['y']);
            $statement->bindValue(':crop_height', $rect['height']);
            $statement->bindValue(':crop_width', $rect['width']);
        }

        $statement->execute();
    }

    protected function create(Entity\Media $image)
    {
        parent::create($image);

        $query = "INSERT INTO
                        `community-voices_images`
                        (media_id, filename, generated_tags, title, description,
                            date_taken, photographer, organization)

                    VALUES
                        (:media_id, :filename, :generated_tags, :title, :description,
                            :date_taken, :photographer, :organization)";

        $statement = $this->conn->prepare($query);

        $statement->bindValue(':media_id', $image->getId());
        $statement->bindValue(':filename', $image->getFilename());
        $statement->bindValue(':generated_tags', $image->getGeneratedTags());
        $statement->bindValue(':title', $image->getTitle());
        $statement->bindValue(':description', $image->getDescription());
        $statement->bindValue(':date_taken', date('Y-m-d H:i:s', $image->getDateTaken()));
        $statement->bindValue(':photographer', $image->getPhotographer());
        $statement->bindValue(':organization', $image->getOrganization());

        $lastImageId = intval($this->conn->lastInsertId());

        $statement->execute();

        if($image->getMetaData() && $this->getMetaDataFields()) { 
            // only update metadata if a) user has wrong metadata migration script (see /migrate/scripts/createNewImageBatchUploadFields.php)
            // and b) user has passed in associated metadata into request

            $metaDataId = $this->setMetaDataFields($image->getMetaData());
            $linkMdTable = "UPDATE `community-voices_images`
                            SET metadata_id = :metadata_id
                            WHERE media_id = :last_image_id";

            $statement = $this->conn->prepare($linkMdTable);
            $statement->bindValue(':metadata_id', $metaDataId);
            $statement->bindValue(':last_image_id', $lastImageId);

            $statement->execute();
        }
    }

    public function getMetaDataFields() {
        $query = "SELECT `COLUMN_NAME` 
        FROM `INFORMATION_SCHEMA`.`COLUMNS` 
        WHERE `TABLE_SCHEMA`='community_voices' 
            AND `TABLE_NAME`='community-voices_image_metadata'";
        $statement = $this->conn->prepare($query);
        $statement->execute();
        $queryResult = $statement->fetchAll();
        
        $allColumnsIncludingId = array_map(function($el) {
           return $el['COLUMN_NAME'];
        },$queryResult);

        if (count($allColumnsIncludingId)==0) { // we haven't run all of the migrations we have needed to create image metadata table!
            return false;
        }

        $allColumnsExcludingId = array_slice($allColumnsIncludingId,1);
        return $allColumnsExcludingId;
    }

    private function setMetaDataFields($metaData) {
        $fieldsInserted = implode(', ', array_keys($metaData));

        $columnsArrayWithBackticks = array_map(function($val){ // allow for spaces
            return "`" . $val . "`";
        },array_keys($metaData));
        $fieldsInserted = implode(', ', $columnsArrayWithBackticks);
        
        $valuesArrayWithSingleQuotes = array_map(function($val){
            return "'" . $val . "'";
        },array_values($metaData));
        $valuesInserted = implode(', ', $valuesArrayWithSingleQuotes);
        $query = "INSERT INTO `community-voices_image_metadata` ($fieldsInserted) VALUES ($valuesInserted)";
        $this->conn->exec($query);

        $idToLink = $this->conn->lastInsertId();
        return intval($idToLink);
    }
    
}
