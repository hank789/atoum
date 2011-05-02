<?php

namespace mageekguy\atoum\scripts;

use
	\mageekguy\atoum,
	\mageekguy\atoum\exceptions,
	\mageekguy\atoum\scripts\phar,
	\mageekguy\atoum\scripts\builder
;

class builder extends atoum\script
{
	protected $php = null;
	protected $vcs = null;
	protected $taggerEngine = null;
	protected $superglobals = null;
	protected $revision = null;
	protected $version = null;
	protected $unitTestRunnerScript = null;
	protected $pharGeneratorScript = null;
	protected $workingDirectory = null;
	protected $destinationDirectory = null;
	protected $scoreDirectory = null;
	protected $errorsDirectory = null;
	protected $revisionFile = null;
	protected $runFile = null;
	protected $pharCreationEnabled = true;
	protected $checkUnitTests = true;
	protected $reportTitle = null;
	protected $fileIteratorInjector = null;
	protected $runnerConfigurationFiles = array();

	public function __construct($name, atoum\locale $locale = null, atoum\adapter $adapter = null)
	{
		parent::__construct($name, $locale, $adapter);

		$this
			->setSuperglobals(new atoum\superglobals())
			->setRunFile($this->adapter->sys_get_temp_dir() . \DIRECTORY_SEPARATOR . md5(get_class($this)))
		;
	}

	public function setVcs(builder\vcs $vcs)
	{
		$this->vcs = $vcs;

		return $this;
	}

	public function getVcs()
	{
		return $this->vcs;
	}

	public function setTaggerEngine(atoum\scripts\tagger\engine $engine)
	{
		$this->taggerEngine = $engine;

		return $this;
	}

	public function getTaggerEngine()
	{
		return $this->taggerEngine;
	}

	public function setPhpPath($path)
	{
		$this->php = (string) $path;

		return $this;
	}

	public function getPhpPath()
	{
		if ($this->php === null)
		{
			if (isset($this->superglobals->_SERVER['_']) === false)
			{
				throw new exceptions\runtime('Unable to find PHP executable');
			}

			$this->setPhpPath($this->superglobals->_SERVER['_']);
		}

		return $this->php;
	}

	public function getRunnerConfigurationFiles()
	{
		return $this->runnerConfigurationFiles;
	}

	public function addRunnerConfigurationFile($file)
	{
		$this->runnerConfigurationFiles[] = (string) $file;

		return $this;
	}

	public function enablePharCreation()
	{
		$this->pharCreationEnabled = true;

		return $this;
	}

	public function disablePharCreation()
	{
		$this->pharCreationEnabled = false;

		return $this;
	}

	public function pharCreationIsEnabled()
	{
		return $this->pharCreationEnabled;
	}

	public function disableUnitTestChecking()
	{
		$this->checkUnitTests = false;

		return $this;
	}

	public function enableUnitTestChecking()
	{
		$this->checkUnitTests = true;

		return $this;
	}

	public function unitTestCheckingIsEnabled()
	{
		return $this->checkUnitTests;
	}

	public function setVersion($version)
	{
		$this->version = (string) $version;

		return $this;
	}

	public function getVersion()
	{
		return $this->version;
	}

	public function setSuperglobals(atoum\superglobals $superglobals)
	{
		$this->superglobals = $superglobals;

		return $this;
	}

	public function getSuperglobals()
	{
		return $this->superglobals;
	}

	public function setScoreDirectory($path)
	{
		$this->scoreDirectory = static::cleanDirectoryPath($path);

		return $this;
	}

	public function getScoreDirectory()
	{
		return $this->scoreDirectory;
	}

	public function setErrorsDirectory($path)
	{
		$this->errorsDirectory = static::cleanDirectoryPath($path);

		return $this;
	}

	public function getErrorsDirectory()
	{
		return $this->errorsDirectory;
	}

	public function setDestinationDirectory($path)
	{
		$this->destinationDirectory = static::cleanDirectoryPath($path);

		return $this;
	}

	public function getDestinationDirectory()
	{
		return $this->destinationDirectory;
	}

	public function setWorkingDirectory($path)
	{
		$this->workingDirectory = static::cleanDirectoryPath($path);

		return $this;
	}

	public function getWorkingDirectory()
	{
		return $this->workingDirectory;
	}

	public function setRevisionFile($path)
	{
		$this->revisionFile = (string) $path;

		return $this;
	}

	public function getRevisionFile()
	{
		return $this->revisionFile;
	}

	public function setReportTitle($title)
	{
		$this->reportTitle = (string) $title;

		return $this;
	}

	public function getReportTitle()
	{
		return $this->reportTitle;
	}

	public function setUnitTestRunnerScript($path)
	{
		$this->unitTestRunnerScript = (string) $path;

		return $this;
	}

