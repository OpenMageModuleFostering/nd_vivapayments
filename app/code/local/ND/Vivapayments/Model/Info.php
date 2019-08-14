<?php 
class ND_Vivapayments_Model_Info
{
    /**
     * Cross-models public exchange keys
     *
     * @var string
     */
    const MERCHANT_ID          = 'MerchantId';
    const STATUS_ID            = 'StatusId';    
    const SOURCE_CODE            = 'SourceCode';
    const TRANSACTION_ID        = 'TransactionId';
    const MERCH_TXN_REF         = 'MerchantTrns';
    const ORDER_CODE              = 'OrderCode';
    const TRANSACTION_TYPE_ID      = 'TransactionTypeId';
    const TRANSACTION_TYPE_MESSAGE = 'TransactionTypeMessage';
    const CREDIT_CARD_NUMBER = 'CcNumber';
    const ISSUING_BANK = 'IssuingBank';
    const CREDIT_CARD_HOLDER = 'CardHolderName';
    const CREDIT_CARD_TYPE = 'CardType';

    /**
     * All payment information map
     *
     * @var array
     */
    protected $_paymentMap = array(
        self::MERCHANT_ID          => 'MerchantId',
        self::STATUS_ID            => 'StatusId',
        self::SOURCE_CODE            => 'SourceCode',
        self::TRANSACTION_ID        => 'TransactionId',
        self::MERCH_TXN_REF         => 'MerchantTrns',
        self::ORDER_CODE              => 'OrderCode',
        self::TRANSACTION_TYPE_ID      => 'TransactionTypeId',
        self::TRANSACTION_TYPE_MESSAGE      => 'TransactionTypeMessage',
        self::CREDIT_CARD_NUMBER => 'CcNumber',
        self::ISSUING_BANK => 'IssuingBank',
        self::CREDIT_CARD_HOLDER => 'CardHolderName',
        self::CREDIT_CARD_TYPE => 'CardType'
    );
    
    /**
     * Viva payment status possible values
     *
     * @var string
     */
    const PAYMENTSTATUS_NONE         = 'none';
    const PAYMENTSTATUS_PENDING      = 'pending';
    const PAYMENTSTATUS_ACCEPTED     = 'accepted';
    const PAYMENTSTATUS_REJECTED     = 'rejected';
    const PAYMENTSTATUS_REVIEWED     = 'reviewed';
    const PAYMENTSTATUS_NOTCHECKED   = 'not_checked';
    const PAYMENTSTATUS_SYSREJECT    = 'system_rejected';

    /**
     * Map of payment information available to customer
     *
     * @var array
     */
    protected $_paymentPublicMap = array(
        //'',
    );

    /**
     * Rendered payment map cache
     *
     * @var array
     */
    protected $_paymentMapFull = array();

    /**
     * All available payment info getter
     *
     * @param Mage_Payment_Model_Info $payment
     * @param bool $labelValuesOnly
     * @return array
     */
    public function getPaymentInfo(Mage_Payment_Model_Info $payment, $labelValuesOnly = false)
    {
        // collect Viva-specific info
        $result = $this->_getFullInfo(array_values($this->_paymentMap), $payment, $labelValuesOnly);

        // add last_trans_id
        $label = Mage::helper('payment')->__('Last Transaction ID');
        $value = $payment->getLastTransId();
        if ($labelValuesOnly) {
            $result[$label] = $value;
        } else {
            $result['last_trans_id'] = array('label' => $label, 'value' => $value);
        }

        return $result;
    }

    /**
     * Public payment info getter
     *
     * @param Mage_Payment_Model_Info $payment
     * @param bool $labelValuesOnly
     * @return array
     */
    public function getPublicPaymentInfo(Mage_Payment_Model_Info $payment, $labelValuesOnly = false)
    {
        return $this->_getFullInfo($this->_paymentPublicMap, $payment, $labelValuesOnly);
    }

    /**
     * Grab data from source and map it into payment
     *
     * @param array|Varien_Object|callback $from
     * @param Mage_Payment_Model_Info $payment
     */
    public function importToPayment($from, Mage_Payment_Model_Info $payment)
    {
        $fullMap = array_merge($this->_paymentMap, $this->_systemMap);
        if (is_object($from)) {
            $from = array($from, 'getDataUsingMethod');
        }
        Varien_Object_Mapper::accumulateByMap($from, array($payment, 'setAdditionalInformation'), $fullMap);
    }

