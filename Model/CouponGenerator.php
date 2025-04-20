<?php
/**
 * @category Mdbhojwani
 * @package Mdbhojwani_ReorderRewards
 * @author Manish Bhojwani <manishbhojwani3@gmail.com>
 * @github https://github.com/mdbhojwani
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Mdbhojwani\ReorderRewards\Model;

use Magento\SalesRule\Model\RuleFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Mdbhojwani\ReorderRewards\Helper\Data as Helper;

/**
 * Class CouponGenerator
 */
class CouponGenerator
{
    protected RuleFactory $ruleFactory;
    protected DateTime $dateTime;
    protected Helper $helper;

    /**
     * @param RuleFactory $ruleFactory
     * @param DateTime $dateTime
     * @param Helper $helper
     */
    public function __construct(
        RuleFactory $ruleFactory,
        DateTime $dateTime,
        Helper $helper
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->dateTime = $dateTime;
        $this->helper = $helper;
    }

    /**
     * Generate and Email Rule 
     * @param $order
     * @param $discountAmount
     * @return void
     */
    public function generateAndEmailRule($order, $discountAmount)
    {
        $rule = $this->ruleFactory->create();
        $rule->setName('Reorder Reward - ' . $order->getIncrementId())
             ->setDescription('Reward for reorder')
             ->setFromDate($this->dateTime->gmtDate())
             ->setToDate($this->dateTime->gmtDate('Y-m-d', strtotime($this->helper->getCouponExpiration())))
             ->setUsesPerCustomer(1)
             ->setCustomerGroupIds([$order->getCustomerGroupId()])
             ->setIsActive(1)
             ->setSimpleAction('by_fixed')
             ->setDiscountAmount($discountAmount)
             ->setCouponType(2)
             ->setCouponCode(strtoupper('REORDER-' . bin2hex(random_bytes(4))))
             ->setWebsiteIds([$order->getStore()->getWebsiteId()])
             ->setDiscountQty(1)
             ->setTimesUsed(1)
             ->setStopRulesProcessing(0)
             ->save();

        $this->helper->sendEmail($order, $rule, $discountAmount);
    }
}