	public function getUnitTestRunnerScript()
	{
		return $this->unitTestRunnerScript;
	}

	public function setPharGeneratorScript($path)
	{
		$this->pharGeneratorScript = (string) $path;

		return $this;
	}

	public function getPharGeneratorScript()
	{
		return $this->pharGeneratorScript;
	}

	public function setRunFile($path)
	{
		$this->runFile = $path;

		return $this;
	}

	public function getRunFile()
	{
		return $this->runFile !== null ? $this->runFile : $this->adapter->sys_get_temp_dir() . \DIRECTORY_SEPARATOR . md5(get_class($this));
	}

	public function getFileIterator($directory)
	{
		if ($this->fileIteratorInjector === null)
		{
			$this->setFileIteratorInjector(function ($directory) { return new \recursiveIteratorIterator(new atoum\src\iterator\filter(new \recursiveDirectoryIterator($directory))); });
		}

		return $this->fileIteratorInjector->__invoke($directory);
	}

	public function setFileIteratorInjector(\closure $fileIteratorInjector)
	{
		$closure = new \reflectionMethod($fileIteratorInjector, '__invoke');

		if ($closure->getNumberOfParameters() != 1)
		{
			throw new exceptions\logic('File iterator injector must take one argument');
		}

		$this->fileIteratorInjector = $fileIteratorInjector;

		return $this;
	}

	public function checkUnitTests()
	{
		$status = true;

		if ($this->checkUnitTests === true)
		{
			if ($this->workingDirectory === null)
			{
				throw new exceptions\logic('Unable to check unit tests, working directory is undefined');
			}

			if ($this->vcs === null)
			{
				throw new exceptions\logic('Unable to check unit tests, version control system is undefined');
			}

			if ($this->unitTestRunnerScript === null)
			{
				throw new exceptions\logic('Unable to check unit tests, unit tests runner script is undefined');
			}

			$this->vcs->exportRepository($this->workingDirectory);

			$descriptors = array(
				1 => array('pipe', 'w'),
				2 => array('pipe', 'w')
			);

			$scoreFile = $this->scoreDirectory === null ? $this->adapter->tempnam($this->adapter->sys_get_temp_dir(), '') : $this->scoreDirectory . DIRECTORY_SEPARATOR . $this->vcs->getRevision();

			$phpPath = $this->getPhpPath();

			$command = $phpPath . ' ' . $this->workingDirectory . \DIRECTORY_SEPARATOR . $this->unitTestRunnerScript . ($this->reportTitle === null ? '' : ' -drt ' . escapeshellarg($this->reportTitle)) . ' -ncc -sf ' . $scoreFile . ' -d ' . $this->workingDirectory . \DIRECTORY_SEPARATOR . 'tests' . \DIRECTORY_SEPARATOR . 'units' . \DIRECTORY_SEPARATOR . 'classes -p ' . $phpPath;

			foreach ($this->runnerConfigurationFiles as $runnerConfigurationFile)
			{
				$command .= ' -c ' . $runnerConfigurationFile;
			}

			try
			{
				$php = $this->adapter->invoke('proc_open', array($command, $descriptors, & $pipes));

				if ($php === false)
				{
					throw new exceptions\runtime('Unable to execute \'' . $command . '\'');
				}

				$phpStatus = $this->adapter->proc_get_status($php);

				if ($phpStatus['running'] === false)
				{
					switch ($phpStatus['exitcode'])
					{
						case 126:
						case 127:
							throw new exceptions\runtime('Unable to find \'' . $phpPath . '\' or it is not executable');

						default:
							throw new exceptions\runtime('Command \'' . $command . '\' failed with exit code \'' . $phpStatus['exitcode'] . '\'');
					}
				}

				$stdOut = $this->adapter->stream_get_contents($pipes[1]);
				$this->adapter->fclose($pipes[1]);

				$stdErr = $this->adapter->stream_get_contents($pipes[2]);
				$this->adapter->fclose($pipes[2]);

				$this->adapter->proc_close($php);

				if ($stdErr != '')
				{
					throw new exceptions\runtime($stdErr);
				}

				$score = @$this->adapter->file_get_contents($scoreFile);

				if ($score === false)
				{
					throw new exceptions\runtime('Unable to read score from file \'' . $scoreFile . '\'');
				}

				$score = $this->adapter->unserialize($score);

				if ($score === false)
				{
					throw new exceptions\runtime('Unable to unserialize score from file \'' . $scoreFile . '\'');
				}

				if ($score instanceof atoum\score === false)
				{
					throw new exceptions\runtime('Contents of file \'' . $scoreFile . '\' is not a score');
				}

				$status = $score->getFailNumber() === 0 && $score->getExceptionNumber() === 0 && $score->getErrorNumber() === 0;
			}
			catch (\exception $exception)
			{
				$this->writeErrorInErrorsDirectory($exception->getMessage());

				$status = false;
			}

			if ($this->scoreDirectory === null)
			{
				if ($this->adapter->unlink($scoreFile) === false)
				{
					throw new exceptions\runtime('Unable to delete score file \'' . $scoreFile . '\'');
				}
			}
		}

		return $status;
	}

