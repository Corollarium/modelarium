<?php declare(strict_types=1);

namespace Modelarium\Laravel\Console\Commands;

use Formularium\Factory\DatatypeFactory;
use Formularium\Exception\Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Console\Command;
use Nette\PhpGenerator\PhpNamespace;

use function Safe\substr;

class ModelariumDirectiveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modelarium:directive
        {name : The directive name}
        {--generator= : the generator it inherits from. May have more than one.}
        {--namespace=Modelarium\Laravel\Directives : the class namespace. Defaults to "Modelarium\Laravel\Directives"}
        {--path= : path to save the file. Defaults to base_path("app/Modelarium/Datatype") }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates directives using Modelarium';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        if (!is_string($name)) {
            $this->error('Name must be a string');
            return;
        }
        $generators = $this->option('generator');
        if (is_string($generators)) {
            $generators = [$generators];
        } elseif (is_array($generators)) {
            // ok
        } else {
            $this->error('Invalid generators');
            return;
        }

        $ns = $this->option('namespace');
        if (!is_string($ns)) {
            $this->error('Namespace must be a string');
            return;
        }
        $path = $this->option('path') ?: base_path("");

        if (!is_dir($path)) {
            \Safe\mkdir($path, 0777, true);
        }

        $printer = new \Nette\PhpGenerator\PsrPrinter;

        $namespace = new PhpNamespace($ns);
        $class = $namespace->addClass(ucfirst($name) . 'Directive');

        foreach ($generators as $g) {
            // TODO: validate $g
            $namespace->addUse("Modelarium\Laravel\Targets\$gGenerator");
            $class->addImplement("{$g}DirectiveInterface");
        }

        $code = "<?php declare(strict_types=1);\n" . $printer->printNamespace($namespace);
        $filename = $path . "/" . ucfirst($name) . 'Directive.php';
        \Safe\file_put_contents($filename, $code);

        $this->info("Directive $name created.");
    }
}
