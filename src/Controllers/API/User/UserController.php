<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\User;

use Hal\Core\Entity\User;
use Hal\UI\API\Normalizer\UserNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;

class UserController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var UserNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param UserNormalizer $normalizer
     */
    public function __construct(ResponseFormatter $formatter, UserNormalizer $normalizer)
    {
        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $user = $request->getAttribute(User::class);

        $resource = $this->normalizer->resource($user);
        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body, 200);
    }
}
