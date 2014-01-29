<?php

class MiniServer
{
    public function actionGifts()
    {
        $gifts = $this->getGiftsFromCMS();
        return json_encode($gifts);
    }

    public function actionGiftsPending()
    {
        $gifts = $this->getGiftsFromCMS();
        return json_encode(array('gifts' => $gifts));
    }

    public function actionOrder()
    {
        var_dump("ORDER");
    }

    public function run()
    {
        $action = $this->getAction();
        $method = "action".$action;

        // find method
        if (!method_exists($this, $method)) {
            throw new \Exception("Unsupported action: ".$action);
        }

        // run action
        $response = $this->$method();

        // output
        header('Content-Type: application/json');
        echo $response;
    }

    public function getAction()
    {
        $action = '';

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (stristr($path, '/api/v1.1/partners/gifts/pending')) {
            $action = 'GiftsPending';
        } elseif (stristr($path, '/api/v1.1/partners/gifts')) {
            $action = 'Gifts';
        } elseif (preg_match("/api\/v1.1\/partners\/gifts/.*\/order/", $path)) {
                $action = 'Order';
        }

        return $action;
    }

    private function getGiftsFromCMS()
    {
        $gifts = array();

        $arSelect = array("ID", "NAME", "DETAIL_PAGE_URL", "PREVIEW_PICTURE");
        $arFilter = array("type" => 'products');
        $data = $this->dbQuery("
            SELECT b_iblock_element.*,
                   b_iblock_element_property.VALUE as PRICE
              FROM b_iblock_element
              LEFT JOIN b_iblock_element_property ON (b_iblock_element_property.IBLOCK_PROPERTY_ID = 2
					AND b_iblock_element_property.IBLOCK_ELEMENT_ID = b_iblock_element.ID)
			 WHERE b_iblock_element_property.VALUE > 0
			 LIMIT 2;
        ");

        foreach($data as $row) {
            $gifts[] = array(
                'uuid' => "uuid_".$row["ID"],
                'product_id' => $row["ID"],
                'name' => $row["NAME"],
                'pledged' => $row["PRICE"],
            );
        }

        return $gifts;
    }

    private function dbQuery($sql)
    {
        $mysqli = mysqli_connect('localhost', 'root', '');
        $mysqli->query("SET NAMES utf8");
        $mysqli->query("SET CHARACTER SET utf8");
        $mysqli->set_charset('utf8');
        $mysqli->select_db('bitrix2');
        $rows = array();

        $result = $mysqli->query($sql);

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }
}

$controller = new MiniServer();
$controller->run();
