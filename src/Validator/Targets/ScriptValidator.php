<?php
/**
 * @copyright (c) 2017 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\Validator\Targets;

use Hal\Core\Entity\Target;
use Hal\Core\Type\TargetEnum;
use Hal\UI\Validator\ValidatorErrorTrait;
use Hal\UI\Validator\ValidatorTrait;
use Psr\Http\Message\ServerRequestInterface;

class ScriptValidator implements TargetValidatorInterface
{
    use ValidatorErrorTrait;
    use ValidatorTrait;

    /**
     * @inheritDoc
     */
    public function isValid(array $parameters): ?Target
    {
        $this->resetErrors();

        return new Target;
    }

    /**
     * @inheritDoc
     */
    public function isEditValid(Target $target, array $parameters): ?Target
    {
        $this->resetErrors();

        return new Target;
    }

    /**
     * @inheritDoc
     */
    public function getFormData(ServerRequestInterface $request, ?Target $target): array
    {
        $data = $request->getParsedBody();

        $type = TargetEnum::TYPE_SCRIPT;

        return [];
    }
}
