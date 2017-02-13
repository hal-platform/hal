<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Application\EncryptedProperty;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class EncryptedPropertiesController implements ControllerInterface
{
    use SortingTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $encryptedRepo;

    /**
     * @var Application
     */
    private $application;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Application $application
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Application $application
    ) {
        $this->template = $template;
        $this->encryptedRepo = $em->getRepository(EncryptedProperty::CLASS);

        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $encrypted = $this->encryptedRepo->findBy(['application' => $this->application]);
        usort($encrypted, $this->sortByEnv());

        $this->template->render([
            'application' => $this->application,
            'encrypted' => $encrypted
        ]);
    }

    private function sortByEnv()
    {
        $order = $this->sortingHelperEnvironmentOrder;

        return function($prop1, $prop2) use ($order) {

            // global to bottom
            if ($prop1->environment() xor $prop2->environment()) {
                return $prop1->environment() ? -1 : 1;
            }

            if ($prop1->environment() === $prop2->environment()) {
                // same env, compare name
                return strcasecmp($prop1->name(), $prop2->name());
            }

            $aName = strtolower($prop1->environment()->name());
            $bName = strtolower($prop2->environment()->name());

            $aOrder = isset($order[$aName]) ? $order[$aName] : 999;
            $bOrder = isset($order[$bName]) ? $order[$bName] : 999;

            if ($aOrder === $bOrder) {
                return 0;
            }

            // compare env order
            return ($aOrder < $bOrder) ? -1 : 1;
        };
    }
}
