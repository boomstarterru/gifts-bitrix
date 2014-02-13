<?php
/**
 * Библиотека для работы с подарками Boomstarter
 * Boomstarter Gifts API
 *
 * @docs https://boomstarter.ru/gifts/
 * @api http://docs.boomstarter.apiary.io/
 * @url https://github.com/boomstarterru/gifts-api
 */

namespace Boomstarter;

const API_VERSION = "1.1";
const USER_AGENT = "Boomstarter Gifts PHP library";


class Exception extends \Exception {}
class DriverException extends Exception {}


interface IHttpRequest
{
    public function setOption($name, $value);
    public function execute();
    public function getInfo($name);
    public function close();
}


/**
 * Interface IRestDriver Интерфейс для REST-драйверов
 * @package Boomstarter
 */
interface IRestDriver
{
    function put($url, $data);
    function post($url, $data);
    function get($url, $data);
    function delete($url, $data);
}


class Model
{
    public function setProperties($properties)
    {
        if (!$properties) {
            return $this;
        }

        foreach($properties as $name=>$value) {
            $this->$name = $value;
        }

        return $this;
    }
}


/**
 * Class CurlRequest
 * Драйвер для HTTP-запросов
 *
 * @package Boomstarter
 */
class HttpRequestCurl implements IHttpRequest
{
    /* @var mixed */
    private $handle = NULL;

    public function __construct($url)
    {
        $this->handle = curl_init($url);
    }

    public function setOption($name, $value)
    {
        curl_setopt($this->handle, $name, $value);
    }

    public function execute()
    {
        $response = curl_exec($this->handle);

        if ($response === FALSE) {
            $info = curl_getinfo($this->handle);
            throw new DriverException(
                'Error occurred during curl exec. Additional info: ' . json_encode($info));
        }

        $http_code = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);

        if ($http_code != 200) {
            $info = curl_getinfo($this->handle);
            throw new DriverException(
                'Unsupported request. Response code: ' . $http_code . '. Expected: 200. Additional info: ' . json_encode($info));
        }

        return $response;
    }

    public function getInfo($name)
    {
        return curl_getinfo($this->handle, $name);
    }

    public function close()
    {
        curl_close($this->handle);
    }
}


/**
 * Class CurlRequest
 * Драйвер для HTTP-запросов
 *
 * @package Boomstarter
 */
class HttpRequestStream implements IHttpRequest
{
    /* @var mixed */
    private $handle = NULL;
    /* @var string */
    private $url = NULL;
    /* @var array */
    private $options = array();

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function execute()
    {
        $options = array('http' => $this->options);
        $context = stream_context_create($options);
        $this->handle = fopen($this->url, 'rb', FALSE, $context);

        if (!$this->handle) {
            throw new DriverException('Error occurred when open stream: ' . $this->url);
        }

        $response = stream_get_contents($this->handle);

        if ($response === FALSE) {
            $info = stream_get_meta_data($this->handle);
            $this->close();
            throw new DriverException(
                'Error occurred during stream exec. Additional info: ' . json_encode($info));
        }

        $this->close();

        return $response;
    }

    public function getInfo($name)
    {
        $info = stream_get_meta_data($this->handle);
        return $info[$name];
    }

    public function close()
    {
        fclose($this->handle);
    }
}


/**
 * Class CurlDriver
 * Драйвер для работы с REST API через curl
 *
 * @package Boomstarter
 */
class RestDriverCurl implements IRestDriver
{
    const REQUEST_METHOD_GET = "GET";
    const REQUEST_METHOD_POST = "POST";
    const REQUEST_METHOD_PUT = "PUT";
    const REQUEST_METHOD_DELETE = "DELETE";

    public function getRequest($url)
    {
        return new HttpRequestCurl($url);
    }

    /**
     * Метод GET
     *
     * @param $url string
     * @param $data array
     * @return string
     * @throws \Boomstarter\DriverException
     */
    public function get($url, $data)
    {
        return $this->makeRequest(self::REQUEST_METHOD_GET, $url, $data);
    }

