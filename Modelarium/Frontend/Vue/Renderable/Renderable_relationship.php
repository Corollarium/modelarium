<?php declare(strict_types=1);

namespace Modelarium\Frontend\Vue\Renderable;

use Formularium\Datatype;
use Formularium\Field;
use Formularium\Renderable;
use Formularium\HTMLNode;
use Formularium\Frontend\Vue\RenderableVueTrait;

class Renderable_relationship extends Renderable
{
    use RenderableVueTrait {
        RenderableVueTrait::viewable as _viewable;
        RenderableVueTrait::editable as _editable;
    }

    /**
     * Subcall of wrapper editable()
     *
     * @param mixed $value
     * @param Field $field
     * @param HTMLNode $previous
     * @return HTMLNode
     */
    public function viewable($value, Field $field, HTMLNode $previous): HTMLNode
    {
        $previous = $this->_viewable($value, $field, $previous);
        // TODO: replace with <Card></Card>, props
        return $previous;
    }

    /**
     * Subcall of wrapper editable()
     *
     * @param mixed $value
     * @param Field $field
     * @param HTMLNode $previous
     * @return HTMLNode
     */
    public function editable($value, Field $field, HTMLNode $previous): HTMLNode
    {
        $previous = $this->_editable($value, $field, $previous);
        // TODO: replace with <RelationshipSelect>, generate SFC
        return $previous;
    }
}
