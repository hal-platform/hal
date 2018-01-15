<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Hal\Core\Entity\Environment;
use Hal\Core\Entity\User;
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
use Hal\UI\Validator\ValidatorErrorTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\URI;
use function password_hash;

class HalBootstrapController implements ControllerInterface
{
    const SETTING_IS_BOOTSTRAPPED = 'hal.is_configured';

    use CSRFTrait;
    use RedirectableControllerTrait;
    use SessionTrait;
    use TemplatedControllerTrait;
    use ValidatorErrorTrait;

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
     * @var URI
     */
    private $uri;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @param EntityManagerInterface $em
     * @param URI $uri
     * @param TemplateInterface $template
     */
    public function __construct(EntityManagerInterface $em, URI $uri, TemplateInterface $template)
    {
        $this->template = $template;
        $this->uri = $uri;

        $this->em = $em;
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

        if (!$this->validateForm($data)) {
            return null;
        }

        $this->addIdentityProvider($data['admin_username'], $data['admin_password']);
        $this->addVersionControlProvider($data['ghe_url'], $data['ghe_token']);
        $this->addEnvironments();

        $isConfigured = (new SystemSetting)
            ->withName(self::SETTING_IS_BOOTSTRAPPED)
            ->withValue('1');

        $this->em->persist($isConfigured);
        $this->em->flush();

        return 'Administrator and GitHub have been configured. Please sign-in with your administrator credentials.';
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    private function validateForm(array $data): bool
    {
        if (!$data['admin_username']) {
            $this->addError('admin_username', 'You must add an administrator user.');
        }

        if (!$data['admin_password']) {
            $this->addError('admin_password', 'Please enter a secure password.');
        }

        if (!$data['ghe_url']) {
            $this->addError('ghe_url', 'Please enter base URL for GitHub.');
        }

        if (!$data['ghe_token']) {
            $this->addError('ghe_token', 'Please enter GitHub Enterprise API token.');
        }

        if ($this->hasErrors()) {
            return false;
        }

        $regex = implode('', [
            '@^',
            'https?\:\/\/',
            '[[:ascii:]]+',
            '$@'
        ]);

        if (!preg_match($regex, $data['ghe_url'], $patterns)) {
            $this->addError('ghe_url', 'Please enter base URL for GitHub.');
        }

        return !$this->hasErrors();
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return void
     */
    private function addIdentityProvider($username, $password)
    {
        $hashed = password_hash($password, \PASSWORD_BCRYPT, [
            'cost' => 10,
        ]);

        $idp = (new UserIdentityProvider)
            ->withType(IdentityProviderEnum::TYPE_INTERNAL)
            ->withName('Internal');

        $adminUser = (new User)
            ->withName($username)
            ->withParameter('internal.password', $hashed)
            ->withProviderUniqueID($username)
            ->withProvider($idp);

        // make user admin
        $permissions = (new UserPermission)
            ->withType(UserPermissionEnum::TYPE_SUPER)
            ->withUser($adminUser);

        $this->em->persist($idp);
        $this->em->persist($adminUser);
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
        $vcs = (new VersionControlProvider)
            ->withType(VCSProviderEnum::TYPE_GITHUB_ENTERPRISE)
            ->withName('GitHub Enterprise')
            ->withParameter('ghe.url', $gheURL)
            ->withParameter('ghe.token', $gheToken);

        $this->em->persist($vcs);
    }

    /**
     * @return void
     */
    private function addEnvironments()
    {
        $staging = (new Environment)
            ->withName('staging');

        $prod = (new Environment)
            ->withName('prod')
            ->withIsProduction(true);

        $this->em->persist($staging);
        $this->em->persist($prod);
    }
}
