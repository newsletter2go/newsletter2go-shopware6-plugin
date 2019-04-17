<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
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
     * @Route("/api/{version}/n2g/customers", name="api.action.n2g.getCustomers", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getCustomersAction(Request $request, Context $context): JsonResponse
    {
        $onlySubscribed = $request->get('subscribed', false);
        $offset = $request->get('offset', false);
        $limit = $request->get('limit', 100);
        $group = $request->get('group', false);
        $emails = json_decode($request->get('emails', "{}"), true);
        //TODO check if available in SW6
        $fields = json_decode($request->get('fields', "{}"), true);
        $subShopId = $request->get('subShopId', 0);

        try {

            $criteria = new Criteria();

            $criteria->addFilter(new EqualsFilter('customer.active', 1));

            if ($onlySubscribed) {
                $criteria->addFilter(new EqualsFilter('customer.newsletter', 1));
            }

            if ($offset && is_numeric($offset)) {
                $criteria->setOffset($offset);
            }

            if ($limit && is_numeric($limit)) {
                $criteria->setLimit($limit);
            }

            if ($group) {
                if ($group === "guest") {
                    $groupFilter = new EqualsFilter('customer.guest', 1);
                } else {
                    $groupFilter = new EqualsFilter('customer.customer_group_id', $group);
                }
                $criteria->addFilter($groupFilter);
            }
            /** @var EntityRepositoryInterface $customerRepository */
            $customerRepository = $this->container->get('customer.repository');
            if (!empty($request->get('id'))) {
                $criteria->addFilter(new EqualsAnyFilter('customer.email',
                    $emails));
            }
            //TODO and add vouchers
            $result =  $customerRepository->search($criteria, $context)->getEntities();
            $response['success'] = true;
            $response['data'] = $result->getElements();
        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }


    /**
     * @Route("/api/{version}/n2g/customers", name="api.action.n2g.updateCustomer", methods={"PUT"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function updateCustomerAction(Request $request, Context $context): JsonResponse
    {
        $id = $request->get('id');
        $subscribed = $request->get('subscribed');
        $statusCode = 400;
        $response = [];
        if ($id && $subscribed) {
            try {
                /** @var EntityRepositoryInterface $customerRepository */
                $customerRepository = $this->container->get('customer.repository');
                $updateResponse = $customerRepository->update([ //TODO make it possible to add new fields
                    [
                        'id' => $id,
                        'newsletter' => $subscribed
                    ]
                ],
                    $context
                );
                $statusCode = 200;
                $response['success'] = true;
                $response['data'] = $updateResponse->getEvents()->getElements();
            } catch (\Exception $exception) {
                $response['success'] = false;
                $response['error'] = $exception->getMessage();
            }
        }

        return new JsonResponse($response, $statusCode);
    }


    /**
     * @Route("/api/{version}/n2g/customers/count", name="api.action.n2g.getCustomers.count", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getCustomersCount(Request $request, Context $context): JsonResponse
    {
        $response = [];
        $onlySubscribed = $request->get('subscribed');
        /** @var EntityRepositoryInterface $customerRepository */
        $customerRepository = $this->container->get('customer.repository');

        try {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('customer.active', 1));

            if ($onlySubscribed) {
                $criteria->addFilter(new EqualsFilter('customer.newsletter', 1));
            }

            $response['success'] =  true;
            $response['count'] = $customerRepository->search($criteria, $context)->count();
        } catch (\Exception $exception) {
            $response['success'] =  false;
            $response['error'] =  $exception->getMessage();
        }

        return new JsonResponse($response);
    }

}
