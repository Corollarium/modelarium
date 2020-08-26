<?php declare(strict_types=1);

namespace Modelarium\Laravel\Datatypes;

use Formularium\Exception\ValidatorException;
use Formularium\Model;

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
     * @return string
     */
    public function getGraphqlField(string $name): string
    {
        if ($this->isInverse) {
            return '';
        }
        $model = new $this->targetClass();
        /**
         * @var \Formularium\Model $formulariumModel
         */
        $formulariumModel = $model->getFormularium();
        return $name . "{\nid\n" . $formulariumModel->toGraphqlQuery() . '}';
    }
}
