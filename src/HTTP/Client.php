<?php

namespace NekoAPI\Component\MalCrawler\HTTP;

use Goutte\Client as GoutteClient;
use NekoAPI\Component\MalCrawler\Exception\UnexpectedResponseStatusCodeException;

/**
 * Class Client
 *
 * @package NekoAPI\Component\MalCrawler\HTTP
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Client
{
    /**
     * @var GoutteClient
     */
    private $goutteClient;

    public function __construct(GoutteClient $goutteClient)
    {
        $this->goutteClient = $goutteClient;
    }

    public function loadURL(string $url)
    {
        $response = $this->getGoutteClient()->request('GET', $url);

        if (200 !== $this->getGoutteClient()->getResponse()->getStatus()) {
            throw new UnexpectedResponseStatusCodeException($this->getGoutteClient()->getResponse()->getStatus());
        }

        return $response;
    }

    /**
     * @return GoutteClient
     */
    public function getGoutteClient(): GoutteClient
    {
        return $this->goutteClient;
    }
}