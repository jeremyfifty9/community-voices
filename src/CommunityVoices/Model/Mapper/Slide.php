<?php

namespace CommunityVoices\Model\Mapper;

use PDO;
use CommunityVoices\Model\Component\DataMapper;
use CommunityVoices\Model\Entity as Entity;

class Slide extends Media
{
    protected static $table = '`community-voices_slides`';

    public function fetch(Entity\Media $slide)
    {
        $this->fetchById($slide);
    }

    private function fetchById(Entity\Media $slide)
    {
        $query = "SELECT    parent.id,
                            parent.added_by,
                            parent.date_created,
                            parent.type,
                            parent.status,
                            child.content_category_id as content_category,
                            child.image_id as image,
                            child.quote_id as quote,
                            child.probability,
                            child.decay_percent,
                            child.decay_start,
                            child.decay_end
                    FROM    " . parent::$table . " parent
                    JOIN    " . self::$table . " child
                    ON      parent.id = child.media_id
                    WHERE   parent.id = :id";

        $statement = $this->conn->prepare($query);

        $statement->bindValue(':id', $slide->getId());

        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $parameters = $this->convertRelationsToEntities($slide->getRelations(), $result);

            $this->applyValues($slide, $parameters);
        }
    }

    public function save(Entity\Media $slide)
    {
        if ($slide->getId()) {
            $this->update($slide);
            return ;
        }

        $this->create($slide);
    }

    protected function update(Entity\Media $slide)
    {
        parent::update($slide);

        $query = "UPDATE    " . self::$table . "
                    SET     content_category_id = :content_category_id
                            image_id = :image_id,
                            quote_id = :quote_id,
                            probability = :probability,
                            decay_percent = :decay_percent,
                            decay_start = :decay_start,
                            decay_end = :decay_end
                    WHERE   media_id = :media_id";

        $statement = $this->conn->prepare($query);

        $statement->bindValue(':media_id', $slide->getId());
        $statement->bindValue(':content_category_id', $slide->getContentCategory()->getId());
        $statement->bindValue(':image_id', $slide->getImage()->getId());
        $statement->bindValue(':quote_id', $slide->getQuote()->getId());
        $statement->bindValue(':probability', $slide->getProbability());
        $statement->bindValue(':decay_percent', $slide->getDecayPercent());
        $statement->bindValue(':decay_start', $slide->getDecayStart());
        $statement->bindValue(':decay_end', $slide->getDecayEnd());

        $statement->execute();
    }

    protected function create(Entity\Media $slide)
    {
        parent::create($slide);

        $query = "INSERT INTO   " . self::$table . "
                                (media_id, content_category_id, image_id, quote_id, probability,
                                    decay_percent, decay_start, decay_end)

                    VALUES      (:media_id, :content_category_id, :image_id, :quote_id, :probability,
                                    :decay_percent, :decay_start, :decay_end)";

        $statement = $this->conn->prepare($query);

        $statement->bindValue(':media_id', $slide->getId());
        $statement->bindValue(':content_category_id', $slide->getContentCategory()->getId());
        $statement->bindValue(':image_id', $slide->getImage()->getId());
        $statement->bindValue(':quote_id', $slide->getQuote()->getId());
        $statement->bindValue(':probability', $slide->getProbability());
        $statement->bindValue(':decay_percent', $slide->getDecayPercent());
        $statement->bindValue(':decay_start', $slide->getDecayStart());
        $statement->bindValue(':decay_end', $slide->getDecayEnd());

        $statement->execute();
    }

    public function delete(Entity\Media $slide)
    {
        parent::delete($slide); //deletion cascades
    }
}
