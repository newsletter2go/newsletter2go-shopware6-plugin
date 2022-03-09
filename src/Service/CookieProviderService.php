<?php

namespace Newsletter2go\Service;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class CookieProviderService implements CookieProviderInterface
{
    public const COOKIE_KEY = 'sib_n2g_cuid';

    private const SIB_COOKIE = [
        'snippet_name' => 'Sendinblue N2G',
        'snippet_description' => 'Sendinblue trackers',
        'cookie' => self::COOKIE_KEY,
        'expiration' => '30',
        'value' => '1',
    ];

    private $originalService;

    public function __construct(CookieProviderInterface $service)
    {
        $this->originalService = $service;
    }

    public function getCookieGroups(): array
    {
        return array_merge(
            $this->originalService->getCookieGroups(),
            [
                self::SIB_COOKIE
            ]
        );
    }
}
