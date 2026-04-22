<?php

namespace app\modules\myclass;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ConsoleOutputStyles
{
    /**
     * 初始化命令行输出样式
     */
    public static function initialize(OutputInterface $output)
    {
        $styles = [
            'info' => new OutputFormatterStyle('green', null, ['bold']),
            'error' => new OutputFormatterStyle('red', null, ['bold']),
            'comment' => new OutputFormatterStyle('yellow'),
            'question' => new OutputFormatterStyle('black', null, ['bold']),
            'warning'  => new OutputFormatterStyle('black', 'yellow', ['bold']),
        ];

        foreach ($styles as $name => $style) {
            $output->getFormatter()->setStyle($name, $style);
        }
    }

    public static function info(OutputInterface $output, string $message)
    {
        $output->writeln("<info>$message</info>");
    }

    public static function error(OutputInterface $output, string $message)
    {
        $output->writeln("<error>$message</error>");
    }

    public static function comment(OutputInterface $output, string $message)
    {
        $output->writeln("<comment>$message</comment>");
    }

    public static function question(OutputInterface $output, string $message)
    {
        $output->writeln("<question>$message</question>");
    }
    public static function warning(OutputInterface $output, string $message)
    {
        $output->writeln("<warning>$message</warning>");
    }
}
