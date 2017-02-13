<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\Admin\Credentials;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\Credential;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

/**
 * @todo add sorting
 */
class CredentialsController implements ControllerInterface
{
    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var EntityRepository
     */
    private $credentialsRepo;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(TemplateInterface $template, EntityManagerInterface $em)
    {
        $this->template = $template;
        $this->credentialsRepo = $em->getRepository(Credential::CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $credentials = $this->credentialsRepo->findBy([], ['name' => 'ASC']);

        $this->template->render([
            'credentials' => $credentials,
        ]);
    }
}
