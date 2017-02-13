<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Github;

use Github\Client;
use Github\HttpClient\Plugin\PathPrepend;
use Http\Client\Common\Plugin\AddHostPlugin;
use function GuzzleHttp\Psr7\uri_for;

class EnterpriseClient extends Client
{
    public function __construct($httpClientBuilder, $apiVersion, $enterpriseUrl)
    {
        parent::__construct($httpClientBuilder, $apiVersion);
        $httpClientBuilder->removePlugin(AddHostPlugin::class);
        $httpClientBuilder->removePlugin(PathPrepend::class);
        $httpClientBuilder->addPlugin(new AddHostPlugin(uri_for($enterpriseUrl)));
        $httpClientBuilder->addPlugin(new PathPrepend(sprintf('/api/%s', $this->getApiVersion())));
    }
}
