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

use function Safe\file_get_contents;
use function Safe\json_encode;

class FrontendVueGenerator
{
    /**
     * @var FrontendGenerator
     */
    protected $generator = null;

    public function __construct(FrontendGenerator $generator)
    {
        $this->generator = $generator;
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
                file_get_contents(__DIR__ . "/Vue/Renderable/RelationshipAutocomplete.vue"),
                "Modelarium/RelationshipAutocomplete.vue"
            )
        );
        $this->getCollection()->push(
            new GeneratedItem(
                GeneratedItem::TYPE_FRONTEND,
                file_get_contents(__DIR__ . "/Vue/Renderable/RelationshipSelect.vue"),
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
                '/" + this.' . $this->generator->getKeyAttribute()
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
            $vueCode->appendExtraProp([
                'name' => $f->getName(),
                'type' => $vueCode->mapTypeToJs($f->getDatatype()),
                'required' => true
            ]);
        }
        $this->makeVue($vue, 'Card', 'viewable', $cardFieldNames);
    }

    protected function vueLink(FrameworkVue $vue): void
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
        $vueCode->setExtraProps($extraprops);
        $cardFieldNames = array_map(function (Field $f) {
            return $f->getName();
        }, $this->generator->getCardFields());
        foreach ($this->generator->getCardFields() as $f) {
            $vueCode->appendExtraProp([
                'name' => $f->getName(),
                'type' => $vueCode->mapTypeToJs($f->getDatatype()),
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
        $extraprops = [
            [
                'name' => 'id',
                'type' => 'String',
                'required' => true
            ]
        ];
        $vueCode->setExtraProps($extraprops);
        foreach ($this->generator->getTableFields() as $f) {
            $vueCode->appendExtraProp([
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

        $items = [
            'Card',
            'Edit',
            'Link',
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

        $this->getCollection()->push(
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
