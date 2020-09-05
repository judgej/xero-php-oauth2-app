<?php

class StorageClass
{
	function __construct() {
		if (! isset($_SESSION)) {
        	$this->init_session();
    	}
   	}

   	public function init_session() {
    	session_start();
	}

    public function getSession() {
    	return $_SESSION['oauth2'];
    }

 	public function startSession($token, $secret, $expires = null)
	{
       	session_start();
	}

    /**
     * @param $token JWT access token
     * @param int|null $expires unix timestamp expiry extracted from $token
     * @param $tenantId (probably does not below here; nothing to do with the access token)
     * @param $refreshToken captured from authentication flow
     * @param $idToken JWT ID token (not used for authentication, captured in the auth flow)
     */
	public function xxxsetToken($token, $expires = null, $tenantId, $refreshToken, $idToken)
	{    
	    $_SESSION['oauth2'] = [
	        'token' => $token,
	        'expires' => $expires,
	        'tenant_id' => $tenantId,
	        'refresh_token' => $refreshToken,
	        'id_token' => $idToken
	    ];
	}

    /**
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
     * Return the access token JWT, expiry time and refresh token.
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

    // TODO: store scopes
    // TODO: store id token
    // TODO: store id token payload
    // TODO: store authentication_event_id

    /**
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
