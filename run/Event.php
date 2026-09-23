<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SchemaTransformer\IO\V2\HttpReader;
use SchemaTransformer\Loggers\TerminalLogger;
use SchemaTransformer\Paginators\WordpressPaginator;
use SchemaTransformer\Run\Factories\StorageFactory;
use SchemaTransformer\Storage\TypesenseStorage\TypesenseCollection;
use SchemaTransformer\Transforms\Event\WPHeadLessEvents\WPHeadlessEventTransform;
use SchemaTransformer\Webhooks\Webhooks;

$id         = 'Event';
$logger     = new TerminalLogger($id);
$lockRunner = new \SchemaTransformer\LockRunner\LockRunner($id, $logger);
$options    = new \SchemaTransformer\Run\Cli\Options();

$lockRunner->lock();

$httpReaderPath = getenv('WP_EVENTS_API_URL');
$headers        = [ 'Content-Type' => 'application/json', 'Accept' => 'application/json' ];

// The host may sit behind HTTP Basic Auth (htpasswd). Only add the header
// when both credentials are configured, since not every deployment needs it.
$apiUser = getenv('WP_EVENTS_API_USER');
$apiPass = getenv('WP_EVENTS_API_PASSWORD');
if ($apiUser !== false && $apiPass !== false) {
    $headers['Authorization'] = 'Basic ' . base64_encode($apiUser . ':' . $apiPass);
}

$transformer = new WPHeadlessEventTransform('WPH-');
$reader      = new HttpReader($httpReaderPath, $transformer, $headers, new WordpressPaginator(), $logger);
$storage     = StorageFactory::create(
    target: $options->getTarget(),
    logger: $logger,
    options: [
        'collection'            => TypesenseCollection::Event,
        'collectionClearFilter' => ['filter_by' => 'x-created-by:=municipio://schema.org-transformer/wp-headless'],
        'collectionName'        => getenv('EVENTS_TYPESENSE_COLLECTION') ?: null,
    ],
);

$storage->store($reader->read());

(new Webhooks(logger: $logger))->trigger(getenv('WP_EVENTS_MONITOR_URL'));