    /**
     * Grab data from payment and map it into target
     *
     * @param Mage_Payment_Model_Info $payment
     * @param array|Varien_Object|callback $to
     * @param array $map
     * @return array|Varien_Object
     */
    public function &exportFromPayment(Mage_Payment_Model_Info $payment, $to, array $map = null)
    {
        $fullMap = array_merge($this->_paymentMap, $this->_systemMap);
        Varien_Object_Mapper::accumulateByMap(array($payment, 'getAdditionalInformation'), $to,
            $map ? $map : array_flip($fullMap)
        );
        return $to;
    }

    /**
     * Check whether the payment is in review state
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    public static function isPaymentReviewRequired(Mage_Payment_Model_Info $payment)
    {
        $paymentStatus = $payment->getAdditionalInformation(self::PAYMENT_STATUS_GLOBAL);
        if (self::PAYMENTSTATUS_PENDING === $paymentStatus) {
            $pendingReason = $payment->getAdditionalInformation(self::PENDING_REASON_GLOBAL);
            return !in_array($pendingReason, array('authorization', 'order'));
        }
        return false;
    }

    /**
     * Check whether fraud order review detected and can be reviewed
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    public static function isFraudReviewAllowed(Mage_Payment_Model_Info $payment)
    {
        return self::isPaymentReviewRequired($payment)
            && 1 == $payment->getAdditionalInformation(self::IS_FRAUD_GLOBAL);
    }

    /**
     * Check whether the payment is completed
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    public static function isPaymentCompleted(Mage_Payment_Model_Info $payment)
    {
        $paymentStatus = $payment->getAdditionalInformation(self::PAYMENT_STATUS_GLOBAL);
        return self::PAYMENTSTATUS_COMPLETED === $paymentStatus;
    }

    /**
     * Check whether the payment was processed successfully
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    public static function isPaymentSuccessful(Mage_Payment_Model_Info $payment)
    {
        $paymentStatus = $payment->getAdditionalInformation(self::PAYMENT_STATUS_GLOBAL);
        if (in_array($paymentStatus, array(
            self::PAYMENTSTATUS_COMPLETED, self::PAYMENTSTATUS_INPROGRESS, self::PAYMENTSTATUS_REFUNDED,
            self::PAYMENTSTATUS_REFUNDEDPART, self::PAYMENTSTATUS_UNREVERSED, self::PAYMENTSTATUS_PROCESSED,
        ))) {
            return true;
        }
        $pendingReason = $payment->getAdditionalInformation(self::PENDING_REASON_GLOBAL);
        return self::PAYMENTSTATUS_PENDING === $paymentStatus
            && in_array($pendingReason, array('authorization', 'order'));
    }

    /**
     * Check whether the payment was processed unsuccessfully or failed
     *
     * @param Mage_Payment_Model_Info $payment
     * @return bool
     */
    public static function isPaymentFailed(Mage_Payment_Model_Info $payment)
    {
        $paymentStatus = $payment->getAdditionalInformation(self::PAYMENT_STATUS_GLOBAL);
        return in_array($paymentStatus, array(
            self::PAYMENTSTATUS_REJECTED
        ));
    }

    /**
     * Explain pending payment reason code
     *
     * @param string $code
     * @return string
     */
    public static function explainPendingReason($code)
    {
        switch ($code) {
            case 'address':
                return Mage::helper('payment')->__('Customer did not include a confirmed address.');
            case 'authorization':
            case 'order':
                return Mage::helper('payment')->__('The payment is authorized but not settled.');
            case 'echeck':
                return Mage::helper('payment')->__('The payment eCheck is not yet cleared.');
            case 'intl':
                return Mage::helper('payment')->__('Merchant holds a non-U.S. account and does not have a withdrawal mechanism.');
            case 'multi-currency': // break is intentionally omitted
            case 'multi_currency': // break is intentionally omitted
            case 'multicurrency':
                return Mage::helper('payment')->__('The payment curency does not match any of the merchant\'s balances currency.');
            case 'paymentreview':
                return Mage::helper('payment')->__('The payment is pending while it is being reviewed by Viva for risk.');
            case 'unilateral':
                return Mage::helper('payment')->__('The payment is pending because it was made to an email address that is not yet registered or confirmed.');
            case 'verify':
                return Mage::helper('payment')->__('The merchant account is not yet verified.');
            case 'upgrade':
                return Mage::helper('payment')->__('The payment was made via credit card. In order to receive funds merchant must upgrade account to Business or Premier status.');
            case 'none': // break is intentionally omitted
            case 'other': // break is intentionally omitted
            default:
                return Mage::helper('payment')->__('Unknown reason. Please contact Viva customer service.');
        }
    }

