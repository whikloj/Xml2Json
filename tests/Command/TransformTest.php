<?php
/**
 * Created by PhpStorm.
 * User: whikloj
 * Date: 2019-09-23
 * Time: 11:36
 */

namespace whikloj\tests\Command;


use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Tester\CommandTester;
use whikloj\Command\Transform;

class TransformTest extends TestCase
{

    /**
     * @var \Symfony\Component\Console\Application
     */
    private $application;

    /**
     * @var \Symfony\Component\Console\Command\Command
     */
    private $command;

    /**
     * @var \Symfony\Component\Console\Tester\CommandTester
     */
    private $tester;


    private $output;

    private $output_directory;

    private $resource_directory;

    public function __construct(
      ?string $name = null,
      array $data = array(),
      string $dataName = ''
    ) {
        parent::__construct($name, $data, $dataName);
        // Can't do realpath as it doesn't exist yet.
        $this->output_directory = __DIR__ . '/../output';
        $this->resource_directory = realpath(__DIR__ . '/../resources');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->application = new Application();
        $this->application->add(new Transform());
        $this->command = $this->application->find('app:transform');
        $this->tester = new CommandTester($this->command);
        $this->output = new ConsoleOutput();
        if (file_exists($this->output_directory)) {
            $this->deleteDirAndContents();
        } else {
           $this->setUpOutput();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        if (file_exists($this->output_directory)) {
            $this->deleteDirAndContents();
        }
    }

    /**
     * Utility to setup output directory.
     */
    private function setUpOutput()
    {
        if (!file_exists($this->output_directory)) {
            mkdir($this->output_directory, 0755);
            $this->output_directory = realpath($this->output_directory);
        }
    }

    /**
     * Utility function to remove the output directory if it exists between tests.
     *
     * @param string $folder
     *   The path to delete.
     */
    private function deleteDirAndContents($folder = NULL)
    {
        if (is_null($folder)) {
            $folder = $this->output_directory;
        }
        $dir = opendir($folder);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                $full = $folder . '/' . $file;
                if ( is_dir($full) ) {
                    $this->deleteDirAndContents($full);
                }
                else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($folder);
    }

    private function assertFilesExist(array $files, $directory = NULL) {
        $this->assertFiles($files, TRUE, $directory);
    }

    private function assertFilesNotExist(array $files, $directory) {
        $this->assertFiles($files, FALSE, $directory);
    }

    /**
     * Check if files either exist or don't exist.
     *
     * @param array $files
     *   Array of file paths.
     */
    private function assertFiles(array $files, $exists = TRUE, $directory = NULL)
    {
        if (!is_null($directory)) {
            array_walk($files, function(&$o) use ($directory) { $o = $directory . '/' . $o; });
        }
        foreach ($files as $file) {
            if ($exists) {
                $this->assertTrue(file_exists($file));
            } else {
                $this->assertFalse(file_exists($file));
            }

        }
    }

    public function testNoArgs()
    {
        $this->tester->execute([
          'command' => $this->command->getName(),
        ]);
        $output = $this->tester->getDisplay();
        $this->assertContains('You must provide either a file or directory to execute against', $output);
    }

    public function testBothArgs()
    {
        $this->tester->execute([
          'command' => $this->command->getName(),
          '--file' => 'somefile.xml',
          '--directory' => 'somedirectory/',

        ]);
        $output = $this->tester->getDisplay();
        $this->assertContains('You must provide *either* a file or directory to execute against.', $output);
    }

    public function testFile()
    {

        $input_file = $this->resource_directory . '/test_mods1.xml';
        $expected_file = $this->output_directory . '/test_mods1.json';
        $this->assertFalse(file_exists($expected_file));
        $this->tester->execute([
            'command' => $this->command->getName(),
            '--file' => $input_file,
            '--output' => $this->output_directory,
        ]);
        $this->assertTrue(file_exists($expected_file));
    }

    public function testDirectory()
    {
        $input_directory = $this->resource_directory . '/subdir';
        $expected_files = [
            'resource2.json',
            'resource3.json',
        ];
        $unexpected_files = [
            'another_dir/resource4.json',
            'another_dir/resource5.json',
        ];
        $this->assertFilesNotExist(array_merge($expected_files, $unexpected_files), $this->output_directory);

        $this->tester->execute([
            'command' => $this->command->getName(),
            '--directory' => $input_directory,
            '--output' => $this->output_directory,
        ]);
        $this->assertEquals(0, $this->tester->getStatusCode());
        $this->assertFilesExist($expected_files, $this->output_directory);
        $this->assertFilesNotExist($unexpected_files, $this->output_directory);

    }

    public function testSubDirectory()
    {
        $input_directory = $this->resource_directory . '/subdir';
        $expected_files = [
            'resource2.json',
            'resource3.json',
            'another_dir/resource4.json',
            'another_dir/resource5.json',
        ];
        $this->assertFilesNotExist($expected_files, $this->output_directory);

        $this->tester->execute([
            'command' => $this->command->getName(),
            '--directory' => $input_directory,
            '--output' => $this->output_directory,
            '--recurse' => TRUE,
        ]);
        $this->assertEquals(0, $this->tester->getStatusCode());
        $this->assertFilesExist($expected_files, $this->output_directory);
    }
}