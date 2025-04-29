<?php


namespace Wideti\DomainBundle\Service\DataProtect\DataController\Dto;


use Symfony\Component\HttpFoundation\Request;
use Wideti\DomainBundle\Entity\DataControllerAgent;

class DataControllerAgentDto
{
    private $fullName;
    private $cpf;
    private $phoneNumber;
    private $email;
    private $jobOccupation;
    /**
     * @var \DateTime
     */
    private $birthday;

    /**
     * @return mixed
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * @return mixed
     */
    public function getCpf()
    {
        return $this->cpf;
    }

    /**
     * @return mixed
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getJobOccupation()
    {
        return $this->jobOccupation;
    }

    /**
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    public function toArrayMap()
    {
        $map = [
            'fullName' => $this->fullName,
            'cpf' => $this->cpf,
            'phoneNumber' => $this->phoneNumber,
            'email' => $this->email,
            'jobOccupation' => $this->jobOccupation
        ];
        if (is_null($this->birthday)){
            $map['birthday'] = null;
        }else{
            $map['birthday'] = $this->birthday->format("Y-m-d");
        }
        return $map;
    }

    public static function createByDataControllerAgent(DataControllerAgent $dataControllerAgent)
    {
        $obj = new DataControllerAgentDto();
        $obj->phoneNumber = $dataControllerAgent->getPhoneNumber();
        $obj->jobOccupation = $dataControllerAgent->getJobOccupation();
        $obj->fullName = $dataControllerAgent->getFullName();
        $obj->email = $dataControllerAgent->getEmail();
        $obj->cpf = $dataControllerAgent->getCpf();
        $obj->birthday = $dataControllerAgent->getBirthday();
        return $obj;
    }

    public static function createByRequest(Request $request)
    {
        $obj = new DataControllerAgentDto();
        $obj->phoneNumber = (int)$request->get('phoneNumber');
        $obj->jobOccupation = $request->get('jobOccupation');
        $obj->fullName = $request->get('fullName');
        $obj->email = $request->get('email');
        $obj->cpf = (string)$request->get('cpf');
        $obj->birthday = \DateTime::createFromFormat("Y-m-d", $request->get('birthday'));
        return $obj;
    }
}