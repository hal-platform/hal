<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Repository\EncryptedProperty;

use Doctrine\ORM\EntityManager;
use QL\Hal\Core\Repository\EncryptedPropertyRepository;
use QL\Hal\Session;
use QL\Hal\Slim\NotFound;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class RemoveEncryptedPropertyHandler implements MiddlewareInterface
{
    const SUCCESS = '';

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type EncryptedPropertyRepository
     */
    private $encryptedRepo;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type Request
     */
    private $request;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param EntityManager $em
     * @param EncryptedPropertyRepository $encryptedRepo
     * @param Session $session
     * @param Url $url
     * @param Request $request
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        EntityManager $em,
        EncryptedPropertyRepository $encryptedRepo,
        Session $session,
        Url $url,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->em = $em;
        $this->encryptedRepo = $encryptedRepo;

        $this->session = $session;
        $this->url = $url;

        $this->request = $request;
        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        if (!$this->request->isPost()) {
            return;
        }

        if (!$property = $this->encryptedRepo->find($this->parameters['id'])) {
            return call_user_func($this->notFound);
        }

        $this->em->remove($property);
        $this->em->flush();

        $message = sprintf('Encrypted Property "%s" Removed.', $property->getName());
        $this->session->flash($message, 'success');
        $this->url->redirectFor('repository.encrypted', ['repository' => $this->parameters['repository']]);
    }
}
