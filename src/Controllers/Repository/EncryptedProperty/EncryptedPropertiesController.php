<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\EncryptedProperty;

use QL\Hal\Core\Entity\Repository\EncryptedPropertyRepository;
use QL\Hal\Core\Entity\Repository\RepositoryRepository;
use QL\Hal\Slim\NotFound;
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

        $rendered = $this->template->render([
            'repository' => $repo,
            'encrypted' => $encrypted
        ]);

        $this->response->setBody($rendered);
    }
}
