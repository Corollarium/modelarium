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
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\Options;
use Formularium\StringUtil;

use function Safe\file_get_contents;
use function Safe\scandir;
use function Safe\substr;

class FrontendVueGenerator
{
    /**
     * @var FrontendGenerator
     */
    protected $generator = null;

    /**
     *
     * @var Options
     */
    protected $options = null;

    /**
     * Option defaults
     *
     * @var array
     */
    const DEFAULT_VUE_OPTIONS = [
        /**
         * Use the runtimeValidator JS library
         */
        'runtimeValidator' => false,

        /**
         * The axios variable name
         */
        'axios' => [
            'importFile' => 'axios',
            'method' => 'axios'
        ],

        /**
         * Generate action buttons even if we don't have a can field in the model
         */
        'actionButtonsNoCan' => false,
        /**
         * cleanIdentifier method
         */
        'cleanIdentifierBody' => 'return identifier;',
        /**
         * escapeIdentifier method
         */
        'escapeIdentifierBody' => 'return identifier;',

        'messages' => [
            'nothingFound' => 'Nothing found'
        ]
    ];

    public function __construct(FrontendGenerator $generator)
    {
        $this->generator = $generator;
        $this->options = (new Options())->setSectionDefaults('vue', self::DEFAULT_VUE_OPTIONS);
        $this->buildTemplateParameters();
    }

    protected function getCollection(): GeneratedCollection
    {
        return $this->generator->getCollection();
    }

    public function generate(): void
    {
        /**
         * @var FrameworkVue $vue
         */
        $vue = $this->generator->getComposer()->getByName('Vue');
        $vueCode = $vue->getVueCode();

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

        $this->vueCard($vue);
        $this->vueLink($vue);
        $this->vueTableItem($vue);
        $this->vueTable($vue);

        $this->makeVue($vue, 'List', 'viewable');
        $this->makeVue($vue, 'Show', 'viewable');
        $this->makeVue($vue, 'Edit', 'editable');
        $this->vueForm($vue);
        $this->makeVueRoutes();
        $this->makeVueIndex();
    }

    public function buildTemplateParameters(): void
    {
        $hasVueRouter = $this->generator != null; //TODO: temporary true condition while we don't have a setting for this

        $hasCan = $this->generator->getModel()->getExtradataValue('hasCan', 'value', false);
        $routeBase = $this->generator->getRouteBase();
        $keyAttribute = $this->generator->getKeyAttribute();
        $targetAttribute = $hasVueRouter ? 'to' : 'href';

        $buttonCreate = $this->generator->getComposer()->nodeElement(
            'Button',
            [
                Button::TYPE => ($hasVueRouter ? 'router-link' : 'a'),
                Button::ATTRIBUTES => [
                    $targetAttribute => "/{$routeBase}/edit",
                ] + ($hasCan ? [ "v-if" => 'can(\'create\')' ]: []),
            ]
        )->setContent(
            '<i class="fa fa-plus"></i> Add new',
            true,
            true
        )->getRenderHTML();

        $buttonEdit = $this->generator->getComposer()->nodeElement(
            'Button',
            [
                Button::TYPE => ($hasVueRouter ? 'router-link' : 'a'),
                Button::ATTRIBUTES => [
                    $targetAttribute => "'/{$routeBase}/' + model.{$keyAttribute} + '/edit'",
                ] + ($hasCan ? [ "v-if" => 'can(\'edit\')' ]: []),
            ]
        )->setContent(
            '<i class="fa fa-pencil"></i> Edit',
            true,
            true
        )->getRenderHTML();

        $buttonDelete = $this->generator->getComposer()->nodeElement(
            'Button',
            [
                Button::TYPE => 'a',
                Button::COLOR => Button::COLOR_WARNING,
                Button::ATTRIBUTES => [
                    'href' => '#',
                    '@click.prevent' => 'remove',
                ] + ($hasCan ? [ "v-if" => 'can(\'delete\')' ]: []),
            ]
        )->setContent(
            '<i class="fa fa-trash"></i> Delete',
            true,
            true
        )->getRenderHTML();

        if (!$hasCan && $this->options->getOption('vue', 'actionButtonsNoCan') === false) {
            $this->generator->templateParameters['buttonCreate'] = '';
            $this->generator->templateParameters['buttonEdit'] = '';
            $this->generator->templateParameters['buttonDelete'] = '';
        } else {
            $this->generator->templateParameters['buttonCreate'] = $buttonCreate;
            $this->generator->templateParameters['buttonEdit'] = $buttonEdit;
            $this->generator->templateParameters['buttonDelete'] = $buttonDelete;
        }
        $this->generator->templateParameters['options'] = $this->options->getSection('vue');

        $this->generator->templateParameters['tableItemFields'] =
            array_values(array_map(function (Field $f) {
                if ($f->getDatatype()->getBasetype() === 'relationship') {
                    $name = $f->getName();
                    return "<{$name}-link v-bind=\"{$name}\"></{$name}-link>";
                }
                return '{{ ' . $f->getName() . ' }}';
            }, $this->generator->getTableFields()));
    }