    /**
     * Explain the refund or chargeback reason code
     *
     * @param $code
     * @return string
     */
    public static function explainReasonCode($code)
    {
        switch ($code) {
            case 'chargeback':
                return Mage::helper('payment')->__('Chargeback by customer.');
            case 'guarantee':
                return Mage::helper('payment')->__('Customer triggered a money-back guarantee.');
            case 'buyer-complaint':
                return Mage::helper('payment')->__('Customer complaint.');
            case 'refund':
                return Mage::helper('payment')->__('Refund issued by merchant.');
            case 'adjustment_reversal':
                return Mage::helper('payment')->__('Reversal of an adjustment.');
            case 'chargeback_reimbursement':
                return Mage::helper('payment')->__('Reimbursement for a chargeback.');
            case 'chargeback_settlement':
                return Mage::helper('payment')->__('Settlement of a chargeback.');
            case 'none': // break is intentionally omitted
            case 'other':
            default:
                return Mage::helper('payment')->__('Unknown reason. Please contact Viva customer service.');
        }
    }

    /**
     * Whether a reversal/refund can be disputed with Viva
     *
     * @param string $code
     * @return bool;
     */
    public static function isReversalDisputable($code)
    {
        switch ($code) {
            case 'none':
            case 'other':
            case 'chargeback':
            case 'buyer-complaint':
            case 'adjustment_reversal':
                return true;
            case 'guarantee':
            case 'refund':
            case 'chargeback_reimbursement':
            case 'chargeback_settlement':
            default:
                return false;
        }
    }

    /**
     * Render info item
     *
     * @param array $keys
     * @param Mage_Payment_Model_Info $payment
     * @param bool $labelValuesOnly
     */
    protected function _getFullInfo(array $keys, Mage_Payment_Model_Info $payment, $labelValuesOnly)
    {
        $result = array();
        foreach ($keys as $key) {
            if (!isset($this->_paymentMapFull[$key])) {
                $this->_paymentMapFull[$key] = array();
            }
            if (!isset($this->_paymentMapFull[$key]['label'])) {
                if (!$payment->hasAdditionalInformation($key)) {
                    $this->_paymentMapFull[$key]['label'] = false;
                    $this->_paymentMapFull[$key]['value'] = false;
                } else {
                    $value = $payment->getAdditionalInformation($key);
                    $this->_paymentMapFull[$key]['label'] = $this->_getLabel($key);
                    $this->_paymentMapFull[$key]['value'] = $value;
                    //$this->_paymentMapFull[$key]['value'] = $this->_getValue($value, $key);
                }
            }
            if (!empty($this->_paymentMapFull[$key]['value'])) {
                if ($labelValuesOnly) {
                    $result[$this->_paymentMapFull[$key]['label']] = $this->_paymentMapFull[$key]['value'];
                } else {
                    $result[$key] = $this->_paymentMapFull[$key];
                }
            }
        }
        return $result;
    }

