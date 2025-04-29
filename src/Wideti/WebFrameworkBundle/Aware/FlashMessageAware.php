<?php

namespace Wideti\WebFrameworkBundle\Aware;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Symfony Server Setup: - [ setSession, ["@session"] ]
 */
trait FlashMessageAware
{
    use SessionAware;

    public function setFlashMessage($type, $message)
    {
        $this->session->getFlashBag()->add(
            $type,
            $message
        );
    }

    public function setCreatedFlashMessage($type = null, $message = null)
    {
        $type    = $type ?: 'notice';
        $message = $message ?: 'Registro criado com sucesso';

        $this->session->getFlashBag()->add($type, $message);
    }

    public function setUpdatedFlashMessage($type = null, $message = null)
    {
        $type    = $type ?: 'notice';
        $message = $message ?: 'Registro alterado com sucesso';

        $this->session->getFlashBag()->add($type, $message);
    }

    public function setDeletedFlashMessage($type = null, $message = null)
    {
        $type    = $type ?: 'notice';
        $message = $message ?: 'Registro removido com sucesso';

        $this->session->getFlashBag()->add($type, $message);
    }

    public function setFailToCreateFlashMessage($type = null, $message = null)
    {
        $type    = $type ?: 'notice';
        $message = $message ?: 'Falha ao cadastrar o registro';

        $this->session->getFlashBag()->add($type, $message);
    }

    public function setFailToGetFlashMessage($type = null, $message = null)
    {
        $type    = $type ?: 'notice';
        $message = $message ?: 'Falha ao obter o registro';

        $this->session->getFlashBag()->add($type, $message);
    }

    public function setProcessingSuccessFlashMessage($type = null, $message = null)
    {
        $type    = $type ?: 'notice';
        $message = $message ?: 'As mensagens estão sendo enviadas para os destinatários.';

        $this->session->getFlashBag()->add($type, $message);
    }

    public function setProcessingFailFlashMessage($type = null, $message = null)
    {
        $type    = $type ?: 'notice';
        $message = $message ?: 'Ocorreu um erro ao realizar o envio das mensagens, tente novamente!';

        $this->session->getFlashBag()->add($type, $message);
    }
}
