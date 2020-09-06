<?php

/**
 * Provide a single point to manage session data.
 */

class SessionClass
{
    function __construct() {
        if (! isset($_SESSION)) {
            session_start();
        }
    }

    /**
     * The current tenant chosen by the user.
     * This sets the tenant that all tenant-specific API requests (most of
     * them) operate against.
     *
     * @param string $tenantId
     */
    public function setTenantId($tenantId)
    {
        $_SESSION['tenant-id'] = $tenantId;
    }

    /**
     * @return string|null
     */
    public function getTenantId()
    {
        return $_SESSION['tenant-id'] ?? null;
    }

    /**
     * The state is a CSRF token to catch CSRF attacks.
     * The state can also be used to carry additional information such as a
     * redirect URL to put the user back onto the page that triggered the
     * authorisation.
     *
     * @param string $state
     */
    public function setState($state)
    {
        $_SESSION['oauth2state'] = $state;
    }

    /**
     * @return string|null
     */
    public function getState()
    {
        return $_SESSION['oauth2state'] ?? null;
    }

    /**
     * Clear the state string.
     */
    public function clearState()
    {
        unset($_SESSION['oauth2state']);
    }
}
