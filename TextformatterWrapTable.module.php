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
            // fix bug 1, see https://stackoverflow.com/a/22490902/6370411
            // fix bug 2, see https://stackoverflow.com/a/8218649
            $dom->loadHTML(mb_encode_numericentity($string, [0x80, 0x10FFFF, 0, ~0], 'UTF-8'), LIBXML_NOERROR);
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
            $string = trim(mb_convert_encoding($html, "UTF-8"));
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

        # data
        $data = $event->arguments(1);

        # modify div classes
        $divClassesRaw = explode(' ', $data['divClass']);
        $divClasses = array();
        foreach ($divClassesRaw as $class) {
            $divClasses[] = wire('sanitizer')->pageNameTranslate($class);
        }

        $data['divClass'] = implode(' ', $divClasses);

        # modify table classes
        $tableClassesRaw = explode(' ', $data['tableClass']);
        $tableClasses = array();
        foreach ($tableClassesRaw as $class) {
            $tableClasses[] = wire('sanitizer')->pageNameTranslate($class);
        }

        $data['tableClass'] = implode(' ', $tableClasses);

        $event->arguments(1, $data);
    }
}
