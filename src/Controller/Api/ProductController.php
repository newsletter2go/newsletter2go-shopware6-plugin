<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    private $productFieldController;

    /**
     * ProductController constructor.
     * @param ProductFieldController $productFieldController
     */
    public function __construct(ProductFieldController $productFieldController)
    {
        $this->productFieldController = $productFieldController;
    }

    /**
     * @Route("/api/{version}/n2g/products", name="api.action.n2g.getProducts", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getProductsAction(Request $request, Context $context): JsonResponse
    {
        $response = [];
        $productNumber = $request->get('productNumber');
        $id = $request->get('id');
        $languageId = $request->get('languageId');

        try {

            if (empty($productNumber) && empty($id)) {
                throw new \Exception('no parameter used: productNumber, id');
            }

            $criteria = new Criteria();
            $criteria->addAssociation('media');
            if ($languageId) {
                $translationCriteria = new Criteria();
                $translationCriteria->addFilter(new EqualsFilter('languageId', $languageId));
                $criteria->addAssociation('translations', $translationCriteria);
            }
            $criteria->addFilter(new EqualsFilter('active', 1));
            if ($productNumber) {
                $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
            } else {
                $criteria->addFilter(new EqualsFilter('id', $id));
            }

            /** @var EntityRepositoryInterface $repository */
            $repository = $this->container->get('product.repository');
            /** @var ProductEntity $product */
            $product = $repository->search($criteria,
                $context
            )->first();

            if (empty($product)) {
                throw new \Exception("Product with product number ${productNumber} not found");
            }

            $product = $this->productFieldController->prepareProductAttributes($product);
            $response['success'] = true;
            $response['data'] = $product;

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
        $productNumber = $request->get('productNumber');

        if (empty($productNumber)) {
            {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'missing "productNumber" parameter'
                ]);
            }
        }

        try {
            $repository = $this->container->get('product.repository');
            $criteria = new Criteria();
            $criteria->addAssociation('media');
            $criteria->addFilter(new EqualsFilter('active', 1));
            $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
            /** @var ProductEntity $result */
            $result = $repository->search($criteria, $context)->first();
            /** @var MediaCollection $media */
            $media = $result->getMedia();

            $data = [];
            /** @var ProductMediaEntity $mediaEntity */
            foreach ($media->getElements() as $mediaEntity) {
                $data[] = $mediaEntity->getMedia()->getUrl();
            }

            $response['success'] = true;
            $response['data'] = $data;

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }
}
