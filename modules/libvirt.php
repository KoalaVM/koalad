<?php
  class __CLASSNAME__ {
    public $depend = array();
    public $name = "libvirt";
    private $hypervisor = array();

    public function createDomain($type, $specs) {
      //
    }

    public function getConnected($type) {
      if (isset($this->hypervisor[$type]))
        return true;
      return false;
    }

    private function getConnection($type) {
      if (isset($this->hypervisor[$type][0]))
        return $this->hypervisor[$type][0];
      return false;
    }

    private function getConnectionInfo($type) {
      if (isset($this->hypervisor[$type][1]))
        return $this->hypervisor[$type][1];
      return false;
    }

    public function getConnectionTypes() {
      return array_keys($this->hypervisor);
    }

    private function loadConfig() {
      $config = @json_decode(StorageHandling::loadFile($this, "config.json"),
        true);
      if (is_array($config)) {
        Logger::debug(var_export($config, true));
        foreach ($config as $type => $info) {
          if (is_array($info) && isset($info["enabled"]) &&
              isset($info["uri"]) && isset($info["auth"]) &&
              isset($info["username"]) && isset($info["password"])) {
            if ($info["enabled"] == true) {
              $res = @libvirt_connect($info["uri"], false,
                ($info["auth"] == true ? array(
                  VIR_CRED_AUTHNAME => $info["username"],
                  VIR_CRED_PASSPHRASE => $info["password"]
                ) : array()));
              if ($res != false) {
                Logger::info("Initializing support for hypervisor type \"".
                  $type."\"");
                $this->hypervisor[$type] = array($res, $info);
              }
              else {
                Logger::info("Error connecting to hypervisor type \"".$type.
                  "\" at URI \"".$info["uri"]."\"".($info["auth"] == true ?
                  " with username \"".$info["username"]."\" and password \"".
                  $info["password"]."\"" : null));
              }
            }
          }
          else {
            Logger::info("Error loading config entry; check config at ".
              "data/libvirt/config.json");
            return false;
          }
        }
      }
      else {
        StorageHandling::saveFile($this, "config.json", json_encode(array(
          "options" => array(
            "volgrp" => "vol_grp1"
          ),
          "hypervisors" => array(
            "kvm" => array(
              "enabled"  => false,
              "uri"      => "qemu:///system",
              "auth"     => false,
              "username" => "billy",
              "password" => "s3cr3tP455w0rd"
            ),
            "xen" => array(
              "enabled"  => false,
              "uri"      => "xen:///",
              "auth"     => false,
              "username" => "billy",
              "password" => "s3cr3tP455w0rd"
            )
          )
        ), JSON_PRETTY_PRINT));
        $this->loadConfig();
      }
      return true;
    }

    public function listDomains($filter = null) {
      $return = array();
      if ($filter == null) {
        $filter = $this->getConnectionTypes();
      }
      else if (!is_array($filter)) {
        $filter = array($filter);
      }
      foreach (array_intersect($this->getConnectionTypes(), $filter) as $type) {
        if (isset($this->hypervisor[$type])) {
          $domains = libvirt_list_domains($this->getConnection($type));
          if (is_array($domains) && count($domains) > 0) {
            $return[$type] = $domains;
          }
        }
      }
      return (count($return) > 0 ? $return : false);
    }

    public function lookupDomain($type, $name) {
      if (isset($this->hypervisor[$type]))
        return @libvirt_domain_lookup_by_name($this->getConnection($type),
          $name);
      return false;
    }

    public function isInstantiated() {
      return $this->loadConfig();
    }
  }
?>
