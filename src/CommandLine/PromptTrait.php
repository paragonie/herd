<?php
/**
 * Created by PhpStorm.
 * User: Scott
 * Date: 12/27/2017
 * Time: 1:23 PM
 */

namespace ParagonIE\Herd\CommandLine;

/**
 * Trait PromptTrait
 * @package ParagonIE\Herd\CommandLine
 */
trait PromptTrait
{
    /**
     * Return the size of the current terminal window
     *
     * @return array<int, int>
     * @psalm-suppress
     */
    public function getScreenSize(): array
    {
        $output = [];
        \preg_match_all(
            "/rows.([0-9]+);.columns.([0-9]+);/",
            \strtolower(\exec('stty -a | grep columns')),
            $output
        );
        /** @var array<int, array<int, int>> $output */
        if (\sizeof($output) === 3) {
            /** @var array<int, int> $width */
            $width = $output[2];
            /** @var array<int, int> $height */
            $height = $output[1];
            return [
                $width[0],
                $height[0]
            ];
        }
        return [80, 25];
    }

    /**
     * Prompt the user for an input value
     *
     * @param string $text
     * @return string
     */
    final protected function prompt(string $text = ''): string
    {
        return \readline($text . ' ');
    }

    /**
     * Interactively prompts for input without echoing to the terminal.
     * Requires a bash shell or Windows and won't work with
     * safe_mode settings (Uses `shell_exec`)
     *
     * @ref http://www.sitepoint.com/interactive-cli-password-prompt-in-php/
     *
     * @param string $text
     * @return string
     * @throws \Exception
     * @psalm-suppress ForbiddenCode
     */
    final protected function silentPrompt(string $text = "Enter Password:"): string
    {
        if (\preg_match('/^win/i', PHP_OS)) {
            $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
            file_put_contents(
                $vbscript,
                'wscript.echo(InputBox("'. \addslashes($text) . '", "", "password here"))'
            );
            $command = "cscript //nologo " . \escapeshellarg($vbscript);

            $exec = (string) \shell_exec($command);
            $password = \rtrim($exec);
            \unlink($vbscript);
            return $password;
        } else {
            /** @var string $command */
            $command = "/usr/bin/env bash -c 'echo OK'";
            /** @var string $exec */
            $exec = (string) \shell_exec($command);
            if (\rtrim($exec) !== 'OK') {
                throw new \Exception("Can't invoke bash");
            }
            $command = "/usr/bin/env bash -c 'read -s -p \"". addslashes($text). "\" mypassword && echo \$mypassword'";
            /** @var string $exec2 */
            $exec2 = (string) \shell_exec($command);
            $password = \rtrim($exec2);
            echo "\n";
            return $password;
        }
    }
}
