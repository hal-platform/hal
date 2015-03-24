<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\EncryptedProperty;

use QL\Hal\Core\Repository\EncryptedPropertyRepository;
use QL\Hal\Core\Repository\RepositoryRepository;
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
     * @type EncryptedPropertyRepository
     */
    private $encryptedRepo;

    /**
     * @type RepositoryRepository
     */
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
     * @param EncryptedPropertyRepository $encryptedRepo
     * @param RepositoryRepository $repoRepo
     * @param Response $response
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EncryptedPropertyRepository $encryptedRepo,
        RepositoryRepository $repoRepo,
        Response $response,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->encryptedRepo = $encryptedRepo;
        $this->repoRepo = $repoRepo;

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
        return function($prop1, $prop2) {

            // global to bottom
            if ($prop1->getEnvironment() xor $prop2->getEnvironment()) {
                return $prop1->getEnvironment() ? -1 : 1;
            }

            if ($prop1->getEnvironment() === $prop2->getEnvironment()) {
                // same env, compare name
                return strcasecmp($prop1->getName(), $prop2->getName());
            }

            $order1 = $prop1->getEnvironment()->getOrder();
            $order2 = $prop2->getEnvironment()->getOrder();
            if ($order1 === $order2) {
                return 0;
            }

            // compare env order
            return ($order1 < $order2) ? -1 : 1;
        };
    }
}
