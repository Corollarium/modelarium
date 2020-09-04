<?php declare(strict_types=1);

namespace Modelarium\Frontend\Bulma\Renderable;

use Formularium\Field;
use Formularium\Renderable;
use Formularium\Frontend\Bulma\RenderableBulmaTrait;
use Formularium\HTMLNode;

class Renderable_relationship extends Renderable
{
    use RenderableBulmaTrait;
    
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
        // create a div around the old select
        $oldSelect = $previous->get('select')[0];
        $newSelect = clone $oldSelect;
        $oldSelect->setTag('div')->setAttribute('class', 'select')->setContent($newSelect);

        foreach ($previous->getContent() as $e) {
            if ($e->getAttribute('class') === ['formularium-comment']) {
                $e->setTag('p')->setAttributes([
                    'class' => 'help',
                ]);
            }
        }

        return $previous;
    }
}
