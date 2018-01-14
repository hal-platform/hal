<?php
/**
 * @copyright (c) 2018 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\VersionControl;

use Hal\Core\Entity\System\VersionControlProvider;
use Psr\Http\Message\ServerRequestInterface;

interface VersionControlValidatorInterface
{
    /**
     * @param array $parameters
     *
     * @return VersionControlProvider|null
     */
    public function isValid(array $parameters): ?VersionControlProvider;

    /**
     * @param VersionControlProvider $provider
     * @param array $parameters
     *
     * @return VersionControlProvider|null
     */
    public function isEditValid(VersionControlProvider $provider, array $parameters): ?VersionControlProvider;

    /**
     * @param ServerRequestInterface $request
     * @param VersionControlProvider|null $provider
     *
     * @return array
     */
    public function getFormData(ServerRequestInterface $request, ?VersionControlProvider $provider): array;

    /**
     * @return array
     */
    public function errors(): array;
}
