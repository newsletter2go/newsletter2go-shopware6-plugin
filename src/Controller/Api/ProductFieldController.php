<?php

namespace Newsletter2go\Controller\Api;


use Newsletter2go\Model\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationCollection;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaCollection;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetDefinition;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSetRelation\CustomFieldSetRelationEntity;
use Shopware\Core\System\CustomField\CustomFieldEntity;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
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
     * @RouteScope(scopes={"api"})
     * @Route("/api/v{version}/n2g/products/fields", name="api.v.action.n2g.getProductFields", methods={"GET"})
     * @Route("/api/n2g/products/fields", name="api.action.n2g.getProductFields", methods={"GET"})
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

    private function getProductDefaultFields(): array
    {
        $defaultFields = [
            new Field('id'),
            new Field('name'),
            new Field('productNumber'),
            new Field('description'),
            new Field('additionalText'),
            new Field('url'),
            new Field('link'),
            new Field('stock', Field::DATATYPE_INTEGER),
            new Field('packUnit'),
            new Field('price', Field::DATATYPE_ARRAY),
            new Field('shippingFree', Field::DATATYPE_BOOLEAN),
            new Field('tax', Field::DATATYPE_ARRAY),
            new Field('media', Field::DATATYPE_ARRAY),
            new Field('manufacturer', Field::DATATYPE_ARRAY),
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

    public function prepareProductAttributes(ProductEntity $productEntity, $languageId = null): array
    {
        $preparedProductList = [];
        $fields = array_merge($this->getProductDefaultFields(), $this->_getProductCustomFields());

        //translations are set if correct language id is set
        if (!empty($productEntity->getTranslations()) && !empty(!empty($productEntity->getTranslations()->getElements()))) {
            $productEntity = $this->translateProduct($productEntity, $languageId);
        }

        /** @var Field $field */
        foreach ($fields as $field) {

            $fieldId = $field->getId();
            $isCustomField = strpos($fieldId, 'n2g_') === 0;

            if ($productEntity->has($fieldId)) {
                $attribute = $productEntity->get($fieldId);

                if (is_string($attribute) || is_numeric($attribute) || is_bool($attribute)) {
                    $preparedProductList[$fieldId] = $attribute;
                } elseif (is_null($attribute)) {
                    $preparedProductList[$fieldId] = '';
                } elseif ($attribute instanceof Entity) {
                    $preparedProductList[$fieldId] = $this->prepareEntity($attribute);
                } elseif ($attribute instanceof PriceCollection) {
                    $preparedProductList[$fieldId] = $this->preparePriceEntity($attribute);
                } elseif ($attribute instanceof ProductMediaCollection) {
                    $media = [];
                    /** @var ProductMediaEntity $mediaEntity */
                    foreach ($attribute->getElements() as $mediaEntity) {
                        if ($mediaEntity->getMedia()->getMediaType()->getName() === 'IMAGE') {
                            $media[] = $mediaEntity->getMedia()->getUrl();
                        }
                    }
                    $preparedProductList[$fieldId] = $media;
                }

            } elseif ($isCustomField && !empty($productEntity->getCustomFields())) {

                $customFields = $productEntity->getCustomFields();
                $customFieldOriginalName = substr($fieldId, 12);

                if (isset($customFields[$customFieldOriginalName])) {
                    $preparedProductList[$fieldId] = $customFields[$customFieldOriginalName];
                }
            } elseif ($fieldId === 'link') {
                $preparedProductList['url'] =  rtrim(getenv('APP_URL'), '/') . '/' ;
                $preparedProductList[$fieldId] = 'detail/' .$productEntity->getId();
            }

        }

        return $preparedProductList;
    }

    private function translateProduct(ProductEntity $productEntity, $languageId = null)
    {
        /** @var ProductTranslationCollection $productTranslations */
        $productTranslations = $productEntity->getTranslations();

        /** @var ProductTranslationEntity $productTranslation */
        if (!empty($languageId)) {
            foreach ($productTranslations as $productTranslation) {
                if ($productTranslation->getLanguageId() == $languageId) {
                    $translation = $productTranslation;
                }
            }
        }

        if (empty($translation)) {
            $translation = $productTranslations->first();
        }

        $TranslatedCustomFields = $translation->getCustomFields();
        //$productEntity->setAdditionalText($translation->getAdditionalText());
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

    private function preparePriceEntity(PriceCollection $price)
    {
        $price = $price->first();
        return [
            'net' => number_format($price->getNet(), 2, '.', '') ?: 0,
            'gross' => number_format($price->getGross(), 2, '.', '') ?: 0
        ];
    }

    private function prepareEntity(Entity $attribute): array
    {
        $entityAttributes = [];

        if ($attribute instanceof TaxEntity) {
            $entityAttributes['taxRate'] = $attribute->getTaxRate() ;
        } elseif ($attribute instanceof ProductManufacturerEntity) {
            $entityAttributes['name'] = $attribute->getName() ?: '';
        }

        return $entityAttributes;
    }
}
