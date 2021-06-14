<?php

namespace CommunityVoices\App\Website\Finder;

use Jenssegers\ImageHash;
use \PDO;

class ImageMatcher
{
    private $hasher;
    private $pdo;

    public function __construct(
        ImageHash\ImageHash $hasher,
        PDO $pdo
    ) {
        $this->hasher = $hasher;
        $this->pdo = $pdo;
    }

    /**
     * Returns a collection of Images that are close to the inputted image
     *
     * @param  String $fn filepath to image
     * @return Array images that match, along with Hamming distance
     */
    public function findCloseMatches($fn)
    {
        $generatedHash = $this->hash($fn);

        $query = "SELECT *,
                        BIT_COUNT(perceptual_hash ^ CAST(CONV(:hash, 16, 10) AS unsigned)) AS d,
                        HEX(perceptual_hash) AS conv_hash
                    FROM `community-voices_images`
                    WHERE perceptual_hash IS NOT NULL
                    ORDER BY d
                    LIMIT 10";

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':hash', $generatedHash);
        $statement->execute();

        $results = $statement->fetchAll(PDO::FETCH_OBJ);

        return $results;
    }

    /**
     * @param  String $fn filepath to image
     * @return String generated hash
     */
    public function hash($fn)
    {
        return $this->hasher->hash($fn)->toHex();
    }
}
