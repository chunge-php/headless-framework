<?php

namespace app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModuleCommand extends Command
{
    protected static $defaultName = 'make:g';

    protected function configure()
    {
        $this->setDescription('Create module skeleton under app/modules/{name}')
            ->addArgument('name', InputArgument::REQUIRED, 'Module name, e.g. blog')
            ->addArgument('describe', InputArgument::OPTIONAL, '描述备注，用于迁移注释')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite files if exist');
    }

    /**
     * Summary of execute
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name  = trim($input->getArgument('name'));
        $describe  = trim($input->getArgument('describe'));
        $force = (bool)$input->getOption('force');

        // 规范化模块名（可按需调整）
        // $name = str_replace(['\\', '/'], '', $name);

        $root = base_path("app/modules/{$name}");


        $dirs = [
            $root,
            $root . '/database',
            $root . '/features',
            $root . '/controllers',
            $root . '/fns',
            $root . '/validate',
            $root . '/model',
            $root . '/routes',

        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                    $output->writeln("<error>Failed to create: {$dir}</error>");
                    return Command::FAILURE;
                }
                // 防止空目录被忽略
                // @file_put_contents($dir . '/.gitkeep', '');
            }
        }

        // 写入 routes 示例文件
        $routesFile = $root . "/routes/api.php";

        $str =     explode('/', $name);
        if (count($str) > 1) {
            $daxie = toStudlyClass(end($str));
        } else {
            $daxie = toStudlyClass($str[0]);
        }
        $namsbase = str_replace('/', '\\', $name);
        $name_controller = $daxie . 'Controller';
        $role_name = str_replace('/','.',$name);
        if ($force || !file_exists($routesFile)) {
            $stub = <<<PHP
<?php
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\\$namsbase\controllers\\$name_controller;
Route::group('/$name', function () {
    Route::get('/index',   [$name_controller::class, 'index'])->name('$role_name.index');   //列表
    Route::post('/create',   [$name_controller::class, 'create'])->name('$role_name.create');   //创建/修改
    Route::get('/show',   [$name_controller::class, 'show'])->name('$role_name.show');   //详情
    Route::post('/delete',   [$name_controller::class, 'delete'])->name('$role_name.delete');   //删除
})->middleware([JwtMiddleware::class]);

PHP;
            @file_put_contents($routesFile, $stub);
        }


        // 写入 features 示例文件
        $registryFile = $root . "/features/registry.php";
        if ($force || !file_exists($registryFile)) {
            $stub = <<<PHP
<?php
use function app\core\Foundation\\feature_group;
return [];


PHP;
            @file_put_contents($registryFile, $stub);
        }
        // 写入 features 示例文件
        $ValidateFile = $root . "/validate/{$daxie}Validate.php";
        if ($force || !file_exists($ValidateFile)) {
            $stub = <<<PHP
<?php

namespace app\\modules\\$namsbase\\validate;

use think\Validate;

class {$daxie}Validate extends Validate
{
    protected \$rule = [
        'id'    => 'require',
        'name'    => 'require',
    ];
    protected \$message = [
        'id.require'    => 'id_not_select',
        'name.require'    => 'name_not_input',
    ];
    // 场景验证
    public \$scene = [
        'POST_create' => ['name'],
        'GET_show' => ['id'],
        'POST_delete' => ['id'],
    ];
}



PHP;
            @file_put_contents($ValidateFile, $stub);
        }
        // 写入 manifest.hf.json
        $manifestFile = $root . '/manifest.hf.php';
        if ($force || !file_exists($manifestFile)) {
            $manifest  = <<<PHP
<?php
use function app\core\Foundation\manifest_group;
            return [
                'name' => '$daxie',
                'version' => '1.0.0',
                'display' => '$describe',
                'requires' => [
                    'php' => '>=8.0',
                    'webman' => '>=1.5',
                    'modules' => [],
                    'features' => [],
                ],
                'provides' => [
                    'features' => [],
                ],
            ];
PHP;

            @file_put_contents($manifestFile, $manifest);
        }
        // 写入一个示例控制器
        $controllerFile = $root . "/controllers/$name_controller.php";
        if ($force || !file_exists($controllerFile)) {

            $controllerStub = <<<PHP
<?php
namespace app\modules\\$namsbase\controllers;

use app\modules\\$namsbase\\fns\\{$daxie}Fn;
use support\Request;

class $name_controller
{

 /**
     * Summary of index
     * @param \support\Request \$request
     * @return \support\Response
     */
    public function index(Request \$request,{$daxie}Fn \${$daxie}Fn)
    {
        \$all = \$request->all();
        \$all['jwtUserId'] = \$request->jwtUserId;
        \$list = \${$daxie}Fn->index(\$all, \$request->limit, \$request->offset);
        return success(\$list);
    }