    /**
     * Метод POST
     *
     * @param $url string
     * @param $data array
     * @return string
     * @throws \Boomstarter\DriverException
     */
    public function post($url, $data)
    {
        return $this->makeRequest(self::REQUEST_METHOD_POST, $url, $data);
    }

    /**
     * Метод PUT
     *
     * @param $url string
     * @param $data array
     * @return string
     * @throws \Boomstarter\DriverException
     */
    public function put($url, $data)
    {
        return $this->makeRequest(self::REQUEST_METHOD_PUT, $url, $data);
    }

    /**
     * Метод DELETE
     *
     * @param $url string
     * @param $data array
     * @return string
     * @throws \Boomstarter\DriverException
     */
    public function delete($url, $data)
    {
        return $this->makeRequest(self::REQUEST_METHOD_DELETE, $url, $data);
    }

    /**
     * @param $method string
     * @param $url string
     * @param $data array
     * @return mixed
     */
    private function makeRequest($method, $url, $data)
    {
        switch ($method) {
            case self::REQUEST_METHOD_GET:
                $url = $url . '?' . http_build_query($data);
                break;
        }

        $curl = $this->getRequest($url);
        $curl->setOption(CURLOPT_RETURNTRANSFER, TRUE);
        $curl->setOption(CURLOPT_HEADER, FALSE);
        $curl->setOption(CURLOPT_USERAGENT, USER_AGENT . ' (' . API_VERSION . '; ' . get_called_class() . ')');
        $curl->setOption(CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $curl->setOption(CURLOPT_FOLLOWLOCATION, TRUE);
        $curl->setOption(CURLOPT_SSL_VERIFYHOST, 0);
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, 0);

        switch ($method) {
            case self::REQUEST_METHOD_POST:
                $curl->setOption(CURLOPT_POST, TRUE);
                $curl->setOption(CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case self::REQUEST_METHOD_DELETE:
                $curl->setOption(CURLOPT_CUSTOMREQUEST, $method);
                $curl->setOption(CURLOPT_POST, TRUE);
                $curl->setOption(CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case self::REQUEST_METHOD_PUT:
                //$curl->setOption(CURLOPT_PUT, TRUE);
                $curl->setOption(CURLOPT_CUSTOMREQUEST, $method);
                $curl->setOption(CURLOPT_POST, TRUE);
                $curl->setOption(CURLOPT_POSTFIELDS, json_encode($data));
                break;
        }

        $response = $curl->execute();

        return $response;
    }
}


/**
 * Class StreamDriver
 * Драйвер для работы с REST API через stream_get_contents
 * Для некоторых провайдеров без поддержки curl.
 * @package Boomstarter
 */
class RestDriverStream implements IRestDriver
{
    const REQUEST_METHOD_GET = "GET";
    const REQUEST_METHOD_POST = "POST";
    const REQUEST_METHOD_PUT = "PUT";
    const REQUEST_METHOD_DELETE = "DELETE";

    public function getRequest($url)
    {
        return new HttpRequestStream($url);
    }

    /**
     * Метод GET
     *
     * @param $url string
     * @param $data array
     * @return string
     * @throws \Boomstarter\DriverException
     */
    public function get($url, $data)
    {
        return $this->makeRequest(self::REQUEST_METHOD_GET, $url, $data);
    }

    /**
     * Метод POST
     *
     * @param $url string
     * @param $data array
     * @return string
     * @throws \Boomstarter\DriverException
     */
    public function post($url, $data)
    {
        return $this->makeRequest(self::REQUEST_METHOD_POST, $url, $data);
    }

    /**
     * Метод PUT
     *
     * @param $url string
     * @param $data array
     * @return string
     * @throws \Boomstarter\DriverException
     */
    public function put($url, $data)
    {
        return $this->makeRequest(self::REQUEST_METHOD_PUT, $url, $data);
    }

    /**
     * Метод DELETE
     *
     * @param $url string
     * @param $data array
     * @return string
     * @throws \Boomstarter\DriverException
     */
    public function delete($url, $data)
    {
        return $this->makeRequest(self::REQUEST_METHOD_DELETE, $url, $data);
    }

    /**
     * @param $method string
     * @param $url string
     * @param $data array
     * @return string
     */
    private function makeRequest($method, $url, $data)
    {
        switch ($method) {
            case self::REQUEST_METHOD_GET:
                $url = $url . '?' . http_build_query($data);
                break;
        }

        $stream = $this->getRequest($url);
        $stream->setOption('method', $method);
        $stream->setOption('user_agent', USER_AGENT . ' (' . API_VERSION . '; ' . get_called_class() . ')');
        $stream->setOption('header', 'Content-Type: application/json');

        switch ($method) {
            case self::REQUEST_METHOD_POST:
            case self::REQUEST_METHOD_PUT:
            case self::REQUEST_METHOD_DELETE:
                $stream->setOption('content', json_encode($data));
                break;
        }

        $response = $stream->execute();

        return $response;
    }
}


/**
 * Class RESTDriverFactory
 * Фабрика драйверов
 *
 * @package Boomstarter
 */
class RestDriverFactory
{
    /**
     * Автоматически выбирает подходящий драйвер
     *
     * @return RestDriverCurl|RestDriverStream
     */
    public static function getAutomatic()
    {
        if (function_exists('curl_exec')) {
            $driver = new RestDriverCurl();

        } elseif (in_array('http', stream_get_wrappers())) {
            $driver = new RestDriverStream();
        } else {
            // Can't detect method. Use stream as universal.
            $driver = new RestDriverStream();
        }

        return $driver;
    }

    /**
     * @return RestDriverCurl
     */
    public static function getCurl()
    {
        return new RestDriverCurl();
    }

    /**
     * @return RestDriverStream
     */
    public static function getStream()
    {
        return new RestDriverStream();
    }
}


/**
 * Class Transport
 * Вызов REST методов
 * @none Использует несколько драйверов
 *
 * @package Boomstarter
 */
class Transport
{
    const REQUEST_METHOD_GET = "GET";
    const REQUEST_METHOD_POST = "POST";
    const REQUEST_METHOD_PUT = "PUT";
    const REQUEST_METHOD_DELETE = "DELETE";

    /* @var IRestDriver */
    private $driver = NULL;
    /* @var string */
    private $shop_uuid = NULL;
    /* @var string */
    private $shop_token = NULL;
    /* @var string */
    private $api_url = 'https://boomstarter.ru/api/v1.1/partners';

    function __construct()
    {
        $this->driver = RestDriverFactory::getAutomatic();
    }

    /**
     * @param $shop_uuid string
     * @return $this
     */
    public function setShopUUID($shop_uuid)
    {
        $this->shop_uuid = $shop_uuid;
        return $this;
    }

    /**
     * @param $shop_token string
     * @return $this
     */
    public function setShopToken($shop_token)
    {
        $this->shop_token = $shop_token;
        return $this;
    }

    /**
     * @param $api_url string
     * @return $this
     */
    public function setApiUrl($api_url)
    {
        $this->api_url = $api_url;
        return $this;
    }

    /**
     * REST-метод GET
     *
     * @param $url string URL
     * @param $data array параметры
     * @return array
     */
    public function get($url, $data)
    {
        return $this->makeRequest(self::REQUEST_METHOD_GET, $url, $data);
    }

    /**
     * REST-метод POST
     *
     * @param $url string URL
     * @param $data array параметры
     * @return array
     */
    public function post($url, $data)
    {
        return $this->makeRequest(self::REQUEST_METHOD_POST, $url, $data);
    }

    /**
     * REST-метод PUT
     *
     * @param $url string URL
     * @param $data array параметры
     * @return array
     */
    public function put($url, $data)
    {
        return $this->makeRequest(self::REQUEST_METHOD_PUT, $url, $data);
    }

    /**
     * REST-метод DELETE
     *
     * @param $url string URL
     * @param $data array параметры
     * @return array
     */
    public function delete($url, $data)
    {
        return $this->makeRequest(self::REQUEST_METHOD_DELETE, $url, $data);
    }

    /**
     * Использовать Curl драйвер
     *
     * @return $this
     */
    public function useCurl()
    {
        $this->driver = RestDriverFactory::getCurl();
        return $this;
    }

    /**
     * Использовать Stream драйвер
     *
     * @return $this
     */
    public function useStream()
    {
        $this->driver = RestDriverFactory::getStream();
        return $this;
    }

    /**
     * @reserved
     * @return IRestDriver|RestDriverCurl|RestDriverStream
     */
    public function getDriver()
    {
        return $this->driver;
    }

    private function parseResponse($response)
    {
        $result = json_decode($response, TRUE);

        if (is_null($result)) {
            throw new Exception("Bad response from API server. Expected JSON. Response: {$response}");
        }

        return $result;
    }

    /**
     * @param $method string "GET"|"POST"|"PUT"|"DELETE"
     * @param $url string
     * @param $data array
     * @return array
     * @throws \Boomstarter\Exception
     */
    private function makeRequest($method, $url, $data)
    {
        // Параметры авторизации
        $data["shop_uuid"] = $this->shop_uuid;
        $data["shop_token"] = $this->shop_token;

        // Вызов метода через драйвер
        switch ($method) {
            case self::REQUEST_METHOD_GET:
                $response = $this->getDriver()->get($this->api_url . $url, $data);
                break;
            case self::REQUEST_METHOD_POST:
                $response = $this->getDriver()->post($this->api_url . $url, $data);
                break;
            case self::REQUEST_METHOD_PUT:
                $response = $this->getDriver()->put($this->api_url . $url, $data);
                break;
            case self::REQUEST_METHOD_DELETE:
                $response = $this->getDriver()->delete($this->api_url . $url, $data);
                break;
            default:
                throw new Exception("Unsupported method: " . $method);
                break;
        }

        // Обработка результата
        $result = $this->parseResponse($response);

        return $result;
    }
}


/**
 * Class GiftIterator
 * Список подарков
 *
 * @package Boomstarter
 */
class GiftIterator extends \ArrayIterator
{
    /* @var int */
    private $total_count = 0;

    /**
     * @return int количество подарков всего (доступных на сервере)
     */
    public function getTotalCount()
    {
        return $this->total_count;
    }

    /**
     * @param $total_count int Количество подарков всего (доступных на сервере)
     * @none Используется при инициализации списка.
     * @return $this
     */
    public function setTotalCount($total_count)
    {
        $this->total_count = $total_count;
        return $this;
    }

    public function asArray()
    {
        return $this->getArrayCopy();
    }

    public function getValues($key)
    {
        $values = array();

        foreach($this as $item) {
            $values[] = $item->$key;
        }

        return $values;
    }
}


class Country extends Model
{
    /* @var int */
    public $id = NULL; // 20
    /* @var string */
    public $name = ""; // "Россия"
}


class City extends Model
{
    /* @var int */
    public $id = NULL; // 49849
    /* @var string */
    public $name = ""; // "Москва"
    /* @var string */
    public $slug = ""; // "moscow-ru"
}


class Location extends Model
{
    /* @var \Boomstarter\Country */
    public $country = NULL;
    /* @var \Boomstarter\City */
    public $city = NULL;

    public function setProperties($properties)
    {
        if (!$properties) {
            return $this;
        }

        foreach($properties as $name=>$value) {

            if (strcmp($name, 'city') == 0) {

                $city = new City();
                $city->setProperties($value);
                $this->city = $city;

            } elseif (strcmp($name, 'country') == 0) {

                $country = new Country();
                $country->setProperties($value);
                $this->country = $country;

            } else {
                $this->$name = $value;
            }
        }

        return $this;
    }
}


class Owner extends Model
{
    /* @var string */
    public $email = ""; // "boomstarter@boomstarter.ru"
    /* @var string */
    public $phone = ""; // "79853867016"
    /* @var string */
    public $first_name = ""; // "Ivan"
    /* @var string */
    public $last_name = ""; // "Ivanov"
}


/**
 * Class API
 * API подарков
 *
 * @package Boomstarter
 */
class API
{
    /* @var Transport */
    private $transport = NULL;

    /**
     * @param $shop_uuid string UUID магазина. Вида: XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
     * @param $shop_token string Приватный токен. Вида: XXXXXXXXXXXXXXXXXXXXXX-X-XXXXXXXXX-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
     */
    function __construct($shop_uuid, $shop_token)
    {
        $this->transport = new Transport();
        $this->transport->setShopUUID($shop_uuid);
        $this->transport->setShopToken($shop_token);
    }

    /**
     * Возвращает список подарков
     * все с сортировкой по дате завешения сбора средств.
     *
     * @param $status NULL|'pending'|'shipping'|'delivered' Фильтр по статусу. NULL-все.
     * @param $limit int Количество. Сколько подарков вернуть за раз
     * @param $offset int Отступ. Сколько подарков пропустить с начала
     * @return GiftIterator  Возвращает массив подарков
     * @throws \Boomstarter\Exception
     */
    private function getGifts($status, $limit, $offset)
    {
        $result = new GiftIterator();
        $data = array();

        // limit, offset
        if ($limit) {
            $data['limit'] = $limit;
        }

        if ($offset) {
            $data['offset'] = $offset;
        }

        // url
        if ($status) {
            $url = '/gifts' . '/' . $status;
        } else {
            $url = '/gifts';
        }

        // request
        $response = $this->getTransport()->get($url, $data);

        if (!isset($response['gifts'])) {
            throw new Exception("No 'gifts' element in response from API server.");
        }

        $items = $response['gifts'];

        foreach($items as $item) {
            $result[] = new Gift($this->getTransport(), $item);
        }

        $result->setTotalCount($response['_metadata']['total_count']);

        return $result;
    }

    /**
     * Возвращает список подарков без фильтра по доставке. Т.е. все.
     *
     * @param int $limit int Количество. Задает лимит на количество возвращаемых элементов в ответе (максимальное значение: 250)
     * @param int $offset int Сдвиг или пропуск первых N-элементов. Формула выглядит так: limit * (page - 1), где page страница.
     * @return GiftIterator Возвращает массив подарков
     */
    public function getGiftsAll($limit=50, $offset=0)
    {
        return $this->getGifts(NULL, $limit, $offset);
    }

    /**
     * Возвращает список подарков в ожидании доставки.
     *
     * @param int $limit int Количество. Задает лимит на количество возвращаемых элементов в ответе (максимальное значение: 250)
     * @param int $offset int Сдвиг или пропуск первых N-элементов. Формула выглядит так: limit * (page - 1), где page страница.
     * @return GiftIterator Возвращает массив подарков
     */
    public function getGiftsPending($limit=50, $offset=0)
    {
        return $this->getGifts('pending', $limit, $offset);
    }

    /**
     * Возвращает список подарков со статусом "в доставке".
     *
     * @param int $limit int Количество. Задает лимит на количество возвращаемых элементов в ответе (максимальное значение: 250)
     * @param int $offset int Сдвиг или пропуск первых N-элементов. Формула выглядит так: limit * (page - 1), где page страница.
     * @return GiftIterator Возвращает массив подарков
     */
    public function getGiftsShipping($limit=50, $offset=0)
    {
        return $this->getGifts('shipping', $limit, $offset);
    }

    /**
     * Возвращает список доставленных подарков.
     *
     * @param int $limit int Количество. Задает лимит на количество возвращаемых элементов в ответе (максимальное значение: 250)
     * @param int $offset int Сдвиг или пропуск первых N-элементов. Формула выглядит так: limit * (page - 1), где page страница.
     * @return GiftIterator Возвращает массив подарков
     */
    public function getGiftsDelivered($limit=50, $offset=0)
    {
        return $this->getGifts('delivered', $limit, $offset);
    }

    /**
     * Переключиться на использование curl для HTTP-запросов.
     *
     * @return $this
     */
    public function useCurl()
    {
        $this->getTransport()->useCurl();
        return $this;
    }

    /**
     * Переключиться на использование stream_get_contents() для HTTP-запросов.
     *
     * @return $this
     */
    public function useStream()
    {
        $this->getTransport()->useStream();
        return $this;
    }

    /**
     * @reserved
     * @return Transport
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @param $gift_uuid string
     * @param $order_id string|int
     * @return Gift
     */
    public function setGiftOrder($gift_uuid, $order_id)
    {
        $gift = new Gift($this->getTransport(), array('uuid' => $gift_uuid));
        return $gift->order($order_id);
    }

    /**
     * @param $gift_uuid string
     * @param $delivery_date string|\DateTime
     * @return Gift
     */
    public function setGiftSchedule($gift_uuid, $delivery_date)
    {
        $gift = new Gift($this->getTransport(), array('uuid' => $gift_uuid));
        return $gift->schedule($delivery_date);
    }

    /**
     * @param $gift_uuid string
     * @return Gift
     */
    public function setGiftStateAccept($gift_uuid)
    {
        $gift = new Gift($this->getTransport(), array('uuid' => $gift_uuid));
        return $gift->setStateAccept();
    }

    /**
     * @param $gift_uuid string
     * @return Gift
     */
    public function setGiftStateShip($gift_uuid)
    {
        $gift = new Gift($this->getTransport(), array('uuid' => $gift_uuid));
        return $gift->setStateShip();
    }

    /**
     * @param $gift_uuid string
     * @return Gift
     */
    public function setGiftStateDelivery($gift_uuid)
    {
        $gift = new Gift($this->getTransport(), array('uuid' => $gift_uuid));
        return $gift->setStateDelivery();
    }

    /**
     * Установливает URL для REST запросов
     *
     * @param $url string URL
     * @return $this
     */
    public function setApiUrl($url)
    {
        $this->getTransport()->setApiUrl($url);
        return $this;
    }
}

/**
 * Class Gift
 * Подарок
 *
 * @package Boomstarter
 */
class Gift extends Model
{
    const STATE_ACCEPT = 'accept';
    const STATE_SHIP = 'ship';
    const STATE_DELIVERY = 'delivery';

    /* @var int */
    public $pledged = NULL;    // 690.0
    /* @var string */
    public $product_id = NULL; // 25330
    /* @var \Boomstarter\Location */
    public $location = NULL; // Location
    /* @var \Boomstarter\Owner */
    public $owner = NULL; // Owner
    /* @var string */
    public $payout_id = NULL;
    /* @var string */
    public $state = ""; // "success_funded"
    /* @var string */
    public $zipcode = NULL;
    /* @var string */
    public $comments = "";
    /* @var string */
    public $uuid = ""; // "5b6a38b7-b555-43e6-8b00-45ea924b283d"
    /* @var string */
    public $name = ""; // "Чехол ArtWizz SeeJacket Alu Anthrazit для iPhone4/4S (AZ515AT)"
    /* @var int */
    public $pledged_cents = NULL; // 69000
    /* @var string */
    public $delivery_state = ""; // "none"
    /* @var string */
    public $region = NULL;
    /* @var string */
    public $district = NULL;
    /* @var string */
    public $city = NULL;
    /* @var string */
    public $street = ""; // "awdawd"
    /* @var string */
    public $house = NULL;
    /* @var string */
    public $building = NULL;
    /* @var string */
    public $construction = NULL;
    /* @var string */
    public $apartment = NULL;
    /* @var int */
    public $order_id = NULL;
    /* @var string */
    public $delivery_date = NULL;

    /* @var Transport */
    private $transport = NULL;

    /**
     * @param $transport Transport Транспорт для вызова REST API
     * @param $properties array Массив свойств. Ключ=>значение
     * @return Gift
     */
    function __construct($transport, $properties=array())
    {
        $this->transport = $transport;
        $this->setProperties($properties);
    }

    public function setProperties($properties)
    {
        foreach($properties as $name=>$value) {

            if (strcmp($name, 'owner') == 0) {

                $owner = new Owner();
                $owner->setProperties($value);
                $this->owner = $owner;

            } elseif (strcmp($name, 'location') == 0) {

                $location = new Location();
                $location->setProperties($value);
                $this->location = $location;

            } else {
                $this->$name = $value;
            }
        }

        return $this;
    }

    /**
     * Подтверждение подарка с передачей ID-заказа магазина.
     *
     * @param $order_id string номер заказа
     * @return Gift
     */
    public function order($order_id)
    {
        $url = "/gifts/{$this->uuid}/order";

        $data = array(
            "order_id" => $order_id
        );

        $result = $this->transport->post($url, $data);

        $this->setProperties($result);

        return $this;
    }

    /**
     * Передача времени или даты доставки подарка.
     *
     * @param $delivery_date string|\DateTime Дата доставки. В любом формате поддерживаемом DateTime()
     * @return Gift
     */
    public function schedule($delivery_date)
    {
        // validate
        $datetime = $delivery_date instanceof \DateTime ? $delivery_date : new \DateTime($delivery_date);

        $url = "/gifts/{$this->uuid}/schedule";

        $data = array(
            //  ISO 8601
            "delivery_date" => $datetime->format(\DateTime::ISO8601)
        );

        $result = $this->transport->post($url, $data);

        $this->setProperties($result);

        return $this;
    }

    /**
     * Завершение доставки, клиенту вручили подарок.
     *
     * @param $delivery_state 'delivery'|'accept'|'ship' Параметр состояния доставки. (delivery - подарок доставлен)
     * @return Gift
     * @throws Exception при некорректном $delivery_state
     */
    private function setState($delivery_state)
    {
        // validate
        if ($delivery_state != self::STATE_ACCEPT && $delivery_state != self::STATE_SHIP && $delivery_state != self::STATE_DELIVERY) {
            throw new Exception("Unsupported delivery state: '{$delivery_state}'. Expected 'delivery'");
        }

        $url = "/gifts/{$this->uuid}/delivery_state";

        $data = array(
            "delivery_state" => $delivery_state
        );

        $result = $this->transport->put($url, $data);

        $this->setProperties($result);

        return $this;
    }

    /**
     * Заказ принят
     *
     * @return Gift
     */
    public function setStateAccept()
    {
        return $this->setState(self::STATE_ACCEPT);
    }

    /**
     * Ушел в доставку
     *
     * @return Gift
     */
    public function setStateShip()
    {
        return $this->setState(self::STATE_SHIP);
    }

    /**
     * Завершение доставки, клиенту вручили подарок.
     *
     * @return Gift
     */
    public function setStateDelivery()
    {
        return $this->setState(self::STATE_DELIVERY);
    }

    public function getNextState()
    {
        switch ($this->delivery_state) {
            case self::STATE_ACCEPT:
                $next_state = self::STATE_SHIP;
                break;

            case self::STATE_SHIP:
                $next_state = self::STATE_DELIVERY;
                break;

            case self::STATE_DELIVERY:
                $next_state = NULL;
                break;

            default:
                $next_state = self::STATE_ACCEPT;
                break;
        }

        return $next_state;
    }
}
