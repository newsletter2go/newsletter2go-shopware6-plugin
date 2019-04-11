<?php declare(strict_types=1);

namespace Swag\Newsletter2go\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/v1/n2g/testConnection", name="api.action.n2g.testConnection", methods={"GET"})
     */
    public function testConnection(Request $request, Context $context): JsonResponse
    {
        return new JsonResponse([
            "success" => true
        ]);
    }

    /**
     * @Route("/api/v1/n2g/getCustomers", name="api.action.n2g.getCustomers", methods={"GET"})
     */
    public function getCustomers(Request $request, Context $context): JsonResponse
    {
        try {
            /** @var EntityRepositoryInterface $customerRepository */
            $customerRepository = $this->container->get('customer.repository');
            $criteria = new Criteria();
            if (!empty($request->get('email'))) {
                $criteria->addFilter(new EqualsFilter('customer.email',
                    $request->get('email')));
            }
            $response = $customerRepository->search($criteria, Context::createDefaultContext());
        } catch (\Exception $exception) {
            $response = $exception->getMessage();

        }

        return new JsonResponse($response);
    }
}
