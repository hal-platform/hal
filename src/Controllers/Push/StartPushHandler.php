<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Push;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\Push;
use QL\Hal\Core\JobIdGenerator;
use QL\Hal\Service\StickyEnvironmentService;
use QL\Hal\Validator\PushStartValidator;
use QL\Hal\Session;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Twig\Context;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class StartPushHandler implements MiddlewareInterface
{
    const SUCCESS = "The build has been queued to be pushed to the requested servers.";

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var PushStartValidator
     */
    private $validator;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var EntityRepository
     */
    private $buildRepo;
    private $pushRepo;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var JobIdGenerator
     */
    private $unique;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var StickyEnvironmentService
     */
    private $stickyService;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param EntityManagerInterface $em
     * @param PushStartValidator $validator
     * @param Session $session
     * @param Url $url
     * @param JobIdGenerator $unique
     * @param Request $request
     * @param Context $context
     * @param StickyEnvironmentService $stickyService
     * @param array $parameters
     */
    public function __construct(
        EntityManagerInterface $em,
        PushStartValidator $validator,
        Session $session,
        Url $url,
        JobIdGenerator $unique,
        Request $request,
        Context $context,
        StickyEnvironmentService $stickyService,
        array $parameters
    ) {
        $this->session = $session;

        $this->buildRepo = $em->getRepository(Build::class);
        $this->pushRepo = $em->getRepository(Push::class);
        $this->em = $em;

        $this->url = $url;
        $this->unique = $unique;

        $this->request = $request;
        $this->context = $context;
        $this->stickyService = $stickyService;
        $this->validator = $validator;
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

        // Can only deploy successful builds
        $build = $this->buildRepo->findOneBy([
            'id' => $this->parameters['build'],
            'status' => 'Success'
        ]);

        if (!$build) {
            // fall through to controller
            return;
        }

        $deployments = $this->request->post('deployments', []);
        $application = $build->application();
        $environment = $build->environment();

        // passed separately, in case one day we support cross-env builds?
        $pushes = $this->validator->isValid($application, $environment, $build, $deployments);

        // Pass through to controller if errors
        if (!$pushes) {
            return $this->context->addContext([
                'errors' => $this->validator->errors()
            ]);
        }

        $this->dupeCatcher($pushes);

        // commit pushes
        foreach ($pushes as $push) {
            // record pushes as active push on each deployment
            $deployment = $push->deployment();
            $deployment->withPush($push);

            $this->em->persist($deployment);
            $this->em->persist($push);
        }

        $this->em->flush();

        // override sticky environment
        $this->stickyService->save($application->id(), $environment->id());

        $this->session->flash(self::SUCCESS, 'success');
        $this->url->redirectFor('application.status', ['application' => $application->id()]);
    }

    /**
     * This will find duplicate Push Ids (recursively) and change the id of the pushes to a new unique hash until
     * there are no duplicates.
     *
     * @param Push[] $pushes
     *
     * @return null
     */
    private function dupeCatcher(array $pushes)
    {
        $ids = array_map(function($push) {
            return $push->id();
        }, $pushes);

        $dupes = $this->pushRepo->findBy(['id' => $ids]);
        if ($dupes) {
            $dupeIds = array_map(function($push) {
                return $push->id();
            }, $dupes);

            $dupePushes = array_filter($pushes, function($push) use ($dupeIds) {
                return in_array($push->id(), $dupeIds);
            });

            foreach ($dupePushes as $push) {
                $push->withId($this->unique->generatePushId());
            }

            $this->dupeCatcher($pushes);
        }
    }
}
