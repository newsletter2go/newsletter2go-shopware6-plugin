<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Checkout\Customer\CustomerEntity;
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
        $subShopId = $request->get('subShopId', null);

        try {

            $criteria = new Criteria();

            $criteria->addFilter(new EqualsFilter('customer.active', 1));

            if ($onlySubscribed) {
                $criteria->addFilter(new EqualsFilter('customer.newsletter', 1));
            }

            if ($subShopId) {
                $criteria->addFilter(new EqualsFilter('customer.salesChannelId', $subShopId));
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
                    $groupFilter = new EqualsFilter('customer.groupId', $group);
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
            $criteria->addAssociation('language');

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
     * @Route("/api/{version}/n2g/customers/subscribe", name="api.action.n2g.subscribeCustomer", methods={"POST"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function subscribeCustomerAction(Request $request, Context $context): JsonResponse
    {
        $code = 400;
        $response = [];

        $email = $request->get('email');
        if ($email && $request->get('Subscribe')) {
            $updateResponse = $this->_updateCustomer($email, true, $context);
            $response = $updateResponse['response'];
            $code = $updateResponse['code'];
        }

        return new JsonResponse($response, $code);
    }

    /**
     * @Route("/api/{version}/n2g/customers/unsubscribe", name="api.action.n2g.unsubscribeCustomer", methods={"POST"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function unsubscribeCustomerAction(Request $request, Context $context): JsonResponse
    {
        $code = 400;
        $response = [];

        $email = $request->get('email');
        if ($email && $request->get('Unsubscribe')) {
            $updateResponse = $this->_updateCustomer($email, false, $context);
            $response = $updateResponse['response'];
            $code = $updateResponse['code'];
        }

        return new JsonResponse($response, $code);
    }

    /**
     * @param $email
     * @param $newsletter
     * @param Context $context
     * @return array
     */
    private function _updateCustomer($email, $newsletter, Context $context): array
    {
        $statusCode = 400;
        $response = [];
        $response['success'] = false;

        try {
            /** @var EntityRepositoryInterface $customerRepository */
            $customerRepository = $this->container->get('customer.repository');
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('email', $email));
            /** @var CustomerEntity $customer */
            $customer = $customerRepository->search($criteria, $context)->first();

            if ($customer) {
                $updateResponse = $customerRepository->upsert([
                    [
                        'id' => $customer->getId(),
                        'newsletter' => $newsletter
                    ]
                ],
                    $context
                );

                $statusCode = 200;
                $response['success'] = true;
                $response['data'] = $updateResponse->getEvents()->getElements();
            }
        } catch (\Exception $exception) {

            $response['error'] = $exception->getMessage();
        }

        return ['response' => $response, 'code' => $statusCode];
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
        $groupId = $request->get('group');
        /** @var EntityRepositoryInterface $customerRepository */
        $customerRepository = $this->container->get('customer.repository');

        try {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('customer.active', 1));

            if ($onlySubscribed) {
                $criteria->addFilter(new EqualsFilter('customer.newsletter', 1));
            }

            if ($groupId) {
                $criteria->addFilter(new EqualsFilter('customer.groupId', $groupId));
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
