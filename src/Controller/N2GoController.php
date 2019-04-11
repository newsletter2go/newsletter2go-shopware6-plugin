<?php declare(strict_types=1);

namespace Swag\Newsletter2go\Controller;

use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class N2GoController extends AbstractController
{

    /**
     * @Route("/api/v1/n2g", name="api.action.n2g", methods={"GET"})
     */
    public function indexAction(): JsonResponse
    {
        return new JsonResponse([
            "status" => "/index"
        ]);
    }

    /**
     * @Route("/api/v1/n2g/testConnection", name="api.action.n2g.testConnection", methods={"GET"})
     */
    public function testConnection(Request $request, Context $context) :JsonResponse
    {
        return new JsonResponse([
            "status" => "ok"
        ]);
    }
}
