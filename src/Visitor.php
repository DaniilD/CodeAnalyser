<?php declare(strict_types=1);

namespace App;

use PhpParser\Node;
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
        $variablesBuffer = [];
        $paramsBuffer = [];
        $propertyBuffer = [];
        if ($node instanceof Class_) {
            if ($this->hasStmts($node)) {
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Node\Stmt\Property) {
                       foreach ($stmt->props as $prop) {
                           $name = $prop->name->name;
                       }

                       $propertyBuffer[$name] = $stmt->type->name;

                    }


                    if ($stmt instanceof ClassMethod) {

                        if (count($stmt->params) > 0) {
                            foreach ($stmt->params as $param) {
                                $paramsBuffer[$param->var->name] = $param->type->name;
                            }
                        }

                        if ($this->hasStmts($stmt)) {
                            foreach ($stmt->stmts as $stmt2) {

                                if ($stmt2 instanceof Expression) {
                                    $name                   = $this->getVariableName($stmt2);
                                    $type                   = $this->getVariableType($stmt2);
                                    $variablesBuffer[$name] = $type;
                                }

                                if ($stmt2 instanceof If_) {
                                    if ($stmt2->cond instanceof Equal) {

                                        if ($stmt2->cond->left instanceof Node\Expr\PropertyFetch) {
                                            $typeLeftVar = $this->getTypeVar($stmt2->cond->left->name, $propertyBuffer);
                                        }else {
                                            $typeLeftVar  = $this->getTypeVar($stmt2->cond->left, $variablesBuffer);
                                        }

                                        if ($stmt2->cond->right instanceof Node\Expr\PropertyFetch) {
                                            $typeRightVar = $this->getTypeVar($stmt2->cond->right->name, $propertyBuffer);
                                        }else {
                                            $typeRightVar = $this->getTypeVar($stmt2->cond->right, $variablesBuffer);
                                        }

                                        if ($this->isNotValidCompare($typeLeftVar, $typeRightVar)) {
                                            $this->errorList[$stmt->name->name] = true;
                                        }else {
                                            $this->errorList[$stmt->name->name] = false;
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


    public function getVariableName($expression): string
    {
        return $expression->expr->var->name;
    }

    public function getTypeVar($var, $varBuffer): string {
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

    public function getVariableType($expression): string
    {
        if ($expression->expr->expr instanceof String_) {
            return VariableTypes::STRING;
        }

        if ($expression->expr->expr instanceof LNumber) {
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
