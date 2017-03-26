<?php

namespace NekoAPI\Component\MalCrawler\Exception;

use Exception;
use Throwable;

/**
 * Class XpathReturnedZeroResultException
 *
 * @package NekoAPI\Component\MalCrawler\Exception
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class XpathReturnedZeroResultException extends Exception
{
    /**
     * @inheritDoc
     */
    public function __construct($url, $xpath)
    {
        parent::__construct(
            sprintf(
                'Xpath "%s" on url "%s" returned 0 results',
                $xpath,
                $url
            )
        );
    }
}