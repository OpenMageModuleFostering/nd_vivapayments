<?php
class ND_Vivapayments_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getTransactionDetails($tid=0)
    {
        if(!$tid) return false;
        $checkoutModel = Mage::getModel('vivapayments/checkout');
        $MerchantId = $checkoutModel->getMerchantId();
        $APIKey = $checkoutModel->getSecretKey();
        $ch = curl_init($checkoutModel->getTransactionUrl().$tid);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $MerchantId.':'.$APIKey);
        if($checkoutModel->isTestMode()) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        }
        else {
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST,'TLSv1');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        if(is_object(json_decode($response))){
            return json_decode($response);
        }else{
            return false;
        }
    }
    
    public function validateOrder($oid=0)
    {
        if(!$oid) return false;
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($oid);
        if(empty($order)) return false;
        return ($order->getStatus()==ND_Vivapayments_Model_Info::PAYMENTSTATUS_PENDING) ? true : false;
    }
}
