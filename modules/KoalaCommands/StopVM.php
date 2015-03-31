<?php
  class __CLASSNAME__ {
    public $depend = array("KoalaCore", "libvirt");
    public $name = "StopVM";
    private $libvirt = null;

    public function receiveKoalaCommand($name, $data) {
      $connection = $data[0];
      $payload = $data[1];

      if (!isset($payload["data"]) || !is_array($payload["data"]) ||
          count($payload["data"]) != 2 || !isset($payload["data"]["type"]) ||
          !isset($payload["data"]["name"])) {
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

      if (!@libvirt_domain_is_active($domain)) {
        return array(false, array(
          "status"   => "300",
          "message" => "Not modified: the requested domain was already inactive"
        ));
      }

      if (@libvirt_domain_destroy($domain)) {
        return array(true, array(
          "status"  => "200",
          "message" => "Success: the domain for the given name has been shut ".
            "down"
        ));
      }

      return array(false, array(
        "status"   => "501",
        "message" => "Internal error: unable to shut down domain for the ".
          "given name"
      ));
    }

    public function isInstantiated() {
      $this->libvirt = ModuleManagement::getModuleByName("libvirt");
      EventHandling::registerForEvent("koalaCommandEvent", $this,
        "receiveKoalaCommand", "StopVM");
      return true;
    }
  }
?>
