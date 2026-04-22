<?php
namespace app\core\Foundation;

final class Manifest
{
    public string $name = '';
    public string $version = '1.0.0';
    public string $display = '';

    /** @var array{php?:string,webman?:string,modules?:array<string,string>,features?:array<string,string>} */
    public array $requires = ['php'=>'>=8.0','webman'=>'>=1.5','modules'=>[], 'features'=>[]];

    /** @var array{modules?:array<string,string>,features?:array<string,string>} */
    public array $optional = ['modules'=>[], 'features'=>[]];

    /** @var array{modules?:array<string,string>,features?:array<string,string>} */
    public array $conflicts = ['modules'=>[], 'features'=>[]];

    /** @var array{features?:array<string,string>} */
    public array $provides = ['features'=>[]];

    /** @var array{routes?:string[],assets?:string[],migrations?:string[]} */
    public array $export = [ 'routes'=>['routes'], 'assets'=>['resources/public'], 'migrations'=>['migration'] ];

    public static function fromFile(string $file): self
    {
        $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $m = new self();
        foreach ($data as $k => $v) {
            if (property_exists($m, $k)) {
                $m->$k = is_array($m->$k) && is_array($v) ? array_replace_recursive($m->$k, $v) : $v;
            }
        }
        return $m;
    }
}
