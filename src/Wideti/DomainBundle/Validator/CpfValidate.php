<?php

namespace Wideti\DomainBundle\Validator;

class CpfValidate
{
    private $cpf;

    private function setCpf($cpf)
    {
        $this->cpf = $cpf;
    }

    public function getcpf()
    {
        return $this->cpf;
    }

    public function cleanCPF()
    {
        $cpfClean = preg_replace('/[^0-9]/', null, $this->getcpf());

        return $cpfClean;
    }

    private function isCpfClean()
    {
        $cpfClean = $this->cleanCPF();

        if (strlen($cpfClean) == 11) {
            $this->setCpf($cpfClean);

            return true;
        } else {
            return false;
        }
    }

    private function cpfValidateSequence()
    {
        $cpf = $this->getcpf();

        for ($position = 0; $position <= 9; $position++) {
            $pattern = "/$position{11}/";
            if (preg_match($pattern, $cpf)) {
                return false;
                break;
            }
        }

        return true;
    }

    private function digitValidate($firstDigit = true)
    {
        if ($firstDigit) {
            $weigth  = 10;
            $digitPosition = 9;
        } else {
            $weigth  = 11;
            $digitPosition = 10;
        }

        $result = 0;
        $length = $weigth -1;

        for ($position = 0; $position < $length; $position++) {
            $pos = (substr($this->getcpf(), $position, 1));
            $result += ($pos*$weigth);
            $weigth--;
        }

        $remainder = ceil($result%11);

        if ($remainder < 2) {
            if (!(substr($this->getcpf(), $digitPosition, 1) == 0)) {
                return false;
            }
        } else {
            if (!(substr($this->getcpf(), $digitPosition, 1) == (11 - $remainder))) {
                return false;
            }
        }

        return true;
    }

    public function validate($cpf)
    {
        $this->setCpf($cpf);

        if (!$this->isCpfClean()) {
            return false;
        }

        if (!$this->cpfValidateSequence()) {
            return false;
        }

        if (!$this->digitValidate()) {
            return false;
        }

        if (!$this->digitValidate(false)) {
            return false;
        }

        return true;
    }
}
