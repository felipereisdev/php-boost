<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use FelipeReisDev\PhpBoost\Core\Services\SnippetGenerator;

class SnippetGeneratorTest extends TestCase
{
    private $generator;
    private $projectInfo;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->projectInfo = [
            'name' => 'test-project',
            'tests' => ['framework' => 'phpunit'],
        ];
        
        $this->generator = new SnippetGenerator($this->projectInfo);
    }

    public function testGenerateController()
    {
        $result = $this->generator->generate('controller', ['name' => 'TestController']);
        
        $this->assertStringContainsString('class TestController', $result);
        $this->assertStringContainsString('namespace App\Http\Controllers', $result);
    }

    public function testGenerateResourceController()
    {
        $result = $this->generator->generate('controller', [
            'name' => 'TestController',
            'resource' => true
        ]);
        
        $this->assertStringContainsString('public function index()', $result);
        $this->assertStringContainsString('public function store(Request $request)', $result);
        $this->assertStringContainsString('public function show($id)', $result);
        $this->assertStringContainsString('public function update(Request $request, $id)', $result);
        $this->assertStringContainsString('public function destroy($id)', $result);
    }

    public function testGenerateModel()
    {
        $result = $this->generator->generate('model', ['name' => 'Product']);
        
        $this->assertStringContainsString('class Product extends Model', $result);
        $this->assertStringContainsString('protected $table = \'products\'', $result);
    }

    public function testGenerateModelWithFactory()
    {
        $result = $this->generator->generate('model', [
            'name' => 'Product',
            'with-factory' => true
        ]);
        
        $this->assertStringContainsString('use HasFactory', $result);
    }

    public function testGenerateService()
    {
        $result = $this->generator->generate('service', ['name' => 'PaymentService']);
        
        $this->assertStringContainsString('class PaymentService', $result);
        $this->assertStringContainsString('public function handle()', $result);
    }

    public function testGenerateRepository()
    {
        $result = $this->generator->generate('repository', [
            'name' => 'ProductRepository',
            'model' => 'Product'
        ]);
        
        $this->assertStringContainsString('class ProductRepository', $result);
        $this->assertStringContainsString('use App\Models\Product', $result);
        $this->assertStringContainsString('public function findById($id)', $result);
        $this->assertStringContainsString('public function all()', $result);
        $this->assertStringContainsString('public function create(array $data)', $result);
    }

    public function testGenerateRequest()
    {
        $result = $this->generator->generate('request', ['name' => 'StoreProductRequest']);
        
        $this->assertStringContainsString('class StoreProductRequest extends FormRequest', $result);
        $this->assertStringContainsString('public function authorize()', $result);
        $this->assertStringContainsString('public function rules()', $result);
    }

    public function testGenerateResource()
    {
        $result = $this->generator->generate('resource', ['name' => 'ProductResource']);
        
        $this->assertStringContainsString('class ProductResource extends JsonResource', $result);
        $this->assertStringContainsString('public function toArray($request)', $result);
    }

    public function testGenerateMigration()
    {
        $result = $this->generator->generate('migration', ['table' => 'products']);
        
        $this->assertStringContainsString('class CreateProductsTable extends Migration', $result);
        $this->assertStringContainsString('Schema::create(\'products\'', $result);
        $this->assertStringContainsString('public function up()', $result);
        $this->assertStringContainsString('public function down()', $result);
    }

    public function testGenerateTest()
    {
        $result = $this->generator->generate('test', ['name' => 'ProductTest']);
        
        $this->assertStringContainsString('class ProductTest extends TestCase', $result);
        $this->assertStringContainsString('public function testExample()', $result);
    }

    public function testGetAvailableTypes()
    {
        $types = $this->generator->getAvailableTypes();
        
        $this->assertContains('controller', $types);
        $this->assertContains('model', $types);
        $this->assertContains('service', $types);
        $this->assertContains('repository', $types);
        $this->assertContains('request', $types);
        $this->assertContains('resource', $types);
        $this->assertContains('migration', $types);
        $this->assertContains('test', $types);
    }

    public function testInvalidSnippetType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->generator->generate('invalid_type');
    }
}
