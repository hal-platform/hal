<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\User;
use Hal\Core\Entity\User\UserIdentity;
use Hal\Core\Entity\User\UserPermission;
use Hal\Core\Entity\System\SystemSetting;
use Hal\Core\Entity\System\UserIdentityProvider;
use Hal\Core\Entity\System\VersionControlProvider;
use Hal\Core\Type\IdentityProviderEnum;
use Hal\Core\Type\UserPermissionEnum;
use Hal\Core\Type\VCSProviderEnum;
use Hal\UI\Controllers\CSRFTrait;
use Hal\UI\Controllers\RedirectableControllerTrait;
use Hal\UI\Controllers\SessionTrait;
use Hal\UI\Controllers\TemplatedControllerTrait;
use Hal\UI\Parameters;
use Hal\UI\Validator\EnvironmentValidator;
use Hal\UI\Validator\IdentityValidator;
use Hal\UI\Validator\UserIdentityProviderValidator;
use Hal\UI\Validator\UserIdentityValidator;
use Hal\UI\Validator\UserValidator;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Validator\VersionControlProviderValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;
use function password_hash;

class HalBootstrapController implements ControllerInterface
{
    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;
    use ValidatorErrorTrait;

    const SETTING_IS_BOOTSTRAPPED = 'hal.is_configured';

    // private const ERR_ENVIRONMENTS = 'An error occurred when adding default environments.';
    // private const ERR_VCS = 'An error occurred when adding default version control system.';
    private const ERR_IDP = 'An error occurred when adding default identity provider.';
    private const ERR_ADMIN_USER = 'An error occurred when adding administrator user.';

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $idpRepo;
    private $settingRepo;

    /**
     * @var EnvironmentValidator
     */
    private $envValidator;

    /**
     * @var VersionControlProviderValidator
     */
    private $vcsValidator;

    /**
     * @var UserIdentityProviderValidator
     */
    private $idpValidator;

    /**
     * @var UserIdentityValidator
     */
    private $identityValidator;

    /**
     * @var UserValidator
     */
    private $userValidator;

