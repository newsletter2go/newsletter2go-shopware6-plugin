<?php

namespace Newsletter2go\Subscriber;


use Newsletter2go\Entity\Newsletter2goConfig;
use Newsletter2go\Service\CookieProviderService;
use Newsletter2go\Service\Newsletter2goConfigService;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class ConversionTracking implements EventSubscriberInterface
{

    private $configService;

    /**
     * ConversionTracking constructor.
     * @param Newsletter2goConfigService $configService
     */
    public function __construct(Newsletter2goConfigService $configService)
    {
        $this->configService = $configService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutFinishPageLoadedEvent::class => ['onCheckoutFinishPageLoaded', 1]
        ];
    }

    public function onCheckoutFinishPageLoaded(CheckoutFinishPageLoadedEvent $event)
    {
        $assignments = ['conversionTracking' => false];

        try {
            $isCookieAllowed = $event->getRequest()->cookies->get('cookie-preference')
                && $event->getRequest()->cookies->get(CookieProviderService::COOKIE_KEY);

            $configFields = [
                Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING,
                Newsletter2goConfig::NAME_VALUE_COMPANY_ID
            ];
            $result = $this->configService->getConfigByFieldNames($configFields);

            if ($isCookieAllowed && !empty($result) && count($result) === count($configFields)) {
                /** @var Newsletter2goConfig $newsletter2goConfig */
                foreach ($result as $newsletter2goConfig) {
                    //check if conversion tracking is activated
                    if ($newsletter2goConfig->getName() === Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING && $newsletter2goConfig->getValue() === 'true') {
                        $assignments['conversionTracking'] = true;
                    } elseif ($newsletter2goConfig->getName() === Newsletter2goConfig::NAME_VALUE_COMPANY_ID) {
                        $assignments['companyId'] = $newsletter2goConfig->getValue();
                    }
                }
            }

        } catch (\Exception $exception) {

        }

        $event->getPage()->assign($assignments);
    }
}
