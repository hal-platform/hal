<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Application\Schema;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Kraken\Application\SkeletorConfigurationTemplate;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Schema;
use QL\Panthor\ControllerInterface;

class PrimeSkeletorSchemaController implements ControllerInterface
{
    const SUCCESS = 'Skeletor configuration schema added.';
    const ERR_HAS_SCHEMA = 'This application has schema and cannot be primed.';

    /**
     * @var Application
     */
    private $application;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $schemaRepo;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var SkeletorConfigurationTemplate
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
