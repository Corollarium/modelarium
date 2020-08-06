<?php declare(strict_types=1);

namespace Modelarium\Frontend;

use Formularium\Datatype;
use Formularium\Element;
use Formularium\Field;
use Formularium\Model;
use Formularium\FrameworkComposer;
use Formularium\Frontend\Blade\Framework as FrameworkBlade;
use Formularium\Frontend\Vue\Framework as FrameworkVue;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\GeneratorInterface;
use Modelarium\GeneratorNameTrait;

use function Safe\file_get_contents;
use function Safe\json_encode;

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

    /**
     * String substitution
     *
     * @var string[]
     */
    protected $templateParameters = [];

    /**
     * Fields
     *
     * @var Field[]
     */
    protected $cardFields = [];

    public function __construct(FrameworkComposer $composer, Model $model)
    {
        $this->composer = $composer;
        $this->model = $model;
        $this->setName($model->getName());
        $this->buildTemplateParameters();
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
            $vue->setFieldModelVariable('model.');
            $this->makeJSModel();
            $this->makeVue($vue, 'Card', 'viewable');
            $this->makeVue($vue, 'List', 'viewable');
            $this->makeVue($vue, 'Table', 'viewable');
            $this->makeVue($vue, 'TableItem', 'viewable');
            $this->makeVue($vue, 'Show', 'viewable');
            $this->makeVue($vue, 'Edit', 'editable');
            $this->makeVue($vue, 'Form', 'editable');
            $this->makeVueRoutes();
            $this->makeVueIndex();
        }

        $this->makeGraphql();

        return $this->collection;
    }

    protected function buildTemplateParameters(): void
    {
        $this->cardFields = $this->model->filterField(
            function (Field $field) {
                return $field->getRenderable('card', false);
            }
        );

        $this->templateParameters = [
            'submitButton' => $this->composer->element('Button', [Element::LABEL => 'Submit'])
        ];
    }

    public function templateCallback(string $stub, FrameworkVue $vue, array $data, Model $m): string
    {
        $x = $this->templateFile(
            $stub,
            array_merge(
                $this->templateParameters,
                $data
            )
        );
        return $x;
    }

    protected function makeVue(FrameworkVue $vue, string $component, string $mode): void
    {
        $path = $this->model->getName() . '/' .
            $this->model->getName() . $component . '.vue';

        $stub = $this->stubDir . "/Vue{$component}.mustache.vue";

        if ($mode == 'editable') {
            $vue->setEditableTemplate(
                function (FrameworkVue $vue, array $data, Model $m) use ($stub) {
                    return $this->templateCallback($stub, $vue, $data, $m);
                }
            );

            $this->collection->push(
                new GeneratedItem(
                    GeneratedItem::TYPE_FRONTEND,
                    $this->model->editable($this->composer),
                    $path
                )
            );
        } else {
            $vue->setViewableTemplate(
                function (FrameworkVue $vue, array $data, Model $m) use ($stub) {
                    return $this->templateCallback($stub, $vue, $data, $m);
                }
            );

            $this->collection->push(
                new GeneratedItem(
                    GeneratedItem::TYPE_FRONTEND,
                    $this->model->viewable($this->composer, []),
                    $path
                )
            );
        }
    }

    protected function getFilters(): array
    {
        $filters = [];
        return $filters;
    }

    protected function makeGraphql(): void
    {
        $cardFieldNames = array_map(
            function (Field $field) {
                return $field->getName();
            },
            $this->cardFields
        );
        $cardFieldParameters = implode("\n", $cardFieldNames);

        $listQuery = <<<EOF
query (\$page: Int!) {
    {$this->lowerNamePlural}(page: \$page) {
        data {
            id
            $cardFieldParameters
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
            'Edit',
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

        $this->collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $this->templateFile($this->stubDir . "/routes.mustache.js"),
                $path
            )
        );
    }

    protected function makeJSModel(): void
    {
        $path = $this->model->getName() . '/model.js';
        $modelJS = 'const model = ' . json_encode($this->model->getDefault()) .
            ";\n\nexport default model;\n";
        
        $this->collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $modelJS,
                $path
            )
        );
    }
}
