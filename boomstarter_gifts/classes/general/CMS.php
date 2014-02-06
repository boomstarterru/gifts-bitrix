<?php

namespace Boomstarter\Gifts;

use Boomstarter\Exception;

class CMS
{
    protected static $instance = NULL;

    public static function &getInstance()
    {
        $className = get_called_class();

        if (!static::$instance) {
            static::$instance = new $className;
        }

        return static::$instance;
    }

    /**
     * @param $key
     * @return string
     */
    public function getOption($key)
    {
        throw new Exception("Not Implemented");
    }

    /**
     * @param $product_id
     * @return mixed
     */
    public function getProduct($product_id)
    {
        throw new Exception("Not Implemented");
    }

    /**
     * @param $product
     * @return float
     */
    public function getProductPrice($product)
    {
        throw new Exception("Not Implemented");
    }

    /**
     * @param $product
     * @return mixed
     */
    public function getProductCurrency($product)
    {
        throw new Exception("Not Implemented");
    }

    /**
     * @param $product
     * @return string
     */
    public function getProductName($product)
    {
        throw new Exception("Not Implemented");
    }

    /**
     *
     */
    public function clearBasket()
    {
        throw new Exception("Not Implemented");
    }

    /**
     * @param $price
     * @param $currency
     * @param $gift_uuid
     * @param string $description
     * @return mixed
     */
    public function createOrder($price, $currency, $gift_uuid, $description='')
    {
        throw new Exception("Not Implemented");
    }

    /**
     * @param $product_id
     * @param $product_name
     * @param $price
     * @param $currency
     * @return mixed
     */
    public function addToBasket($product_id, $product_name, $price, $currency)
    {
        throw new Exception("Not Implemented");
    }

    /**
     * @return mixed
     */
    public function getBoomstarterUser()
    {
        throw new Exception("Not Implemented");
    }

    /**
     * @param $order_id
     * @return mixed
     */
    public function orderBasket($order_id)
    {
        throw new Exception("Not Implemented");
    }

    /**
     * @return string
     */
    public function getShopUuid()
    {
        throw new Exception("Not Implemented");
    }

    /**
     * @return string
     */
    public function getShopToken()
    {
        throw new Exception("Not Implemented");
    }
}

