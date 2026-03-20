<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

interface Config_Merger_Interface
{
    /**
     * @param  bool $returnConfigAsObject
     * @return mixed
     */
    public function get_merged_config($return_config_as_object = true);
    /**
     * @return ConfigMergerInterface
     */
    public function set_merged_config(array $config);
}