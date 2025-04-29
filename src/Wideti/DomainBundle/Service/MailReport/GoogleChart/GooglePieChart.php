<?php
namespace Wideti\DomainBundle\Service\MailReport\GoogleChart;

class GooglePieChart extends GoogleChart implements GoogleChartInterface
{
    protected $type = 'p';

    public function putData(array $data)
    {
        ksort($data);

        foreach ($data as $label => $value) {
            $this->labels[] = $label . ' - ' . $value . '%';
            $this->values[] = $value;
        }
    }

    public function compound()
    {
        $array = [
            'cht'  => $this->getType(),
            'chs'  => $this->getSize(),
            'chd'  => 't:' . $this->getValues(),
            'chl'  => $this->getLabels(),
            'chco'  => 'ec213a',
            'chma' => '5,5,5,5',
        ];

        return $this->factoryLink($array);
    }
}
