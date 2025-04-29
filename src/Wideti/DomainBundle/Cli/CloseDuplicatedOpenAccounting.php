<?php

require_once __DIR__ . '/../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Wideti\DomainBundle\Helpers\DuplicatedAccountingHelper;

/**
 * CLI script interface
 *
 * This script close the user accounting when they switch between
 * two or more access points. In some cases the access points may not
 * be able to send a close accounting package, and this will cover that.
 */
$kernel = new AppKernel('prod', true);
$kernel->boot();

/**
 * Inicia a aplicacao e resgata os servicos necessários para o script
 */
$application    = new Application($kernel);
$container      = $application->getKernel()->getContainer();
$radacctService = $container->get('core.service.radacct');
$monolog        = $container->get('logger');
$output     = new \Symfony\Component\Console\Output\ConsoleOutput();

/**
 * Faz uma consulta no ElasticSearch de todos os accountings que não possuem o acctstoptime
 */

$opened = null;
try {
    $output->writeln("Iniciando busca por sessões abertas e/ou duplicadas");
    $opened = $radacctService->searchDuplicatedOpenedSessions();
} catch (\Exception $ex) {
    $output->writeln("Erro ao buscar sessions: " . $ex->getMessage());
    $monolog->addCritical("in Scrip src/Wideti/DomainBundle/Cli/CloseDuplicatedOpenAccounting.php: " . $ex->getMessage());
    exit(1);
}

while ($curr = current($opened)) {
    $next = next($opened);
    prev($opened);

    /**
     * Pega o índice do documento para que o update seja feito no indice certo
     */
    $currentIndex = $curr['_index'];

    /**
     * Essa verificacao está aqui para quando chegar no último registro, a próxima
     * condicao irá falhar e poderemos ter um falso positivo naquela condicao e
     * pular um registro por acidente
     */
    if ($next === false) {
        /**
         * Aqui verificamos se o accounting encontrado está aberto a mais de 45 minutos
         * (antiga responsabilidade do script QueueFailedAccinterim_updateountings.php)
         */
        if (DuplicatedAccountingHelper::failedOnClose($curr['_source'])) {
            /**
             * Se o accounting estiver aberto a mais de 45 minutos, então chama o método
             * para setar o acctstoptime = NOW -1 second.
             */
            try {
                $message = getActionMessage("Accounting aberto há mais de 45 min", $curr);
                $output->writeln($message);
                $output->writeln("Fechando accounting...");
                $radacctService->updateCloseAccounting($curr['_id'], $currentIndex);
            } catch (\Exception $e) {
                $errorMessage = getErrorMessage($e, $curr);
                $output->writeln($errorMessage);
                $monolog->addCritical(
                    'QueueDuplicatedOpenAccounting - Accounting open more than 45 minutes -
                    Error on update acctstoptime on Elastic, acctuniqueid: ' . $curr['_source']['acctuniqueid'] .
                    "Exception error: " . $e->getMessage()
                );
            }
        }
        break;
    }

    if (DuplicatedAccountingHelper::hasDifference($curr, $next) === true) {
        /**
         * Essa parte do script verifica se o registro atual encontrado bate com as chaves
         * do próximo registro (isso quer dizer que é o mesmo usuário com mesmo ip e mesmo mac com mais
         * de um accounting ainda aberto)
         *
         * Em caso positivo atualiza este registro com a hora de inicio do proximo accounting
         * subtraindo 1 segundo.
         */
        try {
            $message = getActionMessage("Accounting duplicado", $curr);
            $output->writeln($message);
            $radacctService->updateAcctStopTimeSubtractingOneSecond(
                $curr['_id'],
                $next['_source']['acctstarttime'],
                $currentIndex
            );
        } catch (\Exception $e) {
            $errorMessage = getErrorMessage($e, $curr);
            $output->writeln($errorMessage);
            $monolog->addCritical(
                'QueueDuplicatedOpenAccounting - Accounting duplicated - Error on update acctstoptime on Elastic,
                acctuniqueid: ' . $curr['_source']['acctuniqueid'] . "Exception Message: " . $e->getMessage()
            );
        }
    } else {
        /**
         * Aqui verificamos se o accounting encontrado está aberto a mais de 45 minutos
         * (antiga responsabilidade do script QueueFailedAccountings.php).
         */
        if (DuplicatedAccountingHelper::failedOnClose($curr['_source'])) {
            /**
             * Se o accounting estiver aberto a mais de 45 minutos, então chama o método
             * para setar o acctstoptime = NOW -1 second.
             */
            try {
                $message = getActionMessage("Accounting aberto há mais de 45 min", $curr);
                $output->writeln($message);
                $output->writeln("Fechando accounting...");
                $radacctService->updateCloseAccounting($curr['_id'], $currentIndex);
            } catch (\Exception $e) {
                $errorMessage = getErrorMessage($e, $curr);
                $output->writeln($errorMessage);
                $monolog->addCritical(
                    'QueueDuplicatedOpenAccounting - Accounting open more than 45 minutes -
                    Error on update acctstoptime on Elastic, acctuniqueid: ' . $curr['_source']['acctuniqueid'] .
                    "Exception error: " . $e->getMessage()
                );
            }
        }
    }

    /**
     * avança o ponteiro para o proximo registro
     */
    next($opened);
}

function getErrorMessage($exception, $curr) {
    return sprintf(
        "Erro ao atualizar acctstoptime no Elastic, UniqueID: %s. Mensagem: %s",
        $curr['_source']['acctuniqueid'],
        $exception->getMessage()
    );
}

function getActionMessage($action, $curr) {
    return sprintf(
        "%s, IP: %s, SessionID: %s, Username: %s, UniqueID: %s",
        $action,
        $curr['_source']["framedipaddress"],
        $curr['_source']["acctsessionid"],
        $curr['_source']["username"],
        $curr['_source']['acctuniqueid']
    );
}
