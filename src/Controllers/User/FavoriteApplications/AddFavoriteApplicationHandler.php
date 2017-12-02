<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User\FavoriteApplications;

use Doctrine\ORM\EntityManager;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\JSON;
use QL\Panthor\Utility\URI;

/**
 * PUT  /api/internal/settings/favorite-applications/$id (ajax)
 *
 * POST /settings/favorite-applications/$id (nojs)
 */
class AddFavoriteApplicationHandler implements ControllerInterface
{
    use APITrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Application "%s" added to favorites.';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @var JSON
     */
    private $json;

    /**
     * @param EntityManager $em
     * @param URI $uri
     * @param JSON $json
     */
    public function __construct(EntityManager $em, URI $uri, JSON $json)
    {
        $this->em = $em;
        $this->uri = $uri;
        $this->json = $json;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $application = $request->getAttribute(Application::class);
        $user = $this->getUser($request);

        $settings = $user->settings();

        if (!$settings->isFavoriteApplication($application)) {
            $settings->withFavoriteApplication($application);
        }

        // persist to database
        $this->em->persist($settings);
        $this->em->flush();

        $success = sprintf(self::MSG_SUCCESS, $application->name());

        // not ajax? Save a flash and bounce
        if (!$this->isXHR($request)) {
            $this->withFlash($request, Flash::SUCCESS, $success);
            return $this->withRedirectRoute($response, $this->uri, 'applications');
        }

        return $this
            ->withNewBody($response, $this->json->encode(['message' => $success]))
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
