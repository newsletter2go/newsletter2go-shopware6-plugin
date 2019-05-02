<?php

namespace Newsletter2go\Service;


use Newsletter2go\Entity\Newsletter2goConfig;
use Newsletter2go\Entity\Newsletter2goConfigDefinition;
use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class Newsletter2goConfigService
{
    /** @var ContainerInterface $container */
    private $container;

    /** @var Context $context */
    private $context;

    /** @var EntityRepository $n2gConfigRepository */
    private $n2gConfigRepository;

    /**
     * Newsletter2goConfigService constructor.
     * @param Newsletter2goConfigDefinition $newsletter2goConfigDefinition
     */
    public function __construct(Newsletter2goConfigDefinition $newsletter2goConfigDefinition)
    {
        $this->n2gConfigRepository = $newsletter2goConfigDefinition;
        $this->context = Context::createDefaultContext();
    }

    /**
     * @param array|String $fieldName
     * @return null|array
     * @throws InconsistentCriteriaIdsException
     */
    public function getConfigByFieldNames($fieldName): ?array
    {
        $result = null;

        $criteria = new Criteria();

        if (!is_array($fieldName)) {
            $fieldName = [$fieldName];
        }

        $criteria->addFilter(new EqualsAnyFilter(Newsletter2goConfig::FIELD_NAME, $fieldName));
        $result = $this->n2gConfigRepository->search($criteria, $this->context);

        return $result->getElements();
    }

    public function addConfig(String $name, String $value): ?String
    {
        $event = $this->n2gConfigRepository->create([
            [
                'name' => $name,
                'value' => $value
            ]
        ],
            $this->context
        );

        /** @var EntityWrittenEvent $config */
        $config = $event->getEvents()->getElements()[0];

        return $config->getIds()[0];
    }

    public function deleteConfigByName(String $name): bool
    {
        /** @var array $result */
        $result = $this->getConfigByFieldNames($name);

        if (count($result) > 0) {
            /** @var Newsletter2goConfig $config */
            $config = reset($result);
            $errors = $this->n2gConfigRepository->delete([['id' => $config->getId()]], $this->context)->getErrors();
            if (count($errors) === 0) {
                return true;
            }
        }

        return false;
    }
}
