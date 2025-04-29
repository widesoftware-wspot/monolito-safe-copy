<?php
namespace Wideti\DomainBundle\Service\MailReport\GoogleChart;

class GoogleMultiLineChart extends GoogleLineChart
{
    public function addLine($title, array $line)
    {
        $this->data[$title] = $line;
    }

    public function getLineValues()
    {
        $formattedLine = [];

        foreach ($this->data as $line) {
            $formattedLine[] = implode(",", $line);
        }
        return implode("|", $formattedLine);
    }

    public function getLegend()
    {
        return implode("|", array_keys($this->data));
    }

    public function getLabels($glue = '|')
    {
        $formattedLine = [];

        foreach ($this->data as $line) {
            $formattedLine[] = implode("|", array_keys($line));
        }

        $formattedLine = array_unique($formattedLine);

        return implode("|", $formattedLine);
    }

    public function compound()
    {
        $array = [
            'cht'   => $this->getType(),
            'chs'   => $this->getSize(),
            'chxt'  => 'y,x',
            'chd'   => 't:' . $this->getLineValues(),
            'chco'  => '5DD14D,ec213a',
            'chm'   => 'O,5DD14D,0,-1,7|O,ec213a,1,-1,7',
            'chma'  => '25,25,25,25',
            'chdl'  => $this->getLegend(),
            'chdlp' => 'b',
            'chds'  => 'a',
            'chxl'  => '1:|' . $this->getLabels()
        ];

        return $this->factoryLink($array);
    }
}
