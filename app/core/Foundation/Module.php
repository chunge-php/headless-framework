<?php
namespace app\core\Foundation;

final class Module {
    public string $id;
    public string $path;
    public Manifest $manifest;

    public function __construct(string $id, string $path, Manifest $mf) {
        $this->id=$id; $this->path=rtrim($path,'/'); $this->manifest=$mf;
    }
}
