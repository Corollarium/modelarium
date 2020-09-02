<?php declare(strict_types=1);

namespace Modelarium\Frontend\HTML\Renderable;

use Formularium\Datatype;
use Formularium\Field;
use Formularium\Frontend\HTML\Framework;
use Formularium\Frontend\HTML\Renderable;
use Formularium\HTMLNode;

class Renderable_relationship extends Renderable
{
    public function viewable($value, Field $field, HTMLNode $previous): HTMLNode
    {
        return $previous;
    }

    public function editable($value, Field $field, HTMLNode $previous): HTMLNode
    {
        // TODO: autocomplete
        return $this->editableSelect($value, $field, $previous);
    }

    /**
     * @param mixed $value
     * @param Field $field
     * @param HTMLNode $previous
     * @return HTMLNode
     */
    public function editableSelect($value, Field $field, HTMLNode $previous): HTMLNode
    {
        $input = new HTMLNode('select');
    
        $renderable = $field->getRenderables();
        $input->setAttributes([
            'id' => $field->getName() . Framework::counter(),
            'name' => $field->getName(),
            'class' => '',
            'data-attribute' => $field->getName(),
            'data-datatype' => $field->getDatatype()->getName(),
            'data-basetype' => $field->getDatatype()->getBasetype(),
            'title' => $field->getRenderable(static::LABEL, ''),
            'autocomplete' => 'off'
        ]);

        if (isset($renderable[static::PLACEHOLDER])) {
            $input->setAttribute('placeholder', $renderable[static::PLACEHOLDER]);
        }
        if ($field->getValidatorOption(Datatype::REQUIRED)) {
            $input->setAttribute('required', 'required');
        }
        /* TODO if ($validators[Datatype_association::MULTIPLE] ?? false) {
            $input->setAttribute('multiple', 'multiple');
        } */
        foreach ([static::DISABLED, static::READONLY] as $v) {
            if ($field->getRenderable($v, false)) {
                $input->setAttribute($v, $v);
            }
        }
    
        return $this->container($input, $field);
    }

    /**
     * @param mixed $value
     * @param Field $field
     * @param HTMLNode $previous
     * @return HTMLNode
     */
    public function editableAutocomplete($value, Field $field, HTMLNode $previous): HTMLNode
    {
        $input = new HTMLNode('input');
    
        $renderable = $field->getRenderables();
        $validators = $field->getValidators();
        $input->setAttributes([
            'id' => $field->getName() . Framework::counter(),
            'name' => $field->getName(),
            'class' => '',
            'data-attribute' => $field->getName(),
            'data-datatype' => $field->getDatatype()->getName(),
            'data-basetype' => $field->getDatatype()->getBasetype(),
            'title' => $field->getRenderable(static::LABEL, ''),
            'autocomplete' => 'off'
        ]);

        if (isset($renderable[static::PLACEHOLDER])) {
            $input->setAttribute('placeholder', $renderable[static::PLACEHOLDER]);
        }
        if ($validators[Datatype::REQUIRED] ?? false) {
            $input->setAttribute('required', 'required');
        }
        foreach ([static::DISABLED, static::READONLY] as $v) {
            if ($field->getRenderable($v, false)) {
                $input->setAttribute($v, $v);
            }
        }
    
        return $this->container($input, $field);
    }
}
