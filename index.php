<?php declare(strict_types=1);

include_once 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

$code       = <<<'CODE'
<?php
    class Node {
    
        public function test() {
           
           if (1 == "1"){
            
           }
        }
    }
CODE;
$errorFiles = [];
$parser     = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
//$ast       = $parser->parse($code);
//var_dump($ast); die;


function scan($parser, string $path)
{
    $files = scandir($path); //присваиваем переменной массив с листингом директории
    foreach ($files as $file) {
        if ($file === "." || $file === "..") {
            continue;
        }

        if ( ! is_dir($path . "/" . $file)) {
            parse($parser, $path . "/" . $file);
        }
        else {
            scan($parser, $path . "/" . $file);
        }
    }
}

function parse($parser, $file)
{
    try {
        $ast       = $parser->parse(file_get_contents($file));
        $traverser = new NodeTraverser();
        $visitor   = new \App\Visitor();
        $traverser->addVisitor($visitor);

        $ast = $traverser->traverse($ast);

        if(count($visitor->getErrorList()) > 0) {
            echo "FILE: {$file} \n";
            foreach ($visitor->getErrorList() as $error) {
                echo "LINE {$error['line']}: Error in function \"{$error['function']}\", variable type mismatch {$error['leftVar']} and {$error['rightVar']}. \n";
            }
        }
    }
    catch (Error $error) {
        echo "Parse error: {$error->getMessage()}\n";

        return;
    }
}

$path = $argv[1];
//scan($parser, "../bronevik/core/src");
//scan($parser, "./src");
scan($parser, $path);
