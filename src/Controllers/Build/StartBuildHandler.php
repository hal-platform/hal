<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Build;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Service\StickyEnvironmentService;
use QL\Hal\Flasher;
use QL\Hal\Validator\BuildStartValidator;
use QL\Hal\Validator\PushStartValidator;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use Slim\Http\Request;

/**
 * Permission checking is handled by BuildStartValidator
 */
class StartBuildHandler implements MiddlewareInterface
{
    const WAIT_FOR_IT = 'Build has been queued for creation.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type BuildStartValidator
     */
    private $validator;

    /**
     * @type PushStartValidator
     */
    private $pushValidator;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Context
     */
    private $context;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type StickyEnvironmentService
     */
    private $stickyService;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param EntityManagerInterface $em
     * @param BuildStartValidator $validator
     * @param PushStartValidator $pushValidator
     * @param Flasher $flasher
     * @param Context $context
     * @param Request $request
     * @param StickyEnvironmentService $stickyService
     * @param Application $application
     */
    public function __construct(
        EntityManagerInterface $em,
        BuildStartValidator $validator,
        PushStartValidator $pushValidator,
        Flasher $flasher,
        Context $context,
        Request $request,
        StickyEnvironmentService $stickyService,
        Application $application
    ) {
        $this->em = $em;
        $this->validator = $validator;
        $this->pushValidator = $pushValidator;

        $this->flasher = $flasher;
        $this->context = $context;

        $this->request = $request;
        $this->stickyService = $stickyService;
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$this->request->isPost()) {
            return;
        }

        $build = $this->validator->isValid(
            $this->application,
            $this->request->post('environment'),
            $this->request->post('reference'),
            $this->request->post('search')
        );

        // if validator didn't create a build, add errors and pass through to controller
        if (!$build) {
            return $this->context->addContext(['errors' => $this->validator->errors()]);
        }

        $children = null;
        $deployments = $this->request->post('deployments');
        if ($deployments) {
            $children = $this->pushValidator->isProcessValid($build->application(), $build->environment(), $build, $deployments);
            if (!$children) {
                // child push validation failed, bomb out.
                return $this->context->addContext(['errors' => $this->pushValidator->errors()]);
            }
        }

        $deployments = $this->request->post('deployments');
        $children = $this->maybeMakeChildren($build, $deployments);
        if ($deployments && !$children) {
            // child push validation failed, bomb out.
            return $this->context->addContext(['errors' => $this->pushValidator->errors()]);
        }

        // persist to database
        if ($children) {
            foreach ($children as $process) {
                $this->em->persist($process);
            }
        }

        $this->em->persist($build);
        $this->em->flush();

        // override sticky environment
        $this->stickyService->save($this->application->id(), $this->request->post('environment'));

        // flash and redirect
        $this->flasher
            ->withFlash(self::WAIT_FOR_IT, 'success')
            ->load('build', ['build' => $build->id()]);
    }

    /**
     * @param Build $build
     * @param array|null $deployments
     *
     * @return array|null
     */
    private function maybeMakeChildren(Build $build, $deployments)
    {
        if (!$deployments) {
            return null;
        }

        return $this->pushValidator->isProcessValid($build->application(), $build->environment(), $build, $deployments);
    }
}
