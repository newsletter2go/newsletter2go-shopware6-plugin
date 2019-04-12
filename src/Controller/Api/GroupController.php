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
}
