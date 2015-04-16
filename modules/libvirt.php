<?php
  class __CLASSNAME__ {
    public $depend = array();
    public $name = "libvirt";
    private $hypervisor = array();
    private $volgrp = null;

    public function createDomain($type, $specs) {
      if ($this->getConnected($type) == false || !is_array($specs) ||
          !isset($specs["cores"]) || !is_numeric($specs["cores"]) ||
          !isset($specs["mem"]) || !is_numeric($specs["mem"]) ||
          !isset($specs["disk"]) || !is_numeric($specs["disk"]))
        return array(false, array(
          "status"  => "408",
          "message" => "Invalid payload: the minimum required data for this ".
            "request was not provided"
        ));

      $name = null;
      for ($i = 100; $i < 999 && $name == null; $i++) {
        if (!file_exists("/dev/".$this->volgrp."/vm".$i."_img") &&
            $this->lookupDomain($type, "vm".$i) == false) {
          $name = "vm".intval($i);
        }
      }

      if ($name == null)
        // Name selection failed
        return array(false, array(
          "status"  => "501",
          "message" => "Internal error: a suitable name could not be selected"
        ));

      $command = "lvcreate -L ".intval($specs["disk"])."G ".
        escapeshellarg("-n".$name."_img")." ".$this->volgrp;
      if (!preg_match("/Logical volume \"(.*)\" created/", trim(
          @shell_exec($command." 2>&1"))) || !file_exists(
          "/dev/".$this->volgrp."/".$name."_img"))
        // Disk creation failed
        return array(false, array(
          "status"  => "502",
          "message" => "Internal error: logical volume creation failed"
        ));

      $command = "virt-install --boot hd,cdrom --connect ".
        escapeshellarg($this->hypervisor[$type][1]["uri"])." --disk ".
        "device=cdrom --disk ".escapeshellarg("/dev/".$this->volgrp."/".$name.
        "_img")." --hvm --import --memory ".intval($specs["mem"])." --name ".
        escapeshellarg($name)." --noautoconsole --noreboot --vcpus ".
        intval($specs["cores"])." --virt-type ".escapeshellarg($type);
      if (strstr(@shell_exec($command." 2>&1"),
          "Domain creation completed.") &&
          $this->lookupDomain($type, $name) != false)
        return $name;

      Logger::debug($name);
      Logger::debug(var_export($this->listDomains($type), true));
      return array(false, array(
        "status"  => "503",
        "message" => "Internal error: domain creation failed"
      ));
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
      $this->hypervisor = array();
      $config = @json_decode(StorageHandling::loadFile($this, "config.json"),
        true);
      if (is_array($config)) {
        Logger::debug(var_export($config, true));
        if (isset($config["options"]) && is_array($config["options"])) {
          if (isset($config["options"]["isopath"]) &&
              strlen($config["options"]["isopath"]) > 0) {
            if (preg_match("/^[a-z]+[a-z0-9_]*$/i",
                $config["options"]["isopath"])) {
              if (is_dir($config["options"]["isopath"]))
                $this->isopath = $config["options"]["isopath"];
              else {
                Logger::info("Inexistent isopath; check config at ".
                  "data/libvirt/config.json");
                return false;
              }
            }
            else {
              Logger::info("Insecure isopath (must match regex ".
                "\"/^[a-z]+[a-z0-9_]*$/i\"); check config at ".
                "data/libvirt/config.json");
              return false;
            }
          }
          else {
            Logger::info("Error loading isopath option; check config at ".
              "data/libvirt/config.json");
            return false;
          }
          if (isset($config["options"]["volgrp"]) &&
              strlen($config["options"]["volgrp"]) > 0) {
            if (preg_match("/^[a-z]+[a-z0-9_]*$/i",
                $config["options"]["volgrp"])) {
              if (is_dir("/dev/".$config["options"]["volgrp"]))
                $this->volgrp = $config["options"]["volgrp"];
              else {
                Logger::info("Inexistent volgrp name; check config at ".
                  "data/libvirt/config.json");
                return false;
              }
            }
            else {
              Logger::info("Insecure volgrp name (must match regex ".
                "\"/^[a-z]+[a-z0-9_]*$/i\"); check config at ".
                "data/libvirt/config.json");
              return false;
            }
          }
          else {
            Logger::info("Error loading volgrp option; check config at ".
              "data/libvirt/config.json");
            return false;
          }
        }
        else {
          Logger::info("Error loading options; check config at ".
            "data/libvirt/config.json");
          return false;
        }
        if (isset($config["hypervisors"]) && is_array($config["hypervisors"])) {
          foreach ($config["hypervisors"] as $type => $info) {
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
              Logger::info("Error loading hypervisor entry; check config at ".
                "data/libvirt/config.json");
              return false;
            }
          }
        }
        else {
          Logger::info("Error loading hypervisors; check config at ".
            "data/libvirt/config.json");
          return false;
        }

        if (count($this->hypervisor) < 1)
          Logger::info("No hypervisors are enabled; check config at ".
            "data/libvirt/config.json");
      }
      else {
        StorageHandling::saveFile($this, "config.json", json_encode(array(
          "options" => array(
            "isopath" => "/var/lib/libvirt/images",
            "volgrp"  => "vol_grp1"
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

    public function reloadConfig($name, $data) {
      if (!$this->loadConfig()) {
        Logger::info("Failed to reload configuration");
        getMain()->shutdown();
      }
    }

    public function setISO($type, $name, $iso) {
      $domain = $this->lookupDomain($type, $name);
      if ($domain == false) {
        return array(false, array(
          "status"  => "406",
          "message" => "Invalid payload: the minimum required data for this ".
            "request was not provided"
        ));
      }

      $iso = realpath($this->isopath."/".$iso);
      if (substr($iso, 0, strlen($this->isopath)) != $this->isopath ||
          !file_exists($iso) || !is_readable($iso)) {
        if ($iso != null) {
          return array(false, array(
            "status"  => "407",
            "message" => "Invalid ISO: the provided ISO does not exist or ".
              "could not be accessed"
          ));
        }
      }

      if (libvirt_domain_update_device($domain,
          "<disk type='block' device='cdrom'>".
            "<driver type='raw' cache='none' io='native'/>".
            ($iso != null ? "<source dev=".escapeshellarg($iso)." />" : null).
            "<target dev='hdc' bus='ide'/>".
            "<readonly />".
          "</disk>", VIR_DOMAIN_DEVICE_MODIFY_CONFIG))
        return true;
      return array(false, array(
        "status"  => "501",
        "message" => "Internal error: the requested ISO could not be mounted"
      ));
    }

    public function isInstantiated() {
      EventHandling::registerForEvent("backgroundEvent", $this, "reloadConfig");
      return $this->loadConfig();
    }
  }
?>
