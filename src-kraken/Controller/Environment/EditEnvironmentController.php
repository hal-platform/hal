<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\Crypto\Exception\CryptoException;
use MCP\Crypto\Package\TamperResistantPackage;
use QL\Hal\Flasher;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Validator\EnvironmentValidator;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditEnvironmentController implements ControllerInterface
{
    const SUCCESS = 'Environment updated.';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var TamperResistantPackage
     */
    private $encryption;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var EnvironmentValidator
     */
    private $validator;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $environmentRepo;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Environment $environment
     * @param TamperResistantPackage $encryption
     * @param Flasher $flasher
     * @param EnvironmentValidator $validator
     * @param EntityManagerInterface $em
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Environment $environment,
        TamperResistantPackage $encryption,
        Flasher $flasher,
        EnvironmentValidator $validator,
        EntityManagerInterface $em
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->environment = $environment;
        $this->encryption = $encryption;
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
        if ($this->request->isPost()) {
            $form = [
                'service' => $this->request->post('service'),
                'token' => $this->request->post('token'),
                'qks_service' => $this->request->post('qks_service'),
                'qks_key' => $this->request->post('qks_key'),
                'qks_client' => $this->request->post('qks_client'),
                'qks_secret' => $this->request->post('qks_secret'),
            ];
        } else {

            $token = $this->decrypt($this->environment->consulToken());
            $secret = $this->decrypt($this->environment->qksClientSecret());

            $form = [
                'service' => $this->environment->consulServiceURL(),
                'token' => $token,
                'qks_service' => $this->environment->qksServiceURL(),
                'qks_key' => $this->environment->qksEncryptionKey(),
                'qks_client' => $this->environment->qksClientID(),
                'qks_secret' => $secret
            ];
        }

        if ($this->request->isPost()) {
            if ($environment = $this->handleForm($form)) {
                // flash and redirect
                $this->flasher
                    ->withFlash(sprintf(self::SUCCESS, $environment->name()), 'success')
                    ->load('kraken.environment', ['environment' => $environment->id()]);
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
     * @param array $data
     *
     * @return Environment|null
     */
    private function handleForm(array $data)
    {
        $environment = $this->validator->isEditValid(
            $this->environment,
            $data['service'],
            $data['token'],
            $data['qks_service'],
            $data['qks_key'],
            $data['qks_client'],
            $data['qks_secret']
        );

        if ($environment) {
            // persist to database
            $this->em->merge($environment);
            $this->em->flush();
        }

        return $environment;
    }

    /**
     * @param string $encrypted
     *
     * @return string
     */
    private function decrypt($encrypted)
    {
        if (!$encrypted) {
            return '';
        }

        try {
            $decrypted = $this->encryption->decrypt($encrypted);
        } catch (CryptoException $ex) {
            $decrypted = '';
        }

        return $decrypted;
    }
}
