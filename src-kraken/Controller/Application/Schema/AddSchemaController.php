<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Application\Schema;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use QL\Hal\Core\Entity\User;
use QL\Hal\Flasher;
use QL\Kraken\Core\Entity\Application;
use QL\Kraken\Core\Entity\Schema;
use QL\Kraken\Core\Type\EnumType\PropertyEnum;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use Slim\Http\Request;

class AddSchemaController implements ControllerInterface
{
    const SUCCESS = 'Property "%s" added.';
    const ERR_INVALID_KEY = 'Property Keys must be alphanumeric, optionally with separators (._-). Please use less than 150 characters.';
    const ERR_MISSING_KEY = 'You must enter a property key';
    const ERR_INVALID_TYPE = 'Please select a type for this property.';
    const ERR_DUPLICATE = 'This property key already exists.';

    const VALIDATE_KEY_REGEX = '/^[a-zA-Z0-9\_\.\-]{1,150}$/';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var User
     */
    private $currentUser;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository
     */
    private $schemaRepo;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var callable
     */
    private $random;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Application $application
     * @param User $currentUser
     *
     * @param EntityManagerInterface$em
     * @param Flasher $flasher
     * @param callable $random
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Application $application,
        User $currentUser,
        EntityManagerInterface $em,
        Flasher $flasher,
        callable $random
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->application = $application;
        $this->currentUser = $currentUser;

        $this->em = $em;
        $this->schemaRepo = $this->em->getRepository(Schema::CLASS);

        $this->flasher = $flasher;
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
                return $this->flasher
                    ->withFlash(sprintf(self::SUCCESS, $schema->key()), 'success')
                    ->load('kraken.schema', ['application' => $this->application->id()]);
            }
        }

        $context = [
            'application' => $this->application,
            'property_types' => PropertyEnum::map(),

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
        $isSecure = ($this->request->post('secure') === '1');

        if (strlen($key) === 0) {
            $this->errors[] = self::ERR_MISSING_KEY;

        } elseif (preg_match(self::VALIDATE_KEY_REGEX, $key) !== 1) {
            $this->errors[] = self::ERR_INVALID_KEY;

        } elseif (substr($key, 0, 1) === '.' || substr($key, -1) === '.') {
            $this->errors[] = self::ERR_INVALID_KEY;
        }

        $map = PropertyEnum::map();
        if (!isset($map[$type])) {
            $this->errors[] = self::ERR_INVALID_TYPE;
        }

        // dupe check
        if (!$this->errors) {
            $dupe = $this->schemaRepo->findOneBy([
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
