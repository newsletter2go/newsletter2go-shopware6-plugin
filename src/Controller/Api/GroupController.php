<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GroupController extends AbstractController
{
    /**
     * @Route("/api/{version}/n2g/groups", name="api.action.n2g.getGroups", methods={"GET"})
     * @return JsonResponse
     */
    public function getGroupsAction(): JsonResponse
    {
        $response = [];
        try {

            $groups = $this->getCustomerGroups();
            $response['success'] = false;
            $response['data'] = $groups->getElements();

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }

    private function getCustomerGroups(): EntityCollection
    {
        /** @var EntityRepositoryInterface $groupRepository */
        $groupRepository = $this->container->get('customer_group.repository');
        $result = $groupRepository->search(new Criteria(), Context::createDefaultContext());

        return $result->getEntities();
    }
}
