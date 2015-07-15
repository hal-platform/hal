<?php
/**
 * @copyright ©2014 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Hal\Controllers\Api\Build;

use Doctrine\ORM\EntityManagerInterface;
use Predis\Client as Predis;
use QL\Hal\Core\Entity\Application;
use QL\Hal\Core\Entity\Build;
use QL\Hal\Core\Entity\User;
use QL\Hal\Api\ResponseFormatter;
use QL\Hal\Validator\BuildStartValidator;
use QL\HttpProblem\HttpProblemException;
use QL\Panthor\ControllerInterface;

/**
 * Permission checking is handled by BuildStartValidator
 */
class StartBuildController implements ControllerInterface
{
    const RATE_LIMIT_KEY = 'api:rate-limit:%s.%s';
    const RATE_LIMIT_TIME = 10;

    const ERR_CHECK_FORM = 'Cannot start build due to form submission failure. Please check errors.';
    const ERR_RATE_LIMIT = 'Cannot start build for this application more than once every %d seconds. Please wait a moment.';

    /**
     * @type ResponseFormatter
     */
    private $formatter;

    /**
     * @type BuildStartValidator
     */
    private $validator;

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

        EntityManagerInterface $em,
        Predis $predis,

        Application $application,
        User $currentUser,
        array $requestBody
    ) {
        $this->formatter = $formatter;
        $this->validator = $validator;

        $this->em = $em;
        $this->predis = $predis;

        $this->application = $application;
        $this->currentUser = $currentUser;
        $this->requestBody = $requestBody;
    }

    /**
     * {@inheritdoc}
     * @throws HttpProblemException
     */
    public function __invoke()
    {
        $environment = isset($this->requestBody['environment']) ? $this->requestBody['environment'] : '';
        $reference = isset($this->requestBody['reference']) ? $this->requestBody['reference'] : '';

        $build = $this->validator->isValid($this->application, $environment, $reference, '');

        if (!$build instanceof Build) {
            throw HttpProblemException::build(400, 'invalid-submission', self::ERR_CHECK_FORM, [
                'errors' => $this->validator->errors()
            ]);
        }

        $key = sprintf(self::RATE_LIMIT_KEY, $this->application->id(), $build->environment()->id());
        if ($blocked = $this->predis->get($key)) {
            throw HttpProblemException::build(429, 'rate-limit', sprintf(self::ERR_RATE_LIMIT, self::RATE_LIMIT_TIME));
        }

        // persist to database
        $this->em->persist($build);
        $this->em->flush();

        // Set rate limit
        $this->predis->setex($key, self::RATE_LIMIT_TIME, '1');

        $this->formatter->respond($build, 201);
    }
}
