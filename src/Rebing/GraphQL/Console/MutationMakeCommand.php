<?php

namespace Audentio\LaravelGraphQL\Rebing\GraphQL\Console;

use Audentio\LaravelBase\Traits\ExtendConsoleCommandTrait;
use Audentio\LaravelGraphQL\GraphQL\Console\Traits\GraphQLConsoleTrait;

class MutationMakeCommand extends \Rebing\GraphQL\Console\MutationMakeCommand
{
    use ExtendConsoleCommandTrait, GraphQLConsoleTrait;

    protected function getStub()
    {
        return __DIR__ . '/stubs/mutation.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\GraphQL\Mutations';
    }

    protected function qualifyClass($name)
    {
        $name = str_replace('/', '\\', $name);
        $classParts = explode('\\', $name);
        $className = array_pop($classParts);

        $className = $this->suffixCommandClass($className, 'Mutation');

        $actionName = $this->guessActionName($className);
        preg_match_all('/((?:^|[A-Z])[a-z]+)/',$actionName,$matches);
        $action = lcfirst(reset($matches[1]));


        $actionPrefix = substr($className, 0, strlen($action));
        $className = substr($className, strlen($action));
        $className = $this->normalizeTypeName($className, 'Mutation', $actionPrefix);

        $classParts[] = $className;

        return parent::qualifyClass(implode('\\', $classParts));
    }

    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $stub = $this->replaceModelFields($stub, $name);
        $stub = $this->replaceGraphQLType($stub, $name);
        $stub = $this->replaceActionDataType($stub, $name);
        $stub = $this->replaceDataType($stub, $name);
        $stub = $this->replaceTypeClass($stub);

        dump($stub);die;
        return $stub;
    }

    protected function replaceModelFields($stub, $name)
    {
        $dataType = $this->getDataType($name, 'Mutation');
        if ($prefix = config('audentioGraphQL.namePrefix')) {
            $dataType = substr($dataType, strlen($prefix));
        }
        $actionName = $this->guessActionName($dataType . 'Mutation');
        preg_match_all('/((?:^|[A-Z])[a-z]+)/',$actionName,$matches);
        $action = lcfirst(reset($matches[1]));
        $dataTypeName = substr($dataType, strlen($action));
        $prefixedDataTypeName = $this->normalizeTypeName($dataTypeName);

        $modelClass = 'App\Models\\' . $dataTypeName;
        $resourceClass = 'App\GraphQL\Resources\\' . $prefixedDataTypeName . 'Resource';
        $indent = '                        ';
        $replaceItems = [
            'modelInclude' => '',
            'modelGraphQLFields' => '',
            'resourceReturn' => '// TODO: Change the autogenerated stub',
            'actionTypeReturn' => '// TODO: Change the autogenerated stub',
        ];
        if ($action) {
            $replaceItems['actionTypeReturn'] = 'return \'' . $action . '\';';
        }
        if (class_exists($resourceClass)) {
            $replaceItems['modelInclude'] .= "use {$resourceClass};\n";
            $replaceItems['resourceReturn'] = 'return ' . $prefixedDataTypeName . 'Resource::class;';
        }
        if (class_exists($modelClass)) {
            $replaceItems['modelInclude'] .= "use {$modelClass};\n";
            if (method_exists($modelClass, 'getCommonFields')) {
                $replaceItems['modelGraphQLFields'] .= "\n" . $indent . $dataType . '::getCommonFields(' . ($action === 'create' ? '' : 'true') . '),';
            }
            if (method_exists($modelClass, 'getInputFields')) {
                $replaceItems['modelGraphQLFields'] .= "\n" . $indent . $dataType . '::getInputFields(' . ($action === 'create' ? '' : 'true') . '),';
            }
        }
        foreach ($replaceItems as $find => $replace) {
            $stub = str_replace(
                '{' . $find . '}',
                $replace,
                $stub
            );
        }

        return $stub;
    }

    protected function replaceGraphQLType($stub, $name)
    {
        $graphQLType = $this->getDataType($name, 'Mutation');

        return str_replace(
            '{GraphQLType}',
            $graphQLType,
            $stub
        );
    }

    protected function replaceActionDataType($stub, $name)
    {
        $actionName = $this->guessActionName($name);

        return str_replace(
            'actionDataType',
            $actionName,
            $stub
        );
    }

    protected function replaceDataType($stub, $name)
    {
        $actionName = $this->guessActionName($name);
        preg_match_all('/((?:^|[A-Z])[a-z]+)/',$actionName,$matches);

        $dataType = lcfirst(end($matches[1]));

        return str_replace(
            'dataType',
            $dataType,
            $stub
        );
    }

    protected function guessActionName($name)
    {
        preg_match('/([^\\\]+)$/', $name, $matches);
        return lcfirst(substr($matches[1], 0, -8));
    }

    public function handle()
    {
        $return = parent::handle();

        $this->call('config:graphql');

        return $return;
    }
}