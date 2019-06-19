<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use \Bitrix\Highloadblock as HL;
use \Bitrix\Main\Application;
use \Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;

class HBAddress extends CBitrixComponent
{
    private $entityDataClass;
    protected $request;
    protected $folder;
    protected $HBFields;
    protected $HB_ID;

    public function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }

    public function executeComponent()
    {
        CModule::IncludeModule("highloadblock");
       
        try {
            $this->HB_ID = $this->getHBID($this->arParams["HB_CODE"]);
            $this->entityDataClass = $this->GetEntityDataClass();   
        } catch (Exception $e) {
            echo $e->getMessage();
            return;
        }

        $this->prepareParams();

        $this->arResult = $this->getResult();
        $this->includeComponentTemplate();
   
    }

    private function prepareParams(){

        $this->request = Application::getInstance()->getContext()->getRequest();
        $this->folder = $this->arParams["SEF_FOLDER"] ? $this->arParams["SEF_FOLDER"] : "/";
        $this->HBFields = $this->arParams["HB_FIELDS"];
        
    }

    private function getResult(){
        
        $method = $this->request->getRequestMethod();
        switch($method) {
            case "GET":
                $result = $this->getHlElements();
                break;
            case "POST":
                $result = $this->addAddress();
                break;
            case "PUT":
                $result = $this->updateAddress();
                break;
            case "DELETE":
                $result = $this->deleteAddress();
                break;
        }
        return $result;
    }

    private function getIDByURL(){
        $res = [];
        $uriString = $this->request->getRequestUri();
        $uri = new Uri($uriString);
        $url = $uri->getUri(); 
        preg_match("#^".$this->folder."([0-9_]+)/?.*#", $url, $matches);
        $res = array_reverse($matches);
        return($res[0]);
    }

    /**
     * Добавляет адрес
     */

    private function addAddress(){
        $req = $this->request->getPostList()->toArray();
        if(empty($req))
            return false;
            
        $params = [
            "UF_NAME" => $req["name"],
            "UF_ADDRESS"=> $req["address"],
            "UF_CREATE" => new DateTime(),
            "UF_UPDATE" => new DateTime()
        ];

        try {
            $result = $this->addHBElement($params);
        } catch (Exception $e) {
            return [
                "message" => $e->getMessage(),
                "type" => 'error',
            ];
        }
        return $result;
    }

    /**
     * Удаляет адрес
     */
    private function deleteAddress(){
        $id = $this->getIDByURL();
        try {
            $result = $this->deleteHBElement($id);
        } catch (Exception $e) {
            return [
                "message" => $e->getMessage(),
                "type" => 'error',
            ];
        }
        return $result;
    }

    /**
     * Изменяет адрес
     */
    private function updateAddress(){
        $id = $this->getIDByURL();
        $req = $this->getParamsFromBody();
        
        if(empty($req) || empty($id))
            return false;

        foreach($req as $key => $r){
            if(!empty($r))
                $params[$this->HBFields[$key]["code"]] = $r;
        }

        $params["UF_UPDATE"]  = new DateTime();

        try {
            $result = $this->updateHBElement($id,$params);
        } catch (Exception $e) {
            return [
                "message" => $e->getMessage(),
                "type" => 'error',
            ];
        }
        return $result;
    }

    /**
     * Получает ID хайлоад блока по коду
     * @param str $code 
     */

    private function getHBID($code){
        
        $res = false;
        if($hlblock = HL\HighloadBlockTable::getList([
            'filter' => ['=NAME' => $code]
        ])->fetch()){
            $res = $hlblock["ID"];
        }
        return $res;
    }
    
    /**
     * Получает сущность хайлоад блока
     */

    private function GetEntityDataClass() {
        if (empty($this->HB_ID) || !isset($this->HB_ID)){
            throw new Exception("Неверный HigloadBlock id");
            return;
        };
        
        $hlBlock = HL\HighloadBlockTable::getById($this->HB_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlBlock);
        $entityDataClass = $entity->getDataClass();
        return $entityDataClass;
    }

    /**
     * Изменяет элемент хайлоад блока
     * @param int $id 
     * @param array $array
     */

    private function updateHBElement($id, $params){
        if(empty($id)){
            throw new Exception("Не передан id элемента");
            return;
        }

        $result = $this->entityDataClass::update($id, $params);
        if($result->isSuccess()){
            return [
                "message" => "Элемент успешно обновлен",
            ];
        }               
        else{
            $errors = $result->getErrorMessages();
            throw new Exception($errors[0]);
        }
    }

     /**
     * Добавляет элемент хайлоад блока
     */
    
    private function addHBElement($params){
        if(empty($params)){
            throw new Exception("Не переданы параметры элемента");
            return;
        }

        $result = $this->entityDataClass::add($params);
        if($result->isSuccess()){             
            return [
                "message" => "Элемент успешно добавлен",
                "id" =>$result->getId(),
            ];
        }
        else{
            $errors = $result->getErrorMessages();
            throw new Exception($errors[0]);
        }
    }

     /**
     * Удаляет элемент из хайлоад блока
     * @param int $id 
     */

    private function deleteHBElement($id){
        if(empty($id)){
            throw new Exception("Не передан id элемента");
            return;
        }
        
        $result = $this->entityDataClass::delete($id);

        if($result->isSuccess()){
            return [
                "message" => "Элемент успешно удален",
            ];
        }               
        else{
            $errors = $result->getErrorMessages();
            throw new Exception($errors[0]);
        }
    }

    /**
     * Возвращает массив в формате json 'элементов хайлоад блока
     */

    function getHlElements()
    {
        $hlBlock = HL\HighloadBlockTable::getById($this->HB_ID)->fetch();
    
        $entity = HL\HighloadBlockTable::compileEntity($hlBlock);
        $entityDataClass = $entity->getDataClass();
    
        $rsResult = $entityDataClass::getList(array(
            "select" => ['*'],
            "filter" => [],
            "order" => ["ID" => "ASC"]
        ));
    
        $result = [];
        while ($element = $rsResult->fetch()) {
            $el["id"] = $element['ID'];
            foreach($this->HBFields as $key => $field){
                $value = $field["type"] == "date"
                ? $element[$field["code"]]->toString() 
                : $element[$field["code"]];
                $el[$key] = $value;
            }
            $result[] = $el;
        }
        return json_encode($result);
    }
    
      /**
     * Возвращает параметры запроса
     */

    private static function getParamsFromBody()
    {
        $result = [];
        $params = explode('&', file_get_contents('php://input'));
        foreach ($params as $param) {
            $item = explode('=', $param);
            if (count($item) == 2) {
                $result[urldecode($item[0])] = htmlspecialchars(urldecode($item[1]));
            }
        }
        return $result;
    }

}
