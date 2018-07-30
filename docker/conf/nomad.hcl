name = "halagent"

data_dir  = "/var/lib/nomad"
bind_addr = "0.0.0.0"

disable_update_check = true

ports {
  http = 4646
  rpc  = 4647
  serf = 4648
}

server {
  enabled          = true
  bootstrap_expect = 1
}

client {
  enabled       = true
  network_speed = 10

  options {
    "driver.raw_exec.enable" = "1"
  }
}
