<?php

class ND_Vivapayments_Block_Info extends Mage_Payment_Block_Info_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('vivapayments/info.phtml');
    }

    public function toPdf()
    {
        $this->setTemplate('vivapayments/pdf/info.phtml');
        return $this->toHtml();
    }

}