	public function createPhar($version = null)
	{
		$pharBuilt = true;

		if ($this->pharCreationEnabled === true)
		{
			if ($this->vcs === null)
			{
				throw new exceptions\logic('Unable to create phar, version control system is undefined');
			}

			if ($this->destinationDirectory === null)
			{
				throw new exceptions\logic('Unable to create phar, destination directory is undefined');
			}

			if ($this->workingDirectory === null)
			{
				throw new exceptions\logic('Unable to create phar, working directory is undefined');
			}

			if ($this->revisionFile !== null)
			{
				$revision = @$this->adapter->file_get_contents($this->revisionFile);

				if (is_numeric($revision) === true)
				{
					$this->vcs->setRevision($revision);
				}
			}

			$revisions = $this->vcs->getNextRevisions();

			if (sizeof($revisions) > 0)
			{
				$descriptors = array(
					2 => array('pipe', 'w')
				);

				$command = $this->getPhpPath() . ' -d phar.readonly=0 -f ' . $this->workingDirectory . \DIRECTORY_SEPARATOR . $this->pharGeneratorScript . ' -- -d ' . $this->destinationDirectory;

				while (sizeof($revisions) > 0)
				{
					$revision = array_shift($revisions);

					$this->vcs->setRevision($revision);

					try
					{
						if ($this->checkUnitTests() === true)
						{
							if ($this->checkUnitTests === false)
							{
								$this->vcs->exportRepository($this->workingDirectory);
							}

							if ($this->taggerEngine !== null)
							{
								$this->taggerEngine
									->setSrcDirectory($this->workingDirectory)
									->setVersion($version !== null ? $version : 'nightly-' . $revision . '-' . $this->adapter->date('YmdHi'))
									->tagVersion()
								;
							}

							$php = $this->adapter->invoke('proc_open', array($command, $descriptors, & $pipes));

							if ($php === false)
							{
								throw new exceptions\runtime('Unable to execute \'' . $command . '\'');
							}

							$stdErr = $this->adapter->stream_get_contents($pipes[2]);

							$this->adapter->fclose($pipes[2]);

							$this->adapter->proc_close($php);

							if ($stdErr != '')
							{
								throw new exceptions\runtime($stdErr);
							}
						}
					}
					catch (\exception $exception)
					{
						$pharBuilt = false;

						$this->writeErrorInErrorsDirectory($exception->getMessage());
					}

					if ($this->revisionFile !== null && $this->adapter->file_put_contents($this->revisionFile, $revision, \LOCK_EX) === false)
					{
						throw new exceptions\runtime('Unable to save last revision in file \'' . $this->revisionFile . '\'');
					}

					$revisions = $this->vcs->getNextRevisions();
				}
			}
		}

		return $pharBuilt;
	}

	public function run(array $arguments = array())
	{
		$builder = $this;

		$this->argumentsParser->addHandler(
			function($script, $argument, $values) {
				if (sizeof($values) != 0)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				$script
					->disablePharCreation()
					->help()
				;
			},
			array('-h', '--help')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $files) use ($builder) {
				if (sizeof($files) <= 0)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				foreach ($files as $file)
				{
					if (file_exists($file) === false)
					{
						throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Configuration file path \'%s\' is invalid'), $file));
					}

