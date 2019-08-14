<?php

class ND_Vivapayments_Block_Checkout_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {        
        parent::_construct();
        $this->setTemplate('vivapayments/checkout/form.phtml');
    }
    
    public function getCcAvailableTypes()
    {
        return array(
            'VI'=>'VISA', // VISA (VI)
            'MC'=>'MasterCard', // MasterCard (MC)
            'DC'=>'Diners Club', // Diners Club (DC)       
        );
        /*$types = $this->_getConfig()->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('vivapaymentscctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;*/
    }
}
