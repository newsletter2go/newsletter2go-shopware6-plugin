<?php declare(strict_types=1);

namespace Newsletter2go\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
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

    /**
     * @Route("/api/{version}/n2g/getCustomers", name="api.action.n2g.getCustomers", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    public function getCustomersAction(Request $request, Context $context): JsonResponse
    {
        $subscribed = $request->get('subscribed', false);
        //TODO check if needed
//        $offset = $request->get('start', false);
//        $limit = $request->get('limit', false);
        $group = $request->get('group', false);
        $fields = json_decode($request->get('fields', "{}"), true);
        $emails = json_decode($request->get('emails', "{}"), true);
        $subShopId = $request->get('subShopId', 0);

        $criteria = new Criteria();

        if ($subscribed) {
            $criteria->addFilter(new EqualsFilter('customer.newsletter', 1));
        }

        if ($group) {
            if ($group === "guest") {
                $groupFilter = new EqualsFilter('customer.guest', 1);
            } else {
                $groupFilter = new EqualsFilter('customer.customer_group_id', $group);
            }
            $criteria->addFilter($groupFilter);
        }

        $criteria->addFilter(new EqualsFilter('customer.active', 1));

        try {
            /** @var EntityRepositoryInterface $customerRepository */
            $customerRepository = $this->container->get('customer.repository');
            if (!empty($request->get('id'))) {
                $criteria->addFilter(new EqualsAnyFilter('customer.email',
                    $emails));
            }
            $response['success'] = true;
            $response['data'] = $customerRepository->search($criteria, $context)->getEntities()->getElements();
        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }


    /**
     * @Route("/api/{version}/n2g/getGroups", name="api.action.n2g.getGroups", methods={"GET"})
     * @return JsonResponse
     */
    public function getGroupsAction(): JsonResponse
    {
        $groups = $this->getCustomerGroups();
        if ($groups instanceof EntityCollection) {
            $response['success'] = false;
            $response['data'] = $groups->toArray();
        } else {
            $response['success'] = false;
            $response['error'] = $groups;
        }

        return new JsonResponse($response);
    }

    private function getCustomerGroups()
    {
        try {
            /** @var EntityRepositoryInterface $groupRepository */
            $groupRepository = $this->container->get('customer_group.repository');
            /** @var EntityCollection $groups */
            $groups = $groupRepository->search(new Criteria(), Context::createDefaultContext())->getEntities();
            return $groups;

        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * @Route("/api/{version}/n2g/getProduct", name="api.action.n2g.getProduct", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getProduct(Request $request, Context $context): JsonResponse
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
