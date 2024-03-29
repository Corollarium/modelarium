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
use Formularium\Frontend\Vue\VueCode\Computed;
use Formularium\Frontend\Vue\VueCode\Prop;
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

    public function __construct(FrontendGenerator $generator)
    {
        $this->generator = $generator;
        $this->buildTemplateParameters();
    }

    public function getOptions(): Options
    {
        return $this->generator->getOptions();
    }

    public function getStubDir(): string
    {
        return $this->generator->getStubDir() . '/Vue/';
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
            new Prop(
                'id',
                'string',
                true
            )
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
        $this->makeVueCrud();
        $this->makeVueIndex();
        $this->makeVueIndexDynamic();
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
            '<i class="fa fa-plus"></i> ' . $this->getOptions()->getOption('frontend', 'messages')['addNew'],
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
            '<i class="fa fa-pencil"></i> ' . $this->getOptions()->getOption('frontend', 'messages')['edit'],
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
                    '@click.prevent' => 'remove(model.id)',
                ] + ($hasCan ? [ "v-if" => 'can(\'delete\')' ]: []),
            ]
        )->setContent(
            '<i class="fa fa-trash"></i> ' . $this->getOptions()->getOption('frontend', 'messages')['delete'],
            true,
            true
        )->getRenderHTML();

        if (!$hasCan && $this->getOptions()->getOption('vue', 'actionButtonsNoCan') === false) {
            $this->generator->templateParameters['buttonCreate'] = '';
            $this->generator->templateParameters['buttonEdit'] = '';
            $this->generator->templateParameters['buttonDelete'] = '';
        } else {
            $this->generator->templateParameters['buttonCreate'] = $buttonCreate;
            $this->generator->templateParameters['buttonEdit'] = $buttonEdit;
            $this->generator->templateParameters['buttonDelete'] = $buttonDelete;
        }
        $this->generator->templateParameters['options'] = $this->getOptions()->options;

        $this->generator->templateParameters['tableItemFields'] =
            array_values(array_map(function (Field $f) {
                $required = $f->getValidator('required', false);
                if ($f->getDatatype()->getBasetype() === 'relationship') {
                    $name = $f->getName();
                    return "<{$name}-link " . ($required ? '' : "v-if=\"{$name}\"") . "v-bind=\"{$name}\"></{$name}-link>";
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
            new Computed(
                'link',
                'string',
                'return "/' . $this->generator->getRouteBase() .
                    '/" + this.escapeIdentifier(this.' . $this->generator->getKeyAttribute() . ')'
            )
        );
    }

    protected function vueCard(FrameworkVue $vue): void
    {
        $vueCode = $vue->getVueCode();
        // set basic data for vue
        $extraprops = [
            new Prop(
                'id',
                'String',
                true
            )
        ];
        $cardFieldNames = array_map(function (Field $f) {
            return $f->getName();
        }, $this->generator->getCardFields());
        $vueCode->setExtraProps($extraprops);
        $this->vueCodeLinkItem($vue);

        foreach ($this->generator->getCardFields() as $f) {
            $vueCode->appendExtraProp(
                new Prop(
                    $f->getName(),
                    $vueCode->mapTypeToJs($f->getDatatype()),
                    true
                )
            );
        }
        $vueCode->appendMethod(
            'escapeIdentifier(identifier)',
            $this->getOptions()->getOption('vue', 'escapeIdentifierBody')
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
            $vueCode->appendExtraProp(
                new Prop(
                    $f->getName(),
                    $vueCode->mapTypeToJs($f->getDatatype()),
                    true
                )
            );
        }
        foreach ($this->generator->getTitleFields() as $f) {
            $vueCode->appendExtraProp(
                new Prop(
                    $f->getName(),
                    $vueCode->mapTypeToJs($f->getDatatype()),
                    true
                )
            );
        }

        if (!$vueCode->getExtraProps()) {
            $vueCode->appendExtraProp(
                new Prop(
                    'id',
                    'String',
                    true
                )
            );
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
        $vueCode->appendExtraProp(
            new Prop(
                'id',
                'String',
                true
            )
        );

        foreach ($this->generator->getTableFields() as $f) {
            /**
             * @var Field $f
             */
            $required = $f->getValidator('required', false);
            $prop = new Prop(
                $f->getName(),
                $vueCode->mapTypeToJs($f->getDatatype()),
                $required
            );
            if (!$required) {
                if ($f->getDatatype()->getBasetype() === 'relationship') {
                    $prop->default = '() => null';
                } else {
                    $prop->default = $f->getDatatype()->getDefault();
                }
            }

            $vueCode->appendExtraProp($prop);
        }
        $this->makeVue($vue, 'TableItem', 'viewable', $tableFieldNames);
    }

    public function vueTable(FrameworkVue $vue): void
    {
        $this->makeVue($vue, 'Table', 'viewable');
    }

    public function vueForm(FrameworkVue $vue): void
    {
        $vueCode = $vue->getVueCode();
        $vueCode->setExtraProps([]);

        $createGraphqlVariables = $this->generator->getModel()->mapFields(
            function (Field $f) {
                if (!$f->getRenderable('form', true)) {
                    return null;
                }
                $d = $f->getDatatype();
                if ($d->getBasetype() == 'relationship') {
                    return $f->getName() . ": {connect: this.model." . $f->getName() . '}';
                }
                return $f->getName() . ": this.model." . $f->getName();
            }
        );
        $createGraphqlVariables = join(",\n", array_filter($createGraphqlVariables));

        $this->generator->templateParameters['createGraphqlVariables'] = $createGraphqlVariables;
        // they're the same now
        $this->generator->templateParameters['updateGraphqlVariables'] = $createGraphqlVariables;

        $this->makeVue(
            $vue,
            'Form',
            'editable',
            function (Field $f) {
                if (!$f->getExtradata('modelFillable')) {
                    return false;
                }
                return $f->getRenderable('form', true);
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

        $stub = $this->getStubDir() . "/Vue{$component}.mustache.vue";

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
                "export {\n" .
                implode("\n", $export) . "\n};\n";
        };

        $this->getCollection()->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $contents,
                $path
            )
        );
    }

    protected function makeVueIndexDynamic(): void
    {
        $path = $this->generator->getModel()->getName() . '/index.dynamic.js';
        $name = $this->generator->getStudlyName();

        $contents = function ($basepath, $element) use ($name) {
            $dir = $basepath . '/' . $name;
            $import = [];
            $export = [];
            foreach (scandir($dir) as $i) {
                if (StringUtil::endsWith($i, '.vue')) {
                    $componentName = substr($i, 0, -4);
                    $import[] = "const $componentName = () => import(/* webpackChunkName: \"$name\" */ './$componentName.vue');";
                    $export[] = "    {$componentName},";
                }
            }
            return implode("\n", $import) . "\n\n" .
                "export {\n" .
                implode("\n", $export) . "\n};\n";
        };
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
                    $this->getStubDir() . "/routes.mustache.js",
                    [
                        'routeName' => $this->generator->getRouteBase(),
                        'keyAttribute' => $this->generator->getKeyAttribute()
                    ]
                ),
                $path
            )
        );
    }

    protected function makeVueCrud(): void
    {
        $path = $this->generator->getModel()->getName() . '/crud.js';

        $this->getCollection()->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                $this->generator->templateFile(
                    $this->getStubDir() . "/crud.mustache.js",
                    $this->generator->templateParameters
                ),
                $path
            )
        );
    }
}
