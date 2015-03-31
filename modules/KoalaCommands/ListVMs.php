<?php
  class __CLASSNAME__ {
    public $depend = array("KoalaCore", "libvirt");
    public $name = "ListVMs";
    private $libvirt = null;

    public function receiveKoalaCommand($name, $data) {
      $connection = $data[0];
      $payload = $data[1];

      $filter = null;
      if (isset($payload["data"])) {
        if (!is_array($payload["data"]) && $payload["data"] != null) {
          $filter = array($payload["data"]);
        }
        else {
          $filter = $payload["data"];
        }
      }

      $domains = $this->libvirt->listDomains($filter);
      if (is_array($domains) && count($domains) > 0) {
        return array(true, array(
          "status"  => "200",
          "message" => "Success: a list of domains was found",
          "domains" => json_encode($domains)
        ));
      }
      return array(false, array(
        "status"      => "404",
        "message"     => "Not found: no domains were found".($filter != null ?
          " with the provided filter" : null)
      ));
    }

    public function isInstantiated() {
      $this->libvirt = ModuleManagement::getModuleByName("libvirt");
      EventHandling::registerForEvent("koalaCommandEvent", $this,
        "receiveKoalaCommand", "ListVMs");
      return true;
    }
  }
?>
