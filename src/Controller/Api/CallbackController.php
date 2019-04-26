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

        $authKey = $request->get('auth_key', null);
        $accessToken = $request->get('access_token', null);
        $refreshToken = $request->get('refresh_token', null);
        $companyId = $request->get('company_id', null);

        try {
            $this->newsletter2goConfigService->addConfig('auth_key', $authKey);
            $this->newsletter2goConfigService->addConfig('access_token', $accessToken);
            $this->newsletter2goConfigService->addConfig('refresh_token', $refreshToken);
            $this->newsletter2goConfigService->addConfig('company_id', $companyId);

            $response['success'] = true;

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }
}
