<?php
  class __CLASSNAME__ {
    public $depend = array("KoalaCore", "libvirt");
    public $name = "StopVM";
    private $libvirt = null;

    public function receiveKoalaCommand($name, $data) {
      $connection = $data[0];
      $payload = $data[1];

      if (isset($payload["data"]) && is_array($payload["data"]) &&
          count($payload["data"]) == 2 && isset($payload["data"]["type"]) &&
          isset($payload["data"]["name"])) {
        $domain = $this->libvirt->lookupDomain($payload["data"]["type"],
          $payload["data"]["name"]);
        if ($domain != false) {
          if (@libvirt_domain_destroy($domain)) {
            return array(true, array(
              "status"  => "200",
              "message" => "Success: stopped the domain for the given name"
            ));
          }
          else {
            return array(false, array(
              "status"   => "406",
              "message" => "Internal error: unable to stop domain for the ".
                "given name"
            ));
          }
        }
        else {
          return array(false, array(
            "status"   => "405",
            "message" => "Invalid name: no such domain for the given name"
          ));
        }
      }
      else {
        return array(false, array(
          "status"   => "404",
          "message" => "Invalid payload: the minimum required data for this ".
            "request was not provided"
        ));
      }
    }

    public function isInstantiated() {
      $this->libvirt = ModuleManagement::getModuleByName("libvirt");
      EventHandling::registerForEvent("koalaCommandEvent", $this,
        "receiveKoalaCommand", "StopVM");
      return true;
    }
  }
?>
