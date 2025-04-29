<?php


namespace Wideti\DomainBundle\Analytics;


use Wideti\DomainBundle\Analytics\Events\AnalyticEventLogger;
use Wideti\DomainBundle\Analytics\Events\Event;
use Wideti\FrontendBundle\Factory\Nas;

class FrontendEventLogger implements AnalyticEventLogger
{

    /**
     * @param Event $event
     * @return mixed|void
     */
    public function formatEvent($event)
    {

        $deviceMac = $this->getUserIdentifier($event);
        $createdDate = $this->getCreatedDate();

        $this->generateDefaultSessionId();

        return [
                    "user_id" => $deviceMac ? $deviceMac : "Not_Informed",
                    "device_id" => $deviceMac,
                    "user_properties" => [
                        "session_info" => $event->getSession() ? $event->getSession()->getId() : "N/I",
                        "guest_info" => [
                            "guest_id" => $event->getGuest() ? $event->getGuest()->getMysql() : "N/I",
                            "guest_macaddress" => $event->getNas() ? $event->getNas()->getGuestDeviceMacAddress() : "N/I",
                            "user_agent" => $event->getSession() ? $event->getSession()->get("userAgent") : "N/I"
                        ],
                        "ap"=> [
                            "ap_macaddress" => $event->getNas() ? $event->getNas()->getAccessPointMacAddress() : "N/I"
                        ],
                        "client_properties" => [
                            "dominio"      => $event->getClient() ? $event->getClient()->getDomain() : "N/I",
                            "segmento"  => $event->getClient() && $event->getClient()->getSegment() ?
                                $event->getClient()->getSegment()->getName() :
                                "N/I"
                        ],
                        "extra" => $event->getExtraData() ? $event->getExtraData() : "N/I",
                        "created_at" => $createdDate
                    ],
                    "event_type" => $event->getEventType(),
                    "session_id" => $event->getSession() ? $event->getSession()->get('amplitude_session_id') :
                        $this->generateDefaultSessionId()
               ];
    }

    /**
     * @param Event $event
     * @return string
     */
    private function getUserIdentifier(Event $event)
    {
        if ($event->getNas()) {
            return $event->getNas()->getGuestDeviceMacAddress() ?
                $event->getNas()->getGuestDeviceMacAddress() :
                "Not_Informed";
        } elseif ($event->getSession()) {
            $nas = $event->getSession()->get(Nas::NAS_SESSION_KEY);
            if ($nas) {
                return  $nas->getGuestDeviceMacAddress() ? $nas->getGuestDeviceMacAddress() : "Not_Informed";
            }
        }

        return "Not_Informed";

    }

    /**
     * @return false|string
     * @throws \Exception
     */
    private function getCreatedDate()
    {
        try {
            return date_format(new \DateTime('now',
                new \DateTimeZone('America/Sao_Paulo')),
                'Y-m-d H:i:s');
        } catch (\Exception $e) {
            return "N/I";
        }
    }

    private function generateDefaultSessionId()
    {
        $date = new \DateTime();
        return $date->getTimestamp();
    }


}
