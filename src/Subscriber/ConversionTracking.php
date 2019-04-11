<?php

namespace Swag\Newsletter2go\Subscriber;


use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class ConversionTracking extends EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_LOADED_EVENT => 'onProductPurchaseSuccess'
        ];
    }

    public function onProductPurchaseSuccess(EntityLoadedEvent $event)
    {
        echo var_dump($_SERVER);
    }
}
