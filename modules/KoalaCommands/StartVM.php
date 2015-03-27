<?php
  class __CLASSNAME__ {
    public $depend = array("KoalaCore");
    public $name = "StartVM";

    public function receiveKoalaCommand($name, $data) {
      $connection = $data[0];
      $payload = $data[1];

      if (isset($payload["data"]) && strlen($payload["data"]) > 0) {
        //
        return array(true);
      }
      else {
        return array(false, array(
          "error"   => "404",
          "message" => "Invalid payload: required data was not provided for ".
            "this request"
        ));
      }
    }

    public function isInstantiated() {
      EventHandling::registerForEvent("koalaCommandEvent", $this,
        "receiveKoalaCommand", "StartVM");
      return true;
    }
  }
?>
