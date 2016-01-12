<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Flasher;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Validator\EnvironmentValidator;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddEnvironmentController implements ControllerInterface
{
    const SUCCESS = 'Environment "%s" added.';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var EntityManagerInterface
     */
    private $environmentRepo;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var EnvironmentValidator
     */
    private $validator;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Flasher $flasher
     * @param EntityManagerInterface $em
     * @param EnvironmentValidator $validator
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Flasher $flasher,
        EntityManagerInterface $em,
        EnvironmentValidator $validator
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->flasher = $flasher;
        $this->validator = $validator;

        $this->em = $em;
        $this->environmentRepo = $this->em->getRepository(Environment::CLASS);

        $this->errors = [];
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        if ($this->request->isPost() && $environment = $this->handleForm()) {
            return $this->flasher
                ->withFlash(sprintf(self::SUCCESS, $environment->name()), 'success')
                ->load('kraken.environments');
        }

        $context = [
            'errors' => $this->validator->errors(),
            'form' => [
                'name' => $this->request->post('name'),
                'is_prod' => $this->request->post('is_prod'),

                'consul_service' => $this->request->post('consul_service'),
                'consul_token' => $this->request->post('consul_token'),

                'qks_service' => $this->request->post('qks_service'),
                'qks_key' => $this->request->post('qks_key'),
                'qks_client' => $this->request->post('qks_client'),
                'qks_secret' => $this->request->post('qks_secret'),
            ]
        ];

        $this->template->render($context);
    }

    /**
     * @return Environment|null
     */
    private function handleForm()
    {
        $name = $this->request->post('name');
        $isProd = $this->request->post('is_prod');

        $consulService = $this->request->post('consul_service');
        $consulToken = $this->request->post('consul_token');

        $qksService = $this->request->post('qks_service');
        $qksKey = $this->request->post('qks_key');
        $qksClient = $this->request->post('qks_client');
        $qksSecret = $this->request->post('qks_secret');

        $environment = $this->validator->isValid(
            $name,
            $isProd,
            $consulService,
            $consulToken,
            $qksService,
            $qksKey,
            $qksClient,
            $qksSecret
        );

        if ($environment) {
            // persist to database
            $this->em->persist($environment);
            $this->em->flush();
        }

        return $environment;
    }
}
