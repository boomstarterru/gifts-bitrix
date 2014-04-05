<?php

define('STORE_FILE', '/tmp/bitrix.json');
define('STORE_YML', 'http://bitrix2.local/bitrix/catalog_export/yandex_430247.php');


/**
 * Class MiniServer
 * REST протокол
 */
class MiniServer
{
    public function run()
    {
        $path = $this->getPath();
        $rest = $this->getRest($path);

        try {
            /* @var $api API */
            $api = new MiniAPI($rest);
            $output = $api->processAPI();
        } catch (Exception $e) {
            $output = json_encode(Array('error' => $e->getMessage()));
        }

        // output
        echo $output;
    }

    private function getPath()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return $path;
    }

    private function getRest($path)
    {
        $base = '/api/v1.1/partners/';

        if (!stristr($path, $base)) {
            throw new Exception('Unsupported base: '.$path);
        }

        $rest = substr($path, strlen($base));

        return $rest;
    }
}


abstract class API
{
    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';
    /**
     * Property: endpoint
     * The Model requested in the URI. eg: /files
     */
    protected $endpoint = '';
    /**
     * Property: verb
     * An optional additional descriptor about the endpoint, used for things that can
     * not be handled by the basic methods. eg: /files/process
     */
    protected $verb = '';
    /**
     * Property: args
     * Any additional URI components after the endpoint and verb have been removed, in our
     * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>
     */
    protected $args = Array();
    /**
     * Property: file
     * Stores the input of the PUT request
     */
    protected $file = Null;

    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     */
    public function __construct($request)
    {
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");

        $this->args = explode('/', rtrim($request, '/'));
        $this->endpoint = array_shift($this->args);

        if ($this->args && method_exists($this, $this->args[count($this->args)-1])) {
            $this->verb = array_pop($this->args);
        }

        $this->method = $_SERVER['REQUEST_METHOD'];

        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }

        switch($this->method) {
            case 'DELETE':
            case 'POST':
                $this->request = $this->_cleanInputs($_POST);
                $this->file = file_get_contents("php://input");
                break;
            case 'GET':
                $this->request = $this->_cleanInputs($_GET);
                break;
            case 'PUT':
                $this->request = $this->_cleanInputs($_GET);
                $this->file = file_get_contents("php://input");
                break;
            default:
                $this->_response('Invalid Method', 405);
                break;
        }
    }

    public function processAPI()
    {
        if (method_exists($this, $this->endpoint)) {
            return $this->_response($this->{$this->endpoint}($this->args));
        }
        return $this->_response("No Endpoint: ". $this->endpoint, 404);
    }

    private function _response($data, $status = 200)
    {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        return json_encode($data);
    }

    private function _cleanInputs($data)
    {
        $clean_input = Array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }
        return $clean_input;
    }

    private function _requestStatus($code)
    {
        $status = array(
            200 => 'OK',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );
        return ($status[$code])?$status[$code]:$status[500];
    }
}


class MiniAPI extends API
{
    protected function gifts($args)
    {
        if ($this->verb) {
            return $this->{$this->verb}($this->args);
        }

        // все подарки
        return $this->all($this->args);
    }

    protected function all($args)
    {
        $store = $this->getStore();
        $gifts = $store->load();

        $package = array(
            'gifts' => $gifts,
            '_metadata' => array(
                'total_count' => count($gifts)
            ),
        );

        return $package;
    }

    protected function shipping($args)
    {
        $store = new StoreJSON(STORE_FILE);
        $gifts = $store->load();

        $package = array(
            'gifts' => $gifts,
            '_metadata' => array(
                'total_count' => count($gifts)
            ),
        );

        return $package;
    }

    protected function pending($args)
    {
        $store = $this->getStore();
        $gifts = $store->load();

        $package = array(
            'gifts' => $gifts,
            '_metadata' => array(
                'total_count' => count($gifts)
            ),
        );

        return $package;
    }

    protected function delivered($args)
    {
        $store = new StoreJSON(STORE_FILE);
        $gifts = $store->load();

        $package = array(
            'gifts' => $gifts,
            '_metadata' => array(
                'total_count' => count($gifts)
            ),
        );

        return $package;
    }

    protected function order($args)
    {
        $id = $args[0];
        $order_id = json_decode($this->file, TRUE)['order_id'];

        $store = $this->getStore();
        $gifts = $store->load();

        // find gift
        foreach($gifts as &$gift) {
            if ($gift['uuid'] != $id) {
                continue;
            }

            $gift['order_id'] = $order_id;

            $store->save($gifts);

            $package = $gift;

            return $package;
            break;
        }
    }

