<?php

declare(strict_types=1);

namespace SchemaTransformer\Transforms\Event\WPHeadLessEvents\Mappers\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\CoversClass;
use Municipio\Schema\Schema;
use SchemaTransformer\Transforms\Event\WPHeadLessEvents\Mappers\MapLocation;
use SchemaTransformer\Transforms\Event\WPHeadLessEvents\WPHeadlessEventTransform;
use SchemaTransformer\Transforms\Event\WPHeadLessEvents\Mappers\Tests\TestHelper;

#[CoversClass(MapLocation::class)]
final class MapLocationTest extends TestCase
{
    #[TestDox('event::location is taken from an embedded reusable place, with event specific details')]
    public function testReusablePlace()
    {
        $event = (new MapLocation())->map(Schema::event(), [
            'acf' => ['eventPlace' => 7, 'locationDetails' => 'Konferensrum 1'],
            '_embedded' => ['acf:term' => [
                ['id' => 3, 'taxonomy' => 'organization', 'name' => 'Org'],
                ['id' => 7, 'taxonomy' => 'event_place', 'name' => 'Medborgarhuset', 'place' => [
                    '@type' => 'Place', 'name' => 'Medborgarhuset', 'address' => 'Kyrkogatan 1, Eslöv',
                    'latitude' => 55.8346, 'longitude' => 13.3028,
                ]],
            ]],
        ]);
        $place = $event->toArray()['location'][0];
        self::assertSame('Medborgarhuset', $place['name']);
        self::assertSame('Kyrkogatan 1, Eslöv', $place['address']);
        self::assertSame(55.8346, $place['latitude']);
        self::assertSame('Konferensrum 1', $place['description']);
    }

    #[TestDox('event::location falls back to inline fields when no reusable place is embedded')]
    public function testFallsBackToInline()
    {
        $event = (new MapLocation())->map(Schema::event(), ['acf' => [
            'locationName' => 'Dunkers', 'locationAddress' => ['address' => 'Kungsgatan', 'lat' => 1.5, 'lng' => 2.5],
        ]]);
        self::assertSame('Dunkers', $event->toArray()['location'][0]['name']);
    }

    #[TestDox('event::location is constructed from acf.locationName, acf.locationAddress')]
    public function testItWorks()
    {
        (new TestHelper())->expectMapperToConvertSourceTo(
            new MapLocation(),
            '{
                "acf": {
                    "locationName": "Dunkers kulturhus",
                    "locationAddress": {
                        "address": "Dunkers kulturhus, Kungsgatan, Helsingborg, Sverige",
                        "lat": 56.0478422,
                        "lng": 12.6890694,
                        "zoom": 14,
                        "place_id": "ChIJhX209zMyUkYR23v1_qmcpyc",
                        "street_number": 11,
                        "street_name": "Kungsgatan",
                        "city": "Helsingborg",
                        "state": "Skåne län",
                        "post_code": "252 21",
                        "country": "Sverige",
                        "country_short": "SE"
                    }     
                }
            }',
            Schema::event()->location([Schema::place()
                ->name('Dunkers kulturhus')
                ->address('Dunkers kulturhus, Kungsgatan, Helsingborg, Sverige')
                ->latitude(56.0478422)
                ->longitude(12.6890694)
            ])
        );
    }

    #[TestDox('event::location[] when missing acf.locationAddress.name')]
    public function testMissingName()
    {
        (new TestHelper())->expectMapperToConvertSourceTo(
            new MapLocation(),
            '{
                "acf": {
                    "locationName": null,
                    "locationAddress": {
                        "address": "Dunkers kulturhus, Kungsgatan, Helsingborg, Sverige",
                        "lat": 56.0478422,
                        "lng": 12.6890694,
                        "zoom": 14,
                        "place_id": "ChIJhX209zMyUkYR23v1_qmcpyc",
                        "street_number": 11,
                        "street_name": "Kungsgatan",
                        "city": "Helsingborg",
                        "state": "Skåne län",
                        "post_code": "252 21",
                        "country": "Sverige",
                        "country_short": "SE"
                    }
                }
            }',
            Schema::event()->location([])
        );
    }

    #[TestDox('event::location[] when missing acf.locationAddress')]
    public function testMissing()
    {
        (new TestHelper())->expectMapperToConvertSourceTo(
            new MapLocation(),
            '{
                "id": 123
            }',
            Schema::event()->location([])
        );
    }
}
