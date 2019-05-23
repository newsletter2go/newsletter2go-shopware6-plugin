<?php

namespace Newsletter2go\Controller\Api;


use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Language\LanguageEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class LanguageController extends AbstractController
{
    /**
    * @Route("/api/{version}/n2g/languages", name="api.action.n2g.getLanguages", methods={"GET"})
    */
    public function getLanguagesAction(): JsonResponse
    {
        $response = [];
        try {
            /** @var EntityRepository $repository */
            $repository = $this->container->get('language.repository');
            $languages = $repository->search(new Criteria(), Context::createDefaultContext());

            $response['success'] = true;
            $response['data'] = $this->prepareEntityAttributes($languages);

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['data'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }

    private function prepareEntityAttributes(EntityCollection $entityCollection) : array
    {
        $attributes = [];

        /** @var LanguageEntity $entity */
        foreach ($entityCollection->getElements() as $key => $entity) {
            $attributes[$key]['id'] = $entity->getId();
            $attributes[$key]['name'] = $entity->getName();
            $attributes[$key]['localeCode'] = $entity->getLocale()->getCode();
            $attributes[$key]['localeName'] = $entity->getLocale()->getName();
            $attributes[$key]['default'] = false;
        }

        return $attributes;
    }
}
