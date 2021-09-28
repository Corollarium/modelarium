<?php declare(strict_types=1);

namespace Modelarium\CodeGenerator\GraphQL\DatatypeGenerator;

use Illuminate\Support\Str;
use Modelarium\BaseGenerator;
use Formularium\Model;
use Formularium\Field;
use Formularium\CodeGenerator\CodeGenerator;
use Formularium\CodeGenerator\GraphQL\CodeGenerator as GraphQLCodeGenerator;
use Formularium\CodeGenerator\GraphQL\GraphQLDatatypeGenerator;
use Formularium\Extradata;
use Formularium\ExtradataParameter;
use Modelarium\Datatype\Datatype_relationship;

class DatatypeGenerator_relationship extends GraphQLDatatypeGenerator
{
    /**
     * Key for getGraphqlField() params. If false, do not recurse to relationship fields, only id.
     */
    const RECURSE = 'RECURSE_RELATIONSHIP';

    public function getBasetype(): string
    {
        return 'relationship';
    }

    public function field(CodeGenerator $generator, Field $field)
    {
        return $this->recurse($generator, $field, 'field');
    }

    public function variable(CodeGenerator $generator, Field $field): string
    {
        return $this->recurse($generator, $field, 'variable');
    }

    /**
     * Does a recursive rendering for relationship types.
     *
     * @param CodeGenerator $generator
     * @param Field $field
     * @return mixed
     */
    protected function recurse(CodeGenerator $generator, Field $field, string $method)
    {
        /**
         * @var GraphQLCodeGenerator $generator
         */

        $name = $field->getName();

        /**
         * @var Datatype_relationship
         */
        $datatype = $field->getDatatype();

        $recurseLevel = $field->getExtradataValue(self::RECURSE, 'value', 2);
        if (!$recurseLevel) {
            return '';
        }

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
                        function (Field $f) use ($generator, $method, $recurseLevel) {
                            $type = $f->getDatatype();
                            if ($type instanceof Datatype_relationship && !$type->getIsInverse()) {
                                return '';
                            }

                            $extradata = new Extradata(
                                self::RECURSE,
                                [
                                    new ExtradataParameter('value', $recurseLevel)
                                ]
                            );
                            $f->appendExtradata($extradata);
                            $retval = $generator->$method($f);
                            $f->removeExtraData(self::RECURSE);
                            return $retval;
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
                function (Field $f) use ($generator, $method, $recurseLevel) {
                    $extradata = new Extradata(
                        self::RECURSE,
                        [
                            new ExtradataParameter('value', $recurseLevel-1)
                        ]
                    );

                    $f->appendExtradata($extradata);
                    $retval = \Modelarium\Frontend\Util::fieldShow($f) ? $generator->$method($f) : null;
                    $f->removeExtraData(self::RECURSE);
                    return $retval;
                }
            );
            array_unshift($graphqlQuery, 'id');
        }

        $graphqlQuery = join("\n", array_filter($graphqlQuery));

        return $fieldName . "{\n" . $graphqlQuery . '}';
    }
}
