<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application\Schema;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Kraken\Application\SkeletorConfigurationTemplate;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Schema;
use QL\Panthor\ControllerInterface;

class PrimeSkeletorSchemaController implements ControllerInterface
{
    const SUCCESS = 'Skeletor configuration schema added.';
    const ERR_HAS_SCHEMA = 'This application has schema and cannot be primed.';

    /**
     * @type Application
     */
    private $application;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $schemaRepo;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type SkeletorConfigurationTemplate
     */
    private $skeletorTemplate;

    /**
     * @param Application $application
     * @param User $currentUser
     *
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param SkeletorConfigurationTemplate $skeletorTemplate
     */
    public function __construct(
        Application $application,
        User $currentUser,
        EntityManagerInterface $em,
        Flasher $flasher,
        SkeletorConfigurationTemplate $skeletorTemplate
    ) {
        $this->application = $application;
        $this->currentUser = $currentUser;

        $this->em = $em;
        $this->schemaRepo = $this->em->getRepository(Schema::CLASS);

        $this->flasher = $flasher;
        $this->skeletorTemplate = $skeletorTemplate;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $hasSchema = $this->schemaRepo->findOneBy(['application' => $this->application]);

        if ($hasSchema) {
            $this->flasher
                ->withFlash(self::ERR_HAS_SCHEMA, 'error')
                ->load('kraken.schema', ['application' => $this->application->id()]);
        }

        $schemas = $this->skeletorTemplate->generate($this->application, $this->currentUser);

        foreach ($schemas as $schema) {
            $this->em->persist($schema);
        }

        $this->em->flush();

        $this->flasher
            ->withFlash(self::SUCCESS, 'success')
            ->load('kraken.schema', ['application' => $this->application->id()]);
    }
}
