<?php

/**
 * Simple Machines Forum (SMF)
 *
 * @package SMF
 * @author Simple Machines https://www.simplemachines.org
 * @copyright 2022 Simple Machines and individual contributors
 * @license https://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.1.0
 */

// Stuff we will ignore.
$ignoreFiles = array(
	'index\.php',
	'\.(?!php)[^.]*$',
);

try
{
	if (($upgradeFile = fopen('./other/upgrade.php', 'r')) !== false)
	{
		$upgradeContents = fread($upgradeFile, 1250);

		// In production, only check index.english.php
		if (!preg_match('~define\(\'SMF_VERSION\', \'([^\']+)\'\);~i', $upgradeContents, $versionResults))
			throw new Exception('Error: Could not locate SMF_VERSION');
		if (version_compare($versionResults[1], '2.1.0', '>'))
			$ignoreFiles[] = '^(?!index\.)';

		// We need SMF_LANG_VERSION, obviously.
		if (!preg_match('~define\(\'SMF_LANG_VERSION\', \'([^\']+)\'\);~i', $upgradeContents, $versionResults))
			throw new Exception('Error: Could not locate SMF_LANG_VERSION');
		$currentVersion = $versionResults[1];

		$language_dir = './Themes/default/languages';
		if (file_exists('./Languages'))
			$language_dir = './Languages';

		foreach (new DirectoryIterator($language_dir) as $fileInfo)
		{
			if ($fileInfo->getExtension() == 'php')
			{
				foreach ($ignoreFiles as $if)
					if (preg_match('~' . $if . '~i', $fileInfo->getFilename()))
						continue 2;

				if (($file = fopen($fileInfo->getPathname(), 'r')) !== false)
				{
					$contents = fread($file, 500);

					// Just see if the basic match is there.
					$match = '// Version: ' . $currentVersion;
					if (!preg_match('~' . $match . '~i', $contents))
						throw new Exception('Error: The version is missing or incorrect in ' . $fileInfo->getFilename());

					// Get the file prefix.
					preg_match('~([A-Za-z]+)\.english\.php~i', $fileInfo->getFilename(), $fileMatch);
					if (empty($fileMatch))
						throw new Exception('Error: Could not locate the file name in ' . $fileInfo->getFilename());

					// Now match that prefix in a more strict mode.
					$match = '// Version: ' . $currentVersion . '; ' . $fileMatch[1];
					if (!preg_match('~' . $match . '~i', $contents))
						throw new Exception('Error: The version with file name is missing or incorrect in ' . $fileInfo->getFilename());
				}
				else
					throw new Exception('Unable to open file ' . $fileInfo->getFilename());
			}
		}
	}
	else
		throw new Exception('Unable to open file ./upgrade.php');
}
catch (Exception $e)
{
	fwrite(STDERR, $e->getMessage());
	exit(1);
}