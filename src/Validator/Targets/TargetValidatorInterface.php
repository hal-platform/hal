<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\Targets;

use Hal\Core\Entity\Target;
use Psr\Http\Message\ServerRequestInterface;

interface TargetValidatorInterface
{
    /**
     * @param array $parameters
     *
     * @return Target|null
     */
    public function isValid(array $parameters): ?Target;

    /**
     * @param Target $target
     * @param array $parameters
     *
     * @return Target|null
     */
    public function isEditValid(Target $target, array $parameters): ?Target;

    /**
     * @param ServerRequestInterface $request
     * @param Target|null $target
     *
     * @return array
     */
    public function getFormData(ServerRequestInterface $request, ?Target $target): array;

    /**
     * @return array
     */
    public function errors(): array;
}
