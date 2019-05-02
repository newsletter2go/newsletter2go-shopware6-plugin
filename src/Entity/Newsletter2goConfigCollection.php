<?php declare(strict_types=1);

namespace Newsletter2go\Entity;


use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class Newsletter2goConfigCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return Newsletter2goConfig::class;
    }

}
