<?php declare(strict_types=1);

include_once 'vendor/autoload.php';

use App\Enums\VariableTypes;
use PhpParser\Error;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\NodeDumper;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

$code = <<<'CODE'
<?php

    class Node {
        public function test() {
           $a = 0;
           $b = '1';
           
           if ($a === $b){
            
           }
        }
    }
CODE;



$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
try {
    $ast       = $parser->parse($code);
    $traverser = new NodeTraverser();
    $traverser->addVisitor(new class extends NodeVisitorAbstract {
        public function enterNode(\PhpParser\Node $node)
        {
            $variablesBuffer = [];
            if ($node instanceof Class_) {
                if (hasStmts($node)) {
                    foreach ($node->stmts as $stmt) {
                        if ($stmt instanceof ClassMethod) {
                            if (hasStmts($stmt)) {
                                foreach ($stmt->stmts as $stmt2) {

                                    if ($stmt2 instanceof Expression) {
                                        $name = getVariableName($stmt2);
                                        $type = getVariableType($stmt2);
                                        $variablesBuffer[$name] = $type;
                                    }

                                    if ($stmt2 instanceof If_) {
                                        if ($stmt2->cond instanceof Equal) {
                                            $typeLeftVar = getTypeVar($stmt2->cond->left, $variablesBuffer);
                                            $typeRightVar = getTypeVar($stmt2->cond->right, $variablesBuffer);


                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    });

    $ast = $traverser->traverse($ast);
}
catch (Error $error) {
    echo "Parse error: {$error->getMessage()}\n";

    return;
}

function getVariableName($expression): string
{
    return $expression->expr->var->name;
}

function getTypeVar($var, $varBuffer): string {

}

function getVariableValue($expression)
{
    //@TODO
}

function getVariableType($expression): string
{
    if ($expression->expr->expr instanceof String_) {
        return VariableTypes::STRING;
    }

    if ($expression->expr->expr instanceof LNumber) {
        return VariableTypes::INTEGER;
    }

    return VariableTypes::STRING;
}


function hasStmts($node)
{
    if (isset($node->stmts)) {
        if (count($node->stmts)) {
            return true;
        }
    }

    return false;
}


die;
$dumper = new NodeDumper;
echo $dumper->dump($ast) . "\n";
