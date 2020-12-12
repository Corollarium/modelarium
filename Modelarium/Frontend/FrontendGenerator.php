<?php declare(strict_types=1);

namespace Modelarium\Frontend;

use Formularium\Datatype;
use Formularium\Element;
use Formularium\Field;
use Formularium\Model;
use Formularium\FrameworkComposer;
use Formularium\Frontend\HTML\Element\Button;
use Formularium\Frontend\HTML\Element\Table;
use Formularium\Frontend\Vue\Element\Pagination as PaginationVue;
use Formularium\Frontend\Vue\Framework as FrameworkVue;
use Formularium\HTMLNode;
use Formularium\Renderable;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ScalarType as DefinitionScalarType;
use Modelarium\Exception\Exception;
use Modelarium\Parser;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\GeneratorInterface;
use Modelarium\GeneratorNameTrait;
use Modelarium\Types\ScalarType;

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
     * @var Parser
     */
    protected $parser = null;

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
     * Attributed used to fetch the item. It must be a unique key, and
     * defaults to 'id'.
     *
     * @var string
     */
    protected $keyAttribute = 'id';

    /**
     * Attributed used to fetch the item. It must be a unique key, and
     * defaults to lowerName.
     *
     * @var string
     */
    protected $routeBase = '';

    /**
     * String substitution
     *
     * @var array
     */
    protected $templateParameters = [];

    /**
     * Card fields
     *
     * @var Field[]
     */
    protected $cardFields = [];

    /**
     * Table fields
     *
     * @var Field[]
     */
    protected $tableFields = [];

    public function __construct(FrameworkComposer $composer, Model $model, Parser $parser)
    {
        $this->composer = $composer;
        $this->model = $model;
        // TODO: document keyAttribute renderable parameter
        $this->keyAttribute = $model->getRenderable('keyAttribute', 'id');
        $this->routeBase = $this->model->getRenderable('routeBase', $this->lowerName);
        $this->parser = $parser;
        $this->setBaseName($model->getName());
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
            // build the fields for cards and tables
            $vueCode = $vue->getVueCode();
            $cardFieldNames = array_map(function (Field $f) {
                return $f->getName();
            }, $this->cardFields);
            $tableFieldNames = array_map(function (Field $f) {
                return $f->getName();
            }, $this->tableFields);

            // set basic data for vue
            $extraprops = [
                [
                    'name' => 'id',
                    'type' => 'String',
                    'required' => true
                ]
            ];
            $vueCode->setExtraProps($extraprops);

            // build basic vue components
            $this->vuePublish();
            $this->makeVuePaginationComponent();
            $this->makeJSModel();
            $this->vueCodeItem($vue);

            // card
            foreach ($this->cardFields as $f) {
                $vueCode->appendExtraProp([
                    'name' => $f->getName(),
                    'type' => $vueCode->mapTypeToJs($f->getDatatype()),
                    'required' => true
                ]);
            }
            $this->makeVue($vue, 'Card', 'viewable', $cardFieldNames);
            // reset props
            $vueCode->setExtraProps($extraprops);

            // table
            foreach ($this->tableFields as $f) {
                $vueCode->appendExtraProp([
                    'name' => $f->getName(),
                    'type' => $vueCode->mapTypeToJs($f->getDatatype()),
                    'required' => true
                ]);
            }
            $this->makeVue($vue, 'TableItem', 'viewable', $tableFieldNames);
            $vueCode->setExtraProps($extraprops);

            $this->makeVue($vue, 'List', 'viewable');
            $this->makeVue($vue, 'Table', 'viewable');
            $this->makeVue($vue, 'Show', 'viewable');
            $this->makeVue($vue, 'Edit', 'editable');
            $this->makeVue(
                $vue,
                'Form',
                'editable',
                function (Field $f) {
                    if (!$f->getExtradata('modelFillable')) {
                        return false;
                    }
                    return true;
                }
            );
            $this->makeVueRoutes();
            $this->makeVueIndex();
        }

        $this->makeGraphql();

        return $this->collection;
    }

    /**
     * Publishes the Vue component dependencies
     *
     * @return void
     */
    protected function vuePublish(): void
    {
        $this->collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                file_get_contents(__DIR__ . "/Vue/Renderable/RelationshipAutocomplete.vue"),
                "Modelarium/RelationshipAutocomplete.vue"
            )
        );
        $this->collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                file_get_contents(__DIR__ . "/Vue/Renderable/RelationshipSelect.vue"),
                "Modelarium/RelationshipSelect.vue"
            )
        );
        // $this->collection->push(
        //     new GeneratedItem(
        //         GeneratedItem::TYPE_FRONTEND,
        //         file_get_contents(__DIR__ . "/Vue/Renderable/RelationshipSelectMultiple.vue"),
        //         "Modelarium/RelationshipSelectMultiple.vue"
        //     )
        // );
    }

    protected function makeVuePaginationComponent(): void
    {
        $pagination = $this->composer->nodeElement(
            'Pagination',
            [
            ]
        );
        $html = $pagination->getRenderHTML();
        $script = PaginationVue::script();

        $this->collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                "<template>\n$html\n</template>\n<script>\n$script\n</script>\n",
                "Modelarium/Pagination.vue"
            )
        );
    }

    protected function buildTemplateParameters(): void
    {
        $hasVue = $this->composer->getByName('Vue');

        $this->cardFields = $this->model->filterField(
            function (Field $field) {
                return $field->getRenderable('card', false);
            }
        );
        $this->tableFields = $this->model->filterField(
            function (Field $field) {
                return $field->getRenderable('table', false);
            }
        );

        $buttonCreate = $this->composer->nodeElement(
            'Button',
            [
                Button::TYPE => 'a',
                Button::ATTRIBUTES => [
                    'href' => "/{$this->routeBase}/edit"
                ] + ($hasVue ? [ "v-if" => 'can.create' ]: []),
            ]
        )->setContent(
            '<i class="fa fa-plus"></i> Add new',
            true,
            true
        )->getRenderHTML();

        $buttonEdit = $this->composer->nodeElement(
            'Button',
            [
                Button::TYPE => ($hasVue ? 'router-link' : 'a'),
                Button::ATTRIBUTES => [
                    ':to' => "'/{$this->lowerName}/' + model.{$this->keyAttribute} + '/edit'"
                ] + ($hasVue ? [ "v-if" => 'can.edit' ]: []),
            ]
        )->setContent(
            '<i class="fa fa-pencil"></i> Edit',
            true,
            true
        )->getRenderHTML();

        $buttonDelete = $this->composer->nodeElement(
            'Button',
            [
                Button::TYPE => 'a',
                Button::COLOR => Button::COLOR_WARNING,
                Button::ATTRIBUTES => [
                    'href' => '#',
                    '@click.prevent' => 'remove'
                ] + ($hasVue ? [ "v-if" => 'can.delete' ]: []),
            ]
        )->setContent(
            '<i class="fa fa-trash"></i> Delete',
            true,
            true
        )->getRenderHTML();

        /*
         * table
         */
        $table = $this->composer->nodeElement(
            'Table',
            [
                Table::ROW_NAMES => array_map(
                    function (Field $field) {
                        return $field->getRenderable(Renderable::LABEL, $field->getName());
                    },
                    $this->tableFields
                ),
                Table::STRIPED => true
            ]
        );
        /**
         * @var HTMLNode $tbody
         */
        $tbody = $table->get('tbody')[0];
        $tbody->setContent(
            '<' . $this->studlyName . 'TableItem v-for="l in list" :key="l.id" v-bind="l"></' . $this->studlyName . 'TableItem>',
            true,
            true
        );
        $titleFields = $this->model->filterField(
            function (Field $field) {
                return $field->getRenderable('title', false);
            }
        );

        $spinner = $this->composer->nodeElement('Spinner')
        ->addAttribute(
            'v-show',
            'isLoading'
        )->getRenderHTML();
        $this->templateParameters = [
            'buttonSubmit' => $this->composer->element(
                'Button',
                [
                    Button::TYPE => 'submit',
                    Element::LABEL => 'Submit'
                ]
            ),
            'buttonCreate' => $buttonCreate,
            'buttonEdit' => $buttonEdit,
            'buttonDelete' => $buttonDelete,
            'filters' => $this->getFilters(),
            // TODO 'hasCan' => $this->model
            'keyAttribute' => $this->keyAttribute,
            'spinner' => $spinner,
            'tablelist' => $table->getRenderHTML(),
            'tableItemFields' => array_keys(array_map(function (Field $f) {
                return $f->getName();
            }, $this->tableFields)),
            'typeTitle' => $this->studlyName,
            'titleField' => array_key_first($titleFields) ?: 'id'
        ];
    }

    /**
     * Appends computed item
     *
     * @param FrameworkVue $vue
     * @return void
     */
    protected function vueCodeItem(FrameworkVue $vue): void
    {
        $vue->getVueCode()->appendComputed(
            'link',
            'return "/' . $this->routeBase . '/" + this.' . $this->keyAttribute
        );
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

    /**
     * @param FrameworkVue $vue
     * @param string $component
     * @param string $mode
     * @param string[]|callable $restrictFields
     * @return void
     */
    protected function makeVue(FrameworkVue $vue, string $component, string $mode, $restrictFields = null): void
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
                    $this->model->editable($this->composer, [], $restrictFields),
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
                    $this->model->viewable($this->composer, [], $restrictFields),
                    $path
                )
            );
        }
        $vue->resetVueCode();
        $vue->getVueCode()->setFieldModelVariable('model.');
    }

    /**
     * Filters for query, which might be used by component for rendering and props
     *
     * @return array
     */
    protected function getFilters(): array
    {
        $query = $this->parser->getSchema()->getQueryType();
        $filters = [];
        // find the query that matches our pagination model
        foreach ($query->getFields() as $field) {
            if ($field->name === $this->lowerNamePlural) {
                // found. parse its parameters.

                /**
                 * @var FieldArgument $arg
                 */
                foreach ($field->args as $arg) {
                    // if you need to parse directives: $directives = $arg->astNode->directives;

                    $type = $arg->getType();

                    $required = false;
                    if ($type instanceof NonNull) {
                        $type = $type->getWrappedType();
                        $required = true;
                    }

                    if ($type instanceof CustomScalarType) {
                        $typename = $type->astNode->name->value;
                    } elseif ($type instanceof DefinitionScalarType) {
                        $typename = $type->name;
                    }
                    // } elseif ($type instanceof Input with @spread) {
                    else {
                        // TODO throw new Exception("Unsupported type {$arg->name} in query filter generation for {$this->baseName} " . get_class($type));
                        continue;
                    }

                    $filters[] = [
                        'name' => $arg->name,
                        'type' => $typename,
                        'required' => $required,
                        'requiredJSBoolean' => $required ? 'true' : 'false'
                    ];
                }
                break;
            }
        }
        return $filters;
    }

    protected function makeGraphql(): void
    {
        /*
         * card
         */
        $cardFieldNames = array_map(
            function (Field $field) {
                return $field->getName();
            },
            $this->cardFields
        );
        $cardFieldParameters = implode("\n", $cardFieldNames);

        // generate filters for query
        $filters = $this->templateParameters['filters'] ?? [];
        if ($filters) {
            $filtersQuery = ', ' . join(
                ', ',
                array_map(
                    function ($item) {
                        return '$' . $item['name']  . ': ' . $item['type'] . ($item['required'] ? '!' : '');
                    },
                    $filters
                )
            );
            $filtersParams = ', ' . join(
                ', ',
                array_map(
                    function ($item) {
                        return $item['name'] . ': $' . $item['name'];
                    },
                    $filters
                )
            );
        } else {
            $filtersQuery = $filtersParams = '';
        }

        $listQuery = <<<EOF
query (\$page: Int!$filtersQuery) {
    {$this->lowerNamePlural}(page: \$page$filtersParams) {
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

        /*
         * table
         */
        $tableFieldNames = array_map(
            function (Field $field) {
                return $field->getName();
            },
            $this->tableFields
        );
        $tableFieldParameters = implode("\n", $tableFieldNames);

        $tableQuery = <<<EOF
query (\$page: Int!$filtersQuery) {
    {$this->lowerNamePlural}(page: \$page$filtersParams) {
        data {
            id
            $tableFieldParameters
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
                $tableQuery,
                $this->model->getName() . '/queryTable.graphql'
            )
        );

        /*
         * item
         */
        $graphqlQuery = $this->model->mapFields(
            function (Field $f) {
                return \Modelarium\Frontend\Util::fieldShow($f) ? $f->toGraphqlQuery() : null;
            }
        );
        $graphqlQuery = join("\n", array_filter($graphqlQuery));

        $hasCan = method_exists($this->model, 'getCanAttribute');
        $canAttribute = $hasCan ? 'can' : '';
        if ($this->keyAttribute === 'id') {
            $keyAttributeType = 'ID';
        } else {
            $keyAttributeType = $this->model->getField($this->keyAttribute)->getDatatype()->getGraphqlType();
        }

        $itemQuery = <<<EOF
query (\${$this->keyAttribute}: {$keyAttributeType}!) {
    {$this->lowerName}({$this->keyAttribute}: \${$this->keyAttribute}) {
        id
        $graphqlQuery
        $canAttribute
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

        $upsertMutation = <<<EOF
mutation upsert(\${$this->lowerName}: {$this->studlyName}Input!) {
    upsert{$this->studlyName}(input: \${$this->lowerName}) {
        id
    }
}
EOF;
        $this->collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $upsertMutation,
                $this->model->getName() . '/mutationUpsert.graphql'
            )
        );

        $deleteMutation = <<<EOF
mutation delete(\$id: ID!) {
    delete{$this->studlyName}(id: \$id) {
        id
    }
}
EOF;
        $this->collection->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $deleteMutation,
                $this->model->getName() . '/mutationDelete.graphql'
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
            'Table',
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
                "export default {\n" .
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
                $this->templateFile(
                    $this->stubDir . "/routes.mustache.js",
                    [
                        'routeName' => $this->routeBase,
                        'keyAttribute' => $this->keyAttribute
                    ]
                ),
                $path
            )
        );
    }

    protected function makeJSModel(): void
    {
        $path = $this->model->getName() . '/model.js';
        $modelValues = $this->model->getDefault();
        $modelValues['id'] = 0;
        $modelJS = 'const model = ' . json_encode($modelValues) .
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
