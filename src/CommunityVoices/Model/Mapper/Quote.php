<?php

namespace CommunityVoices\Model\Mapper;

use PDO;
use CommunityVoices\Model\Entity;

class Quote extends Media
{
    /**
     * @uses Quote::fetchById
     */
    public function fetch(Entity\Media $quote)
    {
        return $this->fetchById($quote);
    }

    /**
     * Fetches a Quote entity by the ID assigned on the instance. If the instance ID
     * isn't fond, the ID is overwriten as null.
     *
     * @param  Media $quote Quote entity to fetch & map
     */
    private function fetchById(Entity\Media $quote)
    {
        $query = "SELECT
                        parent.id                       AS id,
                        parent.added_by                 AS addedBy,
                        parent.date_created             AS dateCreated,
                        CAST(parent.type AS UNSIGNED)   AS type,
                        CAST(parent.status AS UNSIGNED) AS status,
                        child.text                      AS text,
                        child.attribution               AS attribution,
                        child.date_recorded             AS dateRecorded,
                        child.public_document_link      AS publicDocumentLink,
                        child.source_document_link      AS sourceDocumentLink
                    FROM
                        `community-voices_media` parent
                    JOIN
                        `community-voices_quotes` child
                        ON parent.id = child.media_id
                    WHERE
                        parent.id = :id";

        $statement = $this->conn->prepare($query);

        $statement->bindValue(':id', $quote->getId());

        $statement->execute();

        $results = $statement->fetch(PDO::FETCH_ASSOC);

        if ($results) {
            $convertedParams = $this->convertRelations(
                $this->relations,
                $results
            );

            $this->populateEntity($quote, array_merge($results, $convertedParams));
        } else {
            $quote->setId(null);
        }
    }

    /**
     * Save a Quote entity to database by either: updating a current record if
     * an ID exists or creating a new record.
     *
     * @param Quote instance to save to database
     */
    public function save(Entity\Media $quote)
    {
        if ($quote->getId()) {
            $this->update($quote);
            return ;
        }

        $this->create($quote);
    }

    protected function update(Entity\Media $quote)
    {
        parent::update($quote);

        $query = "UPDATE
                        `community-voices_quotes`
                    SET
                        text = :text,
                        attribution = :attribution,
                        date_recorded = :date_recorded,
                        public_document_link = :public_document_link,
                        source_document_link = :source_document_link
                    WHERE
                        media_id = :media_id";

        $statement = $this->conn->prepare($query);

        $statement->bindValue(':media_id', $quote->getId());
        $statement->bindValue(':text', $quote->getText());
        $statement->bindValue(':attribution', $quote->getAttribution());
        $statement->bindValue(':date_recorded', $quote->getDateRecorded());
        $statement->bindValue(':public_document_link', $quote->getPublicDocumentLink());
        $statement->bindValue(':source_document_link', $quote->getSourceDocumentLink());

        $statement->execute();
    }

    protected function create(Entity\Media $quote)
    {
        parent::create($quote);

        $query = "INSERT INTO
                        `community-voices_quotes`
                        (media_id, text, attribution, date_recorded, public_document_link,
                            source_document_link)
                    VALUES
                        (:media_id, :text, :attribution, :date_recorded, :public_document_link,
                            :source_document_link)";

        $statement = $this->conn->prepare($query);

        $statement->bindValue(':media_id', $quote->getId());
        $statement->bindValue(':text', $quote->getText());
        $statement->bindValue(':attribution', $quote->getAttribution());
        $statement->bindValue(':date_recorded', $quote->getDateRecorded());
        $statement->bindValue(':public_document_link', $quote->getPublicDocumentLink());
        $statement->bindValue(':source_document_link', $quote->getSourceDocumentLink());

        $statement->execute();
    }

    public function delete(Entity\Media $quote)
    {
        parent::delete($quote); //deletion cascades
    }
}
