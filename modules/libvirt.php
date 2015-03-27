<?php
  class __CLASSNAME__ {
    public $depend = array();
    public $name = "libvirt";
    private $kvm = null;
    private $xen = null;

    private function getConnection($type) {
      if ($type == "kvm")
        return $this->kvm;
      else if ($type == "xen")
        return $this->xen;
      else
        return false;
    }

    public function lookupDomain($type, $name) {
      if ($this->getConnection($type) == false)
        return false;
      return libvirt_domain_lookup_by_name($this->getConnection($type), $name);
    }

    public function isInstantiated() {
      $this->kvm = libvirt_connect("qemu:///system", false, array());
      $this->xen = libvirt_connect("xen:///", false, array());
      return true;
    }
  }
?>
