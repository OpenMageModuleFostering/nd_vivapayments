<?php

class ND_Vivapayments_CheckoutController extends ND_Vivapayments_Controller_Abstract
{
    protected $_redirectBlockType = 'vivapayments/checkout_redirect';
    
    public function responseAction()
    {
        $responseParams = $this->getRequest()->getParams();         
        if(!empty($responseParams['t'])) {
            $infoModel = Mage::getModel('vivapayments/info');
            $checkoutModel = Mage::getModel('vivapayments/checkout');
            $response = Mage::helper('vivapayments')->getTransactionDetails($responseParams['t']);            
            if($response->ErrorCode==0) {
                $transaction = $response->Transactions[0];
                if(Mage::helper('vivapayments')->validateOrder($transaction->MerchantTrns) && $transaction->StatusId=='F') {
                    $checkoutModel->afterSuccessOrder($transaction);
                    $this->_redirect('checkout/onepage/success');
                    return;
                }
                else {
                    Mage::getSingleton('core/session')->addError($this->__('Invalid transaction!'));
                    $this->_redirect('checkout/cart');
                    return;
                }
            }
            else {
                if(!empty($response->Transactions[0])) {
                    $transaction = $response->Transactions[0];
                    $order = Mage::getModel('sales/order');
                    $order->loadByIncrementId($transaction->MerchantTrns);
                    $order->addStatusToHistory($order->getStatus(), $response->ErrorText);
                    $order->save();
                    $error = $response->ErrorText;
                }
                else {
                    $error = $this->__('There is an error occured during transaction. Please try again!');
                }
                Mage::getSingleton('core/session')->addError($error);
                $this->_redirect('checkout/cart');
                return;
            }
        }
        else {
            Mage::getSingleton('core/session')->addError(Mage::helper('core')->__('Trasaction is failed!'));
            $this->_redirect('checkout/cart');
            return;
        }
    }
}
