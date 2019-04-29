<?php

namespace Newsletter2go\Entity;


use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class Newsletter2goConfigDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'newsletter2go_config';
    }

    public static function getCollectionClass(): string
    {
        return Newsletter2goConfigCollection::class;
    }

    public static function getEntityClass(): string
    {
        return Newsletter2goConfig::class;
    }

    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new StringField('name', 'name'),
            new StringField('value', 'value'),
            new CreatedAtField(),
            new UpdatedAtField()
        ]);
    }

}
