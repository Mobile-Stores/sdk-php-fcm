<?php
namespace Firebase;

use DateTime;
use DateTimeZone;
use Firebase\Exceptions\FcmException;
use Google_Client;

class CloudMessaging {
    private $token;
    private $url;
    private $projectName;
    
    private $to;
    private $toType;
    private $title;
    private $body;
    private $ttl;
    
    const TO_TYPE_CONDITION = "condition";
    const TO_TYPE_MULTIPLE = "multiple";
    const TO_TYPE_TOPIC = "topic";
    
    public function __construct() {
        $this->url = "https://fcm.googleapis.com/v1/projects/%s/messages:send";
    }
    
    public function send(){
        if (empty($this->token)) {
            throw new FcmException("Informe o arquivo JSON para gerar token de acesso", FcmException::TOKEN_EMPTY);
        }

        if (empty($this->projectName)) {
            throw new FcmException("Informe o nome do projeto do firebase", FcmException::PROJECT_NAME_EMPTY);
        }

        if (empty($this->to)) {
            throw new FcmException("Informe a destino da notificação", FcmException::TO_EMPTY);
        }

        if (empty($this->title)) {
            throw new FcmException("Informe o titulo da notificação", FcmException::TITLE_EMPTY);
        }

        if (empty($this->body)) {
            throw new FcmException("Informe o corpo da notificação", FcmException::BODY_EMPTY);
        }

        $data = array(
            "message" => array(
                "android" => array(                    
                    "data" => array(
                        "title" => $this->title,
                        "body" => $this->body
                    )
                ),
                "apns" => array(
                    "payload" => array(
                        "aps" => array(
                            "alert" => array(
                                "title" => $this->title,
                                "body" => $this->body
                            ),
                            "content-available" => 1,
                            "category" => "FFNotification",
                            "mutable-content" => 1
                        )
                    )
                )
            )
        );
        
        if($this->ttl){
            $dateToTll = new DateTime($ttl);
            $today = new DateTime();

            $dateToTll->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            $today->setTimezone(new DateTimeZone('America/Sao_Paulo'));

            $interval = $dateToTll->getTimestamp() - $today->getTimestamp();

            if($interval > 0){
                $data["message"]["android"]["ttl"] = $interval."s";
                $data["message"]["apns"]["headers"] = array(
                    "apns-expiration" => (string)$dateToTll->getTimestamp()
                );
            }
        }
        
        if($this->payload){
            $data["message"]["android"]["data"] = array_merge($data["message"]["android"]["data"], $this->payload);
            $data["message"]["apns"]["payload"] = array_merge($data["message"]["apns"]["payload"], $this->payload);
        }
        
        switch ($this->toType){
            case self::TO_TYPE_TOPIC:
                $data["message"]["topic"] = $this->to;                
                return $this->post($data);
            break;
        
            case self::TO_TYPE_MULTIPLE:                
                $multipleData = [];
                
                foreach($this->to as $token){
                    $data["message"]["token"] = $token;
                    $multipleData[] = $data;
                }
                                
                return $this->multiplePost($multipleData);
            break;
        
            case self::TO_TYPE_CONDITION:
                $data["message"]["condition"] = $this->to;
                return $this->post($data);
            break;
        }
                
        throw new FcmException("Destino da notificação em formato inválido", FcmException::INVALID_TO_FORMAT);
    }
    
    public function ttl($ttl){
        $this->ttl = $ttl;
        return $this;
    }
    
    public function payload(array $payload){
        $this->payload = $payload;
        return $this;
    }
    
    public function body($body){
        $this->body = $body;
        return $this;
    }
    
    public function title($title){
        $this->title = $title;
        return $this;
    }
        
    public function to($to){
        $this->to = $to;
        
        if(is_string($to)){
            $this->toType = self::TO_TYPE_TOPIC;
        }
        
        if(is_array($this->to)){
            $this->toType = self::TO_TYPE_MULTIPLE;
        }
                
        return $this;
    }
    
    public function condition($condition){
        $this->to = $condition;
        $this->toType = self::TO_TYPE_CONDITION;
        return $this;
    }
    
