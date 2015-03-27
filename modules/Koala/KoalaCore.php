<?php
  class __CLASSNAME__ {
    public $depend = array("rawEvent");
    public $name = "KoalaCore";

    public function receiveRaw($name, $data) {
      $connection = $data[0];
      $data = json_decode($data[1], true);

      // Print the resulting array to debug
      Logger::debug(var_export($data, true));

      if (isset($data["payload64"]) && isset($data["signature"]) &&
          count($data) == 2) {
        //
      }
      else {
        // Malformed request
        $connection->send(json_encode(array(
          "error"   => "400",
          "message" => "Malformed request: requests should be formatted as ".
            "outlined at https://github.com/KoalaVM/koalad/blob/master/README.".
            "md#message-structure"
        )));
      }
    }

    public function isInstantiated() {
      @cli_set_process_title("koalad");
      EventHandling::registerForEvent("rawEvent", $this, "receiveRaw");
      return true;
    }
  }
?>
