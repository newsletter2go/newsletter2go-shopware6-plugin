<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Promotion\PromotionCollection;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CustomerController extends AbstractController
{
    private $customerFieldController;

    /**
     * CustomerController constructor.
     * @param CustomerFieldController $customerFieldController
     */
    public function __construct(CustomerFieldController $customerFieldController)
    {
        $this->customerFieldController = $customerFieldController;
    }

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
        $limit = $request->get('limit', 500);
        $group = $request->get('group', false);
        $emails = $this->prepareEmails($request->get('emails', null));
        $fields = $this->customerFieldController->getCustomerEntityFields($request->get('fields', ''));
        //TODO check if available in SW6
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
                if ($group === 'guest') {
                    $groupFilter = new EqualsFilter('customer.guest', 1);
                } else {
                    $groupFilter = new EqualsFilter('customer.customer_group_id', $group);
                }
                $criteria->addFilter($groupFilter);
            }
            /** @var EntityRepositoryInterface $customerRepository */
            $customerRepository = $this->container->get('customer.repository');
            if (!empty($emails)) {
                $criteria->addFilter(new EqualsAnyFilter('customer.email',
                    $emails));
            }

            $promotionAssociationCriteria = new Criteria();
            $promotionAssociationCriteria->addFilter(new EqualsFilter('active', 1));
            $promotionAssociationCriteria->addAssociation('discounts');
            $criteria->addAssociation('promotions', $promotionAssociationCriteria);

            $result = $customerRepository->search($criteria, $context)->getEntities();
            $preparedList = $this->customerFieldController->prepareCustomerAttributes($result, $fields);
            $response['success'] = true;
            $response['data'] = $preparedList;

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }

    private function prepareEmails($emails) : array
    {
        $preparedEmails = [];
        $emails = preg_replace('/\s+/', '', $emails);

        if (!empty($emails)) {

            try {
                $preparedEmails = explode(',', $emails);
            } catch (\Exception $exception) {

            }
        }

        return $preparedEmails;
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

            $response['success'] = true;
            $response['count'] = $customerRepository->search($criteria, $context)->count();
        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }
}
