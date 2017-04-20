<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class ApplicationController implements ControllerInterface
{
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em,
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->em = $em;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'has_jobs' => $this->doesApplicationHaveChildren($application)
        ]);
    }

    /**
     * @param Application $application
     *
     * @return bool
     */
    private function doesApplicationHaveChildren(Application $application)
    {
        $builds = $this->em
            ->getRepository(Build::class)
            ->findOneBy(['application' => $application]);

        if (count($builds) > 0) return true;

        $deployments = $this->em
            ->getRepository(Push::class)
            ->findOneBy(['application' => $application]);

        if (count($deployments) > 0) return true;

        return false;
    }
}
