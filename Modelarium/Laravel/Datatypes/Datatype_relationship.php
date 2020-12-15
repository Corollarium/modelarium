<?php declare(strict_types=1);

namespace Modelarium\Laravel\Datatypes;

use Formularium\Exception\ValidatorException;
use Formularium\Field;
use Formularium\Model;
use Illuminate\Support\Str;
use Modelarium\BaseGenerator;
use Modelarium\Datatypes\Datatype_relationship as DatatypesDatatype_relationship;

class Datatype_relationship extends \Modelarium\Datatypes\Datatype_relationship
{
    public function getDefault()
    {
        return 0;
    }

    public function getRandom(array $params = [])
    {
        return $this->targetClass::where('active', 1)->inRandomOrder()->limit($params['total'] ?? 1)->get();
    }

    public function validate($value, Model $model = null)
    {
        if (is_array($value)) {
            if ($this->targetClass::whereIn('id', $value)->exists()) {
                return $value;
            }
        } else {
            if ($this->targetClass::where('id', '=', $value)->exists()) {
                return $value;
            }
        }
        throw new ValidatorException('Invalid relationship value');
    }

    /**
     * Returns the Graphql query for this datatype.
     *
     * @param string $name The field name
     * @param array $params User supplied list of parameters, which may be used
     * to control behavior (like recursion)
     * @return string
     */
    public function getGraphqlField(string $name, array $params = []): string
    {
        $recurseLevel = $params[self::RECURSE] ?? 1;
        if (!$recurseLevel) {
            return '';
        }
        if (!($params[self::RECURSE_INVERSE] ?? true) && $this->isInverse) {
            return '';
        }
        
        $model = $this->getTargetClass();
        if ($this->isInverse) {
            $fieldName = Str::snake(Str::studly($name));
        } else {
            $fieldName = BaseGenerator::toTableName($name);
        }

        if ($this->isMorph()) {
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
                        function (Field $f) use ($recurseLevel) {
                            $type = $f->getDatatype();
                            if ($type instanceof Datatype_relationship && !$type->getIsInverse()) {
                                return '';
                            }
                            return $f->toGraphqlQuery([self::RECURSE => $recurseLevel]); // don't subtract
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
                function (Field $f) use ($recurseLevel) {
                    return \Modelarium\Frontend\Util::fieldShow($f) ? $f->toGraphqlQuery([self::RECURSE => $recurseLevel-1]) : null;
                }
            );
            array_unshift($graphqlQuery, 'id');
        }

        $graphqlQuery = join("\n", array_filter($graphqlQuery));

        return $fieldName . "{\n" . $graphqlQuery . '}';
    }
}
