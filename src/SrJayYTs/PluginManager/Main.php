<?php

namespace SrJayYTs\PluginManager;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class Main extends PluginBase {

    public function onEnable() : void {

        $this->initExcludeFile();
        $this->getLogger()->info("SUPERCUB-DataReload enabled!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
        if(!$sender->hasPermission("supercub.datareload")){
            $sender->sendMessage("No tienes permiso para usar este comando.");
            return true;
        }
        
        if($command->getName() === "restart"){
            $this->reloadPluginData();
            $sender->sendMessage("Se han recargado los datos de los plugins (excepto los excluidos).");
        }
        
        return true;
    }

    /*
      Inicializa el archivo de exclusión (exclude.yml) en la carpeta de datos,
      colocando por defecto la entrada para excluir este mismo plugin.
     */
    private function initExcludeFile() : void {
        $excludeFile = $this->getDataFolder() . "exclude.yml";
        if(!file_exists($excludeFile)){
            @mkdir($this->getDataFolder());

            $default = ["excluded" => ["SUPERCUB-DataReload"]];
            $config = new Config($excludeFile, Config::YAML, $default);
            $config->save();
            $this->getLogger()->info("Se creó el archivo de exclusión: exclude.yml.");
        }
    }
    
    /*
      Recorre todos los plugins registrados y recarga su configuración llamando a reload(),
      excepto aquellos cuyo nombre figure en la lista de exclusión almacenada en exclude.yml.
     */
    private function reloadPluginData() : void {
        $excludeFile = $this->getDataFolder() . "exclude.yml";
        $excludeConfig = new Config($excludeFile, Config::YAML);
        $excluded = $excludeConfig->get("excluded", []);
        if(!is_array($excluded)){
            $excluded = [];
        }
        
        $this->getLogger()->info("Plugins excluidos: " . (!empty($excluded) ? implode(", ", $excluded) : "ninguno"));
        
        $pluginManager = $this->getServer()->getPluginManager();
        foreach ($pluginManager->getPlugins() as $plugin) {
            $pluginName = $plugin->getDescription()->getName();
          
            if(in_array($pluginName, $excluded)){
                $this->getLogger()->info("Saltando recarga para el plugin excluido: {$pluginName}");
                continue;
            }
            try {
                $config = $plugin->getConfig();
                if($config instanceof Config){
                    $config->reload();
                    $this->getLogger()->info("Recargada la configuración del plugin: {$pluginName}");
                } else {
                    $this->getLogger()->info("El plugin {$pluginName} no tiene una configuración recargable.");
                }
            } catch (\Throwable $e) {
                $this->getLogger()->error("Error al recargar la configuración del plugin {$pluginName}: " . $e->getMessage());
            }
        }
    }
}
