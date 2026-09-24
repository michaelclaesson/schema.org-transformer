<?php

declare(strict_types=1);

namespace SchemaTransformer\Transforms\Event\WPLegacyEvents\Mappers\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\CoversClass;
use Municipio\Schema\Schema;
use SchemaTransformer\Transforms\Event\WPLegacyEvents\Mappers\Tests\TestHelper;
use SchemaTransformer\Transforms\Event\WPLegacyEvents\Mappers\MapImage;

#[CoversClass(MapImage::class)]
final class MapImageTest extends TestCase
{
    #[TestDox('event::image is mapped from featured_media')]
    public function testMapsFeaturedMedia()
    {
        (new TestHelper())->expectMapperToConvertSourceTo(
            new MapImage(),
            '{
                "featured_media": {
                    "id": 1,
                    "alt_text": "Alt",
                    "source_url": "https://example.com/image.jpg"
                }
            }',
            Schema::event()->image([
                Schema::imageObject()->url('https://example.com/image.jpg')->description('Alt')->caption('Alt')
            ])
        );
    }

    #[TestDox('event::image is mapped from _embedded wp:featuredmedia')]
    public function testMapsEmbeddedFeaturedMedia()
    {
        (new TestHelper())->expectMapperToConvertSourceTo(
            new MapImage(),
            '{
                "_embedded": {"wp:featuredmedia": [{"source_url": "https://example.com/image.jpg", "alt_text": "Alt"}]}
            }',
            Schema::event()->image([
                Schema::imageObject()->url('https://example.com/image.jpg')->description('Alt')->caption('Alt')
            ])
        );
    }

    #[TestDox('event::image is empty when featured_media is missing')]
    public function testHandlesMissingMedia()
    {
        (new TestHelper())->expectMapperToConvertSourceTo(
            new MapImage(),
            '{"featured_media": 0}',
            Schema::event()->image([])
        );
    }
}
