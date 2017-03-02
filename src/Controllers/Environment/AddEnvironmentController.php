<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Environment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\Environment;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddEnvironmentController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Environment "%s" added.';

    private const ERR_FORMAT = 'Environment name must consist of letters, underscores and/or hyphens.';
    private const ERR_LENGTH = 'Environment name must be between 2 and 24 characters.';
    private const ERR_DUPLICATE = 'An environment with this name already exists.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $envRepo;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param Flasher $flasher
     * @param Request $request
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        URI $uri
    ) {
        $this->template = $template;

        $this->envRepo = $em->getRepository(Environment::class);
        $this->em = $em;

        $this->uri = $uri;

        $this->errors = [];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $form = $this->getFormData($request);

        if ($environment = $this->handleForm($form, $request)) {
            $this->withFlash($request, Flash::SUCCESS, sprintf(self::MSG_SUCCESS, $environment->name()));
            return $this->withRedirectRoute($response, $this->uri, 'environments');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->errors,
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return Environment|null
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?Environment
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $environment = $this->validateForm($data['name']);

        if ($environment) {
            $this->em->persist($environment);
            $this->em->flush();
        }

        return $environment;
    }

    /**
     * @param string $name
     *
     * @return Environment|null
     */
    private function validateForm($name)
    {
        if (!preg_match('@^[a-zA-Z_-]*$@', $name)) {
            $this->errors[] = self::ERR_FORMAT;
        }

        if (strlen($name) > 24 || strlen($name) < 2) {
            $this->errors[] = self::ERR_LENGTH;
        }

        if ($this->errors) return null;

        if ($env = $this->envRepo->findOneBy(['name' => $name])) {
            $this->errors[] = self::ERR_DUPLICATE;
        }

        if ($this->errors) return null;

        $environment = (new Environment)
            ->withName($name)
            ->withIsProduction(false);

        return $environment;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        if ($request->getMethod() === 'POST') {
            $form = [
                'name' => $request->getParsedBody()['name'] ?? ''
            ];
        } else {
            $form = [
                'name' => ''
            ];
        }

        return $form;
    }
}
