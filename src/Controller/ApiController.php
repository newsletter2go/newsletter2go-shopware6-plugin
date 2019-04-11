<?php declare(strict_types=1);

namespace Swag\Newsletter2go\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
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
    public function testConnection(): JsonResponse
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
     */
    public function getCustomers(Request $request, Context $context): JsonResponse
    {
        $subscribed = $request->get('subscribed', false);
        //TODO check if needed
//        $offset = $request->get('start', false);
//        $limit = $request->get('limit', false);
        $group = $request->get('group', false);
        $fields = $request->get('fields', []);
        $emails = $request->get('emails', []);
        $subShopId = $request->get('subShopId', 0);

        $criteria = new Criteria();

        if ($subscribed) {
            $criteria->addFilter(new EqualsFilter('customer.newsletter', 1));
        }

        if ($group) {
            $existingGroups = $this->getCustomerGroups();
            if ($existingGroups instanceof EntityCollection) {

                $criteria->addFilter(new EqualsFilter('customer.customer_group_id', $group));
            }
        }


        try {
            /** @var EntityRepositoryInterface $customerRepository */
            $customerRepository = $this->container->get('customer.repository');
            if (!empty($request->get('id'))) {
                $criteria->addFilter(new EqualsFilter('customer.email',
                    $request->get('id')));
            }
            $response['success'] = true;
            $response['items'] = $customerRepository->search($criteria, $context)->getEntities();
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
    public function getGroups(): JsonResponse
    {
        $groups = $this->getCustomerGroups();
        if ($groups instanceof EntityCollection) {
            $response['success'] = false;
            $response['items'] = $groups->toArray();
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
}
