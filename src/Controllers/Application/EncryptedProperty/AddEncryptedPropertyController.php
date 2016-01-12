<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Application\EncryptedProperty;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Core\Repository\EnvironmentRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddEncryptedPropertyController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EnvironmentRepository
     */
    private $envRepo;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Application
     */
    private $application;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Request $request
     * @param Application $application
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Request $request,
        Application $application
    ) {
        $this->template = $template;
        $this->envRepo = $em->getRepository(Environment::CLASS);

        $this->request = $request;
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $this->template->render([
            'form' => [
                'environment' => $this->request->post('environment'),
                'name' => $this->request->post('name'),
                'decrypted' => $this->request->post('decrypted')
            ],
            'application' => $this->application,
            'environments' => $this->envRepo->getAllEnvironmentsSorted()
        ]);
    }
}
