<?php

namespace Wideti\DomainBundle\Service\ApiEntityValidator\CustomFieldValidator\Rules;

class CpfvalidatorRule implements Rule
{

    public function validate(array $fieldValidation, $entityValue, $locale = "pt_br")
    {
        if (!in_array($locale, $fieldValidation['locale'])) {
            return true;
        }

        return $this->cpfValidate($entityValue);
    }

    private function cpfValidate($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', (string) $cpf);
        // Valida tamanho
        if (strlen($cpf) != 11) {
            return false;
        }

        // Calcula e confere primeiro dígito verificador
        for ($i = 0, $j = 10, $soma = 0; $i < 9; $i++, $j--) {
            $soma += $cpf{$i} * $j;
        }
        $resto = $soma % 11;
        if ($cpf{9} != ($resto < 2 ? 0 : 11 - $resto)) {
            return false;
        }

        // Calcula e confere segundo dígito verificador
        for ($i = 0, $j = 11, $soma = 0; $i < 10; $i++, $j--) {
            $soma += $cpf{$i} * $j;
        }

        $resto = $soma % 11;
        return $cpf{10} == ($resto < 2 ? 0 : 11 - $resto);
    }
}