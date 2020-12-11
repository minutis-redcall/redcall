<?php

namespace App\Command;

use App\Services\Phrase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * This command:
 * - fetch all translations on PhraseApp
 * - create missing translations on PhraseApp
 * - remove extra translations from PhraseApp
 */
class PhraseCommand extends Command
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var Phrase
     */
    private $phrase;

    public function __construct(KernelInterface $kernel, Phrase $phrase)
    {
        parent::__construct();

        $this->kernel = $kernel;
        $this->phrase = $phrase;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('phrase:sync')
            ->setDescription('Synchronize translations with PhraseApp')
            ->addOption('sleep', null, InputOption::VALUE_OPTIONAL, 'Use this option for large operations to prevent hitting rate limits', 0)
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Add this option to automatically delete translations that are on Phrase but no more on the app')
            ->addOption('create', null, InputOption::VALUE_NONE, 'Add this option to automatically create translations that are on the app but not yet on Phrase');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // We first fetch all different tags and translations we have in the project
        $tags              = [];
        $localTranslations = [];
        $localFiles        = $this->searchTranslationFilesInProject();
        foreach ($localFiles as $localFile) {
            $tag = $this->getPhraseTagFromFilename($localFile);
            if (!in_array($tag, $tags)) {
                $tags[] = $tag;
            }
            $localTranslations[$localFile] = $this->extractTranslationsFromFile($localFile);
        }

        // For every lang and tag, we now download translations
        $remoteTranslations = [];
        $remoteFiles        = [];
        $locales            = $this->phrase->getLocales();
        foreach ($locales as $localeId => $locale) {
            foreach ($tags as $tag) {
                $remoteFile                      = $this->getFilenameFromPhraseTag($tag, $locale);
                $remoteTranslations[$remoteFile] = $this->downloadRemoteFile($localeId, $tag);
                $remoteFiles[]                   = $remoteFile;
            }
        }

        // Searching for missing keys on Phrase
        foreach ($localFiles as $file) {
            $localKeys  = array_keys($localTranslations[$file]);
            $remoteKeys = array_keys($remoteTranslations[$file]);
            $keysToAdd  = array_diff($localKeys, $remoteKeys);
            foreach ($keysToAdd as $key) {
                $tag    = $this->getPhraseTagFromFilename($file);
                $locale = $this->getLocaleFromFileName($file);
                $value  = $localTranslations[$file][$key];
                if ($input->getOption('create')) {
                    $output->writeln(sprintf('<info>Creating missing translation %s for locale %s: %s</info>', $key, $locale, $value));
                    $this->phrase->createTranslation($tag, array_search($locale, $locales), $key, $value);
                    $remoteTranslations[$file][$key] = $value;
                    if ($input->getOption('sleep')) {
                        sleep($input->getOption('sleep'));
                    }
                } else {
                    $output->writeln(sprintf('<info>Missing translation %s for locale %s: %s - Consider creating it on Phrase</info>', $key, $locale, $value));
                }
            }
        }

        // Searching for expired keys (existing on Phrase but not used anymore by the app)
        $allLocalKeys  = array_unique(call_user_func_array('array_merge', array_map(function (array $localTranslation) {
            return array_keys($localTranslation);
        }, $localTranslations)));
        $allRemoteKeys = array_unique(call_user_func_array('array_merge', array_map(function (array $remoteTranslation) {
            return array_keys($remoteTranslation);
        }, $remoteTranslations)));
        $keysToRemove  = array_diff($allRemoteKeys, $allLocalKeys);
        foreach ($keysToRemove as $key) {
            if ($input->getOption('delete')) {
                $output->writeln(sprintf('<comment>Removing unused translation key: %s</comment>', $key));
                $this->phrase->removeKey($key);
                if ($input->getOption('sleep')) {
                    sleep($input->getOption('sleep'));
                }
            } else {
                $output->writeln(sprintf('<comment>Unused translation key: %s - consider deleting it on Phrase</comment>', $key));
            }
        }

        // Dumping files
        foreach ($remoteTranslations as $file => $keys) {
            $oldContent = is_file($file) ? file_get_contents($file) : null;
            $newContent = Yaml::dump($this->getDeflattedTranslationsFromArray($keys), 64, 2);
            if ($oldContent !== $newContent) {
                file_put_contents($file, $newContent);
                $output->writeln(sprintf('Translations updated: %s', $file));
            }
        }
    }

    /**
     * On Phrase, I added tags for every translation files, they are in the format:
     * <location>_<domain>. Location can either be a bundle directory or the root
     * directory, named "app".
     *
     * Examples:
     * translations/validators.en.yaml => app_validators
     * bundles/password-login-bundle/Resources/translations/messages.fr.yml => password-login-bundle_messages
     *
     * @param string $absolutePath
     *
     * @return string
     */
    private function getPhraseTagFromFilename(string $absolutePath) : string
    {
        [$location, $domain, $locale] = $this->getContextFromFilename($absolutePath);

        return sprintf('%s_%s', $location, $domain);
    }

    private function getContextFromFilename(string $absolutePath) : array
    {
        $relativePath = substr($absolutePath, strlen($this->kernel->getProjectDir()) + 1);
        $matches      = [];
        if (0 === strpos($relativePath, 'bundles')) {
            preg_match('|^bundles/(?<bundle>[^/]+)/Resources/translations/(?<domain>.*)\.(?<locale>.*).ya?ml$|', $relativePath, $matches);
            $location = $matches['bundle'];
            $domain   = $matches['domain'];
            $locale   = $matches['locale'];
        } else {
            preg_match('|^translations/(?<domain>.*)\.(?<locale>.*).yml$|', $relativePath, $matches);
            $location = 'app';
            $domain   = $matches['domain'];
            $locale   = $matches['locale'];
        }

        return [$location, $domain, $locale];
    }

    private function getLocaleFromFileName(string $file) : string
    {
        [$location, $domain, $locale] = $this->getContextFromFilename($file);

        return $locale;
    }

    private function getFilenameFromPhraseTag(string $tag, string $locale) : string
    {
        if (0 === strpos($tag, 'app_')) {
            return sprintf('%s/translations/%s.%s.yml', $this->kernel->getProjectDir(), substr($tag, 4), $locale);
        }

        return sprintf(
            '%s/bundles/%s/Resources/translations/%s.%s.yml',
            $this->kernel->getProjectDir(),
            substr($tag, 0, strpos($tag, '_')),
            substr($tag, strpos($tag, '_') + 1),
            $locale
        );
    }

    /**
     * All translation files in RedCall are in YAML format, and located in a translations
     * directory. They are either in the main project (app) or in a bundle. They are always
     * named in the format <domain>.<locale>.yml
     *
     * @return array
     */
    private function searchTranslationFilesInProject() : array
    {
        $files = [];
        $dir   = new \RecursiveDirectoryIterator($this->kernel->getProjectDir());
        $ite   = new \RecursiveIteratorIterator($dir);
        $reg   = new \RegexIterator($ite, '|.*/translations/.*\.yml$|', \RegexIterator::GET_MATCH);
        foreach ($reg as $file) {
            $files = array_merge($files, $file);
        }

        return $files;
    }

    private function extractTranslationsFromFile(string $file) : array
    {
        $array = Yaml::parseFile($file);

        return $this->getFlattenArrayFromTranslations($array);
    }

    private function downloadRemoteFile(string $localeId, string $tag)
    {
        $yaml = $this->phrase->download($localeId, $tag);

        $array = Yaml::parse($yaml);

        if (!$array) {
            return [];
        }

        return $this->getFlattenArrayFromTranslations($array);;
    }

    /**
     * Transforms a multidimensional array into a flatten one.
     *
     * [
     *     'a' => 'b',
     *     'c' => [
     *         'd' => 'e',
     *         'f' => 'g',
     *     ],
     * ]
     *
     * Should become:
     * [
     *     'a'   => 'b',
     *     'c.d' => 'e',
     *     'c.f' => 'g',
     * ]
     *
     * @param array $translations
     *
     * @return array
     */
    private function getFlattenArrayFromTranslations(array $translations) : array
    {
        $array = [];

        foreach ($translations as $key => $value) {
            if (is_scalar($value)) {
                $array[$key] = $value;
            } else {
                foreach ($this->getFlattenArrayFromTranslations($value) as $childKey => $childValue) {
                    $array[sprintf('%s.%s', $key, $childKey)] = $childValue;
                }
            }
        }

        return $array;
    }

    private function getDeflattedTranslationsFromArray(array $array) : array
    {
        $translations = [];

        foreach ($array as $key => $value) {
            $ref = &$translations;
            foreach (explode('.', $key) as $node) {
                if (!array_key_exists($node, $ref)) {
                    $ref[$node] = [];
                }
                $ref = &$ref[$node];
            }
            $ref = $value;
        }

        return $translations;
    }
}