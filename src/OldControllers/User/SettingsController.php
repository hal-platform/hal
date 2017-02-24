<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\User;

use QL\Hal\Core\Entity\User;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class SettingsController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     * @param User $currentUser
     */
    public function __construct(
        TemplateInterface $template,
        User $currentUser
    ) {
        $this->template = $template;
        $this->currentUser = $currentUser;
    }

    /**
     * @inheritDoc
     */
    public function __invoke()
    {
        $this->template->render([
            'user' => $this->currentUser,
            'tokens' => $this->currentUser->tokens()->toArray(),
            'hasGithubToken' => (strlen($this->currentUser->githubToken()) > 0)
        ]);
    }
}