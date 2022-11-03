<?php

namespace ProcessWire;


class TextformatterWrapTable extends Textformatter implements Module, ConfigurableModule
{

    /**
     * default config value
     */
    protected static $configDefaults = array(
        "divClass" => 'table-responsive',
        "tableClass" => 'table',
    );

    /**
     * Initialize the module
     */
    public function init()
    {
        $this->addHookBefore('Modules::saveConfig', $this, 'checkConfigValues');
    }

    /**
     * get module inputfields
     */
    public static function getModuleConfigInputfields(array $data)
    {

        foreach (self::$configDefaults as $key => $value) {
            if (!isset($data[$key]) || $data[$key] == '') {
                $data[$key] = $value;
            }
        }

        $fields = new InputfieldWrapper();

        # username
        $field = wire('modules')->get('InputfieldText');
        $field->columnWidth(50);
        $field->name = 'divClass';
        $field->label = __('div class name');
        $field->value = (isset($data['divClass'])) ? $data['divClass'] : '';
        # $field->description = __('');
        $fields->add($field);

        # password
        $field = wire('modules')->get('InputfieldText');
        $field->columnWidth(50);
        $field->name = 'tableClass';
        $field->label = __('table class name');
        $field->value = (isset($data['tableClass'])) ? $data['tableClass'] : '';
        # $field->description = __('');
        $fields->add($field);

        return $fields;
    }

    /**
     * Text formatting function as used by the Textformatter interface
     *
     * @param string $string
     */
    public function format(&$string)
    {

        if ($string) {
            $dom = new \DOMDocument('1.0', 'utf-8');
            // fix bug, see https://stackoverflow.com/a/22490902/6370411
            $dom->loadHTML($string);
            $selector = new \DOMXPath($dom);
            $length = $dom->getElementsByTagName('table')->length;

            for ($i = 0; $i < $length; $i++) {

                $table = $dom->getElementsByTagName("table")->item($i);
                $parent = $table->parentNode;

                if ($parent && $parent->tagName != 'div') {

                    # set table class
                    $table->setAttribute('class', (string)$this->tableClass);

                    # create new wrapper div
                    $div = $dom->createElement('div');
                    $div->setAttribute('class', (string)$this->divClass);

                    $clone = $div->cloneNode();
                    $table->parentNode->replaceChild($clone, $table);
                    $clone->appendChild($table);

                    # wire('log')->save('debug', $dom->saveXML($clone));
                }
            }

            $html = $dom->saveHTML($dom->documentElement);
            if (strpos($html, "<html><body>") === 0) $html = substr($html, 12);
            if ($this->endsWidth($html, "</body></html>")) $html = substr($html, 0, -14);
            $string = trim(utf8_decode($html));
        }
    }

    public function endsWidth($haystack, $needle)
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }


    /**
     * modify config values / class names
     *
     * @param HookEvent $event
     * @return void
     */
    public function checkConfigValues(HookEvent $event)
    {
        $classname = $event->arguments[0];
        if ($classname !== $this->className) return;

        # modify data
        $data = $event->arguments(1);
        $data['divClass'] = wire('sanitizer')->pageNameTranslate($data['divClass']);
        $data['tableClass'] = wire('sanitizer')->pageNameTranslate($data['tableClass']);

        $event->arguments(1, $data);
    }
}
