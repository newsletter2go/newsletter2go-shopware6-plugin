<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    /**
     * @Route("/api/{version}/n2g/auth", name="api.action.n2g.auth", methods={"POST"})
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
            $repository = $this->container->get('newsletter2go_config.repository');

            $repository->create([
                [
                    'name' => 'auth_key',
                    'value' => $authKey
                ],
                [
                    'name' => 'access_token',
                    'value' => $accessToken
                ],
                [
                    'name' => 'refresh_token',
                    'value' => $refreshToken
                ],
                [
                    'name' => 'company_id',
                    'value' => $companyId
                ],
            ], $context);

            $response['success'] = true;

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }
}
