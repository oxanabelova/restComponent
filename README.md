Компонент для работы с HighloadBlock в формате JSON (REST API)


Пример вызова компонента 

$APPLICATION->IncludeComponent("zionec:addresses", ".default", array(
    "SEF_FOLDER" => "/api/",
    "HB_FIELDS" => [
        'name' => ['code' => 'UF_NAME', 'type' => 'str'],
        'address' => ['code' => 'UF_ADDRESS', 'type' => 'str'],
        'updated_at' => ['code' => 'UF_CREATE', 'type' => 'date'],
        'created_at' => ['code' => 'UF_UPDATE' ,'type' => 'date'],
    ],
    "HB_CODE" => "ADDRESS" 
));