    /**
     * Render info item labels
     *
     * @param string $key
     */
    public function _getLabel($key)
    {
        switch ($key) {
            case 'MerchantId':
                return Mage::helper('payment')->__('Merchant Id');
            case 'StatusId':
                return Mage::helper('payment')->__('Status Id');
            case 'SourceCode':
                return Mage::helper('payment')->__('Source Code');
            case 'TransactionId':
                return Mage::helper('payment')->__('Transaction Id');
            case 'MerchantTrns':
                return Mage::helper('payment')->__('Merchant Transaction Reference');
            case 'OrderCode':
                return Mage::helper('payment')->__('Order Code');
            case 'TransactionTypeId':
                return Mage::helper('payment')->__('Transaction Type Id');
            case 'TransactionTypeMessage':
                return Mage::helper('payment')->__('Transaction Type Message');
            case 'CcNumber':
                return Mage::helper('payment')->__('Credit Card Number');
            case 'IssuingBank':
                return Mage::helper('payment')->__('Issuing Bank');
            case 'CardHolderName':
                return Mage::helper('payment')->__('Card Holder Name');
            case 'CardType':
                return Mage::helper('payment')->__('Card Type');
        }
        return '';
    }

    /**
     * Apply a filter upon value getting
     *
     * @param string $value
     * @param string $key
     * @return string
     */
    public function _getValue($value, $key)
    {
        $label = '';
        switch ($key) {
            case 'vpc_avs_code':
                $label = $this->_getAvsLabel($value);
                break;
            case 'vpc_cvv2_match':
                $label = $this->_getCvv2Label($value);
                break;
            default:
                return $value;
        }
        return sprintf('#%s%s', $value, $value == $label ? '' : ': ' . $label);
    }

    /**
     * Attempt to convert StatusId check result code into label
     *     
     * @param string $value
     * @return string
     */
    public function _getStatusIdLabel($value)
    {
        switch ($value) {
            case 'E':
                return Mage::helper('payment')->__('The transaction was not completed because of an error');
            case 'A': // international "A"
                return Mage::helper('payment')->__('The transaction is in progress');
            case 'M':
                return Mage::helper('payment')->__('The cardholder has disputed the transaction with the issuing Bank');
            case 'MA': // international "N"
                return Mage::helper('payment')->__('Dispute Awaiting Response');
            case 'MI':
                return Mage::helper('payment')->__('Dispute in Progress');
            case 'ML': // international "X"
                return Mage::helper('payment')->__('A disputed transaction has been refunded (Dispute Lost)');
            case 'MW': // UK-specific "X"
                return Mage::helper('payment')->__('Dispute Won');
            case 'MS':
                return Mage::helper('payment')->__('Suspected Dispute');
            case 'X':
                return Mage::helper('payment')->__('The transaction was cancelled by the merchant');
            case 'R':
                return Mage::helper('payment')->__('The transaction has been fully or partially refunded');
            case 'F':
                return Mage::helper('payment')->__('The transaction has been completed successfully');
            default:
                return $value;
        }
    }

    /**
     * Attempt to convert TransactionTypeId check result code into label
     *     
     * @param string $value
     * @return string
     */
    public function _getTransactionTypeIdLabel($value)
    {
        switch ($value) {
            case '0':
                return Mage::helper('payment')->__('Capture - A Capture event of a preAuthorized transaction');
            case '1':
                return Mage::helper('payment')->__('PreAuth - Authorization hold');
            case '3':
                return Mage::helper('payment')->__('UpdateTaxCard - Tax Card receipt transaction');
            case '4':
                return Mage::helper('payment')->__('RefundCard - Refund transaction');
            case '5':
                return Mage::helper('payment')->__('ChargeCard - Card payment transaction');
            case '6':
                return Mage::helper('payment')->__('Installments - A card payment that will be done with installments');
            case '7':
                return Mage::helper('payment')->__('Void - A cancelled transaction');
            case '13':
                return Mage::helper('payment')->__('Claim Refund - Refund transaction for a claimed transaction');
            case '15':
                return Mage::helper('payment')->__('Dias - Payment made through the DIAS system');
            case '16':
                return Mage::helper('payment')->__('PaymentFromReseller - Cash Payments, through the Viva Payments Authorised Resellers Network');
            case '18':
                return Mage::helper('payment')->__('RefundInstallments - A Refunded installment');
            case '19':
                return Mage::helper('payment')->__('Clearance - Clearance of a transactions batch');
            case '22':
                return Mage::helper('payment')->__('ReverseTaxCard - Refund previous tax card transaction');
            case '24':
                return Mage::helper('payment')->__('BankTranfer - Bank Transfer command from the merchant\'s wallet to their IBAN');
            default:
                return $value;
        }
    }
}
