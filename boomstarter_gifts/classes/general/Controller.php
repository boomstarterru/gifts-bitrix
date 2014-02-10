<?php

namespace Boomstarter\Gifts;

require_once('API.php');

use Boomstarter\Exception;

class Controller
{
    public function actionList()
    {
        throw new Exception("Not Implemented");
    }

    public function actionSetStateDelivery()
    {
        throw new Exception("Not Implemented");
    }

    public function actionSetOrderId()
    {
        throw new Exception("Not Implemented");
    }

    public function actionSchedule()
    {
        throw new Exception("Not Implemented");
    }

    public function run()
    {
        $action = $this->getAction();
        $method = "action".$action;

        // find method
        if (!method_exists($this, $method)) {
            throw new Exception("Unsupported action: ".$action);
        }

        // check access
        if (!$this->checkAccess($action)) {
            $this->showAccessDenied();
            return false;
        }

        // run action
        $this->{$method}();
    }

    public function getApi()
    {
        // api load
        $shop_uuid = $this->getCMS()->getShopUuid();
        $shop_token = $this->getCMS()->getShopToken();

        $api = new \Boomstarter\API($shop_uuid, $shop_token);

        return $api;
    }

    /**
     * @return string
     */
    protected function getAction()
    {
        throw new Exception("Not Implemented");
    }

    /**
     * @param $access string
     * @return bool
     */
    protected function checkAccess($access)
    {
        throw new Exception("Not Implemented");
    }

    /**
     *
     */
    protected function showAccessDenied()
    {
        throw new Exception("Not Implemented");
    }

    /**
     * @return CMS
     */
    protected function getCMS()
    {
        throw new Exception("Not Implemented");
    }

    public function actionCron()
    {
        $api = $this->getApi();
        $gifts = $api->getGiftsPending();
        $log_messages = array();
        /* @var $cms CMS */
        $cms = $this->getCMS();

        /* @var $gift \Boomstarter\Gift */
        foreach($gifts as $gift) {

            // попустить оформленные
            if ($gift->order_id) {
                continue;
            }

            // продукт
            $product = $cms->getProduct($gift->product_id);

            $price = $cms->getProductPrice($product);
            $currency = $cms->getProductCurrency($product);
            $product_name = $cms->getProductName($product);

            // Пользователь
            $cms->getBoomstarterUser();

            // Наполнить корзину
            $cms->clearBasket();
            $cms->addToBasket($gift->product_id, $product_name, $price, $currency);

            // Создать заказ
            $order_id = $cms->createOrder(
                $price,
                $currency,
                $gift->uuid,
                "Клиент: {$gift->owner->first_name} {$gift->owner->last_name}, тел. {$gift->owner->phone}");

            // Выполнить покупку
            $cms->orderBasket($order_id);

            // Отправить код заказа
            try {
                $gift->order($order_id);
                $success = TRUE;
            } catch (Exception $e) {
                $success = FALSE;
            }

            // log
            $log_messages[] = array(
                'date' => date('Y-m-d H:i:s'),
                'action' => "order",
                'uuid' => $gift->uuid,
                'order_id' => $order_id,
                'product_id' => $gift->product_id,
                'product_name' => $gift->name,
                'success' => $success,
            );
        }

        // show log
        echo json_encode($log_messages);
    }
}
