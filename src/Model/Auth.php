<?php

namespace Newsletter2go\Model;


class Auth
{
    private $authKey;
    private $accessToken;
    private $refreshToken;

    /**
     * @return mixed
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @param mixed $authKey
     */
    public function setAuthKey($authKey): void
    {
        $this->authKey = $authKey;
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param mixed $accessToken
     */
    public function setAccessToken($accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return mixed
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param mixed $refreshToken
     */
    public function setRefreshToken($refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }
}
