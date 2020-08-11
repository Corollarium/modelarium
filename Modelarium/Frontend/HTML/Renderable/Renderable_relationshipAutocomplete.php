<?php declare(strict_types=1);

namespace Modelarium\Frontend\HTML\Renderable;

use Formularium\Datatype;
use Formularium\Field;
use Formularium\Frontend\HTML\Framework;
use Formularium\HTMLNode;

class Renderable_relationshipAutocomplete extends Renderable_relationship
{
    public function viewable($value, Field $field, HTMLNode $previous): HTMLNode
    {
        return $previous;
    }

    public function editable($value, Field $field, HTMLNode $previous): HTMLNode
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
