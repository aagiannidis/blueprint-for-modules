<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Statements\SendStatement;
use Blueprint\Tree;

class MailGenerator extends StatementGenerator
{
    protected array $types = ['controllers'];

    public function output(Tree $tree): array
    {
        $stub = $this->filesystem->stub('mail.stub');
        $view_stub = $this->filesystem->stub('mail.view.stub');

        /**
         * @var \Blueprint\Models\Controller $controller
         */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof SendStatement) {
                        continue;
                    }

                    if ($statement->type() !== SendStatement::TYPE_MAIL) {
                        continue;
                    }

                    $path = $this->getStatementPath($statement->mail());
                    if ($this->filesystem->exists($path)) {
                        continue;
                    }

                    $this->create($path, $this->populateStub($stub, $statement));

                    $path = $this->getViewPath($statement->view());
                    if ($this->filesystem->exists($path)) {
                        continue;
                    }

                    $this->create($path, $this->populateViewStub($view_stub, $statement));
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
        return Blueprint::appPath() . '/Mail/' . $name . '.php';
    }

    protected function populateStub(string $stub, SendStatement $sendStatement): string
    {
        $stub = str_replace('{{ namespace }}', config('blueprint.namespace') . '\\Mail', $stub);
        $stub = str_replace('{{ class }}', $sendStatement->mail(), $stub);
        $stub = str_replace('{{ subject }}', $sendStatement->subject(), $stub);
        $stub = str_replace('{{ view }}', $sendStatement->view(), $stub);
        $stub = str_replace('{{ properties }}', $this->populateConstructor('message', $sendStatement), $stub);

        return $stub;
    }

    protected function getViewPath($view): string
    {
        /* modification starts - support for custom path output to modules directory */
        if (self::$p['force_output_to_modules_directory']===true) {                    
            $override_path = self::$p['modules_path'].'/'.self::$p['modules_name'].'/'.self::$p['ViewGenerator'].'/'. str_replace('.', '/', $view) . '.blade.php';
            echo($override_path);
        }
        /* modification ends - support for custom path output to modules directory */

        return 'resources/views/' . str_replace('.', '/', $view) . '.blade.php';
    }

    protected function populateViewStub(string $stub, SendStatement $statement): string
    {
        return str_replace('{{ class }}', $statement->mail(), $stub);
    }
}
