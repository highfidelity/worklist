require 'yaml'

extravars = YAML.load_file 'ops/playbooks/extravars.yml'
playbook = ENV['WORKLIST_PLAYBOOK'] ? ENV['WORKLIST_PLAYBOOK'] : 'setup'

Vagrant.configure("2") do |config|
  config.vm.define "worklist-www"
  config.ssh.username = 'ubuntu'

  config.hostmanager.enabled = true
  config.hostmanager.manage_host = true

  config.vm.provider :virtualbox do |vb,override|
    override.vm.box = "ubuntu/xenial64"
    override.vm.network "private_network", ip: "192.168.127.15"
    config.hostmanager.aliases = %w(worklist.dev www.worklist.dev)
    override.vm.synced_folder './', '/vagrant', owner: 'www-data', group: 'www-data'
  end

  config.vm.provision :ansible do |ansible|
    ansible.playbook = "ops/playbooks/#{playbook}.yml"

    ansible.groups = {
      "web" => ["worklist-www"],
      "db"  => ["worklist-www"]
    }
    ansible.extra_vars = {
      settings: extravars["settings"]
    }
  end
end
