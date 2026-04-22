<?php

namespace app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use app\modules\myclass\ConsoleOutputStyles; // 引入刚刚定义的样式
use app\modules\myclass\CreateDatabase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Phinx\Console\PhinxApplication;
use support\Db;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Helper\QuestionHelper;

class InstallData extends Command
{
    protected static $defaultName = 'install:data';
    protected static $defaultDescription = '初始化 迁移数据库';
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            // 命令的名字（"bin/console" 后面的部分）
            ->setName('insert:data')
            ->addArgument('options', InputArgument::OPTIONAL, '是否初始化.')

            // the short description shown while running "php bin/console list"
            // 运行 "php bin/console list" 时的简短描述
            ->setDescription('初始化 迁移数据库.')

            // the full command description shown when running the command with
            // the "--help" option
            // 运行命令时使用 "--help" 选项时的完整命令描述
            ->setHelp("迁移数据库是否 如果新创建的数据库或者字段会自动创建"); //添加参数
    }
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ConsoleOutputStyles::initialize($output); // 初始化样式
        try {
            $options = $input->getArgument('options') ?? false;

            if ($options == 'exec') {
                $this->insertData($input, $output);
                return  self::FAILURE;
            }
            $fun_password = $this->askQuestion($input, $output, '请输入访问密码: ', true);
            if (md5($fun_password) !== getenv('fun_password')) {
                ConsoleOutputStyles::error($output, '访问密码错误');
                return  self::FAILURE;
            }
            if ($options == 'all') {
                $this->allInit($input, $output);
            } else {
                $this->migration($input, $output);
            }
        } catch (\Exception $e) {
            ConsoleOutputStyles::error($output, '执行异常：' . $e->getMessage());
            return  self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function insertData($input, $output)
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        if (!empty($database)) {
            ConsoleOutputStyles::info($output, '数据库：' . $database);
        }
        if (!empty($username)) {
            ConsoleOutputStyles::info($output, '用户名：' . $username);
        }
        if (!empty($password)) {
            ConsoleOutputStyles::info($output, '登陆密码：' . $password);
        }
        $this->baseDatabaseInit($output, $database, $username, $password);
    }
    /**
     * 只更新数据库字段或者表 不做删除库操作
     */
    private function migration($input, $output)
    {
        ConsoleOutputStyles::info($output, '数据库迁移中.........');
        Db::table('phinxlogs')->whereNotNull('version')->delete();
        $this->phinxMigrate($output);
    }
    /**
     * 这将会执行删除库 创建库 并且初始化 迁移
     */
    private function allInit($input, $output)
    {

        $is_on = $this->askQuestion($input, $output, '是否删除库并重新初始化(y/n): ', true);
        if ($is_on !== 'y') {
            ConsoleOutputStyles::comment($output, '取消初始化');
            return self::FAILURE;
        }
        $database = $this->askQuestion($input, $output, '请输入数据库名称：database (默认' . config('database.connections.mysql.database') . ')', false);
        $username = $this->askQuestion($input, $output, '请输入用户名：username (默认' . config('database.connections.mysql.username') . ')', false);
        $password = $this->askQuestion($input, $output, '请输入密码：password (默认' . config('database.connections.mysql.password') . ')', false);
        if (!empty($database)) {
            ConsoleOutputStyles::info($output, '数据库：' . $database);
        }
        if (!empty($username)) {
            ConsoleOutputStyles::info($output, '用户名：' . $username);
        }
        if (!empty($password)) {
            ConsoleOutputStyles::info($output, '登陆密码：' . $password);
        }
        $this->baseDatabaseInit($output, $database, $username, $password);
    }
    private function baseDatabaseInit($output, $database, $username, $password)
    {
        ConsoleOutputStyles::comment($output, '执行删库初始化中............');
        ConsoleOutputStyles::comment($output, '文件重新加载中............');
        exec('composer dump-autoload'); //重新加载文件
        ConsoleOutputStyles::comment($output, '文件重新加载成功^_^');

        if (empty($database)) {
            $database = config('database.connections.mysql.database');
            ConsoleOutputStyles::question($output, '数据库：' . $database);
        }
        if (empty($username)) {
            $username = config('database.connections.mysql.username');
            ConsoleOutputStyles::question($output, '登陆账户：' . $username);
        }
        if (empty($password)) {
            $password = config('database.connections.mysql.password');
            ConsoleOutputStyles::question($output, '登陆密码：' . $password);
        }
        if (!$username || !$database || !$password) {
            ConsoleOutputStyles::error($output, '缺失必要参数');
            return self::FAILURE;
        }
        $config   = config('database.connections.' . config('database.default'));
        $prefix = config('database.connections.mysql.prefix');
        if (!function_exists('mysqli_connect')) {
            ConsoleOutputStyles::error($output, '初始化失败 婆娘不再!..');
            return self::FAILURE;
        }
        $conn = mysqli_connect($config['host'], $config['username'], $config['password'], '', $config['port']);
        $table = $config['database'];
        ConsoleOutputStyles::info($output, '数据库创建中.........');
        // 检查数据库是否存在
        $db_check_query = mysqli_query($conn, "SHOW DATABASES LIKE '{$table}'");
        $db_exists = mysqli_num_rows($db_check_query) > 0;

        if ($db_exists) {
            mysqli_query($conn, "DROP DATABASE `{$table}`");
        }
        mysqli_query($conn, "CREATE DATABASE `{$table}` CHARSET=" . $config['charset'] . " COLLATE=" . $config['collation']);
        ConsoleOutputStyles::info($output, '创建数据库成功');
        ConsoleOutputStyles::comment($output, '数据库迁移中.........');
        $this->phinxMigrate($output);
        sleep(2);
        $create_data_base =  new CreateDatabase();
        $create_data_base->createInitServe();
        ConsoleOutputStyles::info($output, '数据库迁移成功^_^');
        ConsoleOutputStyles::info($output, '已经初始化了! 要是你误操作 没备份旧数据库 那就准备跑路吧兄弟! ^_^');
    }
    private function phinxMigrate($output)
    {
        // 创建 Phinx 应用实例
        $app = new PhinxApplication();
        // 运行 Phinx migrate
        $input = new ArrayInput([
            'command' => 'migrate',
            '-c'=>base_path('phinx.php'),
            '-e' => 'production'
        ]);
        try {
            $app->doRun($input, $output);
            ConsoleOutputStyles::info($output, "Phinx 迁移成功");
        } catch (\Exception $e) {
            ConsoleOutputStyles::error($output, "Phinx 迁移失败: " . $e->getMessage());
            return self::FAILURE;
        }
       
    }
    private function askQuestion(InputInterface $input, OutputInterface $output, string $questionText, bool $require = false): ?string
    {


        // 初始化命令行输出样式
        ConsoleOutputStyles::initialize($output);

        // 使用自定义样式的提示文字
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $questionTextStyled = "<question>{$questionText}</question>";
        $question = new Question($questionTextStyled);

        $attempts = 3; // 初始化尝试次数
        if ($require) {
            $question->setValidator(function ($answer) use (&$attempts, $output) {
                if (empty($answer)) {
                    $attempts--;
                    ConsoleOutputStyles::error($output, "此参数必填。还剩 {$attempts} 次尝试。");
                    throw new \RuntimeException("此参数必填。还剩 {$attempts} 次尝试。");
                }
                return $answer;
            });
            $question->setMaxAttempts(3); // 设置最大尝试次数
        }

        return $helper->ask($input, $output, $question);
    }
    private function test($input, $output)
    {
        ConsoleOutputStyles::initialize($output); // 初始化样式

        ConsoleOutputStyles::info($output, '这是信息级别的输出');
        ConsoleOutputStyles::error($output, '这是错误级别的输出');
        ConsoleOutputStyles::comment($output, '这是注释级别的输出');
        ConsoleOutputStyles::question($output, '这是问题级别的输出');
    }
}
