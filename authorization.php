<?php

/**
 * Start the OAuth 2.0 code authorisation flow.
 */

use Calcinai\OAuth2\Client\Provider\Xero as XeroProvider;
use Dotenv\Dotenv;

ini_set('display_errors', 'On');
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$clientId = getenv('CLIENT_ID');
$clientSecret = getenv('CLIENT_SECRET');
$redirectUri = getenv('REDIRECT_URI');

$session = new SessionClass();

$provider = new XeroProvider([
    'clientId'                => $clientId,
    'clientSecret'            => $clientSecret,
    'redirectUri'             => $redirectUri,
]);

$scope = getenv('SCOPE');
if (empty($scope)) {
    $scope = 'openid email profile offline_access assets projects accounting.settings accounting.transactions accounting.contacts accounting.journals.read accounting.reports.read accounting.attachments';
}

$options = [
    'scope' => [$scope],
];

// Fetch the authorization URL from the provider;
// this returns the urlAuthorize option and generates and applies any necessary parameters (e.g. state).
$authorizationUrl = $provider->getAuthorizationUrl($options);

// Get the state generated for you and store it to the session.
$session->setState($provider->getState());

// Redirect the user to the authorization URL.
header('Location: ' . $authorizationUrl);
?>
<html>
	<head>
		<title>My Xero App</title>
	</head>
	<body>
		<p>
            <a href="<?php echo htmlspecialchars($authorizationUrl); ?>">Redirecting to Xero</a>
        </p>
	</body>
</html>
