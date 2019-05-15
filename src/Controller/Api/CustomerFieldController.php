<?php

namespace Newsletter2go\Controller\Api;


use Newsletter2go\Model\Field;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Promotion\PromotionCollection;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Shopware\Core\Framework\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\Framework\CustomField\Aggregate\CustomFieldSetRelation\CustomFieldSetRelationEntity;
use Shopware\Core\Framework\CustomField\CustomFieldEntity;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CustomerFieldController extends AbstractController
{
    private $customFieldSetRepository;
    /**
     * CustomerFieldController constructor.
     * @param DefinitionInstanceRegistry $definitionInstanceRegistry
     * @param CustomFieldSetDefinition $customFieldSetDefinition
     */
    public function __construct(DefinitionInstanceRegistry $definitionInstanceRegistry, CustomFieldSetDefinition $customFieldSetDefinition)
    {
        $this->customFieldSetRepository = $definitionInstanceRegistry->getRepository($customFieldSetDefinition->getEntityName());
    }

    /**
     * @Route("/api/{version}/n2g/customers/fields", name="api.action.n2g.getCustomerFields", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getCustomerFields(Request $request, Context $context): JsonResponse
    {
        $data = [];
        $customerFields = array_merge($this->getCustomerDefaultFields(), $this->_getCustomerCustomFields());
        /** @var Field $field */
        foreach ($customerFields as $field) {
            if ($field->getId() === 'customFields') {
                continue;
            }

            $data[] = [
                Field::FIELD_ID => $field->getId(),
                Field::FIELD_NAME => $field->getName(),
                Field::FIELD_TYPE => $field->getType(),
                Field::FIELD_DESCRIPTION => $field->getDescription()
            ];
        }

        return new JsonResponse(['success' => true, 'data' => $data]);
    }

    private function _getCustomerCustomFields()
    {
        $fields = [];
        try {
            //get customer custom fields
            $criteria = new Criteria();
            $criteria->addAssociation('customFields');
            $criteria->addAssociation('relations');
            $result = $this->customFieldSetRepository->search($criteria, Context::createDefaultContext());

            /** @var CustomFieldSetEntity $customFieldSetEntity */
            foreach ($result->getElements() as $customFieldSetEntity) {
                /** @var CustomFieldSetRelationEntity $relation */
                foreach ($customFieldSetEntity->getRelations()->getElements() as $relation) {
                    if ($relation->getEntityName() === 'customer') {
                        /** @var CustomFieldEntity $customField */
                        foreach ($customFieldSetEntity->getCustomFields() as $customField) {
                            $fieldName = $customFieldSetEntity->getName() . '__' . $customField->getName();
                            $fieldDescription = !empty($customField->getTranslated()) ? reset($customField->getTranslated()) : '';
                            $fields[] = new Field('customField_' . $customField->getName(), $customField->getType(), $fieldName, $fieldDescription);
                        }
                    }

                }
            }

        } catch (\Exception $exception) {
            //
        }

        return $fields;
    }

    /**
     * @param String $customFields.
     * @return array
     * @example customFields should be a string with a comma separated values e.g. 'firstName,lastName,phone'
     */
    public function getCustomerEntityFields(String $customFields): array
    {
        $fields = [];

        $customFields = $this->prepareCustomFields($customFields);

        $allCustomerFields = array_merge($this->getCustomerDefaultFields(), $this->_getCustomerCustomFields());
        if (count($customFields) === 0) {
            return $allCustomerFields;
        }

        /** @var Field $customerField */
        foreach ($allCustomerFields as $customerField) {
            //email and id must always be included
            if ($customerField->getId() === 'email' || $customerField->getId() === 'id') {
                $fields[] = $customerField;
//            } elseif ((strpos($customerField->getId(), 'customField_') === 0)) {
//                $fields[] = $customerField;
            } elseif (in_array($customerField->getId(), $customFields)) {
                $fields[] = $customerField;
            }
        }

        return $fields;
    }

    /**
     * @param $customFields
     * @return array
     * @example $customFields should be a string with a comma separated values e.g. 'firstName,lastName,phone'
     */
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
            new Field('customFields', Field::DATATYPE_ARRAY)
        ];

        return $defaultFields;
    }

    public function prepareCustomerAttributes(EntityCollection $customerList, array $fields): array
    {
        $preparedCustomerList = [];
        /**
         * @var String $key
         * @var CustomerEntity $customerEntity
         */
        foreach ($customerList as $key => $customerEntity) {
            /** @var Field $field */
            foreach ($fields as $field) {
                $isCustomField = strpos($field->getId(), 'customField_') === 0 ;
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
                            } else { //is string
                                $preparedCustomerList[$key][$field->getId()] = $attribute;
                            }
                        }
                    }
                } elseif ($isCustomField && !empty($customerEntity->getCustomFields())) {
                    $customFields = $customerEntity->getCustomFields();
                    $customFieldOriginalName = substr($field->getId(), 12);
                    if (isset($customFields[$customFieldOriginalName])) {
                        $preparedCustomerList[$key][$field->getId()] = $customFields[$customFieldOriginalName];
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
            /**
             * @var string $promotionKey
             * @var PromotionEntity $promotionEntity
             */
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
}
