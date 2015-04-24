<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Application;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use MCP\DataType\GUID;
use QL\Kraken\Entity\Application;
use QL\Kraken\Entity\PropertySchema;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Url;
use QL\Hal\Session;
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
     * @type EntityManager
     */
    private $em;

    /**
     * @type EntityRepository
     */
    private $schemaRepository;

    /**
     * @type Url
     */
    private $url;

    /**
     * @type Session
     */
    private $session;

    /**
     * @type array
     */
    private $errors;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Application $application
     *
     * @param $em
     *
     * @param Url $url
     * @param Session $session
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Application $application,
        $em,
        Url $url,
        Session $session
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->application = $application;

        $this->em = $em;
        $this->schemaRepository = $this->em->getRepository(PropertySchema::CLASS);

        $this->url = $url;
        $this->session = $session;

        $this->errors = [];
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        if ($this->request->isPost()) {
            $this->handleForm();
        }

        $context = [
            'application' => $this->application,
            'property_types' => PropertySchema::$dataTypes,

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
     * @return void
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

        if (!isset(PropertySchema::$dataTypes[$type])) {
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

        $this->saveSchema($key, $type, $description, $isSecure);
    }

    /**
     * @param string $key
     * @param string $type
     * @param string $description
     * @param bool $isSecure
     *
     * @return void
     */
    private function saveSchema($key, $type, $description, $isSecure)
    {
        $uniq = GUID::create()->asHex();
        $uniq = strtolower($uniq);

        $schema = (new PropertySchema)
            ->withId($uniq)
            ->withKey($key)
            ->withDataType($type)
            ->withDescription($description)
            ->withIsSecure($isSecure)
            ->withApplication($this->application);

        // persist to database
        $this->em->persist($schema);
        $this->em->flush();

        // flash and redirect
        $this->session->flash(sprintf(self::SUCCESS, $key), 'success');
        $this->url->redirectFor('kraken.application', ['id' => $this->application->id()]);
    }
}
