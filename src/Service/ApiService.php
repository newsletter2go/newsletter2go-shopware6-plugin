<?php declare(strict_types=1);

namespace Newsletter2go\Service;


class ApiService
{
    const GRANT_TYPE = 'https://nl2go.com/jwt';
    const API_BASE_URL = 'https://api.newsletter2go.com';
    const SHOPWARE_N2G_AGENT = 'Shopware/6';

    //TODO
    private $ref = '';
    private $authKey;
    private $accessToken;
    private $refreshToken;

    /**
     * ApiService constructor.
     * @param $authKey
     * @param $accessToken
     * @param $refreshToken
     */
    public function __construct($authKey, $accessToken, $refreshToken)
    {
        $this->authKey = $authKey;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    /**
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @param array $headers
     * @return array
     */
    private function httpRequest($method, $endpoint, $params = [], $headers = ['Content-Type' => 'application/json']) : ?array
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, $headers);
            curl_setopt($ch, CURLOPT_USERAGENT, self::SHOPWARE_N2G_AGENT);

            switch ($method) {
                case 'PATCH':
                case 'POST':
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                    break;
                case 'GET':
                    $encodedParams = array();
                    foreach ($params as $key => $value) {
                        $encodedParams[] = urlencode($key) . '=' . urlencode($value);
                    }

                    $getParams = "?" . http_build_query($params);
                    $endpoint .= $endpoint . $getParams;
                    break;
                default:
                    return null;
            }

            curl_setopt($ch, CURLOPT_URL, self::API_BASE_URL . $endpoint);

            $response['success'] = true;
            $response['data'] = curl_exec($ch);

            curl_close($ch);

        } catch (Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return $response;
    }

    private function refreshToken() : ?array
    {
        $data = [
            'grant_type' => self::GRANT_TYPE,
            'refresh_token' => $this->refreshToken
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->authKey)
        ];

        $result = $this->httpRequest('GET', '/oauth/v2/token', $data, $headers);

        if (isset($result['access_token'])) {
            return $result;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getRef(): string
    {
        return $this->ref;
    }

    /**
     * @param string $ref
     */
    public function setRef(string $ref): void
    {
        $this->ref = $ref;
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

    private function _verifyResponse($response) : array
    {
        // TODO
    }

}
