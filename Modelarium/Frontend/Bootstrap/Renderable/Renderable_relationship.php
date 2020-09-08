<?php declare(strict_types=1);

namespace Modelarium\Frontend\Bootstrap\Renderable;

use Formularium\Field;
use Formularium\Frontend\Bootstrap\RenderableBootstrapWrapperTrait;
use Formularium\Renderable;
use Formularium\HTMLNode;

class Renderable_relationship extends Renderable
{
    use RenderableBootstrapWrapperTrait;
    
    public function viewable($value, Field $field, HTMLNode $previous): HTMLNode
    {
        return $previous;
    }

    /**
     * Subcall of wrapper editable() from RenderableMaterializeTrait
     *
     * @param mixed $value
     * @param Field $field
     * @param HTMLNode $previous
     * @return HTMLNode
     */
    public function editable($value, Field $field, HTMLNode $previous): HTMLNode
    {
        foreach ($previous->get('select') as $input) {
            $input->addAttribute('class', 'custom-select');
        }

        $previous = $this->bootstrapify($value, $field, $previous, 'select');
        $previous = $this->bootstrapify($value, $field, $previous, 'input');
        return $this->wrapper($value, $field, $previous);
    }
}
