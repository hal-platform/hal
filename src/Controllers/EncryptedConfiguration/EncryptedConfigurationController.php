<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\EncryptedConfiguration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\EncryptedProperty;
use Hal\Core\Entity\Application;
use Hal\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class EncryptedConfigurationController implements ControllerInterface
{
    use SortingTrait;
    use TemplatedControllerTrait;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $encryptedRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->encryptedRepo = $em->getRepository(EncryptedProperty::class);
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);

        $encrypteds = $this->encryptedRepo->findBy(['application' => $application]);
        usort($encrypteds, $this->sortByEnv());

        return $this->withTemplate($request, $response, $this->template, [
            'application' => $application,
            'encrypteds' => $encrypteds,
        ]);
    }

    /**
     * @return callable
     */
    private function sortByEnv()
    {
        $order = $this->sortingHelperEnvironmentOrder;

        return function (EncryptedProperty $prop1, EncryptedProperty $prop2) use ($order) {

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
