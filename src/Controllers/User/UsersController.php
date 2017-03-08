<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\UI\Controllers\PaginationTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Hal\Core\Entity\User;
use QL\Hal\Core\Entity\UserType;
use QL\Hal\Core\Repository\UserRepository;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class UsersController implements ControllerInterface
{
    use PaginationTrait;
    use TemplatedControllerTrait;

    private const MAX_PER_PAGE = 100;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var UserRepository
     */
    private $userRepo;

    /**
     * @var EntityRepository
     */
    private $userTypesRepo;

    /**
     * @var callable
     */
    private $notFound;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param callable $notFound
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em, callable $notFound)
    {
        $this->template = $template;

        $this->userRepo = $em->getRepository(User::class);
        $this->userTypesRepo = $em->getRepository(UserType::class);

        $this->notFound = $notFound;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $page = $this->getCurrentPage($request);
        if ($page === null) {
            return ($this->notFound)($request, $response);
        }

        $users = $this->userRepo->getPaginatedUsers(self::MAX_PER_PAGE, ($page-1));
        $userTypes = $this->getTypes();

        $total = count($users);
        $last = ceil($total / self::MAX_PER_PAGE);

        return $this->withTemplate($request, $response, $this->template, [
            'page' => $page,
            'last' => $last,

            'users' => $users,
            'user_permissions' => $userTypes
        ]);
    }

    /**
     * Get all user types in the whole db, collated into per-user buckets
     *
     * @return array
     */
    private function getTypes()
    {
        $types = $this->userTypesRepo->findAll();

        $collated = [];

        foreach ($types as $type) {
            if ($type->type() === 'pleb') {
                $flag = 'isPleb';
            } elseif ($type->type() === 'lead') {
                $flag = 'isLead';
            } elseif ($type->type() === 'btn_pusher') {
                $flag = 'isButtonPusher';
            } elseif ($type->type() === 'super') {
                $flag = 'isSuper';
            }

            $userId = $type->user()->id();
            if (!isset($collated[$userId])) {
                $collated[$userId] = ['hasType' => true];
            }

            $collated[$userId][$flag] = true;
        }

        return $collated;
    }
}
