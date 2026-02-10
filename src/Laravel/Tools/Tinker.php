<?php

namespace FelipeReisDev\PhpBoost\Laravel\Tools;

use FelipeReisDev\PhpBoost\Core\Tools\AbstractTool;
use Psy\Shell;
use Psy\Configuration;

class Tinker extends AbstractTool
{
    public function getName()
    {
        return 'Tinker';
    }

    public function getDescription()
    {
        return 'Execute PHP code in the context of the Laravel application (REPL)';
    }

    public function getInputSchema()
    {
        return [
            'type' => 'object',
            'properties' => [
                'code' => [
                    'type' => 'string',
                    'description' => 'PHP code to execute',
                ],
            ],
            'required' => ['code'],
        ];
    }

    public function isReadOnly()
    {
        return false;
    }

    public function execute(array $arguments)
    {
        $this->validateArguments($arguments, ['code']);

        $code = $arguments['code'];

        if (!class_exists(Shell::class)) {
            throw new \RuntimeException('PsySH is not installed. Run: composer require psy/psysh');
        }

        try {
            ob_start();
            
            $config = new Configuration([
                'updateCheck' => 'never',
                'usePcntl' => false,
            ]);
            
            $shell = new Shell($config);
            
            $code = trim($code);
            if (!str_ends_with($code, ';')) {
                $code .= ';';
            }
            
            $result = $shell->execute($code);
            
            $output = ob_get_clean();

            return [
                'result' => $result,
                'output' => $output ?: null,
            ];
        } catch (\Throwable $e) {
            ob_end_clean();
            
            return [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }
    }
}
