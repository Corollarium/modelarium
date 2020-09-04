<?php declare(strict_types=1);

namespace Modelarium\Frontend\Bootstrap\Renderable;

use Formularium\Field;
use Formularium\Renderable;
use Formularium\Frontend\Bootstrap\RenderableBootstrapTrait;
use Formularium\HTMLNode;

class Renderable_relationship extends Renderable
{
    use RenderableBootstrapTrait;
    
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
    public function _editable($value, Field $field, HTMLNode $previous): HTMLNode
    {
        foreach ($previous->get('select') as $input) {
            $input->addAttribute('class', 'custom-select');
        }

        return $previous;
    }
}
