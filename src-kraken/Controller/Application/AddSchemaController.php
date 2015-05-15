<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\GUID;
use QL\Hal\Core\Entity\User;
use QL\Hal\FlashFire;
use QL\Kraken\Doctrine\PropertyEnumType;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\Schema;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use Slim\Http\Request;

class AddSchemaController implements ControllerInterface
{
    const SUCCESS = 'Property "%s" added.';
    const ERR_INVALID_KEY = 'Property Keys must be alphanumeric.';
    const ERR_MISSING_KEY = 'You must enter a property key';
    const ERR_INVALID_TYPE = 'Please select a type for this property.';
    const ERR_DUPLICATE = 'This property key already exists.';

    const VALIDATE_KEY_REGEX = '/^[a-zA-Z0-9\_\.]{1,250}$/';

    /**
     * @type Request
     */
    private $request;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $schemaRepository;

    /**
     * @type FlashFire
     */
    private $flashFire;

    /**
     * @type callable
     */
    private $random;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Application $application
     * @param User $currentUser
     *
     * @param EntityManagerInterface$em
     * @param FlashFire $flashFire
     * @param callable $random
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Application $application,
        User $currentUser,
        EntityManagerInterface $em,
        FlashFire $flashFire,
        callable $random
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->application = $application;
        $this->currentUser = $currentUser;

        $this->em = $em;
        $this->schemaRepository = $this->em->getRepository(Schema::CLASS);

        $this->flashFire = $flashFire;
        $this->random = $random;

        $this->errors = [];
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        if ($this->request->isPost()) {
            if ($schema = $this->handleForm()) {
                $this->flashFire->fire(sprintf(self::SUCCESS, $schema->key()), 'kraken.applications', 'success', ['id' => $this->application->id()]);
            }
        }

        $context = [
            'application' => $this->application,
            'property_types' => PropertyEnumType::map(),

            'errors' => $this->errors,
            'form' => [
                'key' => $this->request->post('key'),
                'type' => $this->request->post('type'),
                'secure' => $this->request->post('secure'),
                'description' => $this->request->post('description')
            ]
        ];

        $this->template->render($context);
    }

    /**
     * @return Schema|null
     */
    private function handleForm()
    {
        $key = $this->request->post('key');
        $type = $this->request->post('type');
        $description = $this->request->post('description');
        $isSecure = ($this->request->post('isSecure') === '1');

        if (strlen($key) === 0) {
            $this->errors[] = self::ERR_MISSING_KEY;

        } elseif (preg_match(self::VALIDATE_KEY_REGEX, $key) !== 1) {
            $this->errors[] = self::ERR_INVALID_KEY;

        } elseif (substr($key, 0, 1) === '.' || substr($key, -1) === '.') {
            $this->errors[] = self::ERR_INVALID_KEY;
        }

        $map = PropertyEnumType::map();
        if (!isset($map[$type])) {
            $this->errors[] = self::ERR_INVALID_TYPE;
        }

        // dupe check
        if (!$this->errors) {
            $dupe = $this->schemaRepository->findOneBy([
                'application' => $this->application,
                'key' => $key
            ]);

            if ($dupe) {
                $this->errors[] = self::ERR_DUPLICATE;
            }
        }

        if ($this->errors) {
            return null;
        }

        return $this->saveSchema($key, $type, $description, $isSecure);
    }

    /**
     * @param string $key
     * @param string $type
     * @param string $description
     * @param bool $isSecure
     *
     * @return Schema
     */
    private function saveSchema($key, $type, $description, $isSecure)
    {
        $id = call_user_func($this->random);

        $schema = (new Schema)
            ->withId($id)
            ->withKey($key)
            ->withDataType($type)
            ->withDescription($description)
            ->withIsSecure($isSecure)
            ->withApplication($this->application)
            ->withUser($this->currentUser);

        // persist to database
        $this->em->persist($schema);
        $this->em->flush();

        return $schema;
    }
}