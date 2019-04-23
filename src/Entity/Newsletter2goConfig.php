<?php

namespace Newsletter2go\Entity;


use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class Newsletter2goConfig extends Entity
{
    const TABLE_NAME = 'newsletter2go_config';
    const FIELD_NAME = 'name';
    const FIELD_VALUE = 'value';
    const NAME_VALUE_ACCESS_KEY = 'accessKey';
    const NAME_VALUE_SECRET_ACCESS_KEY = 'secretAccessKey';
    const NAME_VALUE_COMPANY_ID = 'companyId';
    const NAME_VALUE_SHOPWARE_INTEGRATION_LABEL = 'integrationLabel';


    use EntityIdTrait;

    /**
     *  @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $value;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

}
