<?php

namespace Newsletter2go\Controller\Api;


use Newsletter2go\Model\Field;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaCollection;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Shopware\Core\Framework\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\Framework\CustomField\Aggregate\CustomFieldSetRelation\CustomFieldSetRelationEntity;
use Shopware\Core\Framework\CustomField\CustomFieldEntity;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Pricing\Price;
use Shopware\Core\System\Tax\TaxEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ProductFieldController extends AbstractController
{

    private $customFieldRepository;

    /**
     * ProductFieldController constructor.
     * @param DefinitionInstanceRegistry $definitionInstanceRegistry
     * @param CustomFieldSetDefinition $customFieldSetDefinition
     */
    public function __construct(
        DefinitionInstanceRegistry $definitionInstanceRegistry,
        CustomFieldSetDefinition $customFieldSetDefinition
    ) {
        $this->customFieldRepository = $definitionInstanceRegistry->getRepository($customFieldSetDefinition->getEntityName());
    }

    /**
     * @Route("/api/{version}/n2g/products/fields", name="api.action.n2g.getProductFields", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getProductAttributes(Request $request, Context $context): JsonResponse
    {
        $data = [];
        $productFields = array_merge($this->getProductDefaultFields(), $this->_getProductCustomFields());

        /** @var Field $field */
        foreach ($productFields as $field) {
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

    /**
     * @param String $customFields .
     * @return array
     * @example customFields should be a string with a comma separated values e.g. 'firstName,lastName,phone'
     */
    public function getCustomerEntityFields(String $customFields): array
    {
        $fields = [];

        $customFields = $this->prepareCustomFields($customFields);

        $allCustomerFields = array_merge($this->getProductDefaultFields(), $this->_getProductCustomFields());
        if (count($customFields) === 0) {
            return $allCustomerFields;
        }

        /** @var Field $customerField */
        foreach ($allCustomerFields as $customerField) {
            //email and id must always be included
            if ($customerField->getId() === 'email' || $customerField->getId() === 'id') {
                $fields[] = $customerField;
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

    private function getProductDefaultFields(): array
    {
        $defaultFields = [
            new Field('id'),
            new Field('name'),
            new Field('description'),
            new Field('additionalText'),
            new Field('link'),
            new Field('productNumber'),
            new Field('price', Field::DATATYPE_ARRAY),
            new Field('shippingFree', Field::DATATYPE_BOOLEAN),
            new Field('tax', Field::DATATYPE_ARRAY),
            new Field('media', Field::DATATYPE_ARRAY),
        ];

        return $defaultFields;
    }

    private function _getProductCustomFields()
    {
        $fields = [];

        try {
            //get product custom fields
            $criteria = new Criteria();
            $criteria->addAssociation('customFields');
            $criteria->addAssociation('relations');
            $result = $this->customFieldRepository->search($criteria, Context::createDefaultContext());

            /** @var CustomFieldSetEntity $customFieldSetEntity */
            foreach ($result->getElements() as $customFieldSetEntity) {
                /** @var CustomFieldSetRelationEntity $relation */
                foreach ($customFieldSetEntity->getRelations()->getElements() as $relation) {
                    if ($relation->getEntityName() === 'product') {
                        /** @var CustomFieldEntity $customField */
                        foreach ($customFieldSetEntity->getCustomFields() as $customField) {
                            $fieldName = $customFieldSetEntity->getName() . '__' . $customField->getName();
                            $fieldDescription = !empty($customField->getTranslated()) ? reset($customField->getTranslated()) : '';
                            $fields[] = new Field(
                                'customField_' . $customField->getName(),
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

    public function prepareProductAttributes(ProductEntity $productEntity): array
    {
        $preparedCustomerList = [];
        $fields = array_merge($this->getProductDefaultFields(), $this->_getProductCustomFields());

        //translations are set if correct language id is set
        if (!empty($productEntity->getTranslations())) {
            $productEntity = $this->translateProduct($productEntity);
        }

        /** @var Field $field */
        foreach ($fields as $field) {

            $fieldId = $field->getId();
            $isCustomField = strpos($fieldId, 'customField_') === 0;

            if ($productEntity->has($fieldId)) {
                $attribute = $productEntity->get($fieldId);

                if (is_string($attribute) || is_numeric($attribute) || is_bool($attribute)) {
                    $preparedCustomerList[$fieldId] = $attribute;
                } elseif (is_null($attribute)) {
                    $preparedCustomerList[$fieldId] = '';
                } elseif ($attribute instanceof Entity) {
                    $preparedCustomerList[$fieldId] = $this->prepareEntity($attribute);
                } elseif ($attribute instanceof Price) {
                    $preparedCustomerList[$fieldId] = $this->preparePriceEntity($attribute);
                } elseif ($attribute instanceof ProductMediaCollection) {
                    $media = [];
                    /** @var ProductMediaEntity $mediaEntity */
                    foreach ($attribute->getElements() as $mediaEntity) {
                        if ($mediaEntity->getMedia()->getMediaType()->getName() === 'IMAGE') {
                            $media[] = $mediaEntity->getMedia()->getUrl();
                        }
                    }
                    $preparedCustomerList[$fieldId] = $media;
                }

            } elseif ($isCustomField && !empty($productEntity->getCustomFields())) {

                $customFields = $productEntity->getCustomFields();
                $customFieldOriginalName = substr($fieldId, 12);

                if (isset($customFields[$customFieldOriginalName])) {
                    $preparedCustomerList[$fieldId] = $customFields[$customFieldOriginalName];
                }
            } elseif ($fieldId === 'link') {
                $preparedCustomerList[$fieldId] =  rtrim(getenv('APP_URL'), '/') . '/detail/' .$productEntity->getId();
            }

        }

        return $preparedCustomerList;
    }

    private function translateProduct(ProductEntity $productEntity)
    {
        /** @var ProductTranslationEntity $translation */
        $translation = $productEntity->getTranslations()->first();
        $TranslatedCustomFields = $translation->getCustomFields();
        $productEntity->setAdditionalText($translation->getAdditionalText());
        $productEntity->setName($translation->getName());
        $productEntity->setDescription($translation->getDescription());
        $productEntityCustomFields = $productEntity->getCustomFields();

        if (is_array($productEntityCustomFields) && is_array($TranslatedCustomFields)) {
            foreach ($productEntityCustomFields as $key => $value) {
                if (!isset($TranslatedCustomFields)) {
                    $TranslatedCustomFields[] = $productEntityCustomFields[$key];
                }
            }

            $productEntity->setCustomFields($TranslatedCustomFields);
        }

        return $productEntity;
    }

    private function preparePriceEntity(Price $price)
    {
        return [
            'net' => $price->getNet() ?: '',
            'gross' => $price->getGross() ?: ''
        ];
    }

    private function prepareEntity(Entity $attribute): array
    {
        $entityAttributes = [];

        if ($attribute instanceof TaxEntity) {
            $entityAttributes['taxRate'] = $attribute->getTaxRate() ;
        }

        return $entityAttributes;
    }
}
