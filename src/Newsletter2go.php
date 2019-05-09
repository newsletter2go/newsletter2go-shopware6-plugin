<?php declare(strict_types=1);

namespace Newsletter2go;

use Doctrine\DBAL\Connection;
use Newsletter2go\Entity\Newsletter2goConfig;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Integration\IntegrationEntity;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
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
        $this->createIntegration($context->getContext());
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
        $connection = $this->container->get(Connection::class);
        $n2gConfig = $connection->executeQuery('SELECT `value` FROM `newsletter2go_config` WHERE `name` = :name', ['name' => 'accessKey'])->fetchAll();

        if (empty($n2gConfig[0]['value'])) {
            return;
        }

        $accessKey= $n2gConfig[0]['value'];

        /** @var EntityRepositoryInterface $integrationRepository */
        $integrationRepository = $this->container->get('integration.repository');
        $integrationCriteria = new Criteria();
        $integrationCriteria->addFilter(new EqualsFilter('accessKey', $accessKey));
        $integration = $integrationRepository->search($integrationCriteria, Context::createDefaultContext());

        if ($integration->first()) {
            /** @var IntegrationEntity $integrationEntity */
            $integrationEntity = $integration->first();
            $integrationRepository->delete([
                ['id' => $integrationEntity->getId()]
            ], Context::createDefaultContext());
        }
    }

    /**
     * this method drops `newsletter2go_config` table
     */
    private function deleteNewsletter2goConfig()
    {
        try {
            $connection = $this->container->get(Connection::class);
            $connection->executeQuery('DROP TABLE IF EXISTS `newsletter2go_config`');
        } catch (\Exception $exception) {
            //
        }
    }

    public function boot(): void
    {
        parent::boot();
    }

    /**
     * delete integration related to plugin and create a new integration
     * @param Context $context
     */
    private function createIntegration(Context $context)
    {

        try {

            /** @var EntityRepositoryInterface $integrationRepository */
            $integrationRepository = $this->container->get('integration.repository');

            $accessKey = AccessKeyHelper::generateAccessKey('integration');
            $secretAccessKey = AccessKeyHelper::generateSecretAccessKey();
            $integrationLabel = 'Newsletter2Go';

            $data = [
                'label' => $integrationLabel,
                'accessKey' => $accessKey,
                'secretAccessKey' => $secretAccessKey,
                'writeAccess' => true
            ];

            /** @var IntegrationEntity $integrationEntity */
            $integrationEntity = $integrationRepository->create([$data], $context);

            $connection = $this->container->get(Connection::class);
            $createdAtTimeStampFormat = (new \DateTime())->format('Y-m-d H:i:s');
            $connection->executeQuery("INSERT INTO `newsletter2go_config` VALUES(:id, :name, :value, :createdAt, NULL) ",
                ['id' => Uuid::randomBytes(), 'name' => Newsletter2goConfig::NAME_VALUE_ACCESS_KEY, 'value' => $accessKey, 'createdAt' => $createdAtTimeStampFormat]);

            $connection->executeQuery("INSERT INTO `newsletter2go_config` VALUES(:id, :name, :value, :createdAt, NULL) ",
                ['id' => Uuid::randomBytes(), 'name' => Newsletter2goConfig::NAME_VALUE_SECRET_ACCESS_KEY, 'value' => $secretAccessKey, 'createdAt' => $createdAtTimeStampFormat]);

        } catch (\Exception $exception) {
            //
        }
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