    /**
     * @var URI
     */
    private $uri;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param EnvironmentValidator $envValidator
     * @param VersionControlProviderValidator $vcsValidator
     * @param UserIdentityProviderValidator $idpValidator
     * @param UserIdentityValidator $identityValidator
     * @param UserValidator $userValidator
     * @param URI $uri
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em,
        EnvironmentValidator $envValidator,
        VersionControlProviderValidator $vcsValidator,
        UserIdentityProviderValidator $idpValidator,
        UserIdentityValidator $identityValidator,
        UserValidator $userValidator,
        URI $uri
    ) {
        $this->template = $template;
        $this->em = $em;

        $this->envValidator = $envValidator;
        $this->vcsValidator = $vcsValidator;
        $this->idpValidator = $idpValidator;
        $this->identityValidator = $identityValidator;
        $this->userValidator = $userValidator;
        $this->uri = $uri;

        $this->settingRepo = $em->getRepository(SystemSetting::class);
        $this->idpRepo = $em->getRepository(UserIdentityProvider::class);

        $this->resetErrors();
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $providers = $this->idpRepo->findAll();
        if ($providers) {
            return $this->withRedirectRoute($response, $this->uri, 'home');
        }

        $setting = $this->idpRepo->findOneBy(['name' => self::SETTING_IS_BOOTSTRAPPED]);
        if ($setting instanceof SystemSetting && $setting->value()) {
            return $this->withRedirectRoute($response, $this->uri, 'home');
        }

        if ($msg = $this->handleForm($request)) {
            $this->withFlashSuccess($request, $msg);
            return $this->withRedirectRoute($response, $this->uri, 'signin');
        }

        return $this->withTemplate($request, $response, $this->template, [
            'errors' => $this->errors
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string|null
     */
    private function handleForm(ServerRequestInterface $request): ?string
    {
        if ($request->getMethod() !== 'POST') {
            return null;
        }

        if (!$this->isCSRFValid($request)) {
            return null;
        }

        $form = $request->getParsedBody();

        $data = [
            'admin_username' => $form['admin_username'] ?? '',
            'admin_password' => $form['admin_password'] ?? '',
            'ghe_url' => $form['ghe_url'] ?? '',
            'ghe_token' => $form['ghe_token'] ?? '',
        ];

        $idp = $this->addIdentityProvider();
        $this->addVersionControlProvider($data['ghe_url'], $data['ghe_token']);
        $this->addEnvironments();

        if ($this->hasErrors()) {
            return null;
        }

        $this->em->flush();

        $this->addAdmin($idp, $data['admin_username'], $data['admin_password']);

        if ($this->hasErrors()) {
            return null;
        }

        $isConfigured = (new SystemSetting)
            ->withName(self::SETTING_IS_BOOTSTRAPPED)
            ->withValue('1');

        $this->em->persist($isConfigured);
        $this->em->flush();

        return 'Administrator and GitHub have been configured. Please sign-in with your administrator credentials.';
    }

    /**
     * @return UserIdentityProvider|null
     */
    private function addIdentityProvider(): ?UserIdentityProvider
    {
        $idp = $this->idpValidator->isValid(IdentityProviderEnum::TYPE_INTERNAL, [
            'name' => 'Internal Auth'
        ]);

        if (!$idp) {
            $this->importErrors($this->idpValidator->errors());
            return null;
        }

        $this->em->persist($idp);
        return $idp;
    }

    /**
     * @param UserIdentityProvider $idp
     * @param string $username
     * @param string $password
     *
     * @return void
     */
    private function addAdmin(UserIdentityProvider $idp, $username, $password)
    {
        $hashed = password_hash($password, \PASSWORD_BCRYPT, [
            'cost' => 10,
        ]);

        $adminUser = $this->userValidator->isValid([
            'name' => $username
        ]);

        $adminIdentity = $this->identityValidator->isValid([
            'internal_username' => $username,
            'id_provider' => $idp->id()
        ]);

        if (!$adminUser || !$adminIdentity) {
            $this->addError(self::ERR_ADMIN_USER);
            return;
        }

        $adminIdentity
            ->withParameter(Parameters::ID_INTERNAL_SETUP_TOKEN, null)
            ->withParameter(Parameters::ID_INTERNAL_SETUP_EXPIRY, null)
            ->withParameter(Parameters::ID_INTERNAL_PASSWORD, $hashed)
            ->withUser($adminUser);

        // make user admin
        $permissions = (new UserPermission)
            ->withType(UserPermissionEnum::TYPE_SUPER)
            ->withUser($adminUser);

        $this->em->persist($adminUser);
        $this->em->persist($adminIdentity);
        $this->em->persist($permissions);
    }

    /**
     * @param string $gheURL
     * @param string $gheToken
     *
     * @return void
     */
    private function addVersionControlProvider($gheURL, $gheToken)
    {
        $vcs = $this->vcsValidator->isValid(VCSProviderEnum::TYPE_GITHUB_ENTERPRISE, [
            'name' => 'GitHub Enterprise',
            'ghe_url' => $gheURL,
            'ghe_token' => $gheToken
        ]);

        if (!$vcs) {
            $this->importErrors($this->vcsValidator->errors());
            return;
        }

        $this->em->persist($vcs);
    }

    /**
     * @return void
     */
    private function addEnvironments()
    {
        $staging = $this->envValidator->isValid('staging', false);
        $this->importErrors($this->envValidator->errors());

        $prod = $this->envValidator->isValid('prod', true);
        $this->importErrors($this->envValidator->errors());

        if (!$staging || !$prod) {
            return;
        }

        $this->em->persist($staging);
        $this->em->persist($prod);
    }
}
