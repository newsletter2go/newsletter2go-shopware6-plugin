<?php declare(strict_types=1);

namespace Newsletter2go\Service;


class ApiService
{
    const GRANT_TYPE = 'https://nl2go.com/jwt';
    const REFRESH_GRANT_TYPE = 'https://nl2go.com/jwt_refresh';
    const API_BASE_URL = 'https://api.newsletter2go.com';

    private $apiKey;
    private $accessToken;
    private $refreshToken;
    private $lastStatusCode;

    /**
     * ApiService constructor.
     * @param $authKey
     * @param $accessToken
     * @param $refreshToken
     */
    public function __construct($authKey, $accessToken, $refreshToken)
    {
        $this->apiKey = $authKey;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    /**
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @param array $headers
     * @param bool $authorize
     * @return array
     */
    private function httpRequest($method, $endpoint, $params = [], $headers = ['Content-Type: application/json'], $authorize = false) : ?array
    {
        $response = [];
        $response['status'] = 0;

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            if ($authorize) {
                // this is needed for refresh token
                curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey);
            }

            switch ($method) {
                case 'PATCH':
                case 'POST':
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                    break;
                case 'GET':
                    $encodedParams = array();
                    if (count($params) > 0) {
                        foreach ($params as $key => $value) {
                            $encodedParams[] = urlencode($key) . '=' . urlencode($value);
                        }

                        $getParams = "?" . http_build_query($params);
                        $endpoint .= $endpoint . $getParams;
                    }
                    break;
                default:
                    return null;
            }
            curl_setopt($ch, CURLOPT_URL, self::API_BASE_URL . $endpoint);

            $response = json_decode(curl_exec($ch), true);
            $this->setLastStatusCode(curl_getinfo($ch, CURLINFO_HTTP_CODE));

            curl_close($ch);

        } catch (\Exception $exception) {
            $response['error'] = $exception->getMessage();
        }

        return $response;
    }

    private function refreshToken() : ?array
    {
        if ($this->getRefreshToken()) {

            $data = [
                'refresh_token' => $this->getRefreshToken(),
                'grant_type' => self::REFRESH_GRANT_TYPE
            ];

            $result = $this->httpRequest('POST', '/oauth/v2/token', $data, [], true);

            if (isset($result['access_token'])) {
                $this->setAccessToken($result['access_token']);
                $this->setRefreshToken($result['refresh_token']);
            }
        } else {
            $this->setLastStatusCode(203);
            $result = ['status' => 203, 'error' => 'no refresh token found'];
        }

        return $result;
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

    public function testConnection()
    {
        $refreshResult = $this->refreshToken();

        if ($this->getLastStatusCode() === 200) {
            $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->getAccessToken()];

            $companyResult =  $this->httpRequest('GET', '/companies', [], $headers);

            return [
                'status' => $companyResult['status'],
                'account_id' => $refreshResult['account_id'],
                'company_id' => $companyResult['value'][0]['id']
            ];

        } else {
            $response['error'] = $refreshResult['error'];
        }

        $response['status'] = $this->getLastStatusCode();

        return $response;
    }

    /**
     * @return mixed
     */
    public function getLastStatusCode()
    {
        return $this->lastStatusCode;
    }

    /**
     * @param mixed $lastStatusCode
     */
    public function setLastStatusCode($lastStatusCode): void
    {
        $this->lastStatusCode = $lastStatusCode;
    }
}
