<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Schema;

use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Property;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Slim\NotFound;

class PropertyController implements ControllerInterface
{
    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type Property
     */
    private $property;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @param TemplateInterface $template
     * @param Application $application
     * @param Property $property
     *
     * @param NotFound $notFound
     */
    public function __construct(
        TemplateInterface $template,
        Application $application,
        Property $property,
        NotFound $notFound
    ) {
        $this->template = $template;
        $this->application = $application;
        $this->property = $property;

        $this->notFound = $notFound;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        if ($this->property->application() !== $this->application) {
            return call_user_func($this->notFound);
        }

        $context = [
            'application' => $this->application,
            'environment' => $this->property->environment(),
            'property' => $this->property
        ];

        $this->template->render($context);
    }
}
