<?php declare(strict_types=1);

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
    const NAME_VALUE_EMAIL = 'email';
    const NAME_VALUE_AUTH_KEY = 'auth_key';
    const NAME_VALUE_ACCESS_TOKEN = 'access_token';
    const NAME_VALUE_REFRESH_TOKEN = 'refresh_token';
    const NAME_VALUE_CONVERSION_TRACKING = 'conversion_tracking';

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
