<?php

namespace Oxapay\Woocommerce;

final class PaymentStatus
{
    const PAYMENT_STATUS_Waiting = 'Waiting';
    const PAYMENT_STATUS_Confirming = 'Paying';
    const PAYMENT_STATUS_PAID = 'Paid';
    const PAYMENT_STATUS_FAIL = 'Failed';   
    const PAYMENT_STATUS_Expired = 'Expired';

    const WC_STATUS_REFUNDED = 'refunded';
    const WC_STATUS_PENDING = 'pending';
    const WC_STATUS_PROCESSING = 'processing';
    const WC_STATUS_COMPLETED = 'completed';
    const WC_STATUS_FAIL = 'failed';

    /**
     * @param $status
     * @return string
     */
    public static function convertToWoocommerceStatus($status)
    {
        switch ($status) {
            case self::PAYMENT_STATUS_Waiting:
                $result = self::WC_STATUS_PENDING;
                break;

            case self::PAYMENT_STATUS_Confirming:
                $result = self::WC_STATUS_PROCESSING;
                break;

            case self::PAYMENT_STATUS_FAIL:
            case self::PAYMENT_STATUS_Expired:
                $result = self::WC_STATUS_FAIL;
                break;
            case self::PAYMENT_STATUS_PAID:
                $result = self::WC_STATUS_COMPLETED;
                break;
        }
        return $result;
    }

}
