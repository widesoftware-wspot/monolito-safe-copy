<?php
namespace Wideti\DomainBundle\Service\MailReport\GoogleChart;

class GoogleChart
{
    protected $link     = "https://chart.googleapis.com/chart";
    protected $type     = null;
    protected $width    = 520;
    protected $height   = 200;
    protected $color    = "ec213a";

    protected $labels   = [];
    protected $values   = [];
    protected $data;

    public function putData(array $data)
    {
        if (array_key_exists('order', $data)) {
            unset($data['order']);
        } else {
            ksort($data);
        }
        foreach ($data as $label => $value) {
            $this->labels[] = $label;
            $this->values[] = $value;
        }
    }

    private function monthFormat($month)
    {
        switch ($month) {
            case '01':
                return 'Jan';
            case '02':
                return 'Fev';
            case '03':
                return 'Mar';
            case '04':
                return 'Abr';
            case '05':
                return 'Mai';
            case '06':
                return 'Jun';
            case '07':
                return 'Jul';
            case '08':
                return 'Ago';
            case '09':
                return 'Set';
            case '10':
                return 'Out';
            case '11':
                return 'Nov';
            case '12':
                return 'Dez';
            default:
                return $month;
        }
    }

    public function getLabels($glue = '|')
    {
        return implode($glue, $this->labels);
    }

    public function getValues($glue = ',')
    {
        return implode($glue, $this->values);
    }

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setHeight($height)
    {
        $this->height = $height;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        if ($this->type === null) {
            throw new \Exception('type is not defined on ' . get_called_class());
        }

        return $this->type;
    }

    public function setWidth($width)
    {
        $this->width = $width;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getSize()
    {
        return $this->width . 'x' . $this->height;
    }

    public function factoryLink(array $compound)
    {
        return $this->link . '?' . http_build_query($compound);
    }
}
