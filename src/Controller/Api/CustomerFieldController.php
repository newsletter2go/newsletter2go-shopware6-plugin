<?php

namespace Newsletter2go\Controller\Api;


use Newsletter2go\Model\Field;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Promotion\PromotionCollection;
use Shopware\Core\Checkout\Promotion\PromotionEntity;
use Shopware\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSetRelation\CustomFieldSetRelationEntity;
use Shopware\Core\System\CustomField\CustomFieldEntity;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CustomerFieldController extends AbstractController
{
    const NEWSLETTER_RECEIVER_STATUS_SUBSCRIBED = 'subscribed';
    const NEWSLETTER_RECEIVER_STATUS_UNSUBSCRIBED = 'unsubscribed';

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
     * @RouteScope(scopes={"api"})
     * @Route("/api/v{version}/n2g/customers/fields", name="api.v.action.n2g.getCustomerFields", methods={"GET"})
     * @Route("/api/n2g/customers/fields", name="api.action.n2g.getCustomerFields", methods={"GET"})
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

        return new JsonResponse(
            [
                'success' => true,
                'data' => $data
            ]
        );
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
                            $translated = $customField->getTranslated();
                            $fieldDescription = !empty($translated) ? reset($translated) : '';
                            $fields[] = new Field(
                                'n2g_' . $customField->getName(),
                                DatatypeHelper::convertToN2gDatatype($customField->getType()),
                                $fieldName,
                                $fieldDescription
                            );
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

        if (empty($customFields)) {
            $customFields = '[]';
        }

        $customFields = json_decode($customFields, true);

        $allCustomerFields = array_merge($this->getCustomerDefaultFields(), $this->_getCustomerCustomFields());
        if (count($customFields) === 0) {
            return $allCustomerFields;
        }

        /** @var Field $customerField */
        foreach ($allCustomerFields as $customerField) {
            //email and id must always be included
            if ($customerField->getId() === 'id' || $customerField->getId() === 'newsletter') {
                $fields[] = $customerField;
            } elseif (in_array($customerField->getId(), $customFields)) {
                $fields[] = $customerField;
            }
        }

        return $fields;
    }

    private function getCustomerDefaultFields(): array
    {
        $defaultFields = [
            new Field('id'),
            new Field('orderCount', Field::DATATYPE_INTEGER),
            new Field('groupId', Field::DATATYPE_STRING),
            new Field('salutation', Field::DATATYPE_STRING, 'Title'),
            new Field('email'),
            new Field('language'),
            new Field('firstName'),
            new Field('lastName'),
            new Field('guest', Field::DATATYPE_BOOLEAN),
            new Field('newsletter', Field::DATATYPE_BOOLEAN),
            new Field('birthday', Field::DATATYPE_DATE),
            new Field('billingCountry', Field::DATATYPE_STRING),
            new Field('billingCity', Field::DATATYPE_STRING),
            new Field('defaultPaymentMethod', Field::DATATYPE_DATE),
            new Field('createdAt', Field::DATATYPE_DATE),
            new Field('updatedAt', Field::DATATYPE_DATE),
            new Field('salesChannelId', Field::DATATYPE_STRING),
            new Field('salesChannelName', Field::DATATYPE_STRING),
            //new Field('promotions', Field::DATATYPE_ARRAY),
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

                $fieldId = $field->getId();
                $isCustomField = strpos($fieldId, 'n2g_') === 0 ;

                if ($customerEntity->has($fieldId)) {
                    $attribute = $customerEntity->get($fieldId);

                    if (is_string($attribute) || is_numeric($attribute) || is_bool($attribute)) {
                        $preparedCustomerList[$key][$fieldId] = $attribute;
                    } elseif (is_null($attribute)) {
                        $preparedCustomerList[$key][$fieldId] = '';
                    } elseif ($attribute instanceof SalutationEntity) {
                        $preparedCustomerList[$key][$fieldId] = $attribute->getDisplayName();
                    } elseif ($attribute instanceof PaymentMethodEntity) {
                        $preparedCustomerList[$key][$fieldId] = $attribute->getName();
                    } elseif ($attribute instanceof LanguageEntity) {
                        $preparedCustomerList[$key][$fieldId] = $attribute->getName();
                    } else {

                        if ($attribute instanceof EntityCollection) {

                            if ($attribute instanceof PromotionCollection) {
                                $preparedCustomerList[$key][$fieldId] = $this->preparePromotionCollection($attribute);
                            }

                        } else {

                            if ($attribute instanceof \DateTimeImmutable) {
                                $preparedCustomerList[$key][$fieldId] = $attribute->format('Y-m-d');
                            }

                        }
                    }

                } elseif ($isCustomField && !empty($customerEntity->getCustomFields())) {

                    $customFields = $customerEntity->getCustomFields();
                    $customFieldOriginalName = substr($fieldId, 4); //remove prefix "n2g_"

                    if (isset($customFields[$customFieldOriginalName])) {
                        $preparedCustomerList[$key][$fieldId] = $customFields[$customFieldOriginalName];
                    }
                } elseif ($field->getId() === 'billingCountry') {
                    $defaultBillingAddress = $customerEntity->getDefaultBillingAddress();
                    if ($defaultBillingAddress instanceof CustomerAddressEntity) {
                        $country = $defaultBillingAddress->getCountry();
                        if ($country instanceof CountryEntity) {
                            $preparedCustomerList[$key][$fieldId] = $country->getName();
                        }
                    }
                } elseif($field->getId() === 'billingCity') {
                    $defaultBillingAddress = $customerEntity->getDefaultBillingAddress();
                    if ($defaultBillingAddress instanceof CustomerAddressEntity) {
                        $preparedCustomerList[$key][$fieldId] = $defaultBillingAddress->getCity();
                    }
                } elseif($field->getId() === 'defaultPaymentMethod') {
                    $defaultPaymentMethod = $customerEntity->getDefaultPaymentMethod();
                    if ($defaultPaymentMethod instanceof PaymentMethodEntity) {
                        $preparedCustomerList[$key][$fieldId] = $defaultPaymentMethod->getName();
                    }
                } elseif($field->getId() === 'salesChannelName') {
                    $salesChannel = $customerEntity->getSalesChannel();
                    if ($salesChannel instanceof SalesChannelEntity) {
                        $preparedCustomerList[$key][$fieldId] = $salesChannel->getName();
                    }
                } else {
                    $preparedCustomerList[$key][$fieldId] = '';
                }

            }
        }

        return $preparedCustomerList;
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
                $promotions[$promotionKey]['name'] = $promotionEntity->getName() ?: '';
                $promotions[$promotionKey]['validFrom'] = $promotionEntity->getValidFrom()->format('Y-m-d H:i:s') ?: '';
                $promotions[$promotionKey]['validUntil'] = $promotionEntity->getValidUntil()->format('Y-m-d H:i:s') ?: '';
                $promotions[$promotionKey]['exclusive'] = $promotionEntity->isExclusive() ?: '';
                $promotions[$promotionKey]['code'] = $promotionEntity->getCode() ?: '';
            }
        }

        return $promotions;
    }

    public function prepareNewsletterReceiver(array $newsletterReceiverList, $fields = [])
    {
        $preparedList = [];

        /** @var NewsletterRecipientEntity $newsletterReceiver */
        foreach ($newsletterReceiverList as $newsletterReceiver) {
            $preparedList[$newsletterReceiver->getId()] = [
                'id' => $newsletterReceiver->getId()
            ];

            if (!empty($fields)) {
                /** @var Field $field */
                foreach ($fields as $field) {
                    $fieldId = $field->getId();
                    if ($fieldId === 'id') {
                        continue;
                    }

                    switch ($fieldId) {
                        case 'email':
                            $preparedList[$newsletterReceiver->getId()][$fieldId] = $newsletterReceiver->getEmail() ?: '';
                            break;
                        case 'firstName':
                            $preparedList[$newsletterReceiver->getId()][$fieldId] = $newsletterReceiver->getFirstName() ?: '';
                            break;
                        case 'lastName':
                            $preparedList[$newsletterReceiver->getId()][$fieldId] =$newsletterReceiver->getLastName() ?: '';
                            break;
                        case 'groupId':
                            $preparedList[$newsletterReceiver->getId()][$fieldId] = GroupController::GROUP_NEWSLETTER_RECIPIENT;
                            break;
                        case 'newsletter':
                            $preparedList[$newsletterReceiver->getId()][$fieldId] = !in_array($newsletterReceiver->getStatus(), [
                                NewsletterSubscribeRoute::STATUS_OPT_OUT,
                                NewsletterSubscribeRoute::STATUS_NOT_SET
                            ]);
                            break;
                        case 'billingCountry':
                            $preparedList[$newsletterReceiver->getId()][$fieldId] = '';
                            break;
                        case 'billingCity':
                            $preparedList[$newsletterReceiver->getId()][$fieldId] = $newsletterReceiver->getCity() ?: '';
                            break;
                        case 'defaultPaymentMethod':
                            $preparedList[$newsletterReceiver->getId()][$fieldId] = '';
                            break;
                        case 'salesChannelId':
                            $newsletterRecipientSAlesChannel = $newsletterReceiver->getSalesChannel();
                            if ($newsletterRecipientSAlesChannel instanceof SalesChannelEntity) {
                                $preparedList[$newsletterReceiver->getId()][$fieldId] = $newsletterRecipientSAlesChannel->getId() ?: '';
                            }
                            break;
                        case 'salesChannelName':
                            $preparedList[$newsletterReceiver->getId()][$fieldId] = $newsletterReceiver->getLastName() ?: '';
                            break;
                        case 'language':
                            $recipientLanguage = $newsletterReceiver->getLanguage();
                            if ($recipientLanguage instanceof \Shopware\Core\System\Language\LanguageEntity) {
                                $preparedList[$newsletterReceiver->getId()][$fieldId] = $newsletterReceiver->getLanguage()->getName() ?: '';
                            }
                            break;
                        case 'updatedAt':
                            $preparedList[$newsletterReceiver->getId()][$fieldId] = $newsletterReceiver->getUpdatedAt() ?: '';
                            break;
                        case strpos($fieldId, 'n2g_') === 0:
                            $preparedList[$newsletterReceiver->getId()][$fieldId] = $this->prepareCustomFields($newsletterReceiver, $fieldId);
                            break;
                        default:
                            $preparedList[$newsletterReceiver->getId()][$fieldId] = '';
                    }
                }
            }
        }

        return $preparedList;
    }

    private function prepareCustomFields(NewsletterRecipientEntity $newsletterReceiver, $fieldId)
    {
        $result = '';
        $fieldWithoutPrefix = substr($fieldId, 4);
        $customFields = $newsletterReceiver->getCustomFields();
        if (!empty($customFields)) {
            if (isset($customFields[$fieldWithoutPrefix])) {
                $result = $customFields[$fieldWithoutPrefix];
            }
        }

        return $result;
    }
}
