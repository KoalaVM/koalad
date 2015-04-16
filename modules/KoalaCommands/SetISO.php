<?php
  class __CLASSNAME__ {
    public $depend = array("KoalaCore", "libvirt");
    public $name = "SetISO";
    private $libvirt = null;

    public function receiveKoalaCommand($name, $data) {
      $connection = $data[0];
      $payload = $data[1];

      if (!isset($payload["data"]) || !is_array($payload["data"]) ||
          count($payload["data"]) != 3 || !isset($payload["data"]["type"]) ||
          !isset($payload["data"]["name"]) || !isset($payload["data"]["iso"])) {
        return array(false, array(
          "status"   => "404",
          "message" => "Invalid payload: the minimum required data for this ".
            "request was not provided"
        ));
      }

      if ($this->libvirt->getConnected($payload["data"]["type"]) == false) {
        return array(false, array(
          "status"   => "500",
          "message" => "Internal error: the provided hypervisor type is not ".
            "supported"
        ));
      }

      $domain = $this->libvirt->lookupDomain($payload["data"]["type"],
        $payload["data"]["name"]);
      if ($domain == false) {
        return array(false, array(
          "status"   => "405",
          "message" => "Invalid name: no such domain for the given name"
        ));
      }

      $ret = $this->libvirt->setISO($payload["data"]["type"],
        $payload["data"]["name"], $payload["data"]["iso"]);
      if (!is_array($ret)) {
        return array(true, array(
          "status"  => "200",
          "message" => "Success: the requested ISO was mounted"
        ));
      }
      return $ret;
    }

    public function isInstantiated() {
      $this->libvirt = ModuleManagement::getModuleByName("libvirt");
      EventHandling::registerForEvent("koalaCommandEvent", $this,
        "receiveKoalaCommand", "SetISO");
      return true;
    }
  }
?>
