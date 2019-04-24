<?php

namespace Newsletter2go\Controller;


use Newsletter2go\Entity\Newsletter2goConfig;
use Newsletter2go\Entity\Newsletter2goConfigCollection;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Shopware\Core\System\Integration\IntegrationEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BackendController extends AbstractController
{
    const CONNECTOR_URL = 'https://ui.newsletter2go.com/integrations/connect/SW6/';

    /**
     * @Route(path="/api/{version}/n2g/backend", name="api.action.n2g.backend", methods={"GET"})
     * @param Request $request
     * @param Context $context
     * @return Newsletter2goConfigCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getNewsletter2goConfig(Request $request, Context $context): Newsletter2goConfigCollection
    {
        $this->createIntegration($context);
        $dd = $this->getConnectorUrlParams($request);

        $repository = $this->container->get('newsletter2go_config.repository');
        $result = $repository->search(new Criteria(), Context::createDefaultContext());

        return $result->getElements();
    }

    private function getConnectorUrlParams(Request $request)
    {
        $params = [];
        $params['version'] = $this->getPluginVersion();
        $params['apiVersion'] = $request->get('version');
        $params['url'] = $request->getSchemeAndHttpHost();
        $params['callback'] = $request->getSchemeAndHttpHost() . '/admin';

        try {
            /** @var EntityRepositoryInterface $n2gConfigRepository */
            $n2gConfigRepository = $this->container->get('newsletter2go_config.repository');
            $n2gConfigElements = $n2gConfigRepository->search((new Criteria())->addFilter(new EqualsAnyFilter('name', ['accessKey', 'secretAccessKey'])), Context::createDefaultContext())->getElements();

            /** @var Newsletter2goConfig $n2gConfigElement */
            foreach ($n2gConfigElements as $n2gConfigElement) {
                if ($n2gConfigElement->getName() === 'accessKey') {
                    $params['accessKey'] = $n2gConfigElement->getValue();
                } elseif ($n2gConfigElement->getName() === 'secretAccessKey') {
                    $params['secretAccessKey'] = $n2gConfigElement->getValue();;
                }
            }

        } catch (\Exception $exception) {
            //
        }

        return $params;
    }

    private function getPluginVersion(): string
    {
        /** @var EntityRepositoryInterface $pluginRepository */
        $pluginRepository = $this->container->get('plugin.repository');
        $plugins = $pluginRepository->search(new Criteria(), Context::createDefaultContext());

        /** @var PluginEntity $plugin */
        foreach ($plugins->getElements() as $plugin) {
            if ($plugin->get('name') === 'Newsletter2go\Newsletter2go') {

                return $plugin->get('version');
            }
        }

        return false;
    }


    private function createIntegration(Context $context)
    {
        /** @var EntityRepositoryInterface $integrationRepository */
        $integrationRepository = $this->container->get('integration.repository');

        $accessKey = AccessKeyHelper::generateAccessKey('integration');
        $secretAccessKey = AccessKeyHelper::generateSecretAccessKey();
        $integrationLabel = 'Newsletter2Go - ' . (new \DateTime())->format('Y-m-d H:i:s');

        $data = [
            'label' => $integrationLabel,
            'accessKey' => $accessKey,
            'secretAccessKey' => $secretAccessKey,
            'writeAccess' => true
        ];

        try {
            /** @var IntegrationEntity $integrationEntity */
            $integrationEntity = $integrationRepository->create([$data], $context);

            /** @var EntityRepositoryInterface $n2gConfigRepository */
            $n2gConfigRepository = $this->container->get('newsletter2go_config.repository');
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsAnyFilter(Newsletter2goConfig::FIELD_NAME,
                [
                    Newsletter2goConfig::NAME_VALUE_ACCESS_KEY,
                    Newsletter2goConfig::NAME_VALUE_SECRET_ACCESS_KEY,
                ]
            ));
            /** @var EntitySearchResult $n2gElements */
            $n2gElements = $n2gConfigRepository->search($criteria, $context);

            if (count($n2gElements->getIds()) > 0) {
                $elementIds = [];
                foreach ($n2gElements->getIds() as $id) {
                    $elementIds[] = ['id' => $id];
                }

                $n2gConfigRepository->delete($elementIds, $context);
            }

            $n2gConfigRepository->create([
                ['name' => Newsletter2goConfig::NAME_VALUE_ACCESS_KEY, 'value' => $accessKey],
                ['name' => Newsletter2goConfig::NAME_VALUE_SECRET_ACCESS_KEY, 'value' => $secretAccessKey],
            ],$context);

        } catch (\Exception $exception) {
            //
        }
    }
}
