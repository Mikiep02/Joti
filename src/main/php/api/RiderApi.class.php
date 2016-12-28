<?php
require_once CLASS_DIR . 'api/ApiException.class.php';

require_once CLASS_DIR . 'datastore/Datastore.class.php';
require_once CLASS_DIR . 'jotihunt/VossenTeam.class.php';
require_once CLASS_DIR . 'jotihunt/Location.class.php';
require_once CLASS_DIR . 'jotihunt/Rider.class.php';
require_once CLASS_DIR . 'jotihunt/RiderLocation.class.php';

require_once CLASS_DIR . 'jotihunt/Gcm.class.php';
require_once CLASS_DIR . 'jotihunt/GcmSender.class.php';

class RiderApi {
    private $request;
    private $apiParts;
    private $riderTeamName;
    private $riderTeam;
    private $siteDriver;
    private $operation;
    private $includeAll = false;

    public function setRequest($request) {
        $this->request = $request;
    }

    private function init() {
        // Check for size of ApiParts (need at least 1, the name)
        $apiParts = $this->request->getApiParts();
        $riderTeamName = array_shift($apiParts);
        $this->apiParts = $apiParts;
        
        $this->siteDriver = Datastore::getSiteDriver();
        
        error_log("riderTeamName=".$riderTeamName);
        if ('me' === $riderTeamName) {
            error_log("getAuthCode=".$this->request->getAuthCode());
            $user = $this->siteDriver->getUser($this->request->getAuthCode());
            error_log("user:");
            //var_dump($user->toArray());
            $this->riderTeam = $this->siteDriver->getRiderByName($user->getUsername());
            error_log("riderTeam=");
            //var_dump($this->riderTeam);
        } else if (null != $riderTeamName) {
            $this->riderTeam = $this->siteDriver->getRiderByName($riderTeamName);
        }
        
        if (count($this->apiParts) > 0) {
            $this->operation = array_shift($this->apiParts);
            if ('all' == $this->operation) {
                $this->includeAll = true;
            }
        }
    }

    public function doGet() {
        global $authMgr;
        $this->init();
        
        if (null == $this->riderTeam) {
            // no team specified, return all
            $result = $this->siteDriver->getAllRiders();
            $locations = $this->siteDriver->getLastRiderLocations();
            $returnVal = array ();
            foreach ( $result as $rider ) {
                $riderInfo = $rider->toArray();
                if (array_key_exists($rider->getId(), $locations)) {
                    $location = $locations [$rider->getId()];
                    $timeLastLocation = strtotime($location->getTime());
                    $currentTime = time();
                    $diff = $currentTime - $timeLastLocation;
                    // If the rider hasn't been seen for 3600 seconds (an hour), we skip it
                    if ($diff > 3600) {
                        continue;
                    }
                    $riderInfo ['displayname'] = $rider->getUser()->getDisplayName();
                    $riderInfo ['location'] = $location->toArray();
                }
                $returnVal [] = $riderInfo;
            }
            return $returnVal;
        } else if (count($this->apiParts) == 0) {
            // If there are no more requestVars, dump all info
            if ($this->includeAll) {
                $locations = $this->siteDriver->getRiderLocation($this->riderTeam->getId());
                $this->riderTeam->setLocations($locations);
            }
            $riderInfo = $this->riderTeam->toArray();
            $riderInfo ['displayname'] = $this->riderTeam->getUser()->getDisplayName();
            
            // Add event ID
            $riderInfo['event_id'] = $authMgr->getMyEventId();
            
            return $riderInfo;
        } else {
            throw new ApiException('Operation ' . $this->operation . ' not part of the RiderApi');
        }
    }

    public function doPost() {
        $this->init();
        $data = $this->request->getRequestVars();
        
        if (isset($data ['locations'])) {
            $result = $this->handleLocationData($data);
            if ($result) {
                header("HTTP/1.0 201 Created");
                return $result;
            }
        }
        
        // If all else fails..        
        header("HTTP/1.0 500 Internal Server Error");
        return array (
                'rowsChanged' => 0,
                'success' => false 
        );
    }
    
    private function handleLocationData($data) {
        $rowsChanged = 0;
        // This is almost always the default (Android sending multiple locations)
        if (isset($data ['locations'])) {
            $json = $data ['locations'];
            error_log('JSON received:' . $json);
            $locations = json_decode($json, true);
            if (null != $locations) {
                if (is_array($locations)) {
                    error_log('JSON decode success array (size):' . sizeof($locations));
                }else {
                    error_log('JSON decode succes:' . $locations);
                }

                foreach ( $locations as $data ) {
                    $rowsChanged += $this->addLocation($data);
                }
                error_log('Num rows added:' . $rowsChanged);
            } else {
                error_log('JSON decode failed, error code:' . json_last_error());
            }
        }
        
        if ($rowsChanged > 0) {
            $newRiderLocation = $this->siteDriver->getRiderLocation($this->riderTeam->getId());
            $gcmResult = 'No GCM results';
            if (count($newRiderLocation) > 0) {
                $allGcmIds = $this->siteDriver->getAllActiveGcms();
                $lastLocation = array_shift($newRiderLocation);
                $payload = array (
                        'hunterLocation' => $lastLocation->toArray() 
                );
                $gcmSender = new GcmSender();
                $gcmSender->setReceiverIds($allGcmIds);
                $gcmSender->setPayload($payload);
                $gcmResult = $gcmSender->send();
            }
            
            return array (
                    'rowsChanged' => $rowsChanged,
                    'gcmResult' => $gcmResult,
                    'success' => true 
            );
        }
        return null;
    }
    
    // $data should be an ARRAY!
    // Returns $numRows
    private function addLocation($data) {
        $location = new RiderLocation();
        $location->setRiderId($this->riderTeam->getId());
        
        $location->setLongitude($data ['longitude']);
        $location->setLatitude($data ['latitude']);
        $location->setTime(time());
        if (isset($data ['adres'])) {
            $location->setAdres($data ['adres']);
        }
        if (isset($data ['time'])) {
            $location->setTime($data ['time']);
        }
        if (isset($data ['accuracy'])) {
            $location->setAccuracy($data ['accuracy']);
        }
        if (isset($data ['provider'])) {
            $location->setProvider($data ['provider']);
        }
        
        return $this->siteDriver->addRiderLocation($location);
    }
}

?>