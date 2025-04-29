<?php
namespace Wideti\DomainBundle\Service\MailReport\GoogleChart;

class GoogleLineChart extends GoogleChart implements GoogleChartInterface
{
    protected $type = "lc";

    public function compound()
    {
        $array = [
            'cht'  => $this->getType(),
            'chs'  => $this->getSize(),
            'chxt' => 'y,x',
            'chd'  => 't:' . $this->getValues(),
            'chm'  => 'o,'.$this->getColor().',0,-1,10',
            'chma' => '25,25,25,25',
            'chxl' => '1:|' . $this->getLabels(),
            'chds' => 'a'
        ];

        return $this->factoryLink($array);
    }
}
