<?php

namespace whikloj\Command;

use FluentDOM\Serializer\Json\RabbitFish;
use http\Exception\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Transform
 * @package whikloj\Command
 * @author whikloj
 * @since 2019-09-21
 */
class Transform extends Command
{

    /**
     * The command string
     *
     * @var string
     */
    protected static $defaultName = 'app:transform';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setHelp("This loads an XML file or directory of XML files then attempts to serialize them out to JSON");
        $this->addOption('file', 'f', InputOption::VALUE_OPTIONAL, "An xml file.");
        $this->addOption('directory', 'd', InputOption::VALUE_OPTIONAL, 'Directory of XML files.');
        $this->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output directory', ".");
        $this->addOption('overwrite', NULL, InputOption::VALUE_NONE, 'Overwrite existing JSON files, default is to skip.');
        $this->addOption('recurse', NULL, InputOption::VALUE_NONE, 'Recurse sub-directories, only for use with --directory.');
        $this->addUsage("(--file file | --directory directory)");
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('file') && !$input->getOption('directory')) {
            $output->writeln("You must provide either a file or directory to execute against.");
            return;
        }
        elseif ($input->getOption('file') && $input->getOption('directory')) {
            $output->writeln("You must provide *either* a file or directory to execute against.");
            return;
        }

        $output_dir = $input->getOption('output');
        $output_dir = realpath($output_dir);
        if (!is_dir($output_dir) || !is_writable($output_dir)) {
            $output->writeln("{$output_dir} is not a writeable directory.");
            return;
        }

        $args = [
            'output' => $output_dir,
            'overwrite' => $input->getOption('overwrite'),
            'recurse' => $input->getOption('recurse'),
        ];

        if ($input->getOption('file')) {
            $this->doFile($input->getOption('file'), $args);
        } else {
            $this->doDirectory($input->getOption('directory'), $args, $output);
        }

    }

    /**
     * Operate on a directory.
     *
     * @param string $directory
     *   The directory.
     * @param array $args
     *   Various command arguments.
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *   Output interface.
     */
    private function doDirectory($directory, $args, OutputInterface $output)
    {
        $directory = realpath($directory);
        if (!is_dir($directory) || !is_readable($directory)) {
            throw new \InvalidArgumentException("Directory {$directory} doesn't exist or is not readable.");
        }
        $contents = scandir($directory);
        $contents = array_diff($contents, ['.', '..']);
        foreach ($contents as $entry) {
            $full_path = "{$directory}/{$entry}";
            $output->writeln("Processing {$full_path}");
            if (is_file($full_path) &&
            strtolower(pathinfo($full_path, PATHINFO_EXTENSION)) == 'xml') {
                try {
                    $this->doFile($full_path, $args);
                } catch (\InvalidArgumentException $e) {
                    $output->writeln($e->getMessage());
                }
            }
            elseif (is_dir($full_path) && $args['recurse']) {
                $temp_output = $args['output'];
                $args['output'] = $this->recurseDir($args, $entry);
                $this->doDirectory($full_path, $args, $output);
                $args['output'] = $temp_output;
            }
        }
    }

    /**
     * When recursing source directories, try to replicate pattern in output directory.
     *
     * @param array $args
     *   Various command arguments.
     * @param $subdir
     *   The sub-directory we are recursing into.
     * @return string
     *   The new output directory.
     */
    private function recurseDir(array &$args, $subdir)
    {
        $temp = rtrim($args['output'], '/') . '/' . $subdir;
        if (!is_dir($temp)) {
            mkdir($temp, 0755);
        }
        if (!is_writable($temp)) {
            throw new InvalidArgumentException("Can't write to directory {$temp}, skipping.");
        }
        return $temp;
    }

    /**
     * Process an XML file.
     *
     * @param $filename
     *   The path to the file.
     * @param $args
     *   Various command arguments.
     */
    private function doFile($filename, $args)
    {
        $filename = realpath($filename);
        if (!is_file($filename) || !is_readable($filename)) {
            throw new \InvalidArgumentException("File {$filename} doesn't exist or is not readable.");
        }

        $output_filename = $this->getOutputFile($filename, $args);

        $dom = new \DOMDocument();
        $dom->load($filename);
        $json = new RabbitFish($dom);

        $this->writeFile($output_filename, $json);
    }

    /**
     * Get the path to the output JSON file.
     *
     * @param $filename
     *   The source file path.
     * @param $args
     *   Various command arguments.
     * @return string
     *   The output file path.
     */
    private function getOutputFile($filename, $args)
    {
        $short_name = pathinfo($filename, PATHINFO_FILENAME);
        $output_filename = $args['output'] . "/" . $short_name . ".json";
        if (file_exists($output_filename) && !$args['overwrite']) {
            throw new \InvalidArgumentException("Output file {$output_filename} exists and overwrite not specified, skipping.");
        }
        return $output_filename;
    }

    /**
     * Actually write the file out.
     *
     * @param $output_file
     *   The output file path.
     * @param $json
     *   The JSON to persist.
     */
    private function writeFile($output_file, $json)
    {
        $fp = @fopen($output_file, 'w');
        fwrite($fp, $json);
        fclose($fp);
    }

}