<?php declare(strict_types=1);

namespace Newsletter2go;

use Newsletter2go\Entity\Newsletter2goConfig;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\Integration\IntegrationEntity;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Newsletter2go extends Plugin
{
    public function install(InstallContext $context): void
    {
        // your code you need to execute while installation
    }

    public function postInstall(InstallContext $context): void
    {
        // your code you need to execute after your plugin gets installed
    }

    public function update(UpdateContext $context): void
    {
        // your code you need to execute while your plugin gets updated
    }

    public function activate(ActivateContext $context): void
    {
        // your code you need to execute while your plugin gets activated
    }

    public function deactivate(DeactivateContext $context): void
    {
        // your code you need to run while your plugin gets deactivated
    }

    public function uninstall(UninstallContext $context): void
    {
        try {
            $this->deleteNewsletter2goIntegration();
            $this->deleteNewsletter2goConfig();
        } catch (\Exception $exception) {
            //
        }
    }

    private function deleteNewsletter2goIntegration()
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter(Newsletter2goConfig::FIELD_NAME, Newsletter2goConfig::NAME_VALUE_ACCESS_KEY));

        /** @var EntityRepository $n2gRepository */
        $n2gRepository = $this->container->get('newsletter2go_config.repository');
        $result = $n2gRepository->search($criteria, Context::createDefaultContext());

        if ($result->first()) {
            $accessKey = $result->first();
            /** @var EntityRepositoryInterface $integrationRepository */
            $integrationRepository = $this->container->get('integration.repository');
            /** @var Newsletter2goConfig $accessKey */
            $integrationCriteria = new Criteria();
            $integrationCriteria->addFilter(new EqualsFilter('accessKey', $accessKey->getValue()));
            $integration = $integrationRepository->search($integrationCriteria, Context::createDefaultContext());

            if ($integration->first()) {
                /** @var IntegrationEntity $integrationEntity */
                $integrationEntity = $integration->first();
                $integrationRepository->delete([
                    ['id' => $integrationEntity->getId()]
                ], Context::createDefaultContext());
            }
        }
    }

    /**
     * this method deletes ALL Newsletter2Go configurations at `newsletter2go_config` table
     * @throws InconsistentCriteriaIdsException
     */
    private function deleteNewsletter2goConfig()
    {
        /** @var EntityRepository $n2gRepository */
        $n2gRepository = $this->container->get('newsletter2go_config.repository');
        $ids = [];
        $result = $n2gRepository->search(new Criteria(), Context::createDefaultContext());
        /** @var Newsletter2goConfig $n2gConfig */
        foreach ($result->getElements() as $n2gConfig) {
            $ids[]['id'] = $n2gConfig->getId();
        }

        $n2gRepository->delete($ids, Context::createDefaultContext());
    }

    public function boot(): void
    {
        parent::boot();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Resources/'));
        $loader->load(__DIR__ . '/Resources/config/services.xml');
    }

    public function configureRoutes(RouteCollectionBuilder $routes, string $environment): void
    {
        $routes->import(__DIR__ . '/Resources/config/routes.xml');
    }

    public function getMigrationNamespace(): string
    {
        return 'Newsletter2go\Migration';
    }
}
