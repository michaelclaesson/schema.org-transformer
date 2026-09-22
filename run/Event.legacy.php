<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SchemaTransformer\IO\V2\HttpReader;
use SchemaTransformer\Loggers\TerminalLogger;
use SchemaTransformer\Paginators\GetParamPaginator;
use SchemaTransformer\Paginators\WordpressPaginator;
use SchemaTransformer\Run\Cli\PaginatorType;
use SchemaTransformer\Run\Factories\StorageFactory;
use SchemaTransformer\Storage\TypesenseStorage\TypesenseCollection;
use SchemaTransformer\Transforms\Event\WPLegacyEvents\WPLegacyEventTransform;
use SchemaTransformer\Webhooks\Webhooks;

$id            = 'Event.legacy';
$logger        = new TerminalLogger($id);
$lockRunner    = new \SchemaTransformer\LockRunner\LockRunner($id, $logger);
$cliOptions    = new \SchemaTransformer\Run\Cli\Options();
$paginatorType = $cliOptions->getPaginatorType();

$lockRunner->lock();

$httpReaderPath = getenv('WP_LEGACY_EVENTS_API_URL')  . '&start_date=' . date('Y-m-d', strtotime('-1 month'));

// GetParamPaginator treats a URL without an explicit page parameter as
// "page 0", so it re-requests page 1 before moving on to page 2. Passing
// page=1 up front avoids that redundant first request.
if ($paginatorType === PaginatorType::GetParam) {
    $httpReaderPath .= '&page=1';
}

$paginator = match ($paginatorType) {
    PaginatorType::Wordpress => new WordpressPaginator(),
    PaginatorType::GetParam  => new GetParamPaginator('page'),
};

$transformer = new WPLegacyEventTransform('L');
$reader      = new HttpReader($httpReaderPath, $transformer, [ 'Content-Type' => 'application/json', 'Accept' => 'application/json', ], $paginator, $logger);
$storage     = StorageFactory::create(
    target: $cliOptions->getTarget(),
    logger: $logger,
    options: [
        'collection'            => TypesenseCollection::Event,
        'collectionClearFilter' => ['filter_by' => 'x-created-by:=municipio://schema.org-transformer/wp-legacy'],
    ],
);

$storage->store($reader->read());

(new Webhooks(logger: $logger))->trigger(getenv('WP_LEGACY_EVENTS_MONITOR_URL'));
