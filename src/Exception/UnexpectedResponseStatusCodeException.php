<?php

namespace NekoAPI\Component\MalCrawler\Exception;

use Exception;
use Throwable;

/**
 * Class UnexpectedResponseStatusCodeException
 *
 * @package NekoAPI\Component\MalCrawler\Exception
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class UnexpectedResponseStatusCodeException extends Exception
{
    /**
     * @inheritDoc
     */
    public function __construct(int $code)
    {
        parent::__construct(
            sprintf(
                'Received unexpected HTTP Status code "%s" from Response',
                $code
            )
        );
    }
}