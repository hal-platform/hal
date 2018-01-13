<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\TargetTemplate;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Target;
use Hal\Core\Entity\TargetTemplate;
use Hal\Core\Repository\TargetRepository;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveTemplateController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    private const MSG_SUCCESS = '"%s" template removed.';
    private const ERR_IN_USE = 'Cannot remove template. Applications are currently using this template.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var TargetRepository
     */
    private $targetRepo;

    /**
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, URI $uri)
    {
        $this->em = $em;
        $this->uri = $uri;

        $this->targetRepo = $em->getRepository(Target::class);
    }

    /**
     * @inheritdoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $template = $request->getAttribute(TargetTemplate::class);

        if ($this->targetRepo->findBy(['template' => $template])) {
            $this->withFlashError($request, self::ERR_IN_USE);
            return $this->withRedirectRoute($response, $this->uri, 'template', ['template' => $group->id()]);
        }

        $this->em->remove($template);
        $this->em->flush();

        $this->withFlashSuccess($request, sprintf(self::MSG_SUCCESS, $template->name()));
        return $this->withRedirectRoute($response, $this->uri, 'templates');
    }
}
