<?php
/**
 * Created by PhpStorm.
 * User: vital
 * Date: 03.02.14
 * Time: 14:05
 */

class Action
{
    public function run($path)
    {
        // self run
        $this->execute();

        // rest
        $rest = $this->getRest($path);

        if ($rest) {
            $this->runRest($rest);
        }
    }

    private function getRest($path)
    {
        $exploded = explode('/', $path, 2);

        if (count($exploded) < 2) {
            return NULL;
        }

        $rest = $exploded[1];

        return $rest;
    }

    private function runRest($rest)
    {
        $class = $this->getClass($rest);

        if (!class_exists($class)) {
            return;
        }

        $next = new $class();
        $next->run($rest);
    }

    private function getClass($path)
    {
        $exploded = explode('/', ltrim($path, '/'), 2);
        $name = $this->sanitizeName($exploded[0]);
        $class = "Action".$name;
        return $class;
    }

    private function sanitizeName($name)
    {
        $name = strtoupper(substr($name, 0, 1)) . substr($name, 1);
        $name = str_replace(".", "_", $name);
        return $name;
    }

    public function execute()
    {
        /*
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $vars = $_GET;
        } else {
            $json = file_get_contents("php://input");
            $vars = json_decode($json, TRUE);
        }

        if ($vars) {
            extract($vars);
        }
        */

        // run action
        ob_start();

        //var_dump(get_called_class());

        $response = ob_get_contents();
        ob_end_clean();

        return $response;
    }
}


class ActionApi extends Action
{
    public function execute()
    {
        var_dump(get_called_class());
    }
}


class ActionV1_1 extends Action
{
    public function execute()
    {
        var_dump(get_called_class());
    }
}


class ActionPartners extends Action
{
    public function execute()
    {
        var_dump(get_called_class());
    }
}


// http://boomstarter.ru/api/v1.1/partners/gifts/pending
// http://boomstarter.ru/api/v1.1/partners/gifts/:uuid/order
class ActionGifts extends Action
{
    public function execute($path)
    {
        var_dump(get_called_class());

        $uuid = $this->getUUID($path);

        if ($uuid) {
            $gift = new ActionGift()($uuid);
            $rest = $this->getRest($path);
            $gift->run($rest);
        }
    }

    public function getRest($path)
    {
        $exploded = explode('/', $path, 2);

        if (count($exploded) < 2) {
            return NULL;
        }

        $uuid = $exploded[0];
        $params[] = $uuid;
        $rest = $exploded[1];

        return 'gift/'.$rest;
    }
}


class Gifts extends Action
{
    public function getById($id)
    {
        // $finder;
        $child = findInMethods();
        $child = findInFolders();
        $child = findInDB();

        $child = new Gift();
        $properties = $gifts->filterById($id);
        $child = new Gift($transport, $properties);

        if ($rest) {
            $child->findRest($rest);
        }
        $child->execlute();

        return $gift;
    }
}


class Main
{
    public function run($path)
    {
        $items = explode('/', $path);

        foreach($items as $item) {
            $action = $this->findAction($item);
            $action->execute();
        }
    }

    // factory
    private function findAction($name)
    {
        $action = $this->findInMethods($name);

        if ($action) {
            return $action;
        }

        $action = $this->findInFolders($name);

        if ($action) {
            return $action;
        }

        $action = $this->findInDB($name);

        if ($action) {
            return $action;
        }

        return NULL;
    }

    private function findInMethods($id)
    {
        if (!method_exists($this, $id)) {
            return NULL;
        }

        return new MethodAction($this, $id);
    }

    private function findInFolders($id)
    {
        if (!file_exists($id)) {
            return NULL;
        }

        include($id);

        $action = new $id();

        return $action;
    }

    private function findInDB($id)
    {
        $store = new StoreJSON(STORE_FILE);
        $properties = $store->load()->filterById($id);
        $action = new Gift($properties);
        return $action;
    }
}


class MethodAction
{
    private $object = NULL;
    private $method = NULL;

    function __construct($object, $method)
    {
        $this->object = $object;
        $this->method = $method;
    }

    public function execute()
    {
        $object = $this->object;
        $method = $this->method;

        $object->$method();
    }
}


class ActionGift extends Action
{
    public function execute() {}
}


