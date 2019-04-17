<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{

    /**
     * @Route("/api/{version}/n2g/products", name="api.action.n2g.getProducts", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getProductsAction(Request $request, Context $context): JsonResponse
    {
        $response = [];
        $identifier = $request->get('identifier');
        $productTile = $request->get('title');

        try {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('active', 1));

            if ($identifier) {
                $criteria->setIds($identifier);
            } elseif ($productTile) {
                $criteria->addFilter([
                    'title' => $productTile
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'missing "identifier" parameter'
                ]);
            }

            /** @var EntityRepositoryInterface $repository */
            $repository = $this->container->get('product.repository');
            $searchResponse = $repository->search($criteria,
                $context
            );

            $response['success'] = false;
            $response['data'] = $searchResponse->getElements();

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/api/{version}/n2g/products/media", name="api.action.n2g.getProductMedia", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getProductMediaAction(Request $request, Context $context): JsonResponse
    {
        $response = [];
        $identifier = $request->get('identifier');
        $productName = $request->get('name');

        try {
            $criteria = new Criteria();

            if ($identifier) {
                $criteria->addFilter(new EqualsFilter('productId', $identifier));
            } elseif ($productName) {
                $products = $this->findProductsByProductName($context, $productName);
                if ($products && $productIds = $this->getProductIdsFromProductArray($products)) {
                    $criteria->addFilter(new EqualsAnyFilter('productId', $productIds));
                } else {
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'product not found'
                    ]);
                }
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'missing "identifier" parameter'
                ]);
            }


            $repository = $this->container->get('product_media.repository');
            $result = $repository->search($criteria, $context);

            $response['success'] = true;
            $response['data'] = $result->getElements();

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }

    private function findProductsByProductName(Context $context, string $productName)
    {
        $repository = $this->container->get('product.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', 1));
        $criteria->addFilter(new EqualsFilter('name', $productName));
        $result = $repository->search($criteria, $context);
        return $result->getElements();
    }

    private function getProductIdsFromProductArray($products) :array
    {
        $productIds = [];
        /** @var ProductEntity $product */
        foreach ($products as $product) {
            $productIds[] = $product->getId();
        }

        return $productIds;
    }

}
