<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\VCS;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;

class AddVersionControlController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;

    private const MSG_SUCCESS = 'Environment "%s" added.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

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
        $this->em = $em;

        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $form = $this->getFormData($request);

        if ($environment = $this->handleForm($form, $request)) {
            $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $environment->name()));
            return $this->withRedirectRoute($response, $this->uri, 'vcs_providers');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'form' => $form,
            'errors' => [],
        ]);
    }

    /**
     * @param array $data
     * @param ServerRequestInterface $request
     *
     * @return UserIdentityProvider|null
     */
    private function handleForm(array $data, ServerRequestInterface $request): ?UserIdentityProvider
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $vcs = null;

        if ($vcs) {
            $this->em->persist($vcs);
            $this->em->flush();
        }

        return $vcs;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    private function getFormData(ServerRequestInterface $request)
    {
        $form = [
            'name' => $request->getParsedBody()['name'] ?? '',
        ];

        return $form;
    }
}
