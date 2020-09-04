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
	public function setToken($token, $expires = null, $tenantId, $refreshToken, $idToken)
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
     * Return the array of tokens, expiry and tenant id.
     *
     * @return array|null
     */
	public function getToken()
	{
	    // If it doesn't exist or is expired, return null.
        // If expired, the refresh token is obtained through getRefreshToken().

	    if (empty($this->getSession())
	        || ($_SESSION['oauth2']['expires'] !== null
	        && $_SESSION['oauth2']['expires'] <= time())
	    ) {
	        return null;
	    }
	    return $this->getSession();
	}

    /**
     * @return string JWT access token
     */
	public function getAccessToken()
	{
	    return $_SESSION['oauth2']['token'];
	}

    /**
     * @return string refresh token captured in the auth flow
     */
	public function getRefreshToken()
	{
	    return $_SESSION['oauth2']['refresh_token'];
	}

    /**
     * @return int unix timestamp time the access token expires
     */
	public function getExpires()
	{
	    return $_SESSION['oauth2']['expires'];
	}

    /**
     * @return mixed the current Xero tenant ID set in the session.
     */
	public function getXeroTenantId()
	{
	    return $_SESSION['oauth2']['tenant_id'];
	}

    /**
     * @param string $tenantId the new Xero tenant ID to set in the session.
     */
    public function setXeroTenantId($tenantId)
    {
        $_SESSION['oauth2']['tenant_id'] = $tenantId;
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

    public function getIdToken()
	{
	    return $_SESSION['oauth2']['id_token'];
	}

	public function getHasExpired()
	{
		if (!empty($this->getSession())) {
			return time() > $this->getExpires();
		} else {
			return true;
		}
	}
}
