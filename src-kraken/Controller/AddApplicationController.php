<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Hal\Core\Entity\Repository as HalRepository;
use QL\Hal\Flasher;
use Slim\Http\Request;

class AddApplicationController implements ControllerInterface
{
    const SUCCESS = 'Application "%s" added.';

    const ERR_DUPLICATE = 'An application with this name, CORE ID, or HAL Repository already exists.';

    const ERR_INVALID_NAME = 'Application names must be alphanumeric.';
    const ERR_INVALID_COREID = 'Please enter a valid numeric Core Application ID.';
    const ERR_INVALID_HAL_REPOSITORY = 'Please select a valid HAL 9000 repository.';

    const VALIDATE_NAME_REGEX = '/^[a-zA-Z0-9]{2,64}$/';
    const VALIDATE_COREID_REGEX = '/^[\d]{6,64}$/';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $applicationRepo;
    private $halRepo;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type callable
     */
    private $random;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Flasher $flasher
     * @param EntityManagerInterface $em
     * @param EntityRepository $halRepo
     * @param callable $random
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Flasher $flasher,
        EntityManagerInterface $em,
        EntityRepository $halRepo,
        callable $random
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->flasher = $flasher;
        $this->random = $random;

        $this->em = $em;
        $this->halRepo = $halRepo;
        $this->applicationRepo = $this->em->getRepository(Application::CLASS);

        $this->errors = [];
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $context = [];

        if ($this->request->isPost()) {

            if ($application = $this->handleForm()) {
                // flash and redirect
                $this->flasher
                    ->withFlash(sprintf(self::SUCCESS, $application->name()), 'success')
                    ->load('kraken.applications');
            }

            $context = [
                'errors' => $this->errors,
                'form' => [
                    'hal_repo' => $this->request->post('hal_repo'),
                    'name' => $this->request->post('name'),
                    'core_id' => $this->request->post('core_id')
                ]
            ];
        }

        $context['available'] = $this->getAvailableRepositories();

        $this->template->render($context);
    }

    /**
     * Gets a list of available repositories from HAL 9000 that can be linked to Kraken Applications
     *
     * @todo cache this
     *
     * @return array
     */
    private function getAvailableRepositories()
    {
        $applications = $this->applicationRepo->findAll();
        $repos = $this->halRepo->findBy([], ['name' => 'ASC']);

        $available = [];
        foreach ($repos as $repo) {
            $available[$repo->getId()] = $repo->getName();
        }

        foreach ($applications as $app) {
            if ($app->halRepository()) {
                unset($available[$app->halRepository()->getId()]);
            }
        }

        return $available;
    }

    /**
     * @return Application|null
     */
    private function handleForm()
    {
        $name = $this->request->post('name');
        $coreId = $this->request->post('core_id');
        $halRepo = $this->request->post('hal_repo');

        if (preg_match(self::VALIDATE_COREID_REGEX, $coreId) !== 1) {
            $this->errors[] = self::ERR_INVALID_COREID;
        }

        if (!$halRepo && preg_match(self::VALIDATE_NAME_REGEX, $name) !== 1) {
            $this->errors[] = self::ERR_INVALID_NAME;
        }

        if ($halRepo) {
            if (!$halRepo = $this->halRepo->find($halRepo)) {
                $this->errors[] = self::ERR_INVALID_HAL_REPOSITORY;
            }
        }

        // dupe check
        if (!$this->errors) {

            $criteria = (new Criteria)
                ->where(Criteria::expr()->eq('coreId', $coreId));

            if ($halRepo instanceof HalRepository) {
                $criteria->orWhere(Criteria::expr()->eq('halRepository', $halRepo));

            } else {
                $criteria->orWhere(Criteria::expr()->eq('name', $coreId));

            }

            $matches = $this->applicationRepo->matching($criteria);
            if ($matches->toArray()) {
                $this->errors[] = self::ERR_DUPLICATE;
            }
        }

        if ($this->errors) {
            return null;
        }

        if ($halRepo instanceof HalRepository) {
            $name = $halRepo->getName();
        } else {
            $halRepo = null;
        }

        return $this->saveApplication($name, $coreId, $halRepo);
    }

    /**
     * @param string $name
     * @param string $coreId
     * @param HalRepository|null $repository
     *
     * @return Application
     */
    private function saveApplication($name, $coreId, HalRepository $repository = null)
    {
        $id = call_user_func($this->random);
        $application = (new Application)
            ->withId($id)
            ->withName($name)
            ->withCoreId($coreId);

        if ($repository) {
            $application->withHalRepository($repository);
        }

        // persist to database
        $this->em->persist($application);
        $this->em->flush();

        return $application;
    }
}
