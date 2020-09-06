<?php

class StorageClass
{
	function __construct() {
		if (! isset($_SESSION)) {
            session_start();
    	}
   	}

    /**
     * Set the three important parts of the OAuth access detais.
     * The "expires" time can be extracted from the JWT token, but extracting it
     * once when storing saves time doing so for every request.
     *
     * @param string $token the full JWT token
     * @param int $expires expiry unixtime
     * @param string $refreshToken
     */
	public function setAccessToken($token, $expires, $refreshToken)
    {
        $_SESSION['oauth2'] = [
            'token' => $token,
            'expires' => $expires,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * Return the access token JWT, expiry time and refresh token all together.
     *
     * @return array|null
     */
	public function getAccessToken()
	{
	    if (empty($_SESSION['oauth2'])) {
	        return null;
	    }

	    return $_SESSION['oauth2'];
	}

    /**
     * The JWT access token is sent with every request to the API as a Bearer authentication.
     *
     * @return string|null JWT access token
     */
	public function getAccessTokenJwt()
	{
	    return $_SESSION['oauth2']['token'] ?? null;
	}

    /**
     * @return string refresh token captured in the auth flow
     */
	public function getRefreshToken()
	{
	    return $_SESSION['oauth2']['refresh_token'] ?? null;
	}

    /**
     * @return int unix timestamp time the access token expires
     */
	public function getExpires()
	{
	    return $_SESSION['oauth2']['expires'] ?? 0;
	}

    /**
     * @return bool true if the access token has expired or does not exist.
     */
    public function hasExpired()
    {
        return time() > $this->getExpires();
    }

    /**
     * A snapshot of the connections are stored after the authentication flow.
     * The connected tenants can still change after the flow, either removed by
     * the API, or by a user through the Xero UI.
     * However, this copy is a useful cache for the front end.
     *
     * @param array|ArrayAccess $connections
     */
    public function setConnections($connections)
    {
        $_SESSION['connections'] = $connections;
    }

    /**
     * @return array|ArrayAccess
     */
    public function getConnections()
    {
        return $_SESSION['connections'] ?? [];
    }

    /**
     * Xero user (resource owner, in OAuth 2.0 parlance) details extracted
     * from the openid id token.#
     *
     * @param array $userDetails
     */
    public function setUserDetails($userDetails)
    {
        $_SESSION['xero_user'] = $userDetails;
    }

    /**
     * @return array
     */
    public function getUserDetails()
    {
        return $_SESSION['xero_user'] ?? [];
    }

    /**
     * The scppe granted on the last authorisation, which will be a
     * cumulative list of all scopes granted over multiple authorisations.
     *
     * @param array|string $scope
     */
    public function setScope($scope)
    {
        if (is_string($scope)) {
            $scope = explode(' ', $scope);
        }

        $_SESSION['scope'] = $scope;
    }

    /**
     * @return array
     */
    public function getScope()
    {
        return $_SESSION['scope'] ?? [];
    }

    /**
     * Every authentication flow gets a unique event ID, and that can be used
     * to determine some of the changes that the user performed during that flow.
     *
     * @param string $id
     */
    public function setAuthenticationEventId($id)
    {
        $_SESSION['auth_event_id'] = $id;
    }

    /**
     * @return string
     */
    public function getAuthenticationEventId()
    {
        return $_SESSION['auth_event_id'] ?? null;
    }
}
