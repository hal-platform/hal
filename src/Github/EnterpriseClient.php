<?php
/**
 * @copyright ©2017 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */
namespace QL\Hal\Github;

use Github\Client;
use Github\HttpClient\Plugin\PathPrepend;
use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Discovery\UriFactoryDiscovery;

class EnterpriseClient extends Client
{
    public function __construct($httpClientBuilder, $apiVersion, $enterpriseUrl)
    {
        parent::__construct($httpClientBuilder, $apiVersion, $enterpriseUrl);
        $httpClientBuilder->removePlugin(AddHostPlugin::class);
        $httpClientBuilder->removePlugin(PathPrepend::class);
        $httpClientBuilder->addPlugin(new AddHostPlugin(UriFactoryDiscovery::find()->createUri($enterpriseUrl)));
        $httpClientBuilder->addPlugin(new PathPrepend(sprintf('/api/%s', $this->getApiVersion())));
    }
}
