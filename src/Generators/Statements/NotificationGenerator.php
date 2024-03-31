<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Statements\SendStatement;
use Blueprint\Tree;

class NotificationGenerator extends StatementGenerator
{
    protected array $types = ['controllers'];

    public function output(Tree $tree): array
    {
        $stub = $this->filesystem->stub('notification.stub');

        /**
         * @var \Blueprint\Models\Controller $controller
         */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof SendStatement) {
                        continue;
                    }

                    if (!$statement->isNotification()) {
                        continue;
                    }

                    $path = $this->getStatementPath($statement->mail());

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
            $override_path = self::$p[$this->get_class_name($this)].'/';
            $override_path = str_replace('\\','/',$override_path);            
            $override_path = '/'.$override_path;
        } else {
            $override_path = '/Notification/';
        }
        /* modification ends - support for custom path output to modules directory */

        return Blueprint::appPath() . $override_path . $name . '.php';
    }

    protected function populateStub(string $stub, SendStatement $sendStatement): string
    {
        $stub = str_replace('{{ namespace }}', config('blueprint.namespace') . '\\Notification', $stub);
        $stub = str_replace('{{ class }}', $sendStatement->mail(), $stub);
        $stub = str_replace('{{ properties }}', $this->populateConstructor('message', $sendStatement), $stub);

        return $stub;
    }
}