    /**
     * Publishes the Vue component dependencies
     *
     * @return void
     */
    protected function vuePublish(): void
    {
        $this->getCollection()->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $this->generator->templateCallback(
                    __DIR__ . "/Vue/Renderable/RelationshipAutocomplete.vue",
                    $this->generator->getComposer()->getByName('Vue'),
                    [],
                    $this->generator->getModel()
                ),
                "Modelarium/RelationshipAutocomplete.vue"
            )
        );
        $this->getCollection()->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $this->generator->templateCallback(
                    __DIR__ . "/Vue/Renderable/RelationshipSelect.vue",
                    $this->generator->getComposer()->getByName('Vue'),
                    [],
                    $this->generator->getModel()
                ),
                "Modelarium/RelationshipSelect.vue"
            )
        );
        // $this->getCollection()->push(
        //     new GeneratedItem(
        //         GeneratedItem::TYPE_FRONTEND,
        //         file_get_contents(__DIR__ . "/Vue/Renderable/RelationshipSelectMultiple.vue"),
        //         "Modelarium/RelationshipSelectMultiple.vue"
        //     )
        // );
    }

    protected function makeVuePaginationComponent(): void
    {
        // TODO: this is called once per type
        $pagination = $this->generator->getComposer()->nodeElement(
            'Pagination',
            [
            ]
        );
        $html = $pagination->getRenderHTML();
        $script = PaginationVue::script();

        $this->getCollection()->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                "<template>\n$html\n</template>\n<script>\n$script\n</script>\n",
                "Modelarium/Pagination.vue"
            )
        );
    }

    /**
     * Appends computed item
     *
     * @param FrameworkVue $vue
     * @return void
     */
    protected function vueCodeLinkItem(FrameworkVue $vue): void
    {
        $vue->getVueCode()->appendComputed(
            'link',
            'return "/' . $this->generator->getRouteBase() .
                '/" + this.escapeIdentifier(this.' . $this->generator->getKeyAttribute() . ')'
        );
    }

    protected function vueCard(FrameworkVue $vue): void
    {
        $vueCode = $vue->getVueCode();
        // set basic data for vue
        $extraprops = [
            [
                'name' => 'id',
                'type' => 'String',
                'required' => true
            ]
        ];
        $cardFieldNames = array_map(function (Field $f) {
            return $f->getName();
        }, $this->generator->getCardFields());
        $vueCode->setExtraProps($extraprops);
        $this->vueCodeLinkItem($vue);

        foreach ($this->generator->getCardFields() as $f) {
            $vueCode->appendExtraProp($f->getName(), [
                'name' => $f->getName(),
                'type' => $vueCode->mapTypeToJs($f->getDatatype()),
                'required' => true
            ]);
        }
        $vueCode->appendMethod(
            'escapeIdentifier(identifier)',
            $this->options->getOption('vue', 'escapeIdentifierBody')
        );

        $this->makeVue($vue, 'Card', 'viewable', $cardFieldNames);
    }

    protected function vueLink(FrameworkVue $vue): void
    {
        $vueCode = $vue->getVueCode();
        // set basic data for vue
        $vueCode->setExtraProps([]);
        $cardFieldNames = array_map(function (Field $f) {
            return $f->getName();
        }, $this->generator->getCardFields());
        foreach ($this->generator->getCardFields() as $f) {
            $vueCode->appendExtraProp($f->getName(), [
                'name' => $f->getName(),
                'type' => $vueCode->mapTypeToJs($f->getDatatype()),
                'required' => true
            ]);
        }
        foreach ($this->generator->getTitleFields() as $f) {
            $vueCode->appendExtraProp($f->getName(), [
                'name' => $f->getName(),
                'type' => $vueCode->mapTypeToJs($f->getDatatype()),
                'required' => true
            ]);
        }

        if (!$vueCode->getExtraProps()) {
            $vueCode->appendExtraProp('id', [
                'name' => 'id',
                'type' => 'String',
                'required' => true
            ]);
        }

        $this->vueCodeLinkItem($vue);
        $this->makeVue($vue, 'Link', 'viewable', $cardFieldNames);
    }

    public function vueTableItem(FrameworkVue $vue): void
    {
        $vueCode = $vue->getVueCode();
        $tableFieldNames = array_map(function (Field $f) {
            return $f->getName();
        }, $this->generator->getTableFields());
        $vueCode->setExtraProps([]);
        $vueCode->appendExtraProp('id',
            [
                'name' => 'id',
                'type' => 'String',
                'required' => true
            ]
        );

        foreach ($this->generator->getTableFields() as $f) {
            $vueCode->appendExtraProp($f->getName(), [
                'name' => $f->getName(),
                'type' => $vueCode->mapTypeToJs($f->getDatatype()),
                'required' => true
            ]);
        }
        $this->makeVue($vue, 'TableItem', 'viewable', $tableFieldNames);
    }

    public function vueTable(FrameworkVue $vue): void
    {
        $this->makeVue($vue, 'Table', 'viewable');
    }

    public function vueForm(FrameworkVue $vue): void
    {
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
        $path = $this->generator->getModel()->getName() . '/' .
            $this->generator->getModel()->getName() . $component . '.vue';

        $stub = $this->generator->getStubDir() . "/Vue{$component}.mustache.vue";

        if ($mode == 'editable') {
            $vue->setEditableTemplate(
                function (FrameworkVue $vue, array $data, Model $m) use ($stub) {
                    return $this->generator->templateCallback($stub, $vue, $data, $m);
                }
            );

            $this->getCollection()->push(
                new GeneratedItem(
                    GeneratedItem::TYPE_FRONTEND,
                    $this->generator->getModel()->editable($this->generator->getComposer(), [], $restrictFields),
                    $path
                )
            );
        } else {
            $vue->setViewableTemplate(
                function (FrameworkVue $vue, array $data, Model $m) use ($stub) {
                    return $this->generator->templateCallback($stub, $vue, $data, $m);
                }
            );

            $this->getCollection()->push(
                new GeneratedItem(
                    GeneratedItem::TYPE_FRONTEND,
                    $this->generator->getModel()->viewable($this->generator->getComposer(), [], $restrictFields),
                    $path
                )
            );
        }
        $vue->resetVueCode();
        $vue->getVueCode()->setFieldModelVariable('model.');
    }

    protected function makeVueIndex(): void
    {
        $path = $this->generator->getModel()->getName() . '/index.js';
        $name = $this->generator->getStudlyName();

        $contents = function ($basepath, $element) use ($name) {
            $dir = $basepath . '/' . $name;
            $import = [];
            $export = [];
            foreach (scandir($dir) as $i) {
                if (StringUtil::endsWith($i, '.vue')) {
                    $name = substr($i, 0, -4);
                    $import[] = "import $name from './$name.vue';";
                    $export[] = "    {$name},";
                }
            }
            return implode("\n", $import) . "\n\n" .
                "export default {\n" .
                implode("\n", $export) . "\n};\n";
        };

        // $items = [
        //     'Card',
        //     'Edit',
        //     'Link',
        //     'List',
        //     'Show',
        //     'Table',
        // ];

        // $import = array_map(
        //     function ($i) use ($name) {
        //         return "import {$name}$i from './{$name}$i.vue';";
        //     },
        //     $items
        // );

        // $export = array_map(
        //     function ($i) use ($name) {
        //         return "    {$name}$i,";
        //     },
        //     $items
        // );

        $this->getCollection()->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $contents,
                $path
            )
        );
    }

    protected function makeVueRoutes(): void
    {
        $path = $this->generator->getModel()->getName() . '/routes.js';

        $this->getCollection()->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $this->generator->templateFile(
                    $this->generator->getStubDir() . "/routes.mustache.js",
                    [
                        'routeName' => $this->generator->getRouteBase(),
                        'keyAttribute' => $this->generator->getKeyAttribute()
                    ]
                ),
                $path
            )
        );
    }
}
