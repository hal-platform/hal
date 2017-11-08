<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\EncryptedConfiguration;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\EncryptedProperty;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\URI;

class RemoveEncryptedPropertyController implements ControllerInterface
{
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Encrypted Property "%s" removed.';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param EntityManagerInterface $em
     * @param URI $uri
     */
    public function __construct(EntityManagerInterface $em, URI $uri)
    {
        $this->em = $em;
        $this->uri = $uri;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $encrypted = $request->getAttribute(EncryptedProperty::class);

        $this->em->remove($encrypted);
        $this->em->flush();

        $message = sprintf(self::MSG_SUCCESS, $encrypted->name());
        $this->withFlash($request, Flash::SUCCESS, $message);
        return $this->withRedirectRoute($response, $this->uri, 'encrypted.configuration', ['application' => $application->id()]);
    }
}
