<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DummyNameController extends Controller
{
    use FormulariumControllerAPI, FormulariumControllerRoutes, FormulariumControllerDebug;

    public function getPermissions()
    {
        $name = $this->getClassName();
        $can = [
            'store' => Gate::allows('store', $name),
            'index' => Gate::allows('index', $name)
        ];
        return $can;
    }

    protected function getStoreRules(Model $model): array
    {
        return [];
    }

    protected function getShowVisibleAttributes(): array
    {
        return []; // this is everything
    }

    protected function getAppendAttributes(): array
    {
        return [];
    }

}
