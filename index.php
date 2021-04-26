<?php declare(strict_types=1);

include_once 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

$code       = <<<'CODE'
<?php
    class Node {
    
        public function test() {
           
           if (1 == $this->getVar()){
            
           }
        }
        
        public function getVar() {
            return "a";
        }
    }
CODE;
$errorFiles = [];
$parser     = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
$ast       = $parser->parse($code);
var_dump($ast); die;


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
        var_dump($file);
        $ast       = $parser->parse(file_get_contents($file));
        $traverser = new NodeTraverser();
        $visitor   = new \App\Visitor();
        $traverser->addVisitor($visitor);

        $ast = $traverser->traverse($ast);
        var_dump($visitor->getErrorList());
    }
    catch (Error $error) {
        echo "Parse error: {$error->getMessage()}\n";

        return;
    }
}

scan($parser, "./src");
