<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\EncryptedProperty;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Core\Entity\Repository;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Response;

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
    private $repoRepo;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->encryptedRepo = $em->getRepository(EncryptedProperty::CLASS);
        $this->repoRepo = $em->getRepository(Repository::CLASS);

        $this->response = $response;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$repo = $this->repoRepo->find($this->parameters['repository'])) {
            return call_user_func($this->notFound);
        }

        $encrypted = $this->encryptedRepo->findBy(['repository' => $repo]);
        usort($encrypted, $this->sortByEnv());

        $rendered = $this->template->render([
            'repository' => $repo,
            'encrypted' => $encrypted
        ]);

        $this->response->setBody($rendered);
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
            if ($prop1->getEnvironment() xor $prop2->getEnvironment()) {
                return $prop1->getEnvironment() ? -1 : 1;
            }

            if ($prop1->getEnvironment() === $prop2->getEnvironment()) {
                // same env, compare name
                return strcasecmp($prop1->getName(), $prop2->getName());
            }

            $aName = strtolower($prop1->getEnvironment()->name());
            $bName = strtolower($prop2->getEnvironment()->name());

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