    protected function delivery_state($args)
    {
        $id = $args[0];
        $delivery_state = json_decode($this->file, TRUE)['delivery_state'];

        $store = new StoreJSON(STORE_FILE);
        $gifts = $store->load();

        // find gift
        foreach($gifts as &$gift) {
            if ($gift['uuid'] != $id) {
                continue;
            }

            $gift['delivery_state'] = $delivery_state;

            $store->save($gifts);

            $package = $gift;

            return $package;
            break;
        }
    }

    protected function reset($args)
    {
        $converter = new ConverterBitrix();
        $converter->convert();
    }

    private function getStore()
    {
        //$store = new StoreJSON(STORE_FILE);
        $store = new StoreYML(STORE_YML);
        return $store;
    }
}


class StoreJSON
{
    private $filename = '';

    function __construct($filename)
    {
        $this->filename = $filename;
    }

    public function save($mixed)
    {
        $dir = dirname($this->filename);

        if (!file_exists($dir)) {
            mkdir($dir, 0777,  TRUE);
        }

        $json = json_encode($mixed);
        file_put_contents($this->filename, $json);
    }

    public function load()
    {
        $json = file_get_contents($this->filename);
        $mixed = json_decode($json, TRUE);
        return $mixed;
    }
}


class StoreYML
{
    private $url = '';

    function __construct($url)
    {
        $this->url = $url;
    }

    public function save($mixed)
    {
    }

    public function load()
    {
        $gifts = array();

        $yml = file_get_contents($this->url);
        $xml = simplexml_load_string($yml);

        foreach($xml->shop->offers->offer as $offer) {
            $gifts[] = array(
                'uuid' => "uuid_".$offer['id'],
                'product_id' => strval($offer['id']),
                'name' => strval($offer->name),
                'pledged' => strval($offer->price),
                'url' => strval($offer->url),
                'image' => strval($offer->picture),
                'owner' => array(
                    'first_name' => 'Тестер',
                    'last_name' => 'Тестеров',
                    'phone' => '8-123-456-78-90',
                ),
            );
        }

        return $gifts;
    }
}


class ConverterBitrix
{
    public function convert()
    {
        // load
        $fetcher = new FetcherBitrix();
        $products = $fetcher->getProducts();

        // convert
        $gifts = $this->buildGifts($products);

        // save
        $store = new StoreJSON(STORE_FILE);
        $store->save($gifts);
    }

    private function buildGifts($products)
    {
        $gifts = array();

        foreach($products as $product) {
            $gifts[] = array(
                'uuid' => "uuid_".$product["id"],
                'product_id' => $product["id"],
                'name' => $product["name"],
                'pledged' => $product["price"],
                'owner' => array(
                    'first_name' => 'Тестер',
                    'last_name' => 'Тестеров',
                    'phone' => '8-123-456-78-90',
                ),
            );
        }

        return $gifts;
    }
}


class FetcherBitrix
{
    public function getProducts()
    {
        $db = new DB('localhost', 'root', '', 'bitrix2');
        $products = array();

        $rows = $db->query("
            SELECT b_iblock_element.*,
                   b_iblock_element_property.VALUE as PRICE
              FROM b_iblock_element
              LEFT JOIN b_iblock_element_property ON (b_iblock_element_property.IBLOCK_PROPERTY_ID = 2
					AND b_iblock_element_property.IBLOCK_ELEMENT_ID = b_iblock_element.ID)
			 WHERE b_iblock_element_property.VALUE > 0
			 LIMIT 2;
        ");

        foreach($rows as $row) {
            $products[] = array(
                'id' => $row["ID"],
                'name' => $row["NAME"],
                'price' => $row["PRICE"],
                );
        }

        return $products;
    }
}


class DB
{
    var $host = '';
    var $login = '';
    var $password = '';
    var $database = '';

    function __construct($host, $login, $password, $database)
    {
        $this->host = $host;
        $this->login = $login;
        $this->password = $password;
        $this->database = $database;
    }

    public function query($sql)
    {
        $mysqli = mysqli_connect($this->host, $this->login, $this->password);
        $mysqli->query("SET NAMES utf8");
        $mysqli->query("SET CHARACTER SET utf8");
        $mysqli->set_charset('utf8');
        $mysqli->select_db($this->database);
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
