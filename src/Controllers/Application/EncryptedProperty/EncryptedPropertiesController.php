<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\EncryptedProperty;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Core\Entity\Application;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class EncryptedPropertiesController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $encryptedRepo;

    /**
     * @type Application
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
        $order = [
            'dev' => 0,
            'test' => 1,
            'beta' => 2,
            'prod' => 3
        ];

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
