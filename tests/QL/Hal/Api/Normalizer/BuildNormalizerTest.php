<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Api\Normalizer;

use Mockery;
use PHPUnit_Framework_TestCase;
use QL\Hal\Core\Entity\Repository;

/**
 *
 */
class BuildNormalizerTest extends PHPUnit_Framework_TestCase
{
    public function testLinkNull()
    {
        $urlHelper = $this->getMockBuilder('QL\Hal\Helpers\UrlHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $userNormalizer = $this->getMockBuilder('QL\Hal\Api\Normalizer\UserNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryNormalizer = $this->getMockBuilder('QL\Hal\Api\Normalizer\RepositoryNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $environmentNormalizer = $this->getMockBuilder('QL\Hal\Api\Normalizer\EnvironmentNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $normalizer = new BuildNormalizer(
            $urlHelper,
            $userNormalizer,
            $repositoryNormalizer,
            $environmentNormalizer
        );

        $this->assertEquals(null, $normalizer->link(null));
    }

    public function testResourceNull()
    {
        $urlHelper = $this->getMockBuilder('QL\Hal\Helpers\UrlHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $userNormalizer = $this->getMockBuilder('QL\Hal\Api\Normalizer\UserNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryNormalizer = $this->getMockBuilder('QL\Hal\Api\Normalizer\RepositoryNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $environmentNormalizer = $this->getMockBuilder('QL\Hal\Api\Normalizer\EnvironmentNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $normalizer = new BuildNormalizer(
            $urlHelper,
            $userNormalizer,
            $repositoryNormalizer,
            $environmentNormalizer
        );

        $this->assertEquals(null, $normalizer->resource(null));
    }

    public function testLink()
    {
        $id = 123;

        $urlHelper = $this->getMockBuilder('QL\Hal\Helpers\UrlHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $userNormalizer = $this->getMockBuilder('QL\Hal\Api\Normalizer\UserNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryNormalizer = $this->getMockBuilder('QL\Hal\Api\Normalizer\RepositoryNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $environmentNormalizer = $this->getMockBuilder('QL\Hal\Api\Normalizer\EnvironmentNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $build = $this->getMockBuilder('QL\Hal\Core\Entity\Build')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $build->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue($id));

        $normalizer = new BuildNormalizer(
            $urlHelper,
            $userNormalizer,
            $repositoryNormalizer,
            $environmentNormalizer
        );

        $expected = [
            'href' => ['api.build', ['id' => $id]],
            'title' => $id
        ];

        $this->assertEquals($expected, $normalizer->link($build));
    }

    public function testResource()
    {
        ## Build Properties

        $id = 123;
        $status = 'thisisastatus';
        $time = 'thisisatime';
        $referenceText = 'reference';
        $referenceUrl = 'referenceurl';
        $commitText = 'commit';
        $commitUrl = 'commiturl';

        $user = $this->getMockBuilder('QL\Hal\Core\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('QL\Hal\Core\Entity\Repository')
            ->disableOriginalConstructor()
            ->setMethods(['getGithubUser', 'getGithubRepo'])
            ->getMock();

        $githubUser = 'ghuser';
        $githubRepo = 'ghrepo';

        $repository->expects($this->atLeast(2))
            ->method('getGithubUser')
            ->will($this->returnValue($githubUser));

        $repository->expects($this->atLeast(2))
            ->method('getGithubRepo')
            ->will($this->returnValue($githubRepo));

        $environment = $this->getMockBuilder('QL\Hal\Core\Entity\Environment')
            ->disableOriginalConstructor()
            ->getMock();

        ## Dependencies

        $url = 'thisisaurl';

        $urlHelper = $this->getMockBuilder('QL\Hal\Helpers\UrlHelper')
            ->disableOriginalConstructor()
            ->setMethods(['urlFor', 'githubReferenceUrl', 'githubCommitUrl'])
            ->getMock();

        $urlHelper->expects($this->atLeast(1))
            ->method('urlFor')
            ->will($this->returnValue($url));

        $urlHelper->expects($this->atLeast(1))
            ->method('githubReferenceUrl')
            ->with($githubUser, $githubRepo, $referenceText)
            ->will($this->returnValue($referenceUrl));

        $urlHelper->expects($this->atLeast(1))
            ->method('githubCommitUrl')
            ->with($githubUser, $githubRepo, $commitText)
            ->will($this->returnValue($commitUrl));

        $userNormalizer = $this->getMockBuilder('QL\Hal\Api\Normalizer\UserNormalizer')
            ->disableOriginalConstructor()
            ->setMethods(['link'])
            ->getMock();

        $userLink = 'userlink';

        $userNormalizer->expects($this->atLeast(1))
            ->method('link')
            ->with($user)
            ->will($this->returnValue($userLink));

        $repositoryNormalizer = $this->getMockBuilder('QL\Hal\Api\Normalizer\RepositoryNormalizer')
            ->disableOriginalConstructor()
            ->setMethods(['link'])
            ->getMock();

        $repositoryLink = 'repositorylink';

        $repositoryNormalizer->expects($this->atLeast(1))
            ->method('link')
            ->with($repository)
            ->will($this->returnValue($repositoryLink));

        $environmentNormalizer = $this->getMockBuilder('QL\Hal\Api\Normalizer\EnvironmentNormalizer')
            ->disableOriginalConstructor()
            ->setMethods(['link'])
            ->getMock();

        $environmentLink = 'environmentlink';

        $environmentNormalizer->expects($this->atLeast(1))
            ->method('link')
            ->with($environment)
            ->will($this->returnValue($environmentLink));

        ## Build

        $build = $this->getMockBuilder('QL\Hal\Core\Entity\Build')
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getUser', 'getRepository', 'getEnvironment', 'getStatus', 'getCreated', 'getStart', 'getEnd', 'getBranch', 'getCommit'])
            ->getMock();

        $build->expects($this->atLeast(2))
            ->method('getId')
            ->will($this->returnValue($id));

        $build->expects($this->atLeast(1))
            ->method('getUser')
            ->will($this->returnValue($user));

        $build->expects($this->atLeast(1))
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $build->expects($this->atLeast(1))
            ->method('getEnvironment')
            ->will($this->returnValue($environment));

        $build->expects($this->atLeast(1))
            ->method('getStatus')
            ->will($this->returnValue($status));

        $build->expects($this->atLeast(1))
            ->method('getCreated')
            ->will($this->returnValue($time));

        $build->expects($this->atLeast(1))
            ->method('getStart')
            ->will($this->returnValue($time));

        $build->expects($this->atLeast(1))
            ->method('getEnd')
            ->will($this->returnValue($time));

        $build->expects($this->atLeast(1))
            ->method('getBranch')
            ->will($this->returnValue($referenceText));

        $build->expects($this->atLeast(1))
            ->method('getCommit')
            ->will($this->returnValue($commitText));

        $normalizer = new BuildNormalizer(
            $urlHelper,
            $userNormalizer,
            $repositoryNormalizer,
            $environmentNormalizer
        );

        $expected = [
            '_links' => [
                'self' => $normalizer->link($build),
                'user' => $userLink,
                'repository' => $repositoryLink,
                'environment' => $environmentLink,
                'logs' => [
                    'href' => ['api.build.logs', ['id' => $id]]
                ]
            ],
            'id' => $id,
            'status' => $status,
            'url' => $url,
            'created' => $time,
            'start' => $time,
            'end' => $time,
            'reference' => [
                'text' => $referenceText,
                'url' => $referenceUrl
            ],
            'commit' => [
                'text' => $commitText,
                'url' => $commitUrl
            ]
        ];

        $this->assertEquals($expected, $normalizer->resource($build));
    }
}
