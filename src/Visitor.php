<?php declare(strict_types=1);

namespace App;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;
use App\Enums\VariableTypes;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;

class Visitor extends NodeVisitorAbstract
{
    private $errorList = [];


    public function enterNode(Node $node)
    {
        $propertyBuffer = [];
        if ($node instanceof Class_) {
            if ($this->hasStmts($node)) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Node\Stmt\Property) {
                       foreach ($stmt->props as $prop) {
                           $name = $prop->name->name;
                       }

                       if ($stmt->type !== null && isset($name)) {
                           $propertyBuffer[$name] = $stmt->type->name;
                       }

                    }

                    if ($stmt instanceof ClassMethod) {
                        $variablesBuffer = [];
                        $paramsBuffer = [];
                        if (count($stmt->params) > 0) {
                            foreach ($stmt->params as $param) {
                                if ($param->type !== null && isset($param->type->name) ){
                                    $paramsBuffer[$param->var->name] = $this->getParamType($param->type->name);
                                }
                            }
                        }

                        if ($this->hasStmts($stmt)) {
                            foreach ($stmt->stmts as $stmt2) {
                                if (isset($stmt2->expr->expr->class)) {
                                    continue;
                                }
                                //var_dump($stmt2);
                                if ($stmt2 instanceof Expression) {
                                    $name                   = $this->getVariableName($stmt2);
                                   // var_dump($stmt2);
                                    $type                   = $this->getVariableType($stmt2);
                                    if (!$type || !$name) {
                                        continue;
                                    }
                                    $variablesBuffer[$name] = $type;
                                }

                                if ($stmt2 instanceof If_) {
                                    if ($stmt2->cond instanceof Equal) {
                                        $leftName = "";
                                        $rightName = "";
                                        $typeRightVar = null;
                                        $typeLeftVar = null;
                                        if ($stmt2->cond->left instanceof LNumber) {
                                            $typeLeftVar = VariableTypes::INTEGER;
                                        }elseif ($stmt2->cond->left instanceof String_) {
                                            $typeLeftVar = VariableTypes::STRING;
                                        }elseif ($stmt2->cond->left instanceof Node\Expr\PropertyFetch) {
                                            $typeLeftVar = $this->getTypeVar($stmt2->cond->left->name, $propertyBuffer);
                                            $leftName = $stmt2->cond->left->name->name;
                                        }elseif($stmt2->cond->left instanceof Variable) {
                                           // var_dump($variablesBuffer);
                                            $typeLeftVar  = $this->getTypeVar($stmt2->cond->left, array_merge($paramsBuffer, $variablesBuffer));
                                            $leftName = $stmt2->cond->left->name;
                                        }

                                        if ($stmt2->cond->right instanceof LNumber){
                                            $typeRightVar = VariableTypes::INTEGER;
                                        }elseif ($stmt2->cond->right instanceof String_) {
                                            $typeRightVar = VariableTypes::STRING;
                                        }elseif ($stmt2->cond->right instanceof Node\Expr\PropertyFetch) {
                                            $typeRightVar = $this->getTypeVar($stmt2->cond->right->name, $propertyBuffer);
                                            $rightName = $stmt2->cond->right->name->name;
                                        }elseif($stmt2->cond->right instanceof Variable) {
                                            $typeRightVar = $this->getTypeVar($stmt2->cond->right, array_merge($paramsBuffer, $variablesBuffer));
                                            $rightName = $stmt2->cond->right->name;
                                        }

                                        $line = $stmt2->cond->getAttribute("startLine");

                                        if ($typeLeftVar === null || $typeRightVar === null) {
                                            continue;
                                        }
                                        if ($this->isNotValidCompare($typeLeftVar, $typeRightVar)) {
                                           // var_dump($typeLeftVar, $typeRightVar);
                                            $this->errorList[] = [
                                                'line' => $line,
                                                'function' => $stmt->name->name,
                                                'leftVar' => $leftName,
                                                'rightVar' => $rightName
                                            ];
                                        }

                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    public function getVariableName($expression): ?string
    {
        if (isset($expression->expr->var->var) || isset($expression->expr->var->name->name)) {
            return null;
        }

        return $expression->expr->var->name;
    }

    public function getTypeVar($var, $varBuffer): ?string {
        return $varBuffer[$var->name];
    }

   public  function getVariableValue($expression)
    {
        //@TODO
    }

    public function isNotValidCompare($typeOne, $typeTwo): bool
    {
        return ($typeOne === VariableTypes::STRING && $typeTwo === VariableTypes::INTEGER)
            || ($typeOne === VariableTypes::INTEGER && $typeTwo === VariableTypes::STRING);
    }

    public function getVariableType($expression): ?string
    {
        if (!isset($expression->expr->expr)) {
            return null;
        }

        if ($expression->expr->expr instanceof Node\Expr\FuncCall) {
            return null;
        }

        if ($expression->expr->expr instanceof String_) {
            return VariableTypes::STRING;
        }

        if ($expression->expr->expr instanceof LNumber) {
            return VariableTypes::INTEGER;
        }

        return VariableTypes::STRING;
    }

    public function getParamType($name): string
    {
        if ($name === "string") {
            return VariableTypes::STRING;
        }

        if ($name === "int") {
            return VariableTypes::INTEGER;
        }

        return VariableTypes::STRING;
    }


    public function hasStmts($node)
    {
        if (isset($node->stmts)) {
            if (count($node->stmts)) {
                return true;
            }
        }

        return false;
    }



    /**
     * @return array
     */
    public function getErrorList(): array
    {
        return $this->errorList;
    }
}
