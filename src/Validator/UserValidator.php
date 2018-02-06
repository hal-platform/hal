<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Repository\UserRepository;
use Hal\Core\Entity\User;
use Psr\Http\Message\ServerRequestInterface;

class UserValidator
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    private const REGEX_CHARACTER_WHITESPACE = '\f\n\r\t\v';

    private const ERR_NAME_CHARACTERS = 'Name must not contain tabs or newlines.';

    /**
     * @var UserRepository
     */
    private $userRepo;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->userRepo = $em->getRepository(User::class);
    }

    /**
     * @param array $data
     *
     * @return User|null
     */
    public function isValid(array $data): ?User
    {
        $name = $data['name'] ?? '';

        $this->resetErrors();

        $this->validateName($name);

        if ($this->hasErrors()) {
            return null;
        }

        $user = (new User)
            ->withName($name);

        return $user;
    }

    /**
     * @param User $user
     * @param array $data
     *
     * @return User|null
     */
    public function isEditValid(User $user, array $data): ?User
    {
        $name = $data['name'] ?? '';

        $this->resetErrors();

        $this->validateName($name);

        if ($this->hasErrors()) {
            return null;
        }

        $user
            ->withName($name);

        return $user;
    }

    /**
     * @param ServerRequestInterface $request
     * @param User|null $user
     *
     * @return array
     */
    public function getFormData(ServerRequestInterface $request, ?User $user): array
    {
        $data = $request->getParsedBody();

        if ($user && $request->getMethod() !== 'POST') {
            $data['name'] = $user->name();
        }

        $form = [
            'name' => $data['name'] ?? '',
        ];

        return $form;
    }

    /**
     * @param string $name
     *
     * @return void
     */
    private function validateName($name)
    {
        if (!$this->validateIsRequired($name) || !$this->validateSanityCheck($name)) {
            $this->addRequiredError('Name', 'name');
        }

        if ($this->hasErrors()) {
            return;
        }

        if (!$this->validateLength($name, 3, 100)) {
            $this->addLengthError('Name', 3, 100, 'name');
        }

        if (!$this->validateCharacterBlacklist($name, self::REGEX_CHARACTER_WHITESPACE)) {
            $this->addError(self::ERR_NAME_CHARACTERS, 'name');
        }
    }
}
