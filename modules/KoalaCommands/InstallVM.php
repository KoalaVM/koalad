<?php
  class __CLASSNAME__ {
    public $depend = array("KoalaCore", "libvirt");
    public $name = "InstallVM";
    private $libvirt = null;

    public function receiveKoalaCommand($name, $data) {
      $connection = $data[0];
      $payload = $data[1];

      if (!isset($payload["data"]) ||
          !is_array($payload["data"]) ||
          !isset($payload["data"]["type"]) ||
          !isset($payload["data"]["name"]) ||
          !isset($payload["data"]["cores"]) ||
          !isset($payload["data"]["mem"]) ||
          !isset($payload["data"]["disk"]) ||
          !isset($payload["data"]["vncpass"]) ||
          !isset($payload["data"]["iso"]) ||
          !isset($payload["data"]["auto"])) {
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

      if (!preg_match("/^[a-z]+[a-z0-9]*$/i", $payload["data"]["name"])) {
        return array(false, array(
          "status"   => "405",
          "message" => "Invalid name: names must match an alphanumeric ".
            "sequence of characters and begin with a non-digit"
        ));
      }

      if ($this->libvirt->lookupDomain($payload["data"]["type"],
          $payload["data"]["name"]) != false) {
        return array(false, array(
          "status"   => "406",
          "message" => "Invalid name: another domain already exists by the ".
            "provided name"
        ));
      }

      if (!is_numeric($payload["data"]["cores"]) ||
          $payload["data"]["cores"] < 1) {
        return array(false, array(
          "status"   => "407",
          "message" => "Invalid CPU count: at least one CPU core must be ".
            "provided"
        ));
      }

      if (!is_numeric($payload["data"]["mem"]) ||
          $payload["data"]["cores"] < 64) {
        return array(false, array(
          "status"   => "408",
          "message" => "Invalid memory size: at least 64 mebibytes must be ".
            "provided"
        ));
      }

      if (!is_numeric($payload["data"]["disk"]) ||
          $payload["data"]["cores"] < 0) {
        return array(false, array(
          "status"   => "409",
          "message" => "Invalid disk size: disk cannot be negative in size"
        ));
      }

      // Check disk conflicts

      if (strlen($payload["data"]["vncpass"]) < 8) {
        return array(false, array(
          "status"   => "410",
          "message" => "Invalid VNC password: length is less than 8 characters"
        ));
      }
    }

    public function isInstantiated() {
      $this->libvirt = ModuleManagement::getModuleByName("libvirt");
      EventHandling::registerForEvent("koalaCommandEvent", $this,
        "receiveKoalaCommand", "InstallVM");
      return true;
    }
  }
?>
