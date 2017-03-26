<?php

namespace NekoAPI\Component\MalCrawler\Test\HTTP;

use GuzzleHttp\Psr7\Response;
use NekoAPI\Component\MalCrawler\Exception\UnexpectedResponseStatusCodeException;
use NekoAPI\Component\MalCrawler\HTTP\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class ClientTest
 *
 * @package NekoAPI\Component\MalCrawler\Test
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class ClientTest extends TestCase
{
    public function testNormalResponse()
    {
        $goutte = $this->getMockBuilder(\Goutte\Client::class)
            ->setMethods(['request', 'getResponse'])
            ->getMock();

        $crawler = $this->getMockBuilder(Crawler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatus'])
            ->getMock();

        $goutte->expects($this->once())
            ->method('request')
            ->with('GET', 'foo_bar', [], [], [], null, true)
            ->willReturn($crawler);

        $goutte->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);

        $response->expects($this->once())
            ->method('getStatus')
            ->willReturn(200);

        $client = new Client($goutte);

        $result = $client->loadURL('foo_bar');

        $this->assertEquals($crawler, $result);
    }

    public function testUnexpectedResponseCode()
    {
        $this->expectException(UnexpectedResponseStatusCodeException::class);
        $this->expectExceptionMessage('Received unexpected HTTP Status code "404" from Response');

        $goutte = $this->getMockBuilder(\Goutte\Client::class)
            ->setMethods(['request', 'getResponse'])
            ->getMock();

        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatus'])
            ->getMock();

        $goutte->expects($this->once())
            ->method('request')
            ->with('GET', 'foo_bar', [], [], [], null, true);

        $goutte->expects($this->exactly(2))
            ->method('getResponse')
            ->willReturn($response);

        $response->expects($this->exactly(2))
            ->method('getStatus')
            ->willReturn(404);

        $client = new Client($goutte);

        $result = $client->loadURL('foo_bar');
    }
}
