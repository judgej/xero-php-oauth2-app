<?php

/**
 * Set the current tenant.
 * Use: set-tenant.php?tenant-id={tenant-id}
 */

use GuzzleHttp\Psr7\ServerRequest;

include __DIR__ . '/vendor/autoload.php';

// Just to avoid messing around with globals.

$serverRequest = ServerRequest::fromGlobals();
$queryParams = $serverRequest->getQueryParams();

$session = new SessionClass();

$tenantId = $queryParams['tenant-id'] ?? null;

if (empty($tenantId)) {
    echo 'Missing parameter tenant-id';
    exit;
}

$storage = new StorageClass();

if ($storage->getAccessToken() === null) {
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

$session->setTenantId($tenantId);

header('Location: get.php');
