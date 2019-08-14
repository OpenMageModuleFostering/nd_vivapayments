<?php

class ND_Vivapayments_Block_Checkout_Failure extends Mage_Core_Block_Template
{
    /**
     *  Return Error message
     *
     *  @return	  string
     */
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
