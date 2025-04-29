<?php


namespace Wideti\DomainBundle\Service\DataProtect\DataController\RequestValidation;


use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Helpers\PropertyValidationHelper;
use Wideti\DomainBundle\Service\DataProtect\DataController\Exceptions\FieldRequiredRuntimeException;
use Wideti\DomainBundle\Service\DataProtect\DataController\Exceptions\InvalidFormatRuntimeException;

class DataControllerRequestValidation
{
    public static function validation(Request $request)
    {
        self::validatorRequired($request);
        self::validatorFormat($request);
    }

    private static function validatorRequired(Request $request)
    {
        $keys = ['fullName', 'email', 'cpf', 'birthday', 'jobOccupation', 'phoneNumber'];
        foreach ($keys as $key){
            if ( PropertyValidationHelper::isEmpty($request->get($key)) ){
                throw new FieldRequiredRuntimeException("O campo [{$key}] é obrigatório!", $key);
            }
        }
    }

    private static function validatorFormat(Request $request)
    {
        $birthday = \DateTime::createFromFormat("Y-m-d", $request->get('birthday'));
        if ($birthday == false){
            throw new InvalidFormatRuntimeException(
                "O campo [birthday] veio com informação inválida. Valor recebido: [{$request->get('birthday')}]",
                'birthday',
                $request->get('birthday')
            );
        }
        $email = $request->get('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidFormatRuntimeException(
                "O [email] é inválido!",
                'email',
                $request->get('email')
            );
        }
        $cpf = (int)$request->get('cpf');
        if ($cpf == false){
            throw new InvalidFormatRuntimeException(
                "O campo [cpf] aceita somente números!",
                'cpf',
                $request->get('cpf')
            );
        }
        $phoneNumber = (int)$request->get('phoneNumber');
        if ($phoneNumber == false){
            throw new InvalidFormatRuntimeException(
                "O campo [phoneNumber] aceita somente números!",
                'phoneNumber',
                $request->get('phoneNumber')
            );
        }
    }
}