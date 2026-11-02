<?php

declare(strict_types=1);

namespace SchemaTransformer\Transforms\Event\WPHeadLessEvents\Mappers;

use Municipio\Schema\Event;
use Municipio\Schema\Schema;
use SchemaTransformer\Transforms\Event\WPHeadLessEvents\Mappers\AbstractWPHeadlessEventMapper;

class MapLocation extends AbstractWPHeadlessEventMapper
{
    public function __construct()
    {
        parent::__construct();
    }

    private const PLACE_TAXONOMY = 'event_place';

    public function map(Event $event, array $data): Event
    {
        $place = $this->mapReusablePlace($data) ?? $this->mapInlinePlace($data);

        if ($place === null) {
            return $event->location([]);
        }

        // Details belong to this event's location, never to the shared place name.
        $details = $data['acf']['locationDetails'] ?? null;
        if (is_string($details) && trim($details) !== '') {
            $place->description($details);
        }

        return $event->location([$place]);
    }

    /**
     * Reusable places are terms in the event_place taxonomy, embedded by the
     * API with a `place` field (name, address, latitude, longitude).
     */
    private function mapReusablePlace(array $data): ?\Municipio\Schema\Place
    {
        $terms = array_merge(
            $data['_embedded']['acf:term'] ?? [],
            ...($data['_embedded']['wp:term'] ?? [])
        );
        $wantedId = $data['acf']['eventPlace'] ?? null;

        foreach ($terms as $term) {
            if (
                ($term['taxonomy'] ?? null) !== self::PLACE_TAXONOMY
                || !is_array($term['place'] ?? null)
                || (is_numeric($wantedId) && isset($term['id']) && (int) $term['id'] !== (int) $wantedId)
            ) {
                continue;
            }

            $place = $term['place'];
            return Schema::place()
                ->name($place['name'] ?? $term['name'] ?? null)
                ->address($place['address'] ?? null)
                ->latitude($place['latitude'] ?? null)
                ->longitude($place['longitude'] ?? null);
        }

        return null;
    }

    private function mapInlinePlace(array $data): ?\Municipio\Schema\Place
    {
        if (empty($data['acf']['locationName'] ?? null)) {
            return null;
        }

        return Schema::place()
            ->name($data['acf']['locationName'])
            ->address($data['acf']['locationAddress']['address'] ?? null)
            ->latitude($data['acf']['locationAddress']['lat'] ?? null)
            ->longitude($data['acf']['locationAddress']['lng'] ?? null);
    }
}
