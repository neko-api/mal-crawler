<?php

namespace NekoAPI\Component\MalCrawler\Test\Resource;

use NekoAPI\Component\MalCrawler\HTTP\Client;
use NekoAPI\Component\MalCrawler\Resource\AnimeResource;
use PHPUnit\Framework\TestCase;

/**
 * Class AnimeResourceTest
 *
 * @package NekoAPI\Component\MalCrawler\Test\Resource
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class AnimeResourceTest extends TestCase
{
    public function testAnimeResource()
    {
        $client = new Client(new \Goutte\Client());

        $resource = new AnimeResource($client);

        $anime = $resource->fetch('https://myanimelist.net/anime/30276/One_Punch_Man');
    }
}
