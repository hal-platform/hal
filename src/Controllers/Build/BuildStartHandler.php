<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Build;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Repository\BuildRepository;
use QL\Hal\Core\JobIdGenerator;
use QL\Hal\Session;
use QL\Hal\Validator\BuildStartValidator;
use QL\Panthor\Twig\Context;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class BuildStartHandler
{
    const WAIT_FOR_IT = 'Build has been queued for creation.';

    /**
     * @type BuildRepository
     */
    private $buildRepo;

    /**
     * @type EntityManager
     */
    private $em;

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
     * @type array
     */
    private $parameters;

    /**
     * @param BuildRepository $buildRepo
     * @param EntityManager $em
     * @param BuildStartValidator $validator
     * @param Session $session
     * @param Url $url
     * @param JobIdGenerator $unique
     * @param Context $context
     * @param Request $request
     * @param array $parameters
     */
    public function __construct(
        BuildRepository $buildRepo,
        EntityManager $em,
        BuildStartValidator $validator,
        Session $session,
        Url $url,
        JobIdGenerator $unique,
        Context $context,
        Request $request,
        array $parameters
    ) {
        $this->buildRepo = $buildRepo;
        $this->em = $em;
        $this->validator = $validator;

        $this->session = $session;
        $this->url = $url;
        $this->unique = $unique;
        $this->context = $context;

        $this->request = $request;
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
