<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Statements\FireStatement;
use Blueprint\Tree;

class EventGenerator extends StatementGenerator
{
    protected array $types = ['controllers'];

    public function output(Tree $tree): array
    {
        $stub = $this->filesystem->stub('event.stub');

        /**
         * @var \Blueprint\Models\Controller $controller
         */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof FireStatement) {
                        continue;
                    }

                    if ($statement->isNamedEvent()) {
                        continue;
                    }

                    $path = $this->getStatementPath($statement->event());

                    if ($this->filesystem->exists($path)) {
                        continue;
                    }

                    $this->create($path, $this->populateStub($stub, $statement));
                }
            }
        }

        return $this->output;
    }

    protected function getStatementPath(string $name): string
    {
        /* modification starts - support for custom path output to modules directory */
        if (self::$p['force_output_to_modules_directory']===true) {                    
            $override_path = self::$p['modules_path'].'/'.self::$p['modules_name'].'/'.self::$p[$this->get_class_name($this)].'/'. $name . '.php';
            echo($override_path);
        }
        /* modification ends - support for custom path output to modules directory */
        
        return Blueprint::appPath() . '/Events/' . $name . '.php';
    }

    protected function populateStub(string $stub, FireStatement $fireStatement): string
    {
        $stub = str_replace('{{ namespace }}', config('blueprint.namespace') . '\\Events', $stub);
        $stub = str_replace('{{ class }}', $fireStatement->event(), $stub);
        $stub = str_replace('{{ properties }}', $this->populateConstructor('event', $fireStatement), $stub);

        return $stub;
    }
}
