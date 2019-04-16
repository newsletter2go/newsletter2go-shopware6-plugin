<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class LanguageController extends AbstractController
{
        /**
        * @Route("/api/{version}/n2g/languages", name="api.action.n2g.languages", methods={"GET"})
        */
        public function getLanguagesAction(): JsonResponse
        {
            try {
                /** @var EntityRepository $repository */
                $repository = $this->container->get('language.repository');
                $languages = $repository->search(new Criteria(), Context::createDefaultContext())->getElements();

                return new JsonResponse([
                    'success' => true,
                    'data' => $languages
                ]);

            } catch (\Exception $exception) {
                return new JsonResponse([
                    'success' => false,
                    'data' => $exception->getMessage()
                ]);
            }
        }
}
