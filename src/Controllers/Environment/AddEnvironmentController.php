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
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddEnvironmentController implements ControllerInterface
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
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param Request $request
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        Flasher $flasher,
        Request $request
    ) {
        $this->template = $template;

        $this->envRepo = $em->getRepository(Environment::CLASS);
        $this->em = $em;

        $this->flasher = $flasher;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $renderContext = [
            'form' => [
                'name' => $this->request->post('name')
            ],
            'errors' => $this->checkFormErrors($this->request)
        ];

        if ($this->handleFormSubmission($this->request, $renderContext['errors'])) {
            $message = sprintf('Environment "%s" added.', $this->request->post('name'));
            return $this->flasher
                ->withFlash($message, 'success')
                ->load('environments');
        }

        $this->template->render($renderContext);
    }

    /**
     * Returns true if the form was submitted successfully.
     *
     * @param Request $request
     * @param array $errors
     * @return null
     */
    private function handleFormSubmission(Request $request, array $errors)
    {
        if (!$request->isPost() || $errors) {
            return false;
        }

        $environment = (new Environment)
            ->withName($request->post('name'))
            ->withIsProduction(false);

        $this->em->persist($environment);
        $this->em->flush();

        return true;
    }

    /**
     * @param Request $request
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

        if (mb_strlen($name, 'UTF-8') > 24 || mb_strlen($name, 'UTF-8') < 2) {
            $errors[] = 'Environment name must be between 2 and 24 characters.';
        }

        if (!$errors && $env = $this->envRepo->findOneBy(['name' => $name])) {
            $errors[] = 'An environment with this name already exists.';
        }

        return $errors;
    }
}