    /**
     * Summary of create
     * @param \support\Request \$request
     * @return \support\Response
     */
    public function create(Request \$request,{$daxie}Fn \${$daxie}Fn)
    {
        \$all = \$request->all();
        \$all['jwtUserId'] = \$request->jwtUserId;
        \$id = \$request->input('id') ?? 0;
        unset(\$all['id']);
        if (\$id > 0) {
            \${$daxie}Fn->update(\$id, \$all);
        } else {
            \${$daxie}Fn->create(\$all);
        }
        return success();
    }
    public function show(Request \$request,{$daxie}Fn \${$daxie}Fn)
    {
        \$all = \$request->all();
        \$all['jwtUserId'] = \$request->jwtUserId;
        return success(\${$daxie}Fn->show(\$all));
    }
    public function delete(Request \$request,{$daxie}Fn \${$daxie}Fn)
    {
        \$all = \$request->all();
        \$all['jwtUserId'] = \$request->jwtUserId;
        return success(\${$daxie}Fn->delete(\$all));
    }
}
PHP;
            @file_put_contents($controllerFile, $controllerStub);
        }

        // 写入一个示例 Service
        $serviceFile = $root . "/fns/{$daxie}Fn.php";
        if ($force || !file_exists($serviceFile)) {
            $serviceStub = <<<PHP
<?php

namespace app\modules\\$namsbase\\fns;

use app\modules\\$namsbase\\model\\$daxie;

class  {$daxie}Fn
{
    protected \$model;
    public function __construct({$daxie} \$model)
    {
        \$this->model = \$model;
    }
    public function index(\$info, \$limit = 20, \$offset = 1):array
    {
        \$where = \$this->model->where('uid', \$info['jwtUserId']);
        \$total = \$where->count();
        if (\$total < 1) {
            return ['total' => 0, 'list' => []];
        } else {
            \$list  = \$where
                ->orderBy('updated_at', 'desc')
                ->limit(\$limit)
                ->offset(\$offset)
                ->get()
                ->toArray();
            return ['total' => \$total, 'list' => \$list];
        }
    }
    public function create(\$info)
    {
        \$is  = \$this->model->where('uid', \$info['jwtUserId'])->where('name', \$info['name'])->exists();
        if (\$is) tryFun('name_exists', info_err);
        \$info['uid'] = \$info['jwtUserId'];
        return \$this->model->insertGetId(filterFields(\$info, \$this->model));
    }
    public function update(\$id,\$info)
    {
        \$is  = \$this->model->where('uid', \$info['jwtUserId'])->where('id', '<>', \$id)->where('name', \$info['name'])->exists();
        if (\$is) tryFun('name_exists', info_err);
        return \$this->model->where('id', \$id)->update(filterFields(\$info, \$this->model));
    }
    public function show(\$info)
    {
        return \$this->model->where('id', \$info['id'])->where('uid', \$info['jwtUserId'])->first()?->toArray();
    }
    public function delete(\$info)
    {
        if (!is_array(\$info['id'])) {
            \$info['id'] = [\$info['id']];
        }
        return \$this->model->whereIn('id', \$info['id'])->where('uid', \$info['jwtUserId'])->delete();
    }
}


PHP;
            @file_put_contents($serviceFile, $serviceStub);
        }

        $output->writeln("<info>Module skeleton created: app/modules/{$name}</info>");
        return Command::SUCCESS;
    }
}
