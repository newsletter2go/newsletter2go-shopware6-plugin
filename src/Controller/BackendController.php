<?php

namespace Newsletter2go\Controller;


use Newsletter2go\Entity\Newsletter2goConfig;
use Newsletter2go\Model\Auth;
use Newsletter2go\Service\ApiService;
use Newsletter2go\Service\Newsletter2goConfigService;
use Shopware\Core\Framework\Context;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     * @Route(path="/api/{version}/n2g/backend", name="api.action.n2g.backend", methods={"GET"})
     * @param Request $request
     * @param Context $context
     */
    public function getNewsletter2goConfig(Request $request, Context $context)
    {
        //
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

    public function getConnectUrl()
    {
        return self::CONNECTOR_URL . http_build_query($this->getConnectorUrlParams());
    }

    private function getConnectorUrlParams()
    {
        $apiVersion = 'v' . PlatformRequest::API_VERSION;
        $params = [];
        $params['url'] = getenv('APP_URL');
        $params['callback'] = getenv('APP_URL') . "/api/{$apiVersion}/n2g/callback";

        try {
            $n2gConfigs = $this->newsletter2goConfigService->getConfigByFieldNames(['accessKey', 'secretAccessKey']);

            /** @var Newsletter2goConfig $n2gConfig */
            foreach ($n2gConfigs as $n2gConfig) {
                if ($n2gConfig->getName() === Newsletter2goConfig::NAME_VALUE_ACCESS_KEY) {
                    $params['accessKey'] = $n2gConfig->getValue();
                }

                if ($n2gConfig->getName() === Newsletter2goConfig::NAME_VALUE_SECRET_ACCESS_KEY) {
                    $params['secretAccessKey'] = $n2gConfig->getValue();
                }
            }

        } catch (\Exception $exception) {
            //
        }

        return $params;
    }

    public function updateConversionTracking(Request $request, Context $context)
    {
        //
    }
}
