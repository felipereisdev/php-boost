<?php

namespace FelipeReisDev\PhpBoost\Core\Services;

class SnippetGenerator
{
    private $projectInfo;
    private $customSnippetsPath;

    public function __construct(array $projectInfo, $customSnippetsPath = null)
    {
        $this->projectInfo = $projectInfo;
        $this->customSnippetsPath = $customSnippetsPath;
    }

    public function generate($type, array $options = [])
    {
        if ($this->hasCustomSnippet($type)) {
            return $this->loadCustomSnippet($type, $options);
        }

        $method = 'generate' . ucfirst($type);
        
        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException("Unknown snippet type: {$type}");
        }

        return $this->{$method}($options);
    }

    public function getAvailableTypes()
    {
        $builtIn = ['controller', 'model', 'service', 'repository', 'request', 'resource', 'migration', 'test'];
        $custom = $this->getCustomSnippetTypes();
        
        return array_merge($builtIn, $custom);
    }

    private function generateController(array $options)
    {
        $name = $options['name'] ?? 'ExampleController';
        $namespace = $options['namespace'] ?? 'App\\Http\\Controllers';
        $resource = isset($options['resource']) && $options['resource'];
        
        $content = "<?php\n\n";
        $content .= "namespace {$namespace};\n\n";
        $content .= "use Illuminate\\Http\\Request;\n";
        
        if ($resource) {
            $content .= "use Illuminate\\Http\\JsonResponse;\n";
        }
        
        $content .= "\nclass {$name}\n{\n";
        
        if ($resource) {
            $content .= $this->generateResourceMethods();
        } else {
            $content .= "    public function index()\n    {\n";
            $content .= "        return view('index');\n";
            $content .= "    }\n";
        }
        
        $content .= "}\n";
        
        return $content;
    }

    private function generateModel(array $options)
    {
        $name = $options['name'] ?? 'Example';
        $namespace = $options['namespace'] ?? 'App\\Models';
        $withFactory = isset($options['with-factory']) && $options['with-factory'];
        $table = $options['table'] ?? strtolower($name) . 's';
        
        $content = "<?php\n\n";
        $content .= "namespace {$namespace};\n\n";
        $content .= "use Illuminate\\Database\\Eloquent\\Model;\n";
        
        if ($withFactory) {
            $content .= "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\n";
        }
        
        $content .= "\nclass {$name} extends Model\n{\n";
        
        if ($withFactory) {
            $content .= "    use HasFactory;\n\n";
        }
        
        $content .= "    protected \$table = '{$table}';\n\n";
        $content .= "    protected \$fillable = [];\n\n";
        $content .= "    protected \$casts = [];\n";
        $content .= "}\n";
        
        return $content;
    }

    private function generateService(array $options)
    {
        $name = $options['name'] ?? 'ExampleService';
        $namespace = $options['namespace'] ?? 'App\\Services';
        
        $content = "<?php\n\n";
        $content .= "namespace {$namespace};\n\n";
        $content .= "class {$name}\n{\n";
        $content .= "    public function __construct()\n    {\n";
        $content .= "    }\n\n";
        $content .= "    public function handle()\n    {\n";
        $content .= "    }\n";
        $content .= "}\n";
        
        return $content;
    }

    private function generateRepository(array $options)
    {
        $name = $options['name'] ?? 'ExampleRepository';
        $model = $options['model'] ?? 'Example';
        $namespace = $options['namespace'] ?? 'App\\Repositories';
        
        $content = "<?php\n\n";
        $content .= "namespace {$namespace};\n\n";
        $content .= "use App\\Models\\{$model};\n\n";
        $content .= "class {$name}\n{\n";
        $content .= "    private \$model;\n\n";
        $content .= "    public function __construct({$model} \$model)\n    {\n";
        $content .= "        \$this->model = \$model;\n";
        $content .= "    }\n\n";
        $content .= "    public function findById(\$id)\n    {\n";
        $content .= "        return \$this->model->find(\$id);\n";
        $content .= "    }\n\n";
        $content .= "    public function all()\n    {\n";
        $content .= "        return \$this->model->all();\n";
        $content .= "    }\n\n";
        $content .= "    public function create(array \$data)\n    {\n";
        $content .= "        return \$this->model->create(\$data);\n";
        $content .= "    }\n\n";
        $content .= "    public function update(\$id, array \$data)\n    {\n";
        $content .= "        \$record = \$this->findById(\$id);\n";
        $content .= "        \$record->update(\$data);\n";
        $content .= "        return \$record;\n";
        $content .= "    }\n\n";
        $content .= "    public function delete(\$id)\n    {\n";
        $content .= "        return \$this->model->destroy(\$id);\n";
        $content .= "    }\n";
        $content .= "}\n";
        
        return $content;
    }

    private function generateRequest(array $options)
    {
        $name = $options['name'] ?? 'ExampleRequest';
        $namespace = $options['namespace'] ?? 'App\\Http\\Requests';
        
        $content = "<?php\n\n";
        $content .= "namespace {$namespace};\n\n";
        $content .= "use Illuminate\\Foundation\\Http\\FormRequest;\n\n";
        $content .= "class {$name} extends FormRequest\n{\n";
        $content .= "    public function authorize()\n    {\n";
        $content .= "        return true;\n";
        $content .= "    }\n\n";
        $content .= "    public function rules()\n    {\n";
        $content .= "        return [];\n";
        $content .= "    }\n";
        $content .= "}\n";
        
        return $content;
    }

    private function generateResource(array $options)
    {
        $name = $options['name'] ?? 'ExampleResource';
        $namespace = $options['namespace'] ?? 'App\\Http\\Resources';
        
        $content = "<?php\n\n";
        $content .= "namespace {$namespace};\n\n";
        $content .= "use Illuminate\\Http\\Resources\\Json\\JsonResource;\n\n";
        $content .= "class {$name} extends JsonResource\n{\n";
        $content .= "    public function toArray(\$request)\n    {\n";
        $content .= "        return [\n";
        $content .= "            'id' => \$this->id,\n";
        $content .= "        ];\n";
        $content .= "    }\n";
        $content .= "}\n";
        
        return $content;
    }

    private function generateMigration(array $options)
    {
        $table = $options['table'] ?? 'examples';
        $action = $options['action'] ?? 'create';
        
        $className = 'Create' . ucfirst($table) . 'Table';
        
        $content = "<?php\n\n";
        $content .= "use Illuminate\\Database\\Migrations\\Migration;\n";
        $content .= "use Illuminate\\Database\\Schema\\Blueprint;\n";
        $content .= "use Illuminate\\Support\\Facades\\Schema;\n\n";
        $content .= "class {$className} extends Migration\n{\n";
        $content .= "    public function up()\n    {\n";
        $content .= "        Schema::create('{$table}', function (Blueprint \$table) {\n";
        $content .= "            \$table->id();\n";
        $content .= "            \$table->timestamps();\n";
        $content .= "        });\n";
        $content .= "    }\n\n";
        $content .= "    public function down()\n    {\n";
        $content .= "        Schema::dropIfExists('{$table}');\n";
        $content .= "    }\n";
        $content .= "}\n";
        
        return $content;
    }

    private function generateTest(array $options)
    {
        $name = $options['name'] ?? 'ExampleTest';
        $namespace = $options['namespace'] ?? 'Tests\\Unit';
        $framework = $this->projectInfo['tests']['framework'] ?? 'phpunit';
        
        $content = "<?php\n\n";
        $content .= "namespace {$namespace};\n\n";
        
        if ($framework === 'pest') {
            $content .= "test('example test', function () {\n";
            $content .= "    expect(true)->toBeTrue();\n";
            $content .= "});\n";
        } else {
            $content .= "use PHPUnit\\Framework\\TestCase;\n\n";
            $content .= "class {$name} extends TestCase\n{\n";
            $content .= "    public function testExample()\n    {\n";
            $content .= "        \$this->assertTrue(true);\n";
            $content .= "    }\n";
            $content .= "}\n";
        }
        
        return $content;
    }

    private function generateResourceMethods()
    {
        $methods = "    public function index()\n    {\n";
        $methods .= "        return response()->json([]);\n";
        $methods .= "    }\n\n";
        
        $methods .= "    public function store(Request \$request)\n    {\n";
        $methods .= "        return response()->json([], 201);\n";
        $methods .= "    }\n\n";
        
        $methods .= "    public function show(\$id)\n    {\n";
        $methods .= "        return response()->json([]);\n";
        $methods .= "    }\n\n";
        
        $methods .= "    public function update(Request \$request, \$id)\n    {\n";
        $methods .= "        return response()->json([]);\n";
        $methods .= "    }\n\n";
        
        $methods .= "    public function destroy(\$id)\n    {\n";
        $methods .= "        return response()->json([], 204);\n";
        $methods .= "    }\n";
        
        return $methods;
    }

    private function hasCustomSnippet($type)
    {
        if (!$this->customSnippetsPath) {
            return false;
        }
        
        $path = $this->customSnippetsPath . '/' . $type . '.php';
        return file_exists($path);
    }

    private function loadCustomSnippet($type, array $options)
    {
        $path = $this->customSnippetsPath . '/' . $type . '.php';
        
        extract($options);
        
        ob_start();
        include $path;
        return ob_get_clean();
    }

    private function getCustomSnippetTypes()
    {
        if (!$this->customSnippetsPath || !is_dir($this->customSnippetsPath)) {
            return [];
        }
        
        $files = scandir($this->customSnippetsPath);
        $types = [];
        
        foreach ($files as $file) {
            if (substr($file, -4) === '.php') {
                $types[] = substr($file, 0, -4);
            }
        }
        
        return $types;
    }
}
