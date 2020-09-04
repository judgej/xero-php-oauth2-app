<?php

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\Api\IdentityApi;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\ServerRequest;
use Calcinai\OAuth2\Client\Provider\Xero as XeroProvider;
use XeroAPI\XeroPHP\JWTClaims;

ini_set('display_errors', 'On');
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$clientId = getenv('CLIENT_ID');
$clientSecret = getenv('CLIENT_SECRET');
$redirectUri = getenv('REDIRECT_URI');

// Storage Class uses sessions for storing token > extend to your DB of choice
$storage = new StorageClass();

$session = new SessionClass();

$provider = new XeroProvider([
    'clientId'                => $clientId,
    'clientSecret'            => $clientSecret,
    'redirectUri'             => $redirectUri,
]);

// Just to avoid messing around with globals.
$serverRequest = ServerRequest::fromGlobals();
$queryParams = $serverRequest->getQueryParams();

$code = $queryParams['code'] ?? null;
$state = $queryParams['state'] ?? null;
$oauthError = $queryParams['error'] ?? null;

if (! isset($code)) {
    // If we don't have an authorization code then get one by starting from the beginning.
    // TODO: treat this like any other error.

    header('Location: index.php?error=true&oauth_error=' . $oauthError);

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($state) || ($state !== $session->getState())) {
    echo 'Invalid State';
    $session->clearState();
} else {
    try {
        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $code
        ]);

        $jwt = new JWTClaims();
        $jwt->setTokenId($accessToken->getValues()['id_token']);
        $jwt->decode();

        $config = Configuration::getDefaultConfiguration()->setAccessToken( (string)$accessToken->getToken() );

        $identityApi = new IdentityApi(new Client(), $config);

        // Get Array of connections (to tenants).

        $connections = $identityApi->getConnections();

        // Store the tenants as a list the user can switch between.

        $storage->setConnections($connections);

        // Save my token, expiration and the first tenant_id
        $storage->setToken(
            $accessToken->getToken(),
            $accessToken->getExpires(),
            $connections[0]->getTenantId(),
            $accessToken->getRefreshToken(),
            $accessToken->getValues()['id_token']
        );

        header('Location: get.php');

    } catch (IdentityProviderException $e) {
        // Failed to get the access token or user details.

        echo sprintf('Failed with error: %s', $e->getMessage());
    }

    // The state is a single-use CSRF token, so we no longer need it.

    $session->clearState();
}

// TODO: display the error or the redirect.
// We could show the identity token details here too, and not automatically redirect.
// Also could show how we detect whether a new tenant has been added or not, and maybe
// list the tenants currently connected.

?>
<html>
    <head>
        <title>My Xero App</title>
    </head>
    <body>      
        <p>
            <a href="get.php">Try out the API here</a>
        </p>
    </body>
</html>
