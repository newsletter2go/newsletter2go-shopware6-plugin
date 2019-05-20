<?php

namespace Newsletter2go\Controller\Api;


use Newsletter2go\Service\Newsletter2goConfigService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CallbackController extends AbstractController
{
    private $newsletter2goConfigService;

    /**
     * AuthController constructor.
     * @param Newsletter2goConfigService $newsletter2goConfigService
     */
    public function __construct(Newsletter2goConfigService $newsletter2goConfigService)
    {
        $this->newsletter2goConfigService = $newsletter2goConfigService;
    }


    /**
     * @Route("/api/{version}/n2g/callback", name="api.action.n2g.callback", methods={"POST"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function authAction(Request $request, Context $context): JsonResponse
    {
        $response = [];
        $config = [];
        $config['auth_key'] = $request->get('auth_key', null);
        $config['access_token'] = $request->get('access_token', null);
        $config['refresh_token'] = $request->get('refresh_token', null);
        $config['company_id'] = $request->get('company_id', null);

        foreach ($config as $key => $value) {
            if (empty($value)) {
                unset($config[$key]);
            }
        }

        try {
            $this->newsletter2goConfigService->addConfig($config);
            $response['success'] = true;

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }
}
