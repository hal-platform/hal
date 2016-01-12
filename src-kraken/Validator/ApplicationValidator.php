<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Validator;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Application as HalApplication;
use QL\Kraken\Core\Entity\Application;

class ApplicationValidator
{
    const ERR_DUPLICATE = 'An application with this name, CORE ID, or HAL Application already exists.';

    const ERR_INVALID_NAME = 'Application names must be alphanumeric.';
    const ERR_INVALID_COREID = 'Please enter a valid 6-digit Core Application ID.';
    const ERR_INVALID_HAL_REPOSITORY = 'Please select a valid HAL 9000 repository.';

    const VALIDATE_NAME_REGEX = '/^[a-zA-Z0-9-.\ ]{2,64}$/';
    const VALIDATE_COREID_REGEX = '/^[\d]{6,32}$/';

    /**
     * @var callable
     */
    private $random;

    /**
     * @var EntityRepository
     */
    private $applicationRepo;
    private $halRepo;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param EntityManagerInterface $em
     * @param callable $random
     */
    public function __construct(EntityManagerInterface $em, callable $random)
    {
        $this->applicationRepo = $em->getRepository(Application::CLASS);
        $this->halRepo = $em->getRepository(HalApplication::CLASS);

        $this->random = $random;

        $this->errors = [];
    }

    /**
     * @param string $coreId
     * @param string $halApp
     * @param string $name
     *
     * @return Application|null
     */
    public function isValid($coreId, $halApp, $name)
    {
        $this->errors = [];

        $halApp = $this->validateValues($coreId, $halApp, $name);

        if ($this->errors) return null;

        // dupe check
        $isDupe = $this->isDupe($coreId, $name, $halApp, null);

        if ($this->errors) return null;

        if ($halApp instanceof HalApplication) {
            $name = $halApp->name();
        } else {
            $halApp = null;
        }

        $id = call_user_func($this->random);
        $application = (new Application)
            ->withId($id)
            ->withName($name)
            ->withCoreId($coreId)
            ->withHalApplication($halApp);

        return $application;
    }

    /**
     * @param Application $application
     * @param string $coreId
     * @param string $halApp
     * @param string $name
     *
     * @return Application|null
     */
    public function isEditValid(Application $application, $coreId, $halApp, $name)
    {
        $this->errors = [];

        $halApp = $this->validateValues($coreId, $halApp, $name);

        if ($this->errors) return null;

        // dupe check
        $isDupe = $this->isDupe($coreId, $name, $halApp, $application);

        if ($this->errors) return null;

        if ($halApp instanceof HalApplication) {
            $name = $halApp->name();
        } else {
            $halApp = null;
        }

        $application
            ->withName($name)
            ->withCoreId($coreId)
            ->withHalApplication($halApp);

        return $application;
    }

    /**
     * @param string $coreId
     * @param string $name
     * @param HalApplication|null $halApp
     * @param Application|null $application
     *
     * @return bool
     */
    private function isDupe($coreId, $name, HalApplication $halApp = null, Application $application = null)
    {
        // check for dupe core Id
        $criteria = (new Criteria)
            ->where(Criteria::expr()->eq('coreId', $coreId));

        // check for dupe hal app, OR name
        if ($halApp instanceof HalApplication) {
            $criteria->orWhere(Criteria::expr()->eq('halApplication', $halApp));

        } else {
            $criteria->orWhere(Criteria::expr()->eq('name', $name));
        }

        if ($application) {
            // exclude this app
            $criteria->andWhere(Criteria::expr()->neq('id', $application->id()));
        }

        $matches = $this->applicationRepo->matching($criteria);
        if ($matches->toArray()) {
            $this->errors[] = self::ERR_DUPLICATE;
            return true;
        }

        return false;
    }

    /**
     * @param string $coreId
     * @param string $halApp
     * @param string $name
     *
     * @return HalApplication|null
     */
    private function validateValues($coreId, $halApp, $name)
    {
        if (preg_match(self::VALIDATE_COREID_REGEX, $coreId) !== 1) {
            $this->errors[] = self::ERR_INVALID_COREID;
        }

        if (!$halApp && preg_match(self::VALIDATE_NAME_REGEX, $name) !== 1) {
            $this->errors[] = self::ERR_INVALID_NAME;
        }

        if ($halApp) {
            if (!$halApp = $this->halRepo->find($halApp)) {
                $this->errors[] = self::ERR_INVALID_HAL_REPOSITORY;
            }
        }

        return ($halApp instanceof HalApplication) ? $halApp : null;
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }
}
