<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller;

use Doctrine\ORM\EntityManagerInterface;
use QL\Kraken\Core\Entity\Environment;
use QL\Kraken\Core\Utility\SortingTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;

class EnvironmentsController implements ControllerInterface
{
    use SortingTrait;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityRepository
     */
    private $repository;

    /**
     * @param TemplateInterface $template
     * @param EntityManagerInterface $em
     */
    public function __construct(
        TemplateInterface $template,
        EntityManagerInterface $em
    ) {
        $this->template = $template;
        $this->repository = $em->getRepository(Environment::CLASS);
    }

    /**
     * @return null
     */
    public function __invoke()
    {
        $environments = $this->repository->findAll();
        usort($environments, $this->environmentSorter());

        $context = [
            'environments' => $environments
        ];

        $this->template->render($context);
    }
}
