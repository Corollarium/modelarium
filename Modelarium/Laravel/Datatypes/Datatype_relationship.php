<?php declare(strict_types=1);

namespace Modelarium\Laravel\Datatypes;

use Formularium\Exception\ValidatorException;
use Formularium\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Datatype_relationship extends \Modelarium\Datatype\Datatype_relationship
{
    public function getDefault()
    {
        return 0;
    }

    public function getRandom(array $params = [])
    {
        $builder = $this->targetClass::select();
        $usingSoftDeletes = in_array(
            SoftDeletes::class,
            /** @phpstan-ignore-next-line */
            array_keys((new \ReflectionClass($this->targetClass))->getTraits())
        );
        if ($usingSoftDeletes) {
            $builder->where('active', 1);
        }
        return $builder->inRandomOrder()->limit($params['total'] ?? 1)->get()->first()->id;
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
}
