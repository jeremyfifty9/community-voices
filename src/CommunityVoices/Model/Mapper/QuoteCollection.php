<?php

namespace CommunityVoices\Model\Mapper;

use PDO;
use InvalidArgumentException;
use CommunityVoices\Model\Component\DataMapper;
use CommunityVoices\Model\Entity;

class QuoteCollection extends DataMapper
{
    public function attributions(\stdClass $container)
    {
        $attributions = [];
        foreach ($this->conn->query('SELECT DISTINCT attribution FROM `community-voices_quotes` WHERE attribution != "" ORDER BY attribution ASC') as $row) {
            $obj = new \stdClass();
            $obj->attribution = $row['attribution'];
            $attributions[] = $obj;
        }
        $container->attributionCollection = $attributions;
    }

    public function fetch(Entity\QuoteCollection $quoteCollection, string $order_str = '', $only_unused = '', $search = '', $tags = null, $attributions = null, int $limit = 1, int $offset = 0)
    {
        /**
         * @todo Fetch mecnahism should determine the general filter type to use,
         * then direct the fetch
         */

        if ($quoteCollection->getFilterType() === Entity\QuoteCollection::FILTER_TYPE_BOUNDARY) {
            return $this->fetchBoundaries($quoteCollection);
        } else {
            switch ($order_str) {
                case 'date_recorded_asc':
                    $sort = 'date_recorded';
                    $order = 'ASC';
                    break;
                case 'date_recorded_desc':
                    $sort = 'date_recorded';
                    $order = 'DESC';
                    break;
                case 'attribution_desc':
                    $sort = 'attribution';
                    $order = 'DESC';
                    break;
                default:
                    $sort = 'date_recorded';
                    $order = 'DESC';
                    break;
            }

            $this->fetchAll($quoteCollection, $only_unused, $search, $tags, $attributions, $limit, $offset, $sort, $order);
        }
    }

    /**
     * Populates collection with the previous and next slide
     */
    public function fetchBoundaries(Entity\QuoteCollection $quoteCollection)
    {
        $query = "SELECT
                        quote.media_id              AS id
                    FROM `community-voices_quotes` quote
                    WHERE
                        (quote.media_id = (SELECT MAX(media_id) FROM `community-voices_quotes` WHERE media_id < :anchorQuoteId)
                        OR quote.media_id = (SELECT MIN(media_id) FROM `community-voices_quotes` WHERE media_id > :anchorQuoteId2))";

        $statement = $this->conn->prepare($query);

        $anchorQuoteId = $quoteCollection->getAnchorQuote()->getId();

        $statement->bindValue(':anchorQuoteId', $anchorQuoteId);
        $statement->bindValue(':anchorQuoteId2', $anchorQuoteId);

        $statement->execute();

        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $key => $entry) {
            $quoteCollection->addEntityFromParams($entry);
        }
    }

    private function fetchAll(Entity\QuoteCollection $quoteCollection, $only_unused, $search, $tags, $attributions, int $limit, int $offset, $sort = 'date_recorded', $order = 'DESC')
    {
        $params = [];
        if ($search == '') {
            $search_query = '';
        } else {
            $search_query = 'AND (text LIKE ? OR attribution LIKE ? OR sub_attribution LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($tags == null) {
            $tag_query = '';
        } else {
            $tag_query = 'AND id IN (SELECT media_id FROM `community-voices_media-group-map` WHERE group_id IN ('.implode(',', array_map('intval', $tags)).'))';
        }
        if ($attributions == null) {
            $attribution_query = '';
        } else {
            $attribution_query = 'AND attribution IN ('.rtrim(str_repeat('?,', count($attributions)), ',').')';
            foreach ($attributions as $param) {
                $params[] = $param;
            }
        }
        $only_unused_query = '';
        if ($only_unused) {
            $only_unused_query = 'AND media_id NOT IN (SELECT quote_id FROM `community-voices_slides` WHERE quote_id IS NOT NULL)';
        }
        $query = " 	SELECT SQL_CALC_FOUND_ROWS
						media.id 						AS id,
						media.added_by 					AS addedBy,
						media.date_created 				AS dateCreated,
                        CAST(media.type AS UNSIGNED)    AS type,
                        CAST(media.status AS UNSIGNED)  AS status,
                        quote.text                      AS text,
                        quote.attribution               AS attribution,
                        quote.sub_attribution           AS subAttribution,
                        quote.date_recorded             AS dateRecorded,
                        quote.public_document_link      AS publicDocumentLink,
                        quote.source_document_link      AS sourceDocumentLink
					FROM
						`community-voices_media` media
					INNER JOIN
						`community-voices_quotes` quote
						ON media.id = quote.media_id
		          	WHERE 1
                    {$search_query} {$tag_query} {$attribution_query} {$only_unused_query}
		         "
                 . $this->query_prep($quoteCollection->status, "media.status")
                 . $this->query_prep($quoteCollection->creators, "media.added_by")
                 . " ORDER BY quote.{$sort} {$order}"
                 . " LIMIT {$offset}, {$limit}";

        $statement = $this->conn->prepare($query);

        $statement->execute($params);

        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $key => $entry) {
            $quoteCollection->addEntityFromParams($entry);
        }
    }
}
