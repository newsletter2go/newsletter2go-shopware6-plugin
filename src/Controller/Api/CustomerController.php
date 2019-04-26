<?php

namespace Newsletter2go\Controller\Api;


use Newsletter2go\Model\Field;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Promotion\PromotionCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
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
        $emails = json_decode($request->get('emails', '[]'), true);
        $fields = $this->getCustomerEntityFields($request->get('fields', ''));
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
            if (!empty($request->get('id'))) {
                $criteria->addFilter(new EqualsAnyFilter('customer.email',
                    $emails));
            }

            $promotionAssociationCriteria = new Criteria();
            $promotionAssociationCriteria->addFilter(new EqualsFilter('active', 1));
            $promotionAssociationCriteria->addAssociation('discounts');
            $criteria->addAssociation('promotions', $promotionAssociationCriteria);

            $result = $customerRepository->search($criteria, $context)->getEntities();
            $preparedList = $this->prepareCustomerAttributes($result, $fields);
            $response['success'] = true;
            $response['data'] = $preparedList;

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }

    private function getCustomerEntityFields(String $customFields): array
    {
        $fields = [];

        $customFields = $this->prepareCustomFields($customFields);

        $defaultCustomerFields = $this->getCustomerDefaultFields();
        if (count($customFields) === 0) {
            return $defaultCustomerFields;
        }

        /** @var Field $defaultCustomerField */
        foreach ($defaultCustomerFields as $defaultCustomerField) {
            //email must always be included
            if ($defaultCustomerField->getId() === 'email') {
                $fields[] = $defaultCustomerField;
                continue;
            }
            if (in_array($defaultCustomerField->getId(), $customFields)) {
                $fields[] = $defaultCustomerField;
            }
        }

        return $fields;
    }

    private function prepareCustomFields($customFields): array
    {
        $customFields = preg_replace('/\s+/', '', $customFields);
        if (empty($customFields)) {
            $customFields = [];
        } else {
            $customFields = explode(',', $customFields);
        }

        return $customFields;
    }

    private function getCustomerDefaultFields(): array
    {
        $defaultFields = [
            new Field('id'),
            new Field('salesChannelId'),
            new Field('languageId'),
            new Field('customerNumber', Field::DATATYPE_INTEGER),
            new Field('group', Field::DATATYPE_ARRAY),
            new Field('salutation', Field::DATATYPE_STRING, 'Title'),
            new Field('searchKeywords', Field::DATATYPE_ARRAY),
            new Field('tags', Field::DATATYPE_ARRAY),
            new Field('attributes', Field::DATATYPE_ARRAY),
            new Field('orderCustomers'),
            new Field('email'),
            new Field('language'),
            new Field('firstName'),
            new Field('lastName'),
            new Field('company'),
            new Field('isGuest', Field::DATATYPE_BOOLEAN),
            new Field('newsletter', Field::DATATYPE_INTEGER),
            new Field('birthday', Field::DATATYPE_DATE),
            new Field('defaultBillingAddress'),
            new Field('defaultShippingAddress'),
            new Field('defaultPaymentMethod', Field::DATATYPE_DATE),
            new Field('createdAt', Field::DATATYPE_DATE),
            new Field('updatedAt', Field::DATATYPE_DATE),
            new Field('salesChannel', Field::DATATYPE_ARRAY),
            new Field('promotions', Field::DATATYPE_ARRAY),
        ];

        return $defaultFields;
    }

    private function prepareCustomerAttributes(EntityCollection $customerList, array $fields): array
    {
        $preparedCustomerList = [];
        /**
         * @var String $key
         * @var CustomerEntity $customerEntity
         */
        foreach ($customerList as $key => $customerEntity) {
            /** @var Field $field */
            foreach ($fields as $field) {
                if ($customerEntity->has($field->getId())) {
                    $attribute = $customerEntity->get($field->getId());

                    if ($attribute instanceof Entity) {
                        $preparedCustomerList[$key][$field->getId()] = $this->prepareEntity($attribute);
                    } else {
                        if ($attribute instanceof EntityCollection) {
                            if ($attribute instanceof PromotionCollection) {
                                $preparedCustomerList[$key][$field->getId()] = $this->preparePromotionCollection($attribute);
                            }
                        } else {
                            if ($attribute instanceof \DateTimeImmutable) {
                                $preparedCustomerList[$key][$field->getId()] = $attribute->format('Y-m-d H:i:s');
                            } else {
                                $preparedCustomerList[$key][$field->getId()] = $attribute;
                            }
                        }
                    }
                }
            }
        }

        return $preparedCustomerList;
    }

    private function prepareEntity(Entity $entity): ?array
    {
        $preparedEntity = [];
        if ($entity instanceof CustomerAddressEntity) {
            $preparedEntity = $this->prepareCustomerAddressEntity($entity);
        }
        if ($entity instanceof SalutationEntity) {
            $preparedEntity['displayName'] = $entity->getDisplayName();
            $preparedEntity['letterName'] = $entity->getLetterName();
        } else {

            if (property_exists($entity, 'id')) {
                $preparedCustomerList['id'] = $entity->getUniqueIdentifier();
            }
            if (property_exists($entity, 'name')) {
                $preparedCustomerList['name'] = $entity->get('name');
            }
        }

        return $preparedEntity;
    }

    private function prepareCustomerAddressEntity(CustomerAddressEntity $customerAddressEntity): ?array
    {
        $addressEntity = [];
        /** @var CountryEntity $country */
        $country = $customerAddressEntity->getCountry();
        $addressEntity['countryIso'] = $country->getIso();
        $addressEntity['countryName'] = $country->getName();
        $addressEntity['city'] = $customerAddressEntity->getCity();

        return $addressEntity;
    }

    private function preparePromotionCollection(PromotionCollection $promotionCollection): ?array
    {
        $promotions = [];

        if ($promotionCollection->count() > 0) {
            foreach ($promotionCollection->getElements() as $promotionKey => $promotionEntity) {
                $promotions[$promotionKey]['id'] = $promotionEntity->getId();
                $promotions[$promotionKey]['name'] = $promotionEntity->getName();
                $promotions[$promotionKey]['name'] = $promotionEntity->getName();
                $promotions[$promotionKey]['percental'] = $promotionEntity->isPercental();
                $promotions[$promotionKey]['validFrom'] = $promotionEntity->getValidFrom()->format('Y-m-d H:i:s');
                $promotions[$promotionKey]['validUntil'] = $promotionEntity->getValidUntil()->format('Y-m-d H:i:s');
                $promotions[$promotionKey]['redeemable'] = $promotionEntity->getRedeemable();
                $promotions[$promotionKey]['exclusive'] = $promotionEntity->isExclusive();
                $promotions[$promotionKey]['priority'] = $promotionEntity->getPriority();
                $promotions[$promotionKey]['codeType'] = $promotionEntity->getCodeType();
                $promotions[$promotionKey]['code'] = $promotionEntity->getCode();
                $promotions[$promotionKey]['discounts'] = $promotionEntity->getDiscounts() ?: null;
            }
        } else {
            $promotions = null;
        }

        return $promotions;
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
