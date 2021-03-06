<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User\FavoriteApplications;

use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Application;
use Hal\Core\Entity\User;
use Hal\UI\Controllers\APITrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\JSON;
use QL\Panthor\Utility\URI;

class RemoveFavoriteApplicationHandler implements ControllerInterface
{
    use APITrait;
    use RedirectableControllerTrait;
    use SessionTrait;

    const MSG_SUCCESS = 'Application "%s" removed from favorites.';

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
        if (!$this->isXHR($request)) {
            return $this->withRedirectRoute($response, $this->uri, 'applications');
        }

        $application = $request->getAttribute(Application::class);
        $user = $this->getUser($request);

        $this->removeFavorite($user, $application);

        $this->em->persist($user);
        $this->em->flush();

        $success = sprintf(self::MSG_SUCCESS, $application->name());

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
    private function removeFavorite(User $user, Application $application)
    {
        $favorites = $user->setting('favorite_applications') ?? [];

        $isFavorite = in_array($application->id(), $favorites, true);
        if (!$isFavorite || !$favorites) {
            return;
        }

        $favorites = array_filter($favorites, function ($appID) use ($application) {
            return ($appID !== $application->id());
        });

        $user->withSetting('favorite_applications', array_values($favorites));
    }
}
