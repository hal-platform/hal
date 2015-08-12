<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Flasher;
use QL\Kraken\ACL;
use QL\Kraken\Core\Entity\Target;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditTargetController implements ControllerInterface
{
    const SUCCESS = 'Encryption key for "%s" updated.';
    const ERR_INVALID_KEY = 'Invalid Key. Encryption Keys must be alphanumeric.';

    const VALIDATE_KEY_REGEX = '/^[a-zA-Z0-9]{2,100}$/';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type Target
     */
    private $target;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type ACL
     */
    private $acl;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Target $target
     * @param Flasher $flasher
     * @param EntityManagerInterface $em
     * @param ACL $acl
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Target $target,
        Flasher $flasher,
        EntityManagerInterface $em,
        ACL $acl
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->target = $target;
        $this->flasher = $flasher;

        $this->em = $em;
        $this->acl = $acl;

        $this->errors = [];
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        if ($this->target->environment()->isProduction()) {
            $this->acl->requireAdmin();
        }

        if ($this->request->isPost()) {
            $form = [
                'key' => $this->request->post('key'),
            ];
        } else {
            $form = [
                'key' => $this->target->key(),
            ];
        }

        if ($this->request->isPost()) {
            if ($target = $this->handleForm($form)) {
                // flash and redirect
                $this->flasher
                    ->withFlash(sprintf(self::SUCCESS, $target->environment()->name()), 'success')
                    ->load('kraken.application', ['application' => $target->application()->id()]);
            }
        }

        $context = [
            'application' => $this->target->application(),
            'environment' => $this->target->environment(),
            'errors' => $this->errors,
            'form' => $form
        ];

        $this->template->render($context);
    }

    /**
     * @param array $data
     *
     * @return Target|null
     */
    private function handleForm(array $data)
    {
        $key = $data['key'];

        if (preg_match(self::VALIDATE_KEY_REGEX, $key) !== 1) {
            $this->errors[] = self::ERR_INVALID_KEY;
        }

        if ($this->errors) {
            return null;
        }

        $this->target
            ->withKey($key);

        $this->em->merge($this->target);
        $this->em->flush();

        return $this->target;
    }
}
