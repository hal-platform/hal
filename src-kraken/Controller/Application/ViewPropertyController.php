<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Property;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Slim\NotFound;

class ViewPropertyController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type EntityManager
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $propRepository;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $parameters;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     *
     * @param $em
     *
     * @param NotFound $notFound
     * @param array $parameters
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        $em,
        NotFound $notFound,
        array $parameters
    ) {
        $this->template = $template;
        $this->application = $application;

        $this->em = $em;
        $this->propRepository = $this->em->getRepository(Property::CLASS);

        $this->notFound = $notFound;
        $this->parameters = $parameters;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $property = $this->propRepository->findOneBy([
            'id' => $this->parameters['property'],
            'application' => $this->application,
        ]);

        if (!$property) {
            return call_user_func($this->notFound);
        }

        $context = [
            'application' => $this->application,
            'environment' => $property->environment(),
            'property' => $property
        ];

        $this->template->render($context);
    }
}
