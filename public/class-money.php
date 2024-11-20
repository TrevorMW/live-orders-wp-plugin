<?php 

class Money {

    public String $displayValue;
    public Float $value;
    public String $currencyCode = '$';

    public function __construct($value, String $currencyCode){
        $formattedAmount = self::formatForCurrency($value); ;
        $money = Utilities::valueArray($value, $currencyCode . '' . $formattedAmount);

        $this->displayValue = $money['displayValue'];
        $this->value        = $money['value'];
        $this->currencyCode = $currencyCode;
    }

    public static function formatForCurrency($value){
        return number_format($value, 2, '.', ',');
    }

    public function getDisplayValue(){
        return $this->displayValue;
    }

    public function setDisplayValue($value){
        $this->displayValue = $this->currencyCode . '' . self::formatForCurrency($value);
    }

    public function getRawValue(){
        return $this->value;
    }

    public function setRawValue($value){
        $this->value = $value;
    }

    public function getCurrencyCode(){
        return $this->currencyCode;
    }

    public function setCurrencyCode($code){
        $this->currencyCode = $code;
    }
}