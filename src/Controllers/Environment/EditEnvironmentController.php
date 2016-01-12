<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Environment;
use QL\Hal\Flasher;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditEnvironmentController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $envRepo;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Request
     */
    private $request;

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
     * @param Flasher $flasher
     * @param Request $request
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Flasher $flasher,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;

        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->em = $em;

        $this->flasher = $flasher;
        $this->request = $request;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$environment = $this->envRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $renderContext = [
            'form' => [
                'name' => ($this->request->isPost()) ? $this->request->post('name') : $environment->name()
            ],
            'env' => $environment,
            'errors' => $this->checkFormErrors($this->request)
        ];

        if ($this->handleFormSubmission($this->request, $environment, $renderContext['errors'])) {
            return $this->flasher
                ->withFlash('Environment updated successfully.', 'success')
                ->load('environment', ['id' => $environment->id()]);
        }

        $this->template->render($renderContext);
    }

    /**
     * Returns true if the form was submitted successfully.
     *
     * @param Request $request
     * @param Environment $environment
     * @param array $errors
     *
     * @return null
     */
    private function handleFormSubmission(Request $request, Environment $environment, array $errors)
    {
        if (!$request->isPost() || $errors) {
            return false;
        }

        $environment->withName($request->post('name'));
        $this->em->merge($environment);
        $this->em->flush();

        return true;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function checkFormErrors(Request $request)
    {
        if (!$request->isPost()) {
            return [];
        }

        $errors = [];
        $name = $request->post('name');

        if (!preg_match('@^[a-zA-Z_-]*$@', $name)) {
            $errors[] = 'Environment name must consist of letters, underscores and/or hyphens.';
        }

        if (strlen($name) > 24 || strlen($name) < 2) {
            $errors[] = 'Environment name must be between 2 and 24 characters.';
        }

        if (!$errors && $env = $this->envRepo->findOneBy(['name' => $name])) {
            $errors[] = 'An environment with this name already exists.';
        }

        return $errors;
    }
}
