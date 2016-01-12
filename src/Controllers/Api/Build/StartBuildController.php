<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Hal\Controllers\Api\Build;

use Doctrine\ORM\EntityManagerInterface;
use Predis\Client as Predis;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\User;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Validator\BuildStartValidator;
use QL\Hal\Validator\PushStartValidator;
use QL\Panthor\ControllerInterface;
use QL\Panthor\Exception\HTTPProblemException;

/**
 * Permission checking is handled by BuildStartValidator
 */
class StartBuildController implements ControllerInterface
{
    const RATE_LIMIT_KEY = 'api:rate-limit:%s.%s';
    const RATE_LIMIT_TIME = 10;

    const ERR_CHECK_FORM = 'Cannot start build due to form submission failure. Please check errors.';
    const ERR_RATE_LIMIT = 'Cannot start build for this application more than once every %d seconds. Please wait a moment.';
    const ERR_INVALID_DEPLOY = 'Cannot create child processes for selected deployments.';

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type BuildStartValidator
     */
    private $validator;

    /**
     * @type PushStartValidator
     */
    private $pushValidator;

    /**
     * @type Predis
     */
    private $predis;

    /**
     * @type Application
     */
    private $application;

    /**
     * @type User
     */
    private $currentUser;

    /**
     * @type array
     */
    private $requestBody;

    /**
     * @param ResponseFormatter $formatter
     * @param BuildStartValidator $validator
     * @param PushStartValidator $pushValidator
     *
     * @param EntityManagerInterface $em
     * @param Predis $predis
     *
     * @param Application $application
     * @param User $currentUser
     * @param array $requestBody
     */
    public function __construct(
        ResponseFormatter $formatter,
        BuildStartValidator $validator,
        PushStartValidator $pushValidator,

        EntityManagerInterface $em,
        Predis $predis,

        Application $application,
        User $currentUser,
        array $requestBody
    ) {
        $this->formatter = $formatter;
        $this->validator = $validator;
        $this->pushValidator = $pushValidator;

        $this->em = $em;
        $this->predis = $predis;

        $this->application = $application;
        $this->currentUser = $currentUser;
        $this->requestBody = $requestBody;
    }

    /**
     * {@inheritdoc}
     * @throws HTTPProblemException
     */
    public function __invoke()
    {
        $environment = isset($this->requestBody['environment']) ? $this->requestBody['environment'] : '';
        $reference = isset($this->requestBody['reference']) ? $this->requestBody['reference'] : '';

        $build = $this->validator->isValid($this->application, $environment, $reference, '');

        if (!$build) {
            throw new HTTPProblemException(400, self::ERR_CHECK_FORM, [
                'errors' => $this->validator->errors()
            ]);
        }

        $key = sprintf(self::RATE_LIMIT_KEY, $this->application->id(), $build->environment()->id());
        if ($blocked = $this->predis->get($key)) {
            throw new HTTPProblemException(429, sprintf(self::ERR_RATE_LIMIT, self::RATE_LIMIT_TIME));
        }

        $children = null;
        $deployments = isset($this->requestBody['deployments']) ? $this->requestBody['deployments'] : [];
        if ($deployments && is_array($deployments)) {
            $children = $this->pushValidator->isProcessValid($build->application(), $build->environment(), $build, $deployments);
            if (!$children) {
                throw new HTTPProblemException(400, self::ERR_INVALID_DEPLOY);
            }
        }

        // persist to database
        if ($children) {
            foreach ($children as $process) {
                $this->em->persist($process);
            }
        }

        $this->em->persist($build);
        $this->em->flush();

        // Set rate limit
        $this->predis->setex($key, self::RATE_LIMIT_TIME, '1');

        $this->formatter->respond($build, 201);
    }
}
