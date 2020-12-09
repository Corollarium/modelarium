<?php declare(strict_types=1);

namespace Modelarium\Frontend\Vue\Renderable;

use Doctrine\Inflector\InflectorFactory;
use Illuminate\Support\Str;
use Formularium\Exception\ClassNotFoundException;
use Formularium\Field;
use Formularium\Renderable;
use Formularium\HTMLNode;
use Formularium\Frontend\Vue\RenderableVueTrait;
use Formularium\Frontend\Vue\Framework as VueFramework;
use Modelarium\Datatypes\Datatype_relationship;
use Modelarium\Datatypes\RelationshipFactory;

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
        /**
         * @var VueFramework $vue
         */
        $vue = $this->framework;
        /**
         * @var Datatype_relationship $datatype
         */
        $datatype = $field->getDatatype();

        $relationship = $datatype->getRelationship();
        if ($relationship === RelationshipFactory::RELATIONSHIP_ONE_TO_ONE ||
            (
                $relationship === RelationshipFactory::RELATIONSHIP_ONE_TO_MANY && !$datatype->getIsInverse()
            )
        ) {
            $isMultiple = true;
        } else {
            $isMultiple = false; // TODO
        }

        if ($isMultiple) {
            $previous = new HtmlNode(
                'div' // TODO: list?
            );
            $p = new HtmlNode(
                $datatype->getTarget() . 'Card',
                [
                    'v-for' => 'item in ' . $vue->getVueCode()->getFieldModelVariable() . $datatype->getTargetPlural(),
                    'v-bind' => 'item', // TODO: check
                    ':key' => 'item.id' // TODO: check
                ]
            );
            $previous->appendContent($p);
        } else {
            $previous = new HtmlNode(
                $datatype->getTarget() . 'Card',
                [
                    'v-bind' => $vue->getVueCode()->getFieldModelVariable() . $datatype->getTarget()
                ]
            );
        }
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

        /*
         * init variables
         */
        $inflector = InflectorFactory::create()->build();
        /**
         * @var VueFramework $vue
         */
        $vue = $this->framework;
        $mvar = $vue->getVueCode()->getFieldModelVariable();
        /**
         * @var Datatype_relationship $datatype
         */
        $datatype = $field->getDatatype();
        // @phpstan-ignore-next-line
        $targetModel = call_user_func($datatype->getTargetClass() . '::getFormularium');
        if ($targetModel === false) {
            throw new ClassNotFoundException("Cannot find model " . $datatype->getTarget());
        }
        /**
         * @var \Formularium\Model $targetModel
         */

        // get the title field
        $titleField = $targetModel->firstField(
            function (Field $field) {
                return $field->getRenderable('title', false);
            }
        );
        // import graphql query
        $query = 'relationList' . $targetModel->getName() . 'Query';
        $targetStudly = Str::studly($datatype->getTarget());
        $vue->getVueCode()->appendImport($query, "raw-loader!../" . $targetStudly . "/queryList.graphql");
        $vue->getVueCode()->appendExtraData($query, $query);
        
        $relationship = $datatype->getRelationship();
        if ($relationship === RelationshipFactory::RELATIONSHIP_MANY_TO_MANY ||
            $relationship === RelationshipFactory::MORPH_MANY_TO_MANY
            // TODO: inverses 1:n?
        ) {
            $component = 'RelationshipMultiple';
        } elseif ($field->getRenderable('relationshipSelect', false)) { // TODO: document
            $component = 'RelationshipSelect';
        } else {
            $component = 'RelationshipAutocomplete';
        }

        // replace the <select> with our component
        foreach (array_merge($previous->get('select'), $previous->get('input')) as $input) {
            $classes = $input->getAttribute('class');
            $input->setTag($component)
                ->setAttributes(
                    [
                        'name' => $field->getName(),
                        'htmlClass' => $classes,
                        'titleField' => ($titleField ? $titleField->getName() : 'id'),
                        ':query' => $query,
                        'targetType' => $datatype->getTarget(),
                        'targetTypePlural' => $datatype->getTargetPlural(),
                        'v-model' => $mvar . $field->getName()
                    ]
                );
        }

        return $previous;
    }
}
