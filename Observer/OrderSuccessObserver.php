<?php
/**
 * @category Mdbhojwani
 * @package Mdbhojwani_ReorderRewards
 * @author Manish Bhojwani <manishbhojwani3@gmail.com>
 * @github https://github.com/mdbhojwani
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Mdbhojwani\ReorderRewards\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Mdbhojwani\ReorderRewards\Model\CouponGenerator;
use Mdbhojwani\ReorderRewards\Helper\Data as Helper;

/**
 * Class OrderSuccessObserver
 */
class OrderSuccessObserver implements ObserverInterface
{
    protected CouponGenerator $couponGenerator;
    protected Helper $helper;

    /**
     * @param CouponGenerator $couponGenerator
     * @param Helper $helper
     */
    public function __construct(
        CouponGenerator $couponGenerator,
        Helper $helper
    ) {
        $this->couponGenerator = $couponGenerator;
        $this->helper = $helper;
    }

    /**
     * Execute Method
     * @param $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $orderTotal = $order->getGrandTotal();

        if (!$this->helper->isEnabled()) {
            return;
        }

        $discountAmount = $this->helper->getDiscountFromMapping($orderTotal);

        if ($discountAmount <= 0) {
            return;
        }

        $this->couponGenerator->generateAndEmailRule($order, $discountAmount);
    }
}
