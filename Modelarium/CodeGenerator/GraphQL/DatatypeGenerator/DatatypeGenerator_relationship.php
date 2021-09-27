<?php declare(strict_types=1);

namespace Modelarium\CodeGenerator\GraphQL\DatatypeGenerator;

use Illuminate\Support\Str;
use Modelarium\BaseGenerator;
use Formularium\Model;
use Formularium\Field;
use Formularium\CodeGenerator\CodeGenerator;
use Formularium\CodeGenerator\GraphQL\CodeGenerator as GraphQLCodeGenerator;
use Formularium\CodeGenerator\GraphQL\GraphQLDatatypeGenerator;
use Modelarium\Datatype\Datatype_relationship;

class DatatypeGenerator_relationship extends GraphQLDatatypeGenerator
{
    /**
     * Key for getGraphqlField() params. If false, do not recurse to relationship fields, only id.
     */
    const RECURSE = 'RECURSE';

    /**
     * Key for getGraphqlField() params
     */
    const RECURSE_INVERSE = 'RECURSE_INVERSE';

    public function getBasetype(): string
    {
        return 'XXXXXXX';
    }

    public function field(CodeGenerator $generator, Field $field)
    {
        /**
         * @var GraphQLCodeGenerator $generator
         */

        $name = $field->getName();

        /**
         * @var Datatype_relationship
         */
        $datatype = $field->getDatatype();

        $params = [];

        // $recurseLevel = $params[self::RECURSE] ?? 1;
        // if (!$recurseLevel) {
        //     return '';
        // }
        // if (!($params[self::RECURSE_INVERSE] ?? true) && $this->isInverse) {
        //     return '';
        // }

        $model = $datatype->getTargetClass();
        if ($datatype->getIsInverse()) {
            $fieldName = Str::snake(Str::studly($name));
        } else {
            $fieldName = BaseGenerator::toTableName($name);
        }

        if ($datatype->isMorph()) {
            $graphqlQuery = ['__typename'];

            /**
             * @var Model $sourceModel
             */
            $sourceModel = call_user_func($this->sourceClass . "::getFormularium"); /** @phpstan-ignore-line */

            $morphableTargets = explode(
                ',',
                $sourceModel->getField($name)->getExtradata('morphTo')->value('targetModels')
            );
            foreach ($morphableTargets as $m) {
                $graphqlQuery[] = "... on $m {";
                $graphqlQuery[] = "id";

                /**
                 * @var \Formularium\Model $formulariumModel
                 */
                $formulariumModel = call_user_func("\\App\\Models\\$m::getFormularium"); /** @phpstan-ignore-line */
                $graphqlQuery = array_merge(
                    $graphqlQuery,
                    $formulariumModel->mapFields(
                        function (Field $f) { // use ($recurseLevel) {
                            $type = $f->getDatatype();
                            if ($type instanceof Datatype_relationship && !$type->getIsInverse()) {
                                return '';
                            }
                            return 'xxx'; // TODO $f->toGraphqlQuery([self::RECURSE => $recurseLevel]); // don't subtract
                        }
                    )
                );
                $graphqlQuery[] = "}";
            }
        } else {
            /**
             * @var \Formularium\Model $formulariumModel
             */
            $formulariumModel = call_user_func("$model::getFormularium"); /** @phpstan-ignore-line */
            $graphqlQuery = $formulariumModel->mapFields(
                function (Field $f) { // use ($recurseLevel) {
                    return \Modelarium\Frontend\Util::fieldShow($f) ? /* $f->toGraphqlQuery([self::RECURSE => $recurseLevel-1]) */ 'zzz' : null;
                }
            );
            array_unshift($graphqlQuery, 'id');
        }

        $graphqlQuery = join("\n", array_filter($graphqlQuery));

        return $fieldName . "{\n" . $graphqlQuery . '}';
    }
}
