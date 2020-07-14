<?php declare(strict_types=1);

namespace Modelarium\Frontend\Vue\Renderable;

use Formularium\Field;
use Formularium\Frontend\Vue\RenderableVueTrait;
use Formularium\HTMLElement;

class Renderable_relationshipSelect extends Renderable_relationship
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
     * @param HTMLElement $previous
     * @return HTMLElement
     */
    public function viewable($value, Field $field, HTMLElement $previous): HTMLElement
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
     * @param HTMLElement $previous
     * @return HTMLElement
     */
    public function editable($value, Field $field, HTMLElement $previous): HTMLElement
    {
        $previous = $this->_editable($value, $field, $previous);
        // TODO: replace with <RelationshipSelect>, generate SFC
        return $previous;
    }
}
