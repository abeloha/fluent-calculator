<?php
class FluentCalculator
{

	const MAX_DIGIT_LEN = 9;

    protected $stack = [];
    protected $digitsAvailable = [
        'zero' => 0,
        'one' => 1,
        'two' => 2,
        'three' => 3,
        'four' => 4,
        'five' => 5,
        'six' => 6,
        'seven' => 7,
        'eight' => 8,
        'nine' => 9,
    ];
    protected $operationsAvailable = [
        'plus' => 1,
        'minus' => 1,
        'times' => 1,
        'dividedBy' => 1,
    ];
    protected $lastSlackItemIsOperation = false;

    public static function init() {
        return new static();
    }

	// you can define 2 (two) more methods
    public function __get($name)
    {
        if (isset($this->digitsAvailable[$name])) {

            if (empty($this->stack) || $this->lastSlackItemIsOperation) {
                $this->stack[] = '';
            }

            $key = count($this->stack) - 1;
          
            $this->stack[$key] .= $this->digitsAvailable[$name];

            $this->lastSlackItemIsOperation = false;
            
        } elseif (!empty($this->operationsAvailable[$name])) {
            if ($this->lastSlackItemIsOperation) {
                $this->stack[count($this->stack) - 1] = $name;
            } else {
                $this->stack[] = $name;
            }
            $this->lastSlackItemIsOperation = true;
        } else {
            throw new InvalidInputException();
        }        

        return $this;
    }

    public function __call($method, $args)
    {
        if (isset($this->digitsAvailable[$method])) {
            if ($this->lastSlackItemIsOperation) {
                $this->stack[] = $this->digitsAvailable[$method];
            } else {
                if (empty($this->stack)) {
                    $this->stack[] = '';
                }

                $this->stack[count($this->stack) - 1] .= $this->digitsAvailable[$method];
            }
        } elseif (!empty($this->operationsAvailable[$method])) {
            // no more operation the right so no need to add the operation
        } else {
            throw new InvalidInputException();
        }

        // now count result:
        $result = 0;
        $is_first_op = true;

        while ($this->stack) {
            $currentOp = array_shift($this->stack);
            if (!empty($this->operationsAvailable[$currentOp])) {
                if (empty($this->stack)) {
                    continue;
                }

                $operand = array_shift($this->stack);

                if (empty($operand) && $operand != 0) {
                    continue;
                }

                if (strlen(abs($operand)) > self::MAX_DIGIT_LEN) {
                    throw new DigitCountOverflowException();
                }

                switch ($currentOp) {
                    case 'plus':
                        $result += $operand;
                        break;

                    case 'minus':
                        if ($is_first_op) {
                            $result = -1 * $operand;
                        } else {
                            $result -= $operand;
                        }
                        break;

                    case 'times':
                        $result *= $operand;
                        break;

                    case 'dividedBy':

                        //avoid division by zero
                        if (0 == $operand) {
                            throw new DivisionByZeroException();
                        }
                        
                        $result = intdiv($result, $operand);
                        break;
                }

            } else {
                $result = (int)$currentOp;
            }

            if (strlen(abs($result)) > self::MAX_DIGIT_LEN) {
                throw new DigitCountOverflowException();
            }

            $is_first_op = false;
        }

        return $result;
    }
}