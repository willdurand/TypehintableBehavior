<?php

/**
 * This file is part of the TypehintableBehavior package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class TypehintableBehavior extends Behavior
{
    private $refFKs		= array();

    private $crossFKs	= array();

    private $fks	    = array();

    private $nullables  = array();

    private $scalars    = array('array', 'callable');

    public function objectMethods($builder)
    {
        if (null !== $this->getParameter('nullable_columns')) {
            foreach (explode(',', $this->getParameter('nullable_columns')) as $column) {
                $this->nullables[] = trim($column);
            }

            unset($this->parameters['nullable_columns']);
        }

        foreach ($this->getParameters() as $class) {
            if (!in_array($class, $this->scalars)) {
                $builder->declareClass($class);
            }
        }
    }

    public function objectFilter(&$script)
    {
        if (0 === count($this->getParameters())) {
            return $script;
        }

        foreach ($this->getTable()->getReferrers() as $refFK) {
            $this->refFKs[$refFK->getTable()->getName()] = $refFK->getRefPhpName() ?: $refFK->getTable()->getPhpName();
        }

        foreach ($this->getTable()->getCrossFks() as $fkList) {
            list($refFK, $crossFK) = $fkList;
            $this->crossFKs[$crossFK->getForeignTable()->getName()] = $crossFK->getRefPhpName() ?: $crossFK->getForeignTable()->getPhpName();
        }

        foreach ($this->getTable()->getForeignKeys() as $fk) {
            $this->fks[$fk->getForeignTableName()] = $fk->getForeignTable()->getPhpName();
        }

        return $this->filter($script);
    }

    protected function getColumnSetter($columnName)
    {
        return 'set' . $this->getTable()->getColumn($columnName)->getPhpName();
    }

    protected function getColumnFkSetter($columnName)
    {
        return 'set' . $this->fks[$columnName];
    }

    protected function getColumnRefAdder($columnName)
    {
        return 'add' . $this->refFKs[$columnName];
    }

    protected function getColumnRefRemover($columnName)
    {
        return 'remove' . $this->refFKs[$columnName];
    }

    protected function getColumnCrossAdder($columnName)
    {
        return 'add' . $this->crossFKs[$columnName];
    }

    protected function getColumnCrossRemover($columnName)
    {
        return 'remove' . $this->crossFKs[$columnName];
    }

    protected function filter(&$script)
    {
        foreach ($this->getParameters() as $columnName => $typehint) {
            $isNullable = in_array($columnName, $this->nullables);

            if ($this->getTable()->hasColumn($columnName)) {
                $funcName = $this->getColumnSetter($columnName);
                $this->filterFunction($funcName, $typehint, $isNullable, $script);
            } elseif (array_key_exists($columnName, $this->refFKs)) {
                $funcName = $this->getColumnRefAdder($columnName);
                $this->filterFunction($funcName, $typehint, $isNullable, $script);

                $funcName = $this->getColumnRefRemover($columnName);
                $this->filterFunction($funcName, $typehint, $isNullable, $script);
            } elseif (array_key_exists($columnName, $this->crossFKs)) {
                $funcName = $this->getColumnCrossAdder($columnName);
                $this->filterFunction($funcName, $typehint, $isNullable, $script);

                $funcName = $this->getColumnCrossRemover($columnName);
                $this->filterFunction($funcName, $typehint, $isNullable, $script);
            } elseif (array_key_exists($columnName, $this->fks)) {
                $funcName = $this->getColumnFkSetter($columnName);
                $this->filterFunction($funcName, $typehint, $isNullable, $script);
            }
        }
    }

    protected function filterFunction($functionName, $typehint, $isNullable, &$script)
    {
        $patternWithTypehint    = sprintf('#function %s\([A-Za-z]+#', $functionName);
        $patternWithoutTypehint = sprintf('#function %s\(#', $functionName);

        if (preg_match($patternWithTypehint, $script)) {
            $pattern     = $patternWithTypehint;
            $replacement = sprintf('function %s(%s', $functionName, $this->fixTypehint($typehint));
        } else {
            $pattern     = $patternWithoutTypehint;
            $replacement = sprintf('function %s(%s ', $functionName, $this->fixTypehint($typehint));
        }

        $script = preg_replace($pattern, $replacement, $script);

        if (true === $isNullable) {
            $pattern     = sprintf('#(%s\$[A-Za-z]+)\)#', preg_quote($replacement));
            $replacement = sprintf('$1 = null)');
            $script      = preg_replace($pattern, $replacement, $script);
        }
    }

    private function fixTypehint($typehint)
    {
        if (!in_array($typehint, $this->scalars)) {
            try {
                $reflClass = new \ReflectionClass($typehint);
                $typehint  = $reflClass->getShortName();
            } catch (Exception $e) {
                // class not available at this time, too bad, we use the full qualified class name
            }
        }

        return $typehint;
    }
}
