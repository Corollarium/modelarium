<?php declare(strict_types=1);

namespace Modelarium\Laravel\Datatypes;

use Formularium\Exception\ValidatorException;
use Formularium\Model;

class Datatype_relationship extends \Modelarium\Datatypes\Datatype_relationship
{
    public static function getNamespace(): string
    {
        return __NAMESPACE__;
    }

    public function getDefault()
    {
        return 0;
    }

    public function getRandom(array $params = [])
    {
        return ${$this->source}::where('active', 1)->inRandomOrder()->limit($params['total'] ?? 1)->get();
    }

    public function validate($value, Model $model = null)
    {
        if (is_array($value)) {
            if (${$this->source}::whereIn('id', $value)->exists()) {
                return $value;
            }
        } else {
            if (${$this->source}::where('id', '=', $value)->exists()) {
                return $value;
            }
        }
        throw new ValidatorException('Invalid relationship value');
    }
}
