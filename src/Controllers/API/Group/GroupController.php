<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Controllers\API\Group;

use Hal\Core\Entity\Group;
use Hal\UI\API\Normalizer\GroupNormalizer;
use Hal\UI\API\ResponseFormatter;
use Hal\UI\Controllers\APITrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use QL\Panthor\ControllerInterface;

class GroupController implements ControllerInterface
{
    use APITrait;

    /**
     * @var ResponseFormatter
     */
    private $formatter;

    /**
     * @var GroupNormalizer
     */
    private $normalizer;

    /**
     * @param ResponseFormatter $formatter
     * @param GroupNormalizer $normalizer
     */
    public function __construct(ResponseFormatter $formatter, GroupNormalizer $normalizer)
    {
        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $group = $request->getAttribute(Group::class);

        $resource = $this->normalizer->resource($group, ['environment']);
        $body = $this->formatter->buildHypermediaResponse($request, $resource);

        return $this->withHypermediaEndpoint($request, $response, $body);
    }
}