    public function sendToTopic($topic, $title, $body, array $payload = array(), $ttl = null){
        if (empty($this->token)) {
            throw new FcmException("Informe o arquivo JSON para gerar token de acesso", FcmException::TOKEN_EMPTY);
        }

        if (empty($this->projectName)) {
            throw new FcmException("Informe o nome do projeto do firebase", FcmException::PROJECT_NAME_EMPTY);
        }

        $data = array(
            "message" => array(
                "android" => array(                    
                    "data" => array(
                        "title" => $title,
                        "body" => $body
                    )
                ),
                "apns" => array(
                    "payload" => array(
                        "aps" => array(
                            "alert" => array(
                                "title" => $title,
                                "body" => $body
                            ),
                            "content-available" => 1,
                            "category" => "FFNotification",
                            "mutable-content" => 1
                        )
                    )
                )
            )
        );
        
        if($ttl){            
            $dateToTll = new DateTime($ttl);
            $today = new DateTime();
            
            $dateToTll->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            $today->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            
            $interval = $dateToTll->getTimestamp() - $today->getTimestamp();

            if($interval > 0){
                $data["message"]["android"]["ttl"] = $interval."s";
                $data["message"]["apns"]["headers"] = array(
                    "apns-expiration" => (string)$dateToTll->getTimestamp()
                );
            }
        }
        
        $data["message"]["android"]["data"] = array_merge($data["message"]["android"]["data"], $payload);
        $data["message"]["apns"]["payload"] = array_merge($data["message"]["apns"]["payload"], $payload);
        
        if(is_string($topic)){
            $data["message"]["topic"] = $topic;
            return $this->post($data);
        }
        
        $multipleData = [];
        
        foreach($topic as $target){
            $data["message"]["token"] = $target;
            $multipleData[] = $data;
        }
        
        return $this->multiplePost($multipleData);
    }
    
    public function setConfigJson($pathToFileJson){
        try{
            $client = new Google_Client();
            $client->setAuthConfig($pathToFileJson);
            $client->addScope("https://www.googleapis.com/auth/firebase.messaging");
            $data = $client->fetchAccessTokenWithAssertion();        
            $this->token = $data["access_token"];
        } catch (Exception $ex) {
            throw new FcmException("[Google_Client] Erro ao gerar o token: " . $ex->getMessage(), FcmException::ERROR_GENERATE_TOKEN);
        }
        
        return $this;
    }
    
    public function setProjectName($name){
        $this->projectName = $name;
        return $this;
    }
    
    public function multiplePost(array $data){
        $mh = curl_multi_init();
        
        $multiCurl = array();
        $result = array();
        
        foreach($data as $i => $post){            
            $url = \sprintf($this->url, $this->projectName);
        
            $multiCurl[$i] = curl_init();

            $header = array(
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            );

            $post = json_encode($post);

            curl_setopt($multiCurl[$i], CURLOPT_URL, $url);
            curl_setopt($multiCurl[$i], CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($multiCurl[$i], CURLOPT_POSTFIELDS, $post);
            curl_setopt($multiCurl[$i], CURLOPT_HTTPHEADER, $header);
            curl_setopt($multiCurl[$i], CURLOPT_SSLVERSION, 1);
            curl_setopt($multiCurl[$i], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($multiCurl[$i], CURLOPT_SSL_VERIFYPEER, false);

            curl_multi_add_handle($mh, $multiCurl[$i]);
        }
        
        $index = -1;
        
        do {
          
            curl_multi_exec($mh, $index);
          
        } while($index > 0);
        
        foreach($multiCurl as $k => $ch) {            
            $jsonRetorno = trim(curl_multi_getcontent($ch));
            $resposta = json_decode($jsonRetorno);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrorCode = curl_errno($ch);

            $result[$k] =  array(
                "code" => $code,
                "data" => $resposta,
                "jsonData" => $jsonRetorno,
                "error" => Helpers\Helper::curlError($curlErrorCode)
            );
            
            curl_multi_remove_handle($mh, $ch);
        }
        
        curl_multi_close($mh);
        
        return $result;
    }
    
    private function post($data){
        $url = \sprintf($this->url, $this->projectName);
        
        $ch = curl_init();
        
        $header = array(
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        );
        
        $post = json_encode($data);
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // JSON de retorno 
        $jsonRetorno = trim(curl_exec($ch));
        $resposta = json_decode($jsonRetorno);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrorCode = curl_errno($ch);

        curl_close($ch);
        
        return array(
            "code" => $code,
            "data" => $resposta,
            "jsonData" => $jsonRetorno,
            "error" => Helpers\Helper::curlError($curlErrorCode)
        );
    }   
}
