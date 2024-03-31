<?php

namespace Blueprint\Generators;

use Blueprint\Concerns\HandlesImports;
use Blueprint\Concerns\HandlesTraits;
use Blueprint\Contracts\Generator;
use Blueprint\Contracts\Model as BlueprintModel;
use Blueprint\Models\Model;
use Blueprint\Tree;

class SeederGenerator extends AbstractClassGenerator implements Generator
{
    use HandlesImports, HandlesTraits;

    protected array $types = ['seeders'];

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $stub = $this->filesystem->stub('seeder.stub');

        foreach ($tree->seeders() as $model) {
            $model = new Model($model);
            $path = $this->getPath($model);
            $this->create($path, $this->populateStub($stub, $model));
        }

        return $this->output;
    }

    protected function getPath(BlueprintModel $blueprintModel): string
    {
        /* modification starts - support for custom path output to modules directory */
        if (self::$p['force_output_to_modules_directory']===true) {                    
            $override_path = self::$p['modules_path'].'/'.self::$p['modules_name'].'/'.self::$p[$this->get_class_name($this)].'/';
            $override_path = str_replace('\\','/',$override_path);            
        } else {
            $override_path = 'database/seeders/';
        }
        /* modification ends - support for custom path output to modules directory */

        $path = $blueprintModel->name();
        if ($blueprintModel->namespace()) {
            $path = str_replace('\\', '/', $blueprintModel->namespace()) . '/' . $path;
        }

        // return 'database/seeders/' . $path . 'Seeder.php';
        return $override_path . $path . 'Seeder.php';
    }

    protected function populateStub(string $stub, BlueprintModel $model): string
    {
        $stub = str_replace('{{ class }}', $model->name() . 'Seeder', $stub);
        $this->addImport($model, 'Illuminate\Database\Seeder');
        $stub = str_replace('//', $this->build($model), $stub);
        $stub = str_replace('use Illuminate\Database\Seeder;', $this->buildImports($model), $stub);

        return $stub;
    }

    protected function build(BlueprintModel $model): string
    {
        $this->addImport($model, $this->tree->fqcnForContext($model->name()));

        return sprintf('%s::factory()->count(5)->create();', class_basename($this->tree->fqcnForContext($model->name())));
    }
}
