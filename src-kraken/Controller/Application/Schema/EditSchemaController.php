<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application\Schema;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Flasher;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Property;
use QL\Kraken\Entity\Schema;
use QL\Kraken\Utility\SortingHelperTrait;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Slim\NotFound;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class EditSchemaController implements ControllerInterface
{
    use SortingHelperTrait;

    const SUCCESS = 'Description for key "%s" updated!';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Schema
     */
    private $schema;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $propertyRepo;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @type NotFound
     */
    private $notFound;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Schema $schema
     *
     * @param EntityManagerInterface$em
     * @param Flasher $flasher
     * @param NotFound $notFound
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Schema $schema,
        EntityManagerInterface $em,
        Flasher $flasher
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->schema = $schema;

        $this->em = $em;
        $this->propertyRepo = $this->em->getRepository(Property::CLASS);

        $this->flasher = $flasher;

        $this->errors = [];
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $application = $this->schema->application();

        $form = [
            'description' => $this->schema->description()
        ];

        if ($this->request->isPost()) {
            $form['description'] = $this->request->post('description');

            if ($schema = $this->handleForm()) {
                $this->flasher
                    ->withFlash(sprintf(self::SUCCESS, $schema->key()), 'success')
                    ->load('kraken.schema', ['application' => $application->id()]);
            }
        }

        $properties = $this->propertyRepo->findBy(['schema' => $this->schema]);
        usort($properties, $this->sorterPropertyByEnvironment());

        $context = [
            'application' => $application,
            'schema' => $this->schema,
            'properties' => $properties,

            'errors' => $this->errors,
            'form' => $form
        ];

        $this->template->render($context);
    }

    /**
     * @return Schema|null
     */
    private function handleForm()
    {
        $description = $this->request->post('description');

        return $this->saveSchema($description);
    }

    /**
     * @param string $description
     *
     * @return Schema
     */
    private function saveSchema($description)
    {
        $this->schema->withDescription($description);

        // persist to database
        $this->em->merge($this->schema);
        $this->em->flush();

        return $this->schema;
    }
}
