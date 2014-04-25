<?php
namespace QL\Hal\Sync;

class TemporaryDirectoryService
{
    /**
     * @var string|null
     */
    private $dir;

    /**
     * @var string|null
     */
    private $error;

    /**
     *
     */
    public function __construct($baseDir = null)
    {
        if ($baseDir === null) {
            $baseDir = sys_get_temp_dir();
        }

        $max = strlen(base_convert(mt_getrandmax(), 10, 36));
        $randdir = str_pad(base_convert(mt_rand(), 10, 36), $max, "0", STR_PAD_LEFT);
        $randdir = sprintf('/hal9000-%s', $randdir);
        $randdir = $baseDir . $randdir;
        exec(sprintf('mkdir %s 2>&1', escapeshellarg($randdir)), $out, $ret);
        if ($ret !== 0) {
            $this->dir = null;
            $this->error = implode("\n", $out);
        } else {
            $this->dir = $randdir;
        }
    }

    /**
     * @return null|string
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * @return string|null
     */
    public function path()
    {
        return $this->dir;
    }

    /**
     * Hopefully this will keep the tmp area clean
     */
    public function __destruct()
    {
        if ($this->dir) {
            exec(sprintf('rm -r %s', escapeshellarg($this->dir)));
        }
    }
}
