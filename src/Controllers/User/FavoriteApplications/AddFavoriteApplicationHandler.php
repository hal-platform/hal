<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User\FavoriteApplications;

use Doctrine\ORM\EntityManager;
use Hal\UI\Flasher;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserSettings;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\Json;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * PUT  /api/internal/settings/favorite-applications/$id (ajax)
 *
 * POST /settings/favorite-applications/$id (nojs)
 */
class AddFavoriteApplicationHandler implements ControllerInterface
{
    const SUCCESS = 'Application "%s" added to favorites.';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var callable
     */
    private $random;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var Application
     */
    private $application;

    /**
     * @param EntityManager $em
     * @param Request $request
     * @param Response $response
     * @param Flasher $flasher
     * @param Json $json
     * @param callable $random
     * @param User $currentUser
     * @param Application $application
     */
    public function __construct(
        EntityManager $em,
        Request $request,
        Response $response,
        Flasher $flasher,
        Json $json,
        callable $random,
        User $currentUser,
        Application $application
    ) {
        $this->em = $em;
        $this->request = $request;
        $this->response = $response;

        $this->flasher = $flasher;
        $this->random = $random;

        $this->json = $json;

        $this->currentUser = $currentUser;
        $this->application = $application;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $settings = $this->currentUser->settings();

        // @todo backfill settings so every user has them (eliminate null checks)
        if (!$settings) {
            $id = call_user_func($this->random);
            $settings = (new UserSettings($id))
                ->withUser($this->currentUser);
        }

        if (!$settings->isFavoriteApplication($this->application)) {
            $settings->withFavoriteApplication($this->application);
        }

        // persist to database
        $this->em->persist($settings);
        $this->em->flush();

        $isAjax = ($this->request->getMediaType() === 'application/json');

        $success = sprintf(self::SUCCESS, $this->application->name());

        // not ajax? Save a flash and bounce
        if (!$isAjax) {
            $this->flasher
                ->withFlash($success, 'success')
                ->load('applications');
        } else {
            $json = $this->json->encode(['message' => $success]);

            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->setBody($json);
        }
    }
}
