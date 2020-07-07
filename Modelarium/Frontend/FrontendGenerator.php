<?php declare(strict_types=1);

namespace Modelarium\Frontend;

use Formularium\Model;
use Formularium\FrameworkComposer;
use Formularium\Frontend\Blade\Framework as FrameworkBlade;
use Formularium\Frontend\Vue\Framework as FrameworkVue;
use Modelarium\GeneratedCollection;
use Modelarium\GeneratedItem;
use Modelarium\GeneratorInterface;

use function Safe\file_get_contents;

class FrontendGenerator implements GeneratorInterface
{
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
            $this->makeVue($vue, 'View', 'viewable');
            $this->makeVue($vue, 'Form', 'editable');
        }

        return $this->collection;
    }

    protected function makeVue(FrameworkVue $vue, string $component, string $mode): void
    {
        $path = 'resources/js/components/' . $this->model->getName() . '/' . $component . '.vue';
        $stub = file_get_contents($this->stubDir . "/Vue{$component}.stub.vue");
        if ($mode == 'editable') {
            $vue->setEditableTemplate($stub);
            $this->collection->push(
                new GeneratedItem(
                    GeneratedItem::TYPE_FRONTEND,
                    $this->model->editable($this->composer),
                    $path
                )
            );
        } else {
            $vue->setViewableTemplate($stub);
            $this->collection->push(
                new GeneratedItem(
                    GeneratedItem::TYPE_FRONTEND,
                    $this->model->viewable($this->composer, []),
                    $path
                )
            );
        }
    }
}
