<?php

namespace CoreShop\Bundle\TrackingBundle;

use CoreShop\Component\Order\Model\CartInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Model\PurchasableInterface;

interface TrackerInterface
{
    /**
     * @param PurchasableInterface $product
     */
    public function trackPurchasableView(PurchasableInterface $product): void;

    /**
     * @param PurchasableInterface $product
     */
    public function trackPurchasableImpression(PurchasableInterface $product): void;

    /**
     * @param PurchasableInterface $product
     * @param int $quantity
     */
    public function trackPurchasableActionAdd(PurchasableInterface $product, $quantity = 1): void;

    /**
     * @param PurchasableInterface $product
     * @param int $quantity
     */
    public function trackPurchasableActionRemove(PurchasableInterface $product, $quantity = 1): void;

    /**
     * @param CartInterface $cart
     * @param null $stepNumber
     * @param null $checkoutOption
     */
    public function trackCheckout(CartInterface $cart, $stepNumber = null, $checkoutOption = null): void;

    /**
     * @param CartInterface $cart
     * @param null $stepNumber
     * @param null $checkoutOption
     */
    public function trackCheckoutStep(CartInterface $cart, $stepNumber = null, $checkoutOption = null): void;

    /**
     * @param CartInterface $cart
     * @param null $stepNumber
     * @param null $checkoutOption
     */
    public function trackCheckoutAction(CartInterface $cart, $stepNumber = null, $checkoutOption = null): void;

    /**
     * @param OrderInterface $order
     */
    public function trackCheckoutComplete(OrderInterface $order): void;
}