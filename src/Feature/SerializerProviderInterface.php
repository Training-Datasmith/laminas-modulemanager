<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Feature;

interface Serializer_Provider_Interface
{
    /** @return array */
    public function get_serializer_config();
}