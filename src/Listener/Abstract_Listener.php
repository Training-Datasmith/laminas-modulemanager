<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use Brick\Var_Exporter\Export_Exception;
use Brick\Var_Exporter\Var_Exporter;
use Laminas\Module_Manager\Listener\Exception\Config_Cannot_Be_Cached_Exception;
use Webimpress\Safe_Writer\File_Writer;
abstract class Abstract_Listener
{
    /** @var ListenerOptions */
    protected $options;
    public function __construct(?Listener_Options $options = null)
    {
        $options = $options ?: new Listener_Options();
        $this->set_options($options);
    }
    /** @return ListenerOptions */
    public function get_options()
    {
        return $this->options;
    }
    /**
     * @param ListenerOptions $options the value to be set
     * @return AbstractListener
     */
    public function set_options(Listener_Options $options)
    {
        $this->options = $options;
        return $this;
    }
    /**
     * Write a simple array of scalars to a file
     *
     * @param  string $filePath
     * @param  array $array
     * @return AbstractListener
     */
    protected function write_array_to_file($file_path, $array)
    {
        try {
            $content = "<?php\n" . Var_Exporter::export($array, Var_Exporter::ADD_RETURN | Var_Exporter::CLOSURE_SNAPSHOT_USES);
        } catch (Export_Exception $e) {
            throw Config_Cannot_Be_Cached_Exception::from_exporter_exception($e);
        }
        File_Writer::write_file($file_path, $content);
        return $this;
    }
}