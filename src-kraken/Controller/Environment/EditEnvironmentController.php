<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Validator\EnvironmentValidator;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Hal\Flasher;
use Slim\Http\Request;

class EditEnvironmentController implements ControllerInterface
{
    const SUCCESS = 'Environment updated.';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Environment
     */
    private $environment;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type EnvironmentValidator
     */
    private $validator;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $environmentRepo;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Environment $environment
     * @param Flasher $flasher
     * @param EnvironmentValidator $validator
     * @param EntityManagerInterface $em
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Environment $environment,
        Flasher $flasher,
        EnvironmentValidator $validator,
        EntityManagerInterface $em
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->environment = $environment;
        $this->flasher = $flasher;
        $this->validator = $validator;

        $this->em = $em;
        $this->environmentRepo = $em->getRepository(Environment::CLASS);

        $this->errors = [];
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $form = [
            'server' => $this->environment->consulServer(),
            'token' => $this->environment->consulToken()
        ];

        if ($this->request->isPost()) {

            $form['server'] = $this->request->post('server');
            $form['token'] = $this->request->post('token');

            if ($environment = $this->handleForm()) {
                // flash and redirect
                $this->flasher
                    ->withFlash(sprintf(self::SUCCESS, $environment->name()), 'success')
                    ->load('kraken.environments');
            }
        }

        $context = [
            'environment' => $this->environment,
            'errors' => $this->validator->errors(),
            'form' => $form
        ];

        $this->template->render($context);
    }

    /**
     * @return Environment|null
     */
    private function handleForm()
    {
        $server = $this->request->post('server');
        $token = $this->request->post('token');

        if ($environment = $this->validator->isEditValid($this->environment, $server, $token)) {

            // persist to database
            $this->em->merge($environment);
            $this->em->flush();
        }

        return $environment;
    }
}
