<?php declare(strict_types=1);

namespace Modelarium\Frontend;

use Formularium\Model;
use Formularium\FrameworkComposer;
use Formularium\Frontend\Blade\Framework as FrameworkBlade;
use Formularium\Frontend\Vue\Framework as FrameworkVue;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\GeneratorInterface;
use Modelarium\GeneratorNameTrait;

use function Safe\file_get_contents;

class FrontendGenerator implements GeneratorInterface
{
    use GeneratorNameTrait;

    /**
     * @var FrameworkComposer
     */
    protected $composer = null;

    /**
     * @var Model
     */
    protected $model = null;

    /**
     * @var GeneratedCollection
     */
    protected $collection;

    /**
     *
     * @var string
     */
    protected $stubDir = __DIR__ . '/stubs';

    public function __construct(FrameworkComposer $composer, Model $model)
    {
        $this->composer = $composer;
        $this->model = $model;
        $this->setName($model->getName());
    }

    public function generate(): GeneratedCollection
    {
        $this->collection = new GeneratedCollection();

        /**
         * @var FrameworkVue $vue
         */
        $vue = $this->composer->getByName('Vue');
        // $blade = FrameworkComposer::getByName('Blade');

        if ($vue !== null) {
            $this->makeVue($vue, 'Base', 'viewable');
            $this->makeVue($vue, 'Card', 'viewable');
            $this->makeVue($vue, 'List', 'viewable');
            $this->makeVue($vue, 'Show', 'viewable');
            $this->makeVue($vue, 'Form', 'editable');
            $this->makeVueRoutes();
            $this->makeVueIndex();
        }

        $this->makeGraphql();

        return $this->collection;
    }

    protected function makeVue(FrameworkVue $vue, string $component, string $mode): void
    {
        $path = $this->model->getName() . '/' .
            $this->model->getName() . $component . '.vue';
        $stub = file_get_contents($this->stubDir . "/Vue{$component}.stub.vue");
        if ($mode == 'editable') {
            $vue->setEditableTemplate($this->template($stub));
            $this->collection->push(
                new GeneratedItem(
                    GeneratedItem::TYPE_FRONTEND,
                    $this->model->editable($this->composer),
                    $path
                )
            );
        } else {
            $vue->setViewableTemplate($this->template($stub));
            $this->collection->push(
                new GeneratedItem(
                    GeneratedItem::TYPE_FRONTEND,
                    $this->model->viewable($this->composer, []),
                    $path
                )
            );
        }
    }

    protected function makeGraphql(): void
    {
        $listQuery = <<<EOF
query (\$page: Int!) {
    {$this->lowerNamePlural}(page: \$page) {
        data {
            id
            TODO
        }
      
        paginatorInfo {
            currentPage
            perPage
            total
            lastPage
        }
    }
}
EOF;

        $this->collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $listQuery,
                $this->model->getName() . '/queryList.graphql'
            )
        );

        $itemQuery = <<<EOF
query (\$id: ID!) {
    {$this->lowerName}(id: \$id) {
        id
        TODO
    }
}
EOF;

        $this->collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $itemQuery,
                $this->model->getName() . '/queryItem.graphql'
            )
        );

        // TODO: variables
        $createMutation = <<<EOF
mutation create(\$name: String!) {
    {$this->lowerName}Create(name: \$name) {
        TODO
    }
}
EOF;
        $this->collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $createMutation,
                $this->model->getName() . '/mutationCreate.graphql'
            )
        );
    }

    protected function makeVueIndex(): void
    {
        $path = $this->model->getName() . '/index.js';
        $name = $this->studlyName;

        $items = [
            'Card',
            'Form',
            'List',
            'Show',
        ];

        $import = array_map(
            function ($i) use ($name) {
                return "import {$name}$i from './{$name}$i.vue';";
            },
            $items
        );

        $export = array_map(
            function ($i) use ($name) {
                return "    {$name}$i,\n";
            },
            $items
        );

        $this->collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                implode("\n", $import) . "\n" .
                "export {\n" .
                implode("\n", $export) . "\n};\n",
                $path
            )
        );
    }

    protected function makeVueRoutes(): void
    {
        $path = $this->model->getName() . '/routes.js';
        $stub = file_get_contents($this->stubDir . "/routes.stub.js");

        $this->collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $this->template($stub),
                $path
            )
        );
    }
}
