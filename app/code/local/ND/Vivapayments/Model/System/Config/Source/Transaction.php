<?php

class ND_Vivapayments_Model_System_Config_Source_Transaction
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Payment (Sale)')),
            array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('Authorization')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            1 => Mage::helper('adminhtml')->__('Payment (Sale)'),
            2 => Mage::helper('adminhtml')->__('Authorization'),
        );
    }

}
