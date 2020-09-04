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

        /*
         * init variables
         */
        $inflector = InflectorFactory::create()->build();
        /**
         * @var VueFramework $vue
         */
        $vue = $this->framework;
        $mvar = $vue->getFieldModelVariable();
        /**
         * @var Datatype_relationship $datatype
         */
        $datatype = $field->getDatatype();
        /**
         * @var Formularium\Model $targetModel
         */
        $targetModel = call_user_func($datatype->getTargetClass() . '::getFormularium');
        if (!$targetModel) {
            throw new ClassNotFoundException("Cannot find model " . $datatype->getTarget());
        }

        // get the title field
        $titleField = $targetModel->firstField(
            function (Field $field) {
                return $field->getRenderable('title', false);
            }
        );
        // import graphql query
        $query = 'relationList' . $targetModel->getName() . 'Query';
        $targetStudly = Str::studly($datatype->getTarget());
        $vue->appendImport($query, "raw-loader!../" . $targetStudly . "/queryList.graphql");
        $vue->appendExtraData($query, $query);

        // replace the <select> with our component
        foreach ($previous->get('select') as $input) {
            $classes = $input->getAttribute('class');
            $input->setTag('RelationshipSelect')
                ->setAttributes(
                    [
                        'name' => $field->getName(),
                        'htmlClass' => $classes,
                        'nameField' => ($titleField ? $titleField->getName() : 'id'),
                        ':query' => $query,
                        'targetType' => $datatype->getTarget(),
                        'targetTypePlural' => $inflector->pluralize(mb_strtolower($datatype->getTarget())),
                        'v-model' => $mvar . $field->getName()
                    ]
                );
        }

        return $previous;
    }
}
