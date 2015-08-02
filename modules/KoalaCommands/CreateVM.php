<?php
  class __CLASSNAME__ {
    public $depend = array("KoalaCore", "libvirt");
    public $name = "CreateVM";
    private $libvirt = null;

    public function receiveKoalaCommand($name, $data) {
      $connection = $data[0];
      $payload = $data[1];

      if (!isset($payload["data"]) ||
          !is_array($payload["data"]) ||
          count($payload["data"]) != 4 ||
          !isset($payload["data"]["type"]) ||
          !isset($payload["data"]["cores"]) ||
          !isset($payload["data"]["mem"]) ||
          !isset($payload["data"]["disk"])) {
        return array(false, array(
          "status"  => "404",
          "message" => "Invalid payload: the minimum required data for this ".
            "request was not provided"
        ));
      }

      if ($this->libvirt->getConnected($payload["data"]["type"]) == false) {
        return array(false, array(
          "status"  => "500",
          "message" => "Internal error: the provided hypervisor type is not ".
            "supported"
        ));
      }

      if (!is_numeric($payload["data"]["cores"]) ||
          $payload["data"]["cores"] < 1) {
        return array(false, array(
          "status"  => "405",
          "message" => "Invalid CPU count: at least one CPU core must be ".
            "provided"
        ));
      }

      if (!is_numeric($payload["data"]["mem"]) ||
          $payload["data"]["mem"] < 64) {
        return array(false, array(
          "status"  => "406",
          "message" => "Invalid memory size: at least 64 mebibytes must be ".
            "provided"
        ));
      }

      if (!is_numeric($payload["data"]["disk"]) ||
          $payload["data"]["disk"] < 0) {
        return array(false, array(
          "status"  => "407",
          "message" => "Invalid disk size: disk cannot be negative in size"
        ));
      }

      $name = $this->libvirt->createDomain($payload["data"]["type"],
        $payload["data"]);
      if (!is_array($name)) {
        return array(true, array(
          "status"  => "200",
          "message" => "Success: a VM with the requested specifications was ".
            "created",
          "name"    => $name
        ));
      }
      return $name;
    }

    public function isInstantiated() {
      $this->libvirt = ModuleManagement::getModuleByName("libvirt");
      EventHandling::registerForEvent("koalaCommandEvent", $this,
        "receiveKoalaCommand", "CreateVM");
      return true;
    }
  }
?>
