<?php
  class __CLASSNAME__ {
    public $depend = array("KoalaCore", "libvirt");
    public $name = "Test";
    private $libvirt = null;

    public function receiveKoalaCommand($name, $data) {
      $connection = $data[0];
      $payload = $data[1];

      return array(true, array(
        "status"      => "200",
        "message"     => "Success: authentication test succeeded",
        "hypervisors" => $this->libvirt->getConnectionTypes()
      ));
    }

    public function isInstantiated() {
      $this->libvirt = ModuleManagement::getModuleByName("libvirt");
      EventHandling::registerForEvent("koalaCommandEvent", $this,
        "receiveKoalaCommand", "Test");
      return true;
    }
  }
?>
