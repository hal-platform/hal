<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Flasher;
use QL\Kraken\ACL;
use QL\Kraken\Core\Entity\Property;
use QL\Kraken\Validator\PropertyValidator;
use QL\Panthor\ControllerInterface;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Utility\Json;
use Slim\Http\Request;

class EditPropertyController implements ControllerInterface
{
    const SUCCESS = 'Property "%s" updated.';
    const ERR_DECODING = 'Decoding failure. The property "%s" is invalid.';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TemplateInterface
     */
    private $template;

    /**
     * @var Property
     */
    private $property;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var PropertyValidator
     */
    private $validator;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Flasher
     */
    private $flasher;

    /**
     * @var ACL
     */
    private $acl;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Property $property
     *
     * @param EntityManagerInterface $em
     * @param PropertyValidator $validator
     * @param Json $json
     * @param Flasher $flasher
     * @param ACL $acl
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Property $property,

        EntityManagerInterface $em,
        PropertyValidator $validator,
        Json $json,
        Flasher $flasher,
        ACL $acl
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->property = $property;

        $this->em = $em;
        $this->validator = $validator;
        $this->json = $json;
        $this->flasher = $flasher;
        $this->acl = $acl;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        $this->acl->requireDeployPermissions($this->property->application(), $this->property->environment());

        if ($property = $this->handleForm()) {
            // flash and redirect
            $this->flasher
                ->withFlash(sprintf(self::SUCCESS, $property->schema()->key()), 'success')
                ->load('kraken.property', [
                    'property' => $this->property->id()
                ]);
        }

        $context = [
            'application' => $this->property->application(),
            'environment' => $this->property->environment(),
            'property' => $this->property,

            'errors' => $this->validator->errors(),
            'form' => $this->getCurrent()
        ];

        $this->template->render($context);
    }

    /**
     * @return Property|null
     */
    private function handleForm()
    {
        if (!$this->request->isPost()) {
            return null;
        }

        $value = $this->validator->resolvePropertyValueFromRequest($this->request, $this->property->schema());

        if ($property = $this->validator->isEditValid($this->property, $value)) {

            // persist to database
            $this->em->merge($property);
            $this->em->flush();

            return $property;
        }

        return null;
    }

    /**
     * Get current form data (from POST or db)
     *
     * @throws Stop Exception
     *
     * @return array
     */
    private function getCurrent()
    {
        $schema = $this->property->schema();
        $formField = sprintf('value_%s', $schema->dataType());

        // Get from post, if available
        if ($this->request->isPost()) {
            $value = $this->validator->resolvePropertyValueFromRequest($this->request, $schema);
            return $this->formize($formField, $value);
        }

        // Get from db

        // secure values are not decrypted
        if ($schema->isSecure()) {
            return $this->formize($formField, '');
        }

        $field = $this->json->decode($this->property->value());
        if ($field === null) {
            return $this->flasher
                ->withFlash(self::ERR_DECODING, 'error')
                ->load('kraken.property', ['property' => $this->property->id()]);
        }

        if (is_bool($field)) {
            $field = ($field) ? 'true' : 'false';
        }

        return $this->formize($formField, $field);
    }

    /**
     * @param string $explicitField
     * @param string|string[] $value
     *
     * @return array
     */
    private function formize($explicitField, $value)
    {
        if (is_array($value)) {
            $original = reset($value);
        } else {
            $original = $value;
        }

        return [
            'value' => $original,
            $explicitField => $value
        ];
    }
}
