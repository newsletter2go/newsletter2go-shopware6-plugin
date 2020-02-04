<?php declare(strict_types=1);

namespace Newsletter2go\Entity;


use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class Newsletter2goConfigDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'newsletter2go_config';
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            new StringField('name', 'name'),
            new LongTextField('value', 'value'),
            new CreatedAtField(),
            new UpdatedAtField()
        ]);
    }

    public function getCollectionClass(): string
    {
        return Newsletter2goConfigCollection::class;
    }

    public function getEntityClass(): string
    {
        return Newsletter2goConfig::class;
    }
}
