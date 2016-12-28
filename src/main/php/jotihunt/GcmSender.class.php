<?php
require_once CLASS_DIR . 'jotihunt/Gcm.class.php';

class GcmSender {
    private $googleUri = 'https://android.googleapis.com/gcm/send';
    private $googleApiKey = GOOGLE_GCM_API_KEY;
    private $proximoUser = PROXIMO_USER;
    private $proximoPass = PROXIMO_PASS;
    private $proximoHost = PROXIMO_HOST;
    private $receiverIds = array ();
    private $payload = array ();

    public function setReceiverIds($receiverIds) {
        $this->receiverIds = array ();
        foreach ( $receiverIds as $gmc ) {
            $this->receiverIds [] = $gmc->getGcmId();
        }
    }

    public function setPayload($payload = array()) {
        $this->payload = $payload;
    }

    public function send() {
        if (sizeof($this->receiverIds) == 0) {
            return 'No receiverIds, nothing to send, cancelling GCM request';
        }
        $data = array (
                'registration_ids' => $this->receiverIds 
        );
        $allData = array_merge($data, array (
                'data' => $this->payload 
        ));
        $postdata = json_encode($allData);
        
        $optsArr = array ();
        $requestHeaders = array (
                'Authorization: key=' . $this->googleApiKey,
                sprintf('Content-Length: %d', strlen($postdata)),
                'Content-type: application/json' 
        );
        
        if ($this->useProxy()) {
            $auth = base64_encode($this->proximoUser . ':' . $this->proximoPass);
            $requestHeaders = array_merge($requestHeaders, array (
                    'Proxy-Authorization: Basic ' . $auth 
            ));
            
            $optsArr = array_merge($optsArr, array (
                    'request_fulluri' => true,
                    'proxy' => 'tcp://' . $this->proximoHost 
            ));
        }
        
        $optsArr = array_merge($optsArr, array (
                'method' => 'POST',
                'header' => implode("\n", $requestHeaders),
                'content' => $postdata 
        ));
        
        $opts = array (
                'http' => $optsArr 
        );
        $context = stream_context_create($opts);
        
        $use_include_path = false;
        $result = file_get_contents($this->googleUri, $use_include_path, $context);
        
        return $result;
    }

    private function useProxy() {
        return defined('PROXIMO_ENABLED');
    }
}