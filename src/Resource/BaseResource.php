<?php

namespace NekoAPI\Component\MalCrawler\Resource;

use NekoAPI\Component\MalCrawler\Exception\XpathReturnedZeroResultException;
use NekoAPI\Component\MalCrawler\HTTP\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class BaseResource
 *
 * @package NekoAPI\Component\MalCrawler\Resource
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
abstract class BaseResource
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $lastUrl;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }
    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getLastUrl(): ?string
    {
        return $this->lastUrl;
    }

    /**
     * @param string $url
     *
     * @return Crawler
     */
    public function loadURL(string $url): Crawler
    {
        $this->lastUrl = $url;

        return $this->getClient()->loadURL($url);
    }

    protected function extractXpathNodeText(Crawler $crawler, string $xPath)
    {
        $node = $crawler->filterXPath($xPath)->first();

        if ($node->count() < 1) {
            throw new XpathReturnedZeroResultException($this->getLastUrl(), $xPath);
        }

        return $node->text();
    }

    protected function extractXpathNodeAttribute(Crawler $crawler, string $xPath, string $attributeName)
    {
        $node = $crawler->filterXPath($xPath)->first();

        if ($node->count() < 1) {
            throw new XpathReturnedZeroResultException($this->getLastUrl(), $xPath);
        }

        return $node->attr($attributeName);
    }
}