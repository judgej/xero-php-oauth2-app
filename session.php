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
