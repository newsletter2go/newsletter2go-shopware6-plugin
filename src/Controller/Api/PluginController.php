<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class PluginController extends AbstractController
{
    /**
     * @Route("/api/{version}/n2g/test", name="api.action.n2g.testConnection", methods={"GET"})
     */
    public function testConnectionAction(): JsonResponse
    {
        return new JsonResponse([
            "success" => true
        ]);
    }

    /**
     * @Route("/api/{version}/n2g/info", name="api.action.n2g.getPluginInfo", methods={"GET"})
     */
    public function getPluginInfoAction(): JsonResponse
    {
        $response = [];
        try {
            /** @var EntityRepositoryInterface $pluginRepository */
            $pluginRepository = $this->container->get('plugin.repository');
            $plugins = $pluginRepository->search(new Criteria(), Context::createDefaultContext());

            /** @var PluginEntity $plugin */
            foreach ($plugins->getElements() as $plugin) {
                if ($plugin->get('name') === 'Newsletter2go\Newsletter2go') {
                    $response['success'] = true;
                    $response['version'] = $plugin->get('version');
                    break;
                }
            }

            if (empty($response['version'])) {
                $response['success'] = false;
                $response['error'] = 'plugin name not found';
            }


        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['version'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }
}
