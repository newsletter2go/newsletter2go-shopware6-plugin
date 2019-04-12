<?php declare(strict_types=1);

namespace Newsletter2go\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    /**
     * @Route("/api/{version}/n2g/testConnection", name="api.action.n2g.testConnection", methods={"GET"})
     */
    public function testConnectionAction(): JsonResponse
    {
        return new JsonResponse([
            "success" => true
        ]);
    }
}
