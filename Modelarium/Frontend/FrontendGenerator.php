<?php declare(strict_types=1);

namespace Modelarium\Frontend;

use Formularium\Element;
use Formularium\Field;
use Formularium\Framework;
use Formularium\Model;
use Formularium\FrameworkComposer;
use Formularium\Frontend\HTML\Element\Button;
use Formularium\Frontend\HTML\Element\Table;
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
    protected $fModel = null;

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
    public $templateParameters = [];

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

    /**
     * title fields
     *
     * @var Field[]
     */
    protected $titleFields = [];

    public function __construct(FrameworkComposer $composer, Model $model, Parser $parser)
    {
        $this->composer = $composer;
        $this->fModel = $model;
        $this->setBaseName($model->getName());
        // TODO: document keyAttribute renderable parameter
        $this->keyAttribute = $model->getRenderable('keyAttribute', 'id');
        $this->routeBase = $this->fModel->getRenderable('routeBase', $this->lowerName);
        $this->parser = $parser;
        $this->buildTemplateParameters();
    }

    public function generate(): GeneratedCollection
    {
        $this->collection = new GeneratedCollection();
        if ($this->fModel->getExtradata('frontendSkip')) {
            return $this->collection;
        }

        /**
         * @var FrameworkVue $vue
         */
        $vue = $this->composer->getByName('Vue');
        // $blade = FrameworkComposer::getByName('Blade');

        $this->makeJSModel();

        if ($vue !== null) {
            $vueGenerator = new FrontendVueGenerator($this);
            $vueGenerator->generate();
        }

        $this->makeGraphql();

        return $this->collection;
    }

    public function buildTemplateParameters(): void
    {
        $this->titleFields = $this->fModel->filterField(
            function (Field $field) {
                return $field->getRenderable('title', false);
            }
        );
        $this->cardFields = $this->fModel->filterField(
            function (Field $field) {
                return $field->getRenderable('card', false);
            }
        );
        $this->tableFields = $this->fModel->filterField(
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
                ],
            ]
        )->setContent(
            '<i class="fa fa-plus"></i> Add new',
            true,
            true
        )->getRenderHTML();

        $buttonEdit = $this->composer->nodeElement(
            'Button',
            [
                Button::TYPE => 'a',
                Button::ATTRIBUTES => [
                    ':to' => "'/{$this->lowerName}/' + model.{$this->keyAttribute} + '/edit'"
                ],
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
                ],
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
        $titleFields = $this->fModel->filterField(
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

    public function templateCallback(string $stub, Framework $f, array $data, Model $m): string
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
        $graphqlQuery = $this->fModel->mapFields(
            function (Field $f) use ($cardFieldNames) {
                if (in_array($f->getName(), $cardFieldNames)) {
                    // TODO: filter subfields in relationships
                    return $f->toGraphqlQuery();
                }
                return null;
            }
        );
        $cardFieldParameters = join("\n", array_filter($graphqlQuery));

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
                $this->fModel->getName() . '/queryList.graphql'
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

        $graphqlQuery = $this->fModel->mapFields(
            function (Field $f) use ($tableFieldNames) {
                if (in_array($f->getName(), $tableFieldNames)) {
                    // TODO: filter subfields in relationships
                    return $f->toGraphqlQuery();
                }
                return null;
            }
        );

        $tableFieldParameters = join("\n", array_filter($graphqlQuery));

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
                $this->fModel->getName() . '/queryTable.graphql'
            )
        );

        /*
         * item
         */
        $graphqlQuery = $this->fModel->mapFields(
            function (Field $f) {
                return \Modelarium\Frontend\Util::fieldShow($f) ? $f->toGraphqlQuery() : null;
            }
        );
        $graphqlQuery = join("\n", array_filter($graphqlQuery));

        $hasCan = $this->fModel->getExtradataValue('hasCan', 'value', false);
        $canAttribute = $hasCan ? 'can' : ''; // TODO: subvalues?
        if ($this->keyAttribute === 'id') {
            $keyAttributeType = 'ID';
        } else {
            $keyAttributeType = $this->fModel->getField($this->keyAttribute)->getDatatype()->getGraphqlType();
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
                $this->fModel->getName() . '/queryItem.graphql'
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
                $this->fModel->getName() . '/mutationUpsert.graphql'
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
                $this->fModel->getName() . '/mutationDelete.graphql'
            )
        );
    }
    
    protected function makeJSModel(): void
    {
        $path = $this->fModel->getName() . '/model.js';
        $modelValues = $this->fModel->getDefault();
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

    /**
     * Get the value of composer
     *
     * @return  FrameworkComposer
     */
    public function getComposer(): FrameworkComposer
    {
        return $this->composer;
    }

    /**
     * Get the value of collection
     *
     * @return  GeneratedCollection
     */
    public function getCollection(): GeneratedCollection
    {
        return $this->collection;
    }

    /**
     * Get card fields
     *
     * @return  Field[]
     */
    public function getCardFields(): array
    {
        return $this->cardFields;
    }

    /**
     * Get table fields
     *
     * @return  Field[]
     */
    public function getTableFields(): array
    {
        return $this->tableFields;
    }

    /**
     * Get defaults to 'id'.
     *
     * @return  string
     */
    public function getKeyAttribute(): string
    {
        return $this->keyAttribute;
    }

    /**
     * Get defaults to lowerName.
     *
     * @return  string
     */
    public function getRouteBase(): string
    {
        return $this->routeBase;
    }

    /**
     * Get the value of model
     *
     * @return  Model
     */
    public function getModel()
    {
        return $this->fModel;
    }

    /**
     * Get the value of stubDir
     *
     * @return  string
     */
    public function getStubDir()
    {
        return $this->stubDir;
    }

    /**
     * Get title fields
     *
     * @return  Field[]
     */
    public function getTitleFields()
    {
        return $this->titleFields;
    }
}
