<?php

namespace Newsletter2go\Controller;


use Newsletter2go\Entity\Newsletter2goConfig;
use Newsletter2go\Model\Auth;
use Newsletter2go\Service\ApiService;
use Newsletter2go\Service\Newsletter2goConfigService;
use Shopware\Core\Framework\Context;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BackendController extends AbstractController
{
    const CONNECTOR_URL = 'https://ui.newsletter2go.com/integrations/connect/SW6/';

    private $newsletter2goConfigService;
    private $apiService;

    /**
     * BackendController constructor.
     * @param Newsletter2goConfigService $newsletter2goConfigService
     */
    public function __construct(Newsletter2goConfigService $newsletter2goConfigService)
    {
        $this->newsletter2goConfigService = $newsletter2goConfigService;

        $auth = $this->getNewsletter2goAuth();
        $this->apiService = new ApiService($auth->getAuthKey(), $auth->getAccessToken(), $auth->getRefreshToken());
    }

    /**
     * @Route(path="/api/{version}/n2g/connection", name="api.action.n2g.connection", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function testConnection(Request $request, Context $context) : JsonResponse
    {
        $result = $this->apiService->testConnection();
        // prevent to be blocked
        if ($this->apiService->getLastStatusCode() === 400) {
            $this->newsletter2goConfigService->deleteConfigByName(Newsletter2goConfig::NAME_VALUE_ACCESS_TOKEN);
            $this->newsletter2goConfigService->deleteConfigByName(Newsletter2goConfig::NAME_VALUE_REFRESH_TOKEN);
        }

        return new JsonResponse($result);
    }

    /**
     * @Route(path="/api/{version}/n2g/connection", name="api.action.n2g.disconnection", methods={"DELETE"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function disconnect(Request $request, Context $context) : JsonResponse
    {
        try {
            $this->newsletter2goConfigService->updateConfigs([Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING => 'false']);

            if ($this->newsletter2goConfigService->deleteConfigByName(Newsletter2goConfig::NAME_VALUE_REFRESH_TOKEN) &&
                $this->newsletter2goConfigService->deleteConfigByName(Newsletter2goConfig::NAME_VALUE_ACCESS_TOKEN) &&
                $this->newsletter2goConfigService->deleteConfigByName(Newsletter2goConfig::NAME_VALUE_COMPANY_ID) ) {

                return new JsonResponse(['status' => 200]);
            }

        } catch (\Exception $exception) {

        }

        return new JsonResponse(['status' => 400]);
    }

    private function getNewsletter2goAuth() : Auth
    {
        $auth = new Auth();
        try {
            $configs = $this->newsletter2goConfigService->getConfigByFieldNames(['auth_key', 'access_token', 'refresh_token']);
            /** @var Newsletter2goConfig $config */
            foreach ($configs as $config) {
                switch ($config->getName()) {
                    case Newsletter2goConfig::NAME_VALUE_AUTH_KEY:
                        $auth->setAuthKey($config->getValue());
                        break;
                    case Newsletter2goConfig::NAME_VALUE_ACCESS_TOKEN:
                        $auth->setAccessToken($config->getValue());
                        break;
                    case Newsletter2goConfig::NAME_VALUE_REFRESH_TOKEN:
                        $auth->setRefreshToken($config->getValue());
                        break;
                }
            }
        } catch (\Exception $exception) {
            //
        }

        return $auth;
    }

    /**
     * @Route(path="/api/{version}/n2g/integration", name="api.action.n2g.integration", methods={"GET"})
     * @return JsonResponse
     */
    public function getConnectUrl() :JsonResponse
    {
        return new JsonResponse(['integration' => self::CONNECTOR_URL . '?' .http_build_query($this->getConnectorUrlParams())]);
    }

    private function getConnectorUrlParams()
    {
        $apiVersion = 'v' . PlatformRequest::API_VERSION;
        $params = [];
        $params['url'] = getenv('APP_URL');
        $params['callback'] = getenv('APP_URL') . "/api/{$apiVersion}/n2g/callback";

        try {
            $n2gConfigs = $this->newsletter2goConfigService->getConfigByFieldNames([Newsletter2goConfig::NAME_VALUE_ACCESS_KEY, Newsletter2goConfig::NAME_VALUE_SECRET_ACCESS_KEY]);

            /** @var Newsletter2goConfig $n2gConfig */
            foreach ($n2gConfigs as $n2gConfig) {
                if ($n2gConfig->getName() === Newsletter2goConfig::NAME_VALUE_ACCESS_KEY) {
                    $params[Newsletter2goConfig::NAME_VALUE_ACCESS_KEY] = $n2gConfig->getValue();
                }

                if ($n2gConfig->getName() === Newsletter2goConfig::NAME_VALUE_SECRET_ACCESS_KEY) {
                    $params[Newsletter2goConfig::NAME_VALUE_SECRET_ACCESS_KEY] = $n2gConfig->getValue();
                }
            }

        } catch (\Exception $exception) {
            //
        }

        return $params;
    }

    /**
     * @Route(path="/api/{version}/n2g/company", name="api.action.n2g.company", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getCompany(Request $request, Context $context) : JsonResponse
    {
        $companyId = '';
        try {
            $result = $this->newsletter2goConfigService->getConfigByFieldNames(Newsletter2goConfig::NAME_VALUE_COMPANY_ID);

            if (!empty($result)) {
                /** @var Newsletter2goConfig $companyIdConfig */
                $companyIdConfig = reset($result);
                $companyId = $companyIdConfig->getValue();
            }

        } catch (\Exception $exception) {

        }

        return new JsonResponse([Newsletter2goConfig::NAME_VALUE_COMPANY_ID => $companyId]);
    }
}
