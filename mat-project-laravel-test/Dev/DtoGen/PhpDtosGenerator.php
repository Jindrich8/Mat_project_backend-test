<?php

namespace Dev\DtoGen {

    use Illuminate\Support\Str;
    use Swaggest\JsonSchema\JsonSchema;
    use Swaggest\PhpCodeBuilder\PhpFunction;
    use Swaggest\PhpCodeBuilder\PhpStdType;

    class PhpDtosGenerator
    {
        const DEFS = "defs";

        public static function generate(mixed $schemaData, string $rootName, string $basePath, string $baseNameSpace,string $relResolverDir = null, string $schemaFilePath = null, string $separator = DIRECTORY_SEPARATOR)
        {
            if ($schemaFilePath) {
                $schemaFilePath = realpath($schemaFilePath);
                if (!$schemaFilePath) {
                    $schemaFilePath = null;
                }
            }
            echo "\n!!Generating dtos... !!\n";
            $relResolverDir ??= MyFileInfo::dirname($schemaFilePath);

            $remoteRefProvider = $relResolverDir ? 
             new RelativeSchemaRefResolver($relResolverDir) 
            : null;

            $schemaContext = new \Swaggest\JsonSchema\Context($remoteRefProvider);
            $swaggerSchema = \Swaggest\JsonSchema\Schema::import($schemaData,$schemaContext);

            $appPath = $basePath;
            $appNs = MyFileInfo::omitAllExtensions($baseNameSpace);
            echo "AppNS: " . $appNs . "\n";
            $app = new \Swaggest\PhpCodeBuilder\App\PhpApp();
            $app->setNamespaceRoot($appNs, '.');

            $builder = new \Swaggest\PhpCodeBuilder\JsonSchema\PhpBuilder();
            $builder->namesFromDescriptions =true;
            $builder->buildSetters = true;
            $builder->makeEnumConstants = true;

            $rootName = Str::studly($rootName);
            $quotedSeparator = null;

            $builder->classCreatedHook = new \Swaggest\PhpCodeBuilder\JsonSchema\ClassHookCallback(
                function (\Swaggest\PhpCodeBuilder\PhpClass $class,string $path,JsonSchema $schema) 
                use ($app, $appNs, $rootName, $schemaFilePath, $separator, $quotedSeparator) {
                    $desc = '';
                    if ($schema->title) {
                        $desc = $schema->title;
                    }
                    if ($schema->description) {
                        $desc .= "\n" . $schema->description;
                    }
                    if ($fromRefs = $schema->getFromRefs()) {
                        $desc .= "\nBuilt from " . implode("\n" . ' <- ', $fromRefs);
                    }

                    $class->setDescription(trim($desc));
                    $createFuncBody = <<<'EOF'
                $instance = parent::create();
                EOF;
                $hasConstants = false;
                   $props = $schema->properties;
                   foreach($props as $name => $value){
                   $const = $value->const;
                   if($const){
                    $hasConstants = true;
                    if(!is_numeric($const)){
                        $const = "\"$const\"";
                    }
                    $createFuncBody.="\n\$instance->$name = $const;";
                   }
                   }
                
                
                   if($hasConstants){
                    $createFuncBody.="\nreturn \$instance;";
                $createFunc = new PhpFunction(name:'create',visibility:'public',isStatic:true);
                $createFunc->setResult(PhpStdType::tStatic())
                ->setBody($createFuncBody);
                $class->addMethod($createFunc);
                   }
                  
                    $class->setNamespace($appNs);
                    if ('#' === $path) {
                        $class->setName($rootName); // Class name for root schema
                    } elseif (strpos($path, "#/" . PhpDtosGenerator::DEFS . "/") === 0) {
                        $class->setName(\Swaggest\PhpCodeBuilder\PhpCode::makePhpClassName(
                            substr($path, strlen("#/" . PhpDtosGenerator::DEFS . "/"))
                        ));
                    } else {
                        $className = $class->getName();
                    //     echo "Class " . $className."\n";
                    //     echo "Titles: " . $schema->title. "\n";
                    //     echo "Description: " .$schema->description."\n";
                    //    // var_dump($schema);
                    //     var_dump($class);
                    //     var_dump($path);
                        if($schema->title){
                            $className = $schema->title;
                        }
                        else if ($path && mb_strlen($className) > 15) {
                      //    echo "PATH " . $path."\n";
                            $path = Str::remove(['$', '[', ']', '(', ')'], $path);
                            $pathParts = preg_split("/->|#/u", $path);
                            if (!$pathParts) {
                                $pathParts = [$path];
                            }
                            if ($schemaFilePath && !Str::startsWith($path,'#')) {
                                $i = 0;
                                $len = min(strlen($path), strlen($schemaFilePath));
                                for (; $i < $len && $path[$i] === $schemaFilePath[$i]; ++$i);
                                $pathParts[0] = substr($path, $i);
                            }
                            if (!$quotedSeparator) {
                                $quotedSeparator = preg_quote($separator, '/');
                            }
                            $regex2 = <<< END
/$quotedSeparator/u
END;
                            $firstPathPart = MyFileInfo::omitAllExtensions(array_shift($pathParts));
                            $parts = [];
                            $splitted = preg_split($regex2, $firstPathPart);
                            if ($splitted) {
                                array_push($parts, ...array_slice($splitted, -2));
                            }
                            foreach ($pathParts as $part) {
                                $splitted = preg_split($regex2, $part);
                                if ($splitted) {
                                    array_push($parts, ...$splitted);
                                }
                            }
                            if ($parts) {
                                $result = "";
                                foreach ($parts as $group) {
                                    if ($group) {
                                        $result .= Str::studly($group);
                                    }
                                }

                                if ($result) {
                                    $className = $result;
                                }
                            }
                        } 
                       
                        echo "Class: " . $className . "\n";
                        $class->setName(\Swaggest\PhpCodeBuilder\PhpCode::makePhpClassName(
                            $className
                        ));
                    }
                    $app->addClass($class);
                }
            );
            $builder->getType($swaggerSchema);
            $app->clearOldFiles($appPath);
            $app->store($appPath);
        }
    }
}