					if (is_readable($file) === false)
					{
						throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Unable to read configuration file \'%s\''), $file));
					}

					require_once($file);
				}
			},
			array('-c', '--configuration-files')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $files) use ($builder) {
				if (sizeof($files) <= 0)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				foreach ($files as $file)
				{
					if (file_exists($file) === false)
					{
						throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Runner configuration file path \'%s\' is invalid'), $file));
					}

					if (is_readable($file) === false)
					{
						throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Unable to read runner configuration file \'%s\''), $file));
					}

					$script->addRunnerConfigurationFile($file);
				}
			},
			array('-rc', '--runner-configuration-files')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $path) {
				if (sizeof($path) != 1)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				$script->setPhpPath(current($path));
			},
			array('-p', '--php')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $directory) {
				if (sizeof($directory) != 1)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				$script->setWorkingDirectory(current($directory));
			},
			array('-w', '--working-directory')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $directory) {
				if (sizeof($directory) != 1)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				$script->setDestinationDirectory(current($directory));
			},
			array('-d', '--destination-directory')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $url) {
				if (sizeof($url) != 1)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				$script->setRepositoryUrl(current($url));
			},
			array('-r', '--repository-url')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $directory) {
				if (sizeof($directory) != 1)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				$script->setScoreDirectory(current($directory));
			},
			array('-sd', '--score-directory')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $file) {
				if (sizeof($file) != 1)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				$script->setRevisionFile(current($file));
			},
			array('-rf', '--revision-file')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $directory) {
				if (sizeof($directory) != 1)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				$script->setErrorsDirectory(current($directory));
			},
			array('-ed', '--errors-directory')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $version) {
				if (sizeof($version) != 1)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				$script->setVersion(current($version));
			},
			array('-v', '--version')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $unitTestRunnerScript) {
				if (sizeof($unitTestRunnerScript) != 1)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				$script->setUnitTestRunnerScript(current($unitTestRunnerScript));
			},
			array('-utrs', '--unit-test-runner-script')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $pharGeneratorScript) {
				if (sizeof($pharGeneratorScript) != 1)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				$script->setPharGeneratorScript(current($pharGeneratorScript));
			},
			array('-pgs', '--phar-generator-script')
		);

		$this->argumentsParser->addHandler(
			function($script, $argument, $reportTitle) {
				if (sizeof($reportTitle) != 1)
				{
					throw new exceptions\logic\invalidArgument(sprintf($script->getLocale()->_('Bad usage of %s, do php %s --help for more informations'), $argument, $script->getName()));
				}

				$script->setReportTitle(current($reportTitle));
			},
			array('-rt', '--report-title')
		);

		parent::run($arguments);

		$alreadyRun = false;

		$pid = @$this->adapter->file_get_contents($this->runFile);

		if (is_numeric($pid) === false || $this->adapter->posix_kill($pid, 0) === false)
		{
			if ($this->pharCreationEnabled === true)
			{
				$runFile = @$this->adapter->fopen($this->runFile, 'w+');

				if ($runFile === false)
				{
					throw new exceptions\runtime(sprintf($this->locale->_('Unable to open run file \'%s\''), $this->runFile));
				}

				if ($this->adapter->flock($runFile, \LOCK_EX | \LOCK_NB) === false)
				{
					throw new exceptions\runtime(sprintf($this->locale->_('Unable to get exclusive lock on run file \'%s\''), $this->runFile));
				}

				$this->adapter->fwrite($runFile, $this->adapter->getmypid());

				$this->createPhar($this->version);

				$this->adapter->fclose($runFile);
				@$this->adapter->unlink($this->runFile);
			}
		}

		return $this;
	}

	public function help()
	{
		$this
			->writeMessage(sprintf($this->locale->_('Usage: %s [options]'), $this->getName()) . PHP_EOL)
			->writeMessage($this->locale->_('Available options are:') . PHP_EOL)
		;

		$this->writeLabels(
			array(
				'-h, --help' => $this->locale->_('Display this help'),
				'-c <file>, --configuration-file <file>' => $this->locale->_('Use <file> as configuration file for builder'),
				'-rc <file>, --runner-configuration-file <file>' => $this->locale->_('Use <file> as configuration file for runner'),
				'-v <string>, --v <string>' => $this->locale->_('Version <string> will be used as version name'),
				'-rf <file>, --revision-file <file>' => $this->locale->_('Save last revision in <file>'),
				'-sd <file>, --score-directory <directory>' => $this->locale->_('Save score in <directory>'),
				'-r <url>, --repository-url <url>' => $this->locale->_('Url of subversion repository'),
				'-w <directory>, --working-directory <directory>' => $this->locale->_('Checkout file from subversion in <directory>'),
				'-d <directory>, --destination-directory <directory>' => $this->locale->_('Save phar in <directory>'),
				'-ed <directory>, --errors-directory <directory>' => $this->locale->_('Save errors in <directory>')
			)
		);

		return $this;
	}

	public function writeErrorInErrorsDirectory($error)
	{
		if ($this->errorsDirectory !== null)
		{
			$revision = $this->vcs === null ? null : $this->vcs->getRevision();

			if ($revision === null)
			{
				throw new exceptions\logic('Revision is undefined');
			}

			$errorFile = $this->errorsDirectory . \DIRECTORY_SEPARATOR . $revision;

			if ($this->adapter->file_put_contents($errorFile, $error, \LOCK_EX | \FILE_APPEND) === false)
			{
				throw new exceptions\runtime('Unable to save error in file \'' . $errorFile . '\'');
			}
		}

		return $this;
	}

	protected function cleanDirectoryPath($path)
	{
		return rtrim($path, DIRECTORY_SEPARATOR);
	}
}

?>
