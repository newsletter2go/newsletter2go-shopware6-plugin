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
            new Field('guest', Field::DATATYPE_BOOLEAN),
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
                $newAttribute = null;
                $fieldId = $field->getId();
                $isCustomField = strpos($fieldId, 'customField_') === 0 ;
                if ($customerEntity->has($fieldId)) {
                    $attribute = $customerEntity->get($fieldId);

                    if (is_string($attribute) || is_numeric($attribute)) {
                        $preparedCustomerList[$key][$fieldId] = $attribute;
                        continue;

                    }else if ($attribute instanceof Entity) {
                        $newAttribute = $this->prepareEntity($fieldId, $attribute);
                    } else {
                        if ($attribute instanceof EntityCollection) {
                            if ($attribute instanceof PromotionCollection) {
                                $newAttribute = $this->preparePromotionCollection($fieldId, $attribute);
                            }
                        } else {
                            if ($attribute instanceof \DateTimeImmutable) {
                                $newAttribute = [$attribute->format('Y-m-d H:i:s')];
                            }
                        }
                    }
                } elseif ($isCustomField && !empty($customerEntity->getCustomFields())) {
                    $customFields = $customerEntity->getCustomFields();
                    $customFieldOriginalName = substr($fieldId, 12);
                    if (isset($customFields[$customFieldOriginalName])) {
                        $newAttribute = $customFields[$customFieldOriginalName];
                    }
                }

                if (!empty($newAttribute) && !$isCustomField) {
                    if (!is_array($newAttribute)) {
                        var_dump($newAttribute);
                    }
                    $preparedCustomerList[$key] = array_merge($preparedCustomerList[$key], $newAttribute);
                } else {
                    $preparedCustomerList[$key][$fieldId] = null;
                }
            }
        }

        return $preparedCustomerList;
    }

    private function prepareEntity($fieldId, Entity $entity): ?array
    {
        $preparedEntity = [];
        if ($entity instanceof CustomerAddressEntity) {
            $preparedEntity = $this->prepareCustomerAddressEntity($fieldId, $entity);
        } elseif ($entity instanceof SalutationEntity) {
            $preparedEntity[$fieldId . ucfirst('displayName')] = $entity->getDisplayName();
            $preparedEntity[$fieldId . ucfirst('letterName')] = $entity->getLetterName();
        } else {

            if (property_exists($entity, 'id')) {
                $preparedEntity[$fieldId . ucfirst('id')] = $entity->getUniqueIdentifier();
            }
            if (property_exists($entity, 'name')) {
                $preparedEntity[$fieldId . ucfirst('name')] = $entity->get('name');
            }
        }

        return $preparedEntity;
    }

    private function prepareCustomerAddressEntity($fieldId, CustomerAddressEntity $customerAddressEntity): ?array
    {
        $addressEntity = [];
        /** @var CountryEntity $country */
        $country = $customerAddressEntity->getCountry();
        $addressEntity[$fieldId . ucfirst('countryIso')] = $country->getIso();
        $addressEntity[$fieldId . ucfirst('countryName')] = $country->getName();
        $addressEntity[$fieldId . ucfirst('city')] = $customerAddressEntity->getCity();

        return $addressEntity;
    }

    private function preparePromotionCollection($fieldId, PromotionCollection $promotionCollection): ?array
    {
        $promotions = [];

        if ($promotionCollection->count() > 0) {
            /**
             * @var string $promotionKey
             * @var PromotionEntity $promotionEntity
             */
            foreach ($promotionCollection->getElements() as $promotionKey => $promotionEntity) {
                $promotions[$promotionKey][$fieldId . ucfirst('id')] = $promotionEntity->getId();
                $promotions[$promotionKey][$fieldId . ucfirst('name')] = $promotionEntity->getName();
                $promotions[$promotionKey][$fieldId . ucfirst('name')] = $promotionEntity->getName();
                $promotions[$promotionKey][$fieldId . ucfirst('percental')] = $promotionEntity->isPercental();
                $promotions[$promotionKey][$fieldId . ucfirst('validFrom')] = $promotionEntity->getValidFrom()->format('Y-m-d H:i:s');
                $promotions[$promotionKey][$fieldId . ucfirst('validUntil')] = $promotionEntity->getValidUntil()->format('Y-m-d H:i:s');
                $promotions[$promotionKey][$fieldId . ucfirst('redeemable')] = $promotionEntity->getRedeemable();
                $promotions[$promotionKey][$fieldId . ucfirst('exclusive')] = $promotionEntity->isExclusive();
                $promotions[$promotionKey][$fieldId . ucfirst('priority')] = $promotionEntity->getPriority();
                $promotions[$promotionKey][$fieldId . ucfirst('codeType')] = $promotionEntity->getCodeType();
                $promotions[$promotionKey][$fieldId . ucfirst('code')] = $promotionEntity->getCode();
                $promotions[$promotionKey][$fieldId . ucfirst('discounts')] = $promotionEntity->getDiscounts() ?: null;
            }
        }

        return $promotions;
    }
}
