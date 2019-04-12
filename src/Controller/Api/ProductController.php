<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{

    /**
     * @Route("/api/{version}/n2g/getProduct", name="api.action.n2g.getProduct", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getProductAction(Request $request, Context $context): JsonResponse
    {
        $response = [];
        $identifier = $request->get('identifier');

        if ($identifier) {
            /** @var EntityRepositoryInterface $customRepository */
            $repository = $this->container->get('product.repository');
            try {
                $searchResponse = $repository->search(new Criteria(
                    [
                        $identifier
                    ]),
                    $context
                );

                $response['success'] = false;
                $response['data'] = $searchResponse->getElements();

            } catch (\Exception $exception) {
                $response['success'] = false;
                $response['error'] = $exception->getMessage();
            }
        }

        return new JsonResponse([
            "success" => true,
            'data' => $response
        ]);
    }
}
