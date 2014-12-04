<?php
///**
// * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
// *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
// *    is strictly prohibited.
// */
//
//namespace QL\Hal\Api\Normalizer;
//
//use Mockery;
//use PHPUnit_Framework_TestCase;
//use QL\Hal\Core\Entity\Deployment;
//use QL\Hal\Core\Entity\Repository;
//use QL\Hal\Core\Entity\Server;
//
///**
// * @todo fix this test
// * @requires function skipthistest
// */
//class DeploymentNormalizerTest extends PHPUnit_Framework_TestCase
//{
//    public $api;
//    public $repoNormalizer;
//    public $serverNormalizer;
//
//    public function setUp()
//    {
//        $this->api = Mockery::mock('QL\Hal\Helpers\ApiHelper');
//        $this->repoNormalizer = Mockery::mock('QL\Hal\Api\RepositoryNormalizer');
//        $this->serverNormalizer = Mockery::mock('QL\Hal\Api\ServerNormalizer');
//    }
//
//    public function testNormalizationOfLink()
//    {
//        $deployment = new Deployment;
//        $deployment->setId('1234');
//
//        $this->api
//            ->shouldReceive('parseLink')
//            ->andReturn('link');
//
//        $normalizer = new DeploymentNormalizer($this->api, $this->repoNormalizer, $this->serverNormalizer);
//        $actual = $normalizer->linked($deployment);
//
//        $this->assertSame('link', $actual);
//    }
//
//    public function testNormalizationWithoutCriteria()
//    {
//        $repo = new Repository;
//        $server = new Server;
//
//        $deployment = new Deployment;
//        $deployment->setId('1234');
//        $deployment->setPath('/server/path');
//        $deployment->setRepository($repo);
//        $deployment->setServer($server);
//
//        $this->api
//            ->shouldReceive('parseLink')
//            ->andReturn('link');
//
//        $this->repoNormalizer
//            ->shouldReceive('linked')
//            ->andReturn('linked-repo');
//        $this->serverNormalizer
//            ->shouldReceive('linked')
//            ->andReturn('linked-server');
//
//        $normalizer = new DeploymentNormalizer($this->api, $this->repoNormalizer, $this->serverNormalizer);
//        $actual = $normalizer->normalize($deployment);
//
//        $expected = [
//            'id' => '1234',
//            'path' => '/server/path',
//            '_links' => [
//                'self' => 'link',
//                'lastPush' => 'link',
//                'lastSuccessfulPush' => 'link',
//                'index' => 'link',
//                'repository' => 'linked-repo',
//                'server' => 'linked-server'
//            ]
//        ];
//
//        $this->assertSame($expected, $actual);
//    }
//
//    public function testNormalizationCriteriaCascadesToChildEntity()
//    {
//        $repo = new Repository;
//        $server = new Server;
//
//        $deployment = new Deployment;
//        $deployment->setId('1234');
//        $deployment->setPath('/server/path');
//        $deployment->setRepository($repo);
//        $deployment->setServer($server);
//
//        $this->api
//            ->shouldReceive('parseLink')
//            ->andReturn('link');
//
//        $this->repoNormalizer
//            ->shouldReceive('normalize')
//            ->with($repo, ['test1'])
//            ->andReturn('normalized-repo');
//        $this->serverNormalizer
//            ->shouldReceive('normalize')
//            ->with($server, ['test2'])
//            ->andReturn('normalized-server');
//
//        $normalizer = new DeploymentNormalizer($this->api, $this->repoNormalizer, $this->serverNormalizer);
//        $actual = $normalizer->normalize($deployment, [
//            'repository' => ['test1'],
//            'server' => ['test2']
//        ]);
//
//        $expected = [
//            'id' => '1234',
//            'path' => '/server/path',
//            '_links' => [
//                'self' => 'link',
//                'lastPush' => 'link',
//                'lastSuccessfulPush' => 'link',
//                'index' => 'link'
//            ],
//            '_embedded' => [
//                'repository' => 'normalized-repo',
//                'server' => 'normalized-server'
//            ]
//        ];
//
//        $this->assertSame($expected, $actual);
//    }
//}
