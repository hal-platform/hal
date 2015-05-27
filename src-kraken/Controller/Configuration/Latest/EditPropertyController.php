<?php
/**
 * @copyright ©2015 Quicken Loans Inc. All rights reserved. Trade Secret,
 *    Confidential and Proprietary. Any dissemination outside of Quicken Loans
 *    is strictly prohibited.
 */

namespace QL\Kraken\Controller\Configuration\Latest;

use Doctrine\ORM\EntityManagerInterface;
use QL\Hal\Flasher;
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
     * @type Request
     */
    private $request;

    /**
     * @type TemplateInterface
     */
    private $template;

    /**
     * @type Property
     */
    private $property;

    /**
     * @type EntityManagerInterface
     */
    private $em;

    /**
     * @type PropertyValidator
     */
    private $validator;

    /**
     * @type Json
     */
    private $json;

    /**
     * @type Flasher
     */
    private $flasher;

    /**
     * @param Request $request
     * @param TemplateInterface $template
     * @param Property $property
     *
     * @param EntityManagerInterface $em
     * @param PropertyValidator $validator
     * @param Json $json
     * @param Flasher $flasher
     */
    public function __construct(
        Request $request,
        TemplateInterface $template,
        Property $property,

        EntityManagerInterface $em,
        PropertyValidator $validator,
        Json $json,
        Flasher $flasher
    ) {
        $this->request = $request;
        $this->template = $template;
        $this->property = $property;

        $this->em = $em;
        $this->validator = $validator;
        $this->json = $json;
        $this->flasher = $flasher;
    }

    /**
     * @return void
     */
    public function __invoke()
    {
        if ($this->request->isPost()) {
            if ($property = $this->handleForm()) {
                // flash and redirect
                $this->flasher
                    ->withFlash(sprintf(self::SUCCESS, $property->schema()->key()), 'success')
                    ->load('kraken.property', [
                        'property' => $this->property->id()
                    ]);
            }
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
