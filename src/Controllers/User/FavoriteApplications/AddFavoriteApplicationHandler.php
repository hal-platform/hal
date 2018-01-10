<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User\FavoriteApplications;

use Doctrine\ORM\EntityManagerInterface;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Flash;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\User;
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
     * @var EntityManagerInterface
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
     * @param EntityManagerInterface $em
     * @param URI $uri
     * @param JSON $json
     */
    public function __construct(EntityManagerInterface $em, URI $uri, JSON $json)
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

        $this->addFavorite($user, $application);

        // persist to database
        $this->em->persist($user);
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

    /**
     * @param User $user
     * @param Application $application
     *
     * @return void
     */
    private function addFavorite(User $user, Application $application)
    {
        $favorites = $user->setting('favorite_applications') ?? [];

        $isFavorite = in_array($application->id(), $favorites, true);
        if ($isFavorite) {
            return;
        }

        $favorites[] = $application->id();

        $user->withSetting('favorite_applications', $favorites);
    }
}
