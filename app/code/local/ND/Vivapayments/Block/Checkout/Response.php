<?php
class ND_Vivapayments_Block_Checkout_Response extends Mage_Core_Block_Template
{
    /**
     *  Return Error message
     *
     *  @return	  string
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('vivapayments/checkout/response.phtml');
    }
    
    public function getErrorMessage ()
    {
        $msg = Mage::getSingleton('checkout/session')->getVivaErrorMessage();
        Mage::getSingleton('checkout/session')->unsVivaErrorMessage();
        return $msg;
    }

    /**
     * Get continue shopping url
     */
    public function getContinueShoppingUrl()
    {
        return Mage::getUrl('checkout/cart');
    }
}
