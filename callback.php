<?php

/**
 * Handle the return from Xero.
 */

use Firebase\JWT\JWT;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\Api\IdentityApi;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\ServerRequest;
use Calcinai\OAuth2\Client\Provider\Xero as XeroProvider;
use Dotenv\Dotenv;

ini_set('display_errors', 'On');
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$clientId = getenv('CLIENT_ID');
$clientSecret = getenv('CLIENT_SECRET');
$redirectUri = getenv('REDIRECT_URI');

// Storage Class uses sessions for storing token > extend to your DB of choice
$storage = new StorageClass();

// Put all session access through a class for visibility.
$session = new SessionClass();

$provider = new XeroProvider([
    'clientId'      => $clientId,
    'clientSecret'  => $clientSecret,
    'redirectUri'   => $redirectUri,
]);

// Just to avoid messing around with globals.
$serverRequest = ServerRequest::fromGlobals();
$queryParams = $serverRequest->getQueryParams();

$code = $queryParams['code'] ?? null;
$state = $queryParams['state'] ?? null;
$oauthError = $queryParams['error'] ?? null;

if (! isset($code)) {
    // If we don't have an authorization code then something has gone wrong.

    $errorMessage =  sprintf('Failed with oauth_error:  %s', $oauthError);

} elseif (empty($state) || ($state !== $session->getState())) {
    // No state was provided, or the state does not match what we are expecting.

    $errorMessage = 'Invalid or missing state';
} else {
    try {
        // Try to get an access token using the authorization code grant.
        // This returned access token includes the ID token, so don't let
        // the name confuse you.

        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $code
        ]);

        // Unwrap the access token JWT claims as we are interested in one or two
        // of the custom claims.

        list($header, $body, $crypto) = explode('.', $accessToken->getToken());
        $accessTokenClaims = JWT::jsonDecode(JWT::urlsafeB64Decode($body));

        $storage->setAuthenticationEventId($accessTokenClaims->authentication_event_id);

        $resourceOwner = $provider->getResourceOwner($accessToken);

        // The camel case or snake case will depend on whether the resource owner
        // is an identityApi Connection or a provider XeroTennant.

        $resourceOwnerAuthTime = $resourceOwner->authTime ?? $resourceOwner->auth_time;

        $storage->setUserDetails([
            'givenName' => $resourceOwner->givenName ?? $resourceOwner->given_name,
            'familyName' => $resourceOwner->familyName ?? $resourceOwner->family_name,
            'email' => $resourceOwner->email,
            'preferredUsername' => $resourceOwner->preferredUsername ?? $resourceOwner->preferred_username,
            'xeroUserid' => $resourceOwner->xeroUserid ?? $resourceOwner->xero_userid,
            'subject' => $resourceOwner->sub,
            'authTime' => $resourceOwnerAuthTime,
            'authTimeFormatted' => (new DateTime())->setTimestamp($resourceOwnerAuthTime)->format(\DATE_RSS),
            'expires' => $resourceOwner->exp,
            'expiresFormatted' => (new DateTime())->setTimestamp($resourceOwner->exp)->format(\DATE_RSS),
            'issuedAt' => $resourceOwner->iat,
            'issuedAtFormatted' => (new DateTime())->setTimestamp($resourceOwner->iat)->format(\DATE_RSS),
        ]);

        $storage->setScope($accessToken->getValues()['scope']);

        // Use the authentication to get the connected tenant details.

        $config = Configuration::getDefaultConfiguration()
            ->setAccessToken((string)$accessToken->getToken());

        $identityApi = new IdentityApi(new Client(), $config);

        // Save the access token, along with its expiry time and refresh token.
        // This is all that is needed to be authenticated and authorised (scopes are included).

        $storage->setAccessToken(
            $accessToken->getToken(),
            $accessToken->getExpires(),
            $accessToken->getRefreshToken(),
        );

        // Get Array of connections (to tenants).
        // The properties of these connections are snake_case or
        // getCamelCase getters.

        $connections = $identityApi->getConnections();

        // A list of just the new connections made can be requested like this.
        // An empty list will be returned if no new tenants are connected.

        $newConnections = $identityApi->getConnections([
            'authEventId' => $storage->getAuthenticationEventId(),
        ]);

        // Or get the tenants from the Xero provider.
        // The properties of these tenants are camelCase.

        $tenants = $provider->getTenants($accessToken);

        $newTenants = $provider->getTenants($accessToken, [
            'authEventId' => $storage->getAuthenticationEventId(),
        ]);

        // Store the tenants as a list the user can switch between.

        //$storage->setConnections($connections);
        $storage->setConnections($tenants);

        // Set the active tenant to just be the first in the list.

        $session->setTenantId(reset($connections)->getTenantId());

    } catch (IdentityProviderException $e) {
        // Failed to get the access token or user details.

        $errorMessage = sprintf('IdentityProviderException failed with error: %s', $e->getMessage());
    }
}

// The state is a single-use CSRF token; we no longer need it at this point.

$session->clearState();

// TODO: display the error or the redirect.
// We could show the identity token details here too, and not automatically redirect.
// Also could show how we detect whether a new tenant has been added or not, and maybe
// list the tenants currently connected.

$headerLinks = <<<HEADERLINKS
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="http://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.11/handlebars.min.js"  crossorigin="anonymous"></script>
HEADERLINKS;
?>
<?php if (! empty($errorMessage)) { ?>
    <html>
        <head>
            <title>My Xero App - Authentication Error</title>
            <?php echo $headerLinks; ?>
        </head>
        <body>
            <div class="container">
                <h2>Authorisation or Authentication Error</h2>

                <p>
                    <?php echo htmlspecialchars($errorMessage); ?>
                </p>
                <p>
                    <a href="/">Try again</a>
                </p>
            </div>
        </body>
    </html>
<?php } else { ?>
    <html>
        <head>
            <title>My Xero App</title>
            <?php echo $headerLinks; ?>
        </head>
        <body>
            <div class="container">
                <h2>API</h2>

                <p><a href="get.php">Try out the API here</a></p>

                <h2>Resource Owner details</h2>

                <table class="table">
                    <tr>
                        <th>Property</th>
                        <th>Value</th>
                    </tr>
                    <?php foreach ($storage->getUserDetails() as $property => $value) { ?>
                        <tr>
                            <td><?php echo $property; ?></td>
                            <td><?php echo htmlspecialchars($value); ?></td>
                        </tr>
                    <?php } ?>
                </table>

                <h2>Current Scope</h2>

                <table class="table">
                    <?php foreach ($storage->getScope() as $value) { ?>
                        <tr>
                            <td><?php echo $value; ?></td>
                        </tr>
                    <?php } ?>
                </table>

                <h2>Connected Tenants</h2>

                <table class="table">
                    <tr>
                        <th>Type</th>
                        <th>Name</th>
                        <th>First Connected Time</th>
                        <th>Last Connected Time</th>
                    </tr>
                    <?php foreach ($storage->getConnections() as $connection) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($connection->tenantType); ?></td>
                            <td><?php echo htmlspecialchars($connection->tenantName); ?></td>
                            <td>
                                <?php echo $connection->createdDateUtc->format(\DATE_RSS); ?>
                                <?php echo ($connection->authEventId === $storage->getAuthenticationEventId()
                                    ? ' <strong>[ADDED THIS AUTH]</strong>'
                                    : '') ?>
                            </td>
                            <td>
                                <span title="Event: <?php echo htmlspecialchars($connection->authEventId); ?>">
                                    <?php echo $connection->updatedDateUtc->format(\DATE_RSS); ?>
                                </span>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </body>
    </html>
<?php } ?>
