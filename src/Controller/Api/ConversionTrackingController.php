<?php

namespace Newsletter2go\Controller\Api;


use Newsletter2go\Entity\Newsletter2goConfig;
use Newsletter2go\Service\Newsletter2goConfigService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ConversionTrackingController
{
    private $newsletter2goConfigService;

    /**
     * ConversionTrackingController constructor.
     * @param Newsletter2goConfigService $newsletter2goConfigService
     */
    public function __construct(Newsletter2goConfigService $newsletter2goConfigService)
    {
        $this->newsletter2goConfigService = $newsletter2goConfigService;
    }

    /**
     * @Route(path="/api/v{version}/n2g/tracking", name="api.action.n2g.updateTracking", methods={"PUT"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function updateConversionTracking(Request $request, Context $context)
    {
        $response = [];
        try {
            $result = $this->newsletter2goConfigService->getConfigByFieldNames(Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING);
            if (empty($result)) {
                $this->newsletter2goConfigService->addConfig([Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING => 'false']);

                $response[Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING] = false;

            } else {
                $conversionTracking = $request->get(Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING, false);
                $conversionTrackingString = ($conversionTracking === true) ? 'true': 'false';
                $this->newsletter2goConfigService->updateConfigs(['conversion_tracking' => $conversionTrackingString]);
                $response[Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING] = $conversionTracking;
            }

        } catch (\Exception $exception) {
            $response[Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }

    /**
     * @Route(path="/api/v{version}/n2g/tracking", name="api.action.n2g.getTracking", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function getConversionTracking(Request $request, Context $context) : JsonResponse
    {
        $response = [];

        try {
            $result = $this->newsletter2goConfigService->getConfigByFieldNames(Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING);
            if (count($result) > 0) {
                /** @var Newsletter2goConfig $conversionTracking */
                $conversionTracking = reset($result);
                $booleanConversionTracking = ($conversionTracking->getValue() === 'true');
                $response[Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING] = $booleanConversionTracking;
            } else {
                $this->newsletter2goConfigService->addConfig([Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING => 'false']);
                $response[Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING] = false;
            }

        } catch (\Exception $exception) {
            $response[Newsletter2goConfig::NAME_VALUE_CONVERSION_TRACKING] = false;
            $response['error'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }
}
