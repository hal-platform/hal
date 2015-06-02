<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Application\EncryptedProperty;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\EncryptedProperty;
use QL\Hal\Session;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\MiddlewareInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class RemoveEncryptedPropertyHandler implements MiddlewareInterface
{
    const SUCCESS = '';

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
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
     * @param EntityManagerInterface $em
     * @param Session $session
     * @param Url $url
     * @param Request $request
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        EntityManagerInterface $em,
        Session $session,
        Url $url,
        Request $request,
        NotFound $notFound,
        array $parameters
    ) {
        $this->em = $em;
        $this->encryptedRepo = $em->getRepository(EncryptedProperty::CLASS);

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

        $message = sprintf('Encrypted Property "%s" Removed.', $property->name());
        $this->session->flash($message, 'success');
        $this->url->redirectFor('repository.encrypted', ['repository' => $this->parameters['repository']]);
    }
}
