<?php
/**
 * Copyright 2022-2023 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Theme\Model;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class Theme
{
    public function __construct(private $name)
    {
        if (!file_exists($this->getPath())) {
            throw new \FOSSBilling\Exception('Theme ":name" does not exist', [':name' => $name]);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function isAdminAreaTheme()
    {
        return str_contains($this->name, 'admin_');
    }

    public function isAssetsPathWritable()
    {
        return is_writable($this->getPathAssets());
    }

    public function getSnippets()
    {
        $path = $this->getPathHtml();
        $snippets = glob($path . DIRECTORY_SEPARATOR . 'snippet_*.html.twig');
        $result = [];
        foreach ($snippets as $snippet) {
            $result[basename($snippet)] = str_replace('snippet_', '', pathinfo($snippet, PATHINFO_FILENAME));
        }

        return $result;
    }

    public function getUploadedAssets()
    {
        $assets_folder = $this->getPathAssets();
        $files = $this->getSettingsPageFiles();
        $uploaded = [];
        foreach ($files as $file) {
            if (file_exists(Path::normalize($assets_folder . '/' . $file))) {
                $uploaded[] = [
                    'name' => $file,
                    'url' => $this->getUrl() . '/assets/' . $file,
                ];
            }
        }

        return $uploaded;
    }

    private function getSettingsPageFiles()
    {
        $str = $this->getSettingsPageHtml();
        if (empty($str)) {
            return [];
        }

        $dom = new \DOMDocument();
        $dom->loadHTML($str);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query("//input[@type='file']/@name");
        $files = [];
        foreach ($nodes as $node) {
            $files[] = $node->textContent;
        }

        return $files;
    }

    /**
     * @return string
     */
    public function getSettingsPageHtml()
    {
        $spp = Path::normalize($this->getPathConfig() . '/settings.html');
        if (!file_exists($spp)) {
            error_log('Theme ' . $this->getName() . ' does not have settings page');

            return '';
        }

        $settings_page = file_get_contents($spp);
        $settings_page = $this->strip_tags_content($settings_page, '<script><style>');

        // remove style attributes
        $settings_page = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $settings_page);

        // fix unclosed text area
        $settings_page = preg_replace('/<textarea (.*)\/>/i', '<textarea $1></textarea>', $settings_page);

        return $settings_page;
    }

    private function getSettingsData()
    {
        $cp = $this->getPathSettingsDataFile();
        if (!file_exists($cp)) {
            return [];
        }

        $json = file_get_contents($cp);
        $array = json_decode($json, 1);
        if (!is_array($array)) {
            return [];
        }

        return $array;
    }

    public function getPresetsFromSettingsDataFile()
    {
        $array = $this->getSettingsData();

        return $array['presets'] ?? [];
    }

    public function getCurrentPreset()
    {
        $array = $this->getSettingsData();

        return $array['current'] ?? 'Default';
    }

    public function getPresetFromSettingsDataFile($preset)
    {
        $array = $this->getPresetsFromSettingsDataFile();

        return (is_array($array) && isset($array[$preset])) ? $array[$preset] : [];
    }

    public function getUrl()
    {
        return SYSTEM_URL . 'themes/' . $this->name;
    }

    public function getPath()
    {
        return Path::normalize(PATH_THEMES . '/' . $this->name);
    }

    public function getPathConfig()
    {
        return Path::normalize($this->getPath() . '/config');
    }

    public function getPathAssets()
    {
        return Path::normalize($this->getPath() . '/assets');
    }

    public function getPathHtml()
    {
        return Path::normalize($this->getPath() . '/html');
    }

    public function getPathSettingsDataFile()
    {
        return Path::normalize($this->getPathConfig() . '/settings_data.json');
    }

    /**
     * @param string $text
     */
    private function strip_tags_content($text, $tags = '', $invert = true)
    {
        preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
        $tags = array_unique($tags[1]);

        if (is_array($tags) && !empty($tags)) {
            if ($invert === false) {
                return preg_replace('@<(?!(?:' . implode('|', $tags) . ')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
            } else {
                return preg_replace('@<(' . implode('|', $tags) . ')\b.*?>.*?</\1>@si', '', $text);
            }
        } elseif ($invert === false) {
            return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
        }

        return $text;
    }
}
