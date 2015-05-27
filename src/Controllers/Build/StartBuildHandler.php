<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Build;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\JobIdGenerator;
use QL\Hal\Services\StickyEnvironmentService;
use QL\Hal\Session;
use QL\Hal\Validator\BuildStartValidator;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class StartBuildHandler implements MiddlewareInterface
{
    const WAIT_FOR_IT = 'Build has been queued for creation.';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $buildRepo;

    /**
     * @type BuildStartValidator
     */
    private $validator;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type JobIdGenerator
     */
    private $unique;

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
     * @param Session $session
     * @param Url $url
     * @param JobIdGenerator $unique
     * @param Context $context
     * @param Request $request
     * @param StickyEnvironmentService $stickyService
     * @param array $parameters
     */
    public function __construct(
        EntityManagerInterface $em,
        BuildStartValidator $validator,
        Session $session,
        Url $url,
        JobIdGenerator $unique,
        Context $context,
        Request $request,
        StickyEnvironmentService $stickyService,
        array $parameters
    ) {
        $this->em = $em;
        $this->buildRepo = $em->getRepository(Build::CLASS);
        $this->validator = $validator;

        $this->session = $session;
        $this->url = $url;
        $this->unique = $unique;
        $this->context = $context;

        $this->request = $request;
        $this->stickyService = $stickyService;
        $this->parameters = $parameters;
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
            $this->parameters['id'],
            $this->request->post('environment'),
            $this->request->post('reference'),
            $this->request->post('search')
        );

        // if validator didn't create a build, add errors and pass through to controller
        if (!$build) {
            $this->context->addContext([
                'errors' => $this->validator->errors()
            ]);

            return;
        }

        // set ID

        $id = $this->unique->generateBuildId();
        $build->setId($id);

        // check for ID dupes
        $this->dupeCatcher($build);

        // persist to database
        $this->em->persist($build);
        $this->em->flush();

        // override sticky environment
        $this->stickyService->save($this->parameters['id'], $this->request->post('environment'));

        // flash and redirect
        $this->session->flash(self::WAIT_FOR_IT, 'success');
        $this->url->redirectFor('build', ['build' => $build->getId()], [], 303);
    }

    /**
     * @param Build $build
     * @return null
     */
    private function dupeCatcher(Build $build)
    {
        $dupe = $this->buildRepo->find($build->getId());
        if ($dupe) {
            $id = $this->unique->generateBuildId();
            $build->setId($id);
            $this->dupeCatcher($build);
        }
    }
}
