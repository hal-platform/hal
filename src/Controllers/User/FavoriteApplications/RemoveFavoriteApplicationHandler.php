<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\User\FavoriteApplications;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserSettings;
use QL\Hal\Flasher;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Utility\Json;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * DELETE  /api/internal/settings/favorite-applications/$id (ajax)
 *
 * POST /settings/favorite-applications/$id/delete (nojs)
 */
class RemoveFavoriteApplicationHandler implements ControllerInterface
{
    const SUCCESS = 'Application "%s" removed from favorites.';

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type Response
     */
    private $response;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Json
     */
    private $json;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type Application
     */
    private $application;

    /**
     * @param EntityManager $em
     * @param Request $request
     * @param Response $response
     * @param Flasher $flasher
     * @param Json $json
     * @param User $currentUser
     * @param Application $application
     */
    public function __construct(
        EntityManager $em,
        Request $request,
        Response $response,
        Flasher $flasher,
        Json $json,
        User $currentUser,
        Application $application
    ) {
        $this->em = $em;
        $this->request = $request;
        $this->response = $response;

        $this->flasher = $flasher;
        $this->json = $json;

        $this->currentUser = $currentUser;
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $settings = $this->currentUser->settings();

        // @todo backfill settings so every user has them (eliminate null checks)
        if ($settings && $settings->isFavoriteApplication($this->application)) {
            $settings->withoutFavoriteApplication($this->application);

            // persist to database
            $this->em->persist($settings);
            $this->em->flush();
        }

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
