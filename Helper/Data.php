<?php
/**
 * @category Mdbhojwani
 * @package Mdbhojwani_ReorderRewards
 * @author Manish Bhojwani <manishbhojwani3@gmail.com>
 * @github https://github.com/mdbhojwani
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Mdbhojwani\ReorderRewards\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /**
     * Class constants
     */
    const XML_PATH_ENABLED = 'mdbhojwani_reorderrewards/general/enabled';
    const XML_PATH_DISCOUNT_MAPPING = 'mdbhojwani_reorderrewards/general/discount_mapping';
    const XML_PATH_COUPON_EXPIRATION = 'mdbhojwani_reorderrewards/general/expiration';
    const XML_PATH_EMAIL_TEMPLATE = 'mdbhojwani_reorderrewards/email/email_template';
    const XML_PATH_EMAIL_SENDER_NAME = 'mdbhojwani_reorderrewards/email/sender_name';
    const XML_PATH_EMAIL_SENDER_EMAIL = 'mdbhojwani_reorderrewards/email/sender_email';

    protected TransportBuilder $transportBuilder;
    protected StoreManagerInterface $storeManager;

    /**
     * @param Context $context
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getDiscountFromMapping($grandTotal)
    {
        // Example config: "0-100:5,100-500:10,500+:20"
        $mapping = $this->scopeConfig->getValue(self::XML_PATH_DISCOUNT_MAPPING, ScopeInterface::SCOPE_STORE);
        $ranges = explode(',', $mapping);

        foreach ($ranges as $range) {
            if (strpos($range, '+') !== false) {
                list($min, $amount) = explode('+', $range);
                if ($grandTotal >= trim($min)) {
                    return trim($amount);
                }
            } elseif (strpos($range, '-') !== false) {
                list($minMax, $amount) = explode(':', $range);
                list($min, $max) = explode('-', $minMax);
                if ($grandTotal >= trim($min) && $grandTotal < trim($max)) {
                    return trim($amount);
                }
            }
        }
        return 0;
    }

    /**
     * @return string
     */
    public function getCouponExpiration()
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_COUPON_EXPIRATION, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $order
     * @param $rule
     * @param $discountAmount
     * @return void
     */
    public function sendEmail($order, $rule, $discountAmount)
    {
        $emailTemplateId = $this->scopeConfig->getValue(self::XML_PATH_EMAIL_TEMPLATE, ScopeInterface::SCOPE_STORE);
        $sender = [
            'name' => $this->scopeConfig->getValue(self::XML_PATH_EMAIL_SENDER_NAME),
            'email' => $this->scopeConfig->getValue(self::XML_PATH_EMAIL_SENDER_EMAIL)
        ];

        $transport = $this->transportBuilder
            ->setTemplateIdentifier($emailTemplateId)
            ->setTemplateOptions([
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $order->getStoreId()
            ])
            ->setTemplateVars([
                'increment_id' => $order->getIncrementId(),
                'customer_name' => $order->getCustomerName(),
                'coupon_code' => $rule->getCouponCode(),
                'discount_amount' => $discountAmount,
                'expiry_date' => $rule->getToDate()
            ])
            ->setFrom($sender)
            ->addTo($order->getCustomerEmail())
            ->getTransport();

        $transport->sendMessage();
    }
}
