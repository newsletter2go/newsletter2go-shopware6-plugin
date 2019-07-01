<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GroupController extends AbstractController
{
    const GROUP_NEWSLETTER_RECEIVER = 'newsletter_receiver';

    /**
     * @Route("/api/v{version}/n2g/groups", name="api.action.n2g.getGroups", methods={"GET"})
     * @return JsonResponse
     */
    public function getGroupsAction(): JsonResponse
    {
        $response = [];
        try {

            $groups = $this->getCustomerGroups();
            $preparedGroups = $this->prepareEntityAttributes($groups);

            $preparedGroups[self::GROUP_NEWSLETTER_RECEIVER] =
                [
                    'id' => self::GROUP_NEWSLETTER_RECEIVER,
                    'name' => self::GROUP_NEWSLETTER_RECEIVER
                ];

            $response['success'] = true;
            $response['data'] = $preparedGroups;

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

    private function prepareEntityAttributes(EntityCollection $entityCollection) : array
    {
        $attributes = [];

        /** @var CustomerGroupEntity $entity */
        foreach ($entityCollection->getElements() as $key => $entity) {
            $attributes[$key]['id'] = $entity->getId();
            $attributes[$key]['name'] = $entity->getName();
            $attributes[$key]['description'] = $entity->getDisplayGross();
        }

        return $attributes;
    }
}
