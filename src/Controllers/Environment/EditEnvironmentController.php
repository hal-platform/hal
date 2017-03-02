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

class EditEnvironmentController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Environment updated successfully.';

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
     * @param URI $uri
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
        $environment = $request->getAttribute(Environment::class);

        $form = $this->getFormData($request, $environment);

        if ($modified = $this->handleForm($form, $request, $environment)) {
            $this->withFlash($request, Flash::SUCCESS, self::MSG_SUCCESS);
            return $this->withRedirectRoute($response, $this->uri, 'environment', ['environment' => $modified->id()]);
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => $this->errors,

            'env' => $environment
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     * @param Environment $environment
     *
     * @return Environment|null
     */
    private function handleForm(array $data, ServerRequestInterface $request, Environment $environment): ?Environment
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        $name = $data['name'];

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

        $environment->withName($name);

        $this->em->merge($environment);
        $this->em->flush();

        return $environment;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Environment $environment
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request, Environment $environment)
    {
        if ($request->getMethod() === 'POST') {
            $form = [
                'name' => $request->getParsedBody()['name'] ?? ''
            ];
        } else {
            $form = [
                'name' => $environment->name()
            ];
        }

        return $form;
    }
}
