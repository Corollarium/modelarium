<?php
/**
 * This file was automatically generated by Modelarium.
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DummyClass extends Model
{
    use FormulariumModel;

    /**
     * The formularium model
     *
     * @var App\Formularium\DummyClass
     */
    protected $formulariumModel;

    protected $fillable = [ 'name' ];

    public function __construct(array $attributes = [])
    {
        $this->initFormularium();
        $attributes = array_merge($this->formulariumModel->getDefault(), $attributes);
        parent::__construct($attributes);
    }

    /**
     * The formularium model
     *
     * @return App\FormulariumLaravel\BaseModel
     */
    public function getFormulariumModel(): App\FormulariumLaravel\BaseModel
    {
        return $this->formulariumModel;
    }

    // dummyMethods
}