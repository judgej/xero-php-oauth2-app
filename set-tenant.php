<?php

/**
 * Set the current tenant.
 * Use: set-tenant.php?tenant-id={tenant-id}
 */

use GuzzleHttp\Psr7\ServerRequest;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\Api\IdentityApi;
use GuzzleHttp\Client;

include __DIR__ . '/vendor/autoload.php';

// Just to avoid messing around with globals.

$serverRequest = ServerRequest::fromGlobals();
$queryParams = $serverRequest->getQueryParams();

$tenantId = $queryParams['tenant-id'] ?? null;

if (empty($tenantId)) {
    echo 'Missing parameter tenant-id';
    exit;
}

$storage = new StorageClass();

if ($storage->getToken() === null) {
    echo 'No active access token. Please log in first.';
    exit;
}

// Check that the tenant exists, using the list of connections we saved
// when authorising.

$filtered = $tenantIds = array_filter($storage->getConnections(), function ($item) use ($tenantId) {
    return $item->getTenantId() === $tenantId;
});

if (empty($filtered)) {
    echo 'Access token is not connected to this tenant';
    exit;
}

$storage->setXeroTenantId($tenantId);

header('Location: get.php');
