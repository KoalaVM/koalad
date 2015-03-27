<?php
  class __CLASSNAME__ {
    public $depend = array("RawEvent");
    public $name = "KoalaCore";
    private $gpg = null;

    public function receiveRaw($name, $data) {
      $connection = $data[0];
      $data = json_decode($data[1], true);

      if (isset($data["payload64"]) && isset($data["signature"]) &&
          count($data) == 2) {
        // Unpack payload
        $payload = json_decode(base64_decode($data["payload64"]), true);
        if (is_array($payload) && isset($payload["command"])) {
          $info = gnupg_verify($this->gpg, $data["payload64"],
            base64_decode($data["signature"]));
          if (is_array($info) && isset($info[0]["status"]) &&
              $info[0]["status"] == 0) {
            // Signature is valid
            $found = 0;
            $event = EventHandling::getEventByName("koalaCommandEvent");
            if ($event != false && count($event[2]) > 0) {
              foreach ($event[2] as $id => $registration) {
                // Skip not applicable registrations
                if ($registration[2] == null ||
                    strtolower(trim($registration[2])) !=
                    strtolower(trim($payload["command"]))) {
                  continue;
                }
                // Trigger the koalaCommand event for each registered module
                $status = EventHandling::triggerEvent("koalaCommandEvent", $id,
                    array($connection, $payload));
                $connection->send(json_encode($status[1]));
                $connection->disconnect();
                $found++;
              }
            }
            if ($found == 0) {
              // Unknown command
              $connection->send(json_encode(array(
                "error"   => "403",
                "message" => "Unknown command: the requested command does ".
                  "not exist or could not be found"
              )));
              $connection->disconnect();
            }
          }
          else {
            // Invalid signature
            $connection->send(json_encode(array(
              "error"   => "402",
              "message" => "Invalid signature: the provided payload could ".
                "not be authenticated by the provided signature"
            )));
            $connection->disconnect();
          }
        }
        else {
          // Error unpacking payload
          $connection->send(json_encode(array(
            "error"   => "401",
            "message" => "Error processing payload64: payload64 should be ".
              "formatted as outlined at https://github.com/KoalaVM/koalad/blob".
              "/master/README.md#payload64"
          )));
          $connection->disconnect();
        }
      }
      else {
        // Malformed request
        $connection->send(json_encode(array(
          "error"   => "400",
          "message" => "Malformed request: requests should be formatted as ".
            "outlined at https://github.com/KoalaVM/koalad/blob/master/README.".
            "md#message-structure"
        )));
        $connection->disconnect();
      }
    }

    public function isInstantiated() {
      $pubkey = StorageHandling::loadFile($this, "gpg.pub");
      if ($pubkey != null) {
        $this->gpg = gnupg_init();
        gnupg_import($this->gpg, $pubkey);
        EventHandling::createEvent("koalaCommandEvent", $this);
        EventHandling::registerForEvent("rawEvent", $this, "receiveRaw");
        return true;
      }
      return false;
    }
  }
?>
