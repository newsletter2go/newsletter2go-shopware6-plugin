<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CustomerController extends AbstractController
{
    /**
     * @Route("/api/{version}/n2g/getCustomers", name="api.action.n2g.getCustomers", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     * @throws InconsistentCriteriaIdsException
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
}
