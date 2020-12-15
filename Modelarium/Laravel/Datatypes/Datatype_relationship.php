<?php declare(strict_types=1);

namespace Modelarium\Laravel\Datatypes;

use Formularium\Exception\ValidatorException;
use Formularium\Field;
use Formularium\Model;
use Modelarium\BaseGenerator;

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
        if (!($params[self::RECURSE] ?? true)) {
            return '';
        }
        if (!($params[self::RECURSE_INVERSE] ?? true) && $this->isInverse) {
            return '';
        }
        
        $model = $this->getTargetClass();
        if ($this->isMorph()) {
            $graphqlQuery = ['__typename'];
            $this->morphableTargets = ['Wine', 'Beer']; // TODO
            foreach ($this->morphableTargets as $m) {
                $graphqlQuery[] = "... on $m {";
                $graphqlQuery[] = "id";

                /**
                 * @var \Formularium\Model $formulariumModel
                 */
                $formulariumModel = call_user_func("\\App\\Models\\$m::getFormularium"); /** @phpstan-ignore-line */
                $graphqlQuery = array_merge(
                    $graphqlQuery,
                    $formulariumModel->mapFields(
                        function (Field $f) {
                            return $f->toGraphqlQuery([self::RECURSE => false]);
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
                function (Field $f) {
                    return \Modelarium\Frontend\Util::fieldShow($f) ? $f->toGraphqlQuery([self::RECURSE => false]) : null;
                }
            );
            array_unshift($graphqlQuery, 'id');
        }

        $graphqlQuery = join("\n", array_filter($graphqlQuery));

        return BaseGenerator::toTableName($name) . "{\n" . $graphqlQuery . '}';
    }
}
