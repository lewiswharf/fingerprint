<?php

class Extension_Fingerprint extends Extension {

    /*-------------------------------------------------------------------------
        Delegates:
    -------------------------------------------------------------------------*/

    public function getSubscribedDelegates() {
        return array(
            array(
                'page' => '/system/preferences/',
                'delegate' => 'AddCustomPreferenceFieldsets',
                'callback' => 'addCustomPreferenceFieldsets'
            ),
            array(
                'page' => '/system/preferences/',
                'delegate' => 'Save',
                'callback' => 'savePreferences'
            ),
            array(
                'page' => '/frontend/',
                'delegate' => 'FrontendOutputPostGenerate',
                'callback' => 'actionCreateFingerprint'
            ),
            array(
                'page' => '/frontend/',
                'delegate' => 'EventPreSaveFilter',
                'callback' => 'eventPreSaveFilter'
            ),
        );
    }

    /*-------------------------------------------------------------------------
        Definition:
    -------------------------------------------------------------------------*/

    public function actionCreateFingerprint($context) {
        $doc = new DOMDocument;
        // Suppress warning
        if (@!$doc->loadHTML($context['output'])) {
            Administration::instance()->customError(__('Error creating fingerprint'), __('Page could not be read to create fingerprint.'));
        } else {
            $xpath = new DOMXpath($doc);
            $fields = array();
            foreach ($xpath->query('//form[@method="post"]//input[@type="hidden"]') as $input) {
                $fields[] = $input->getAttribute('name');
                $values .= $input->getAttribute('value');
            }
            if ($values == '') return true;

            $s = & $_SESSION[__SYM_COOKIE_PREFIX_ . 'fingerprint'];

            $s['fields'] = serialize($fields);
            $s['token'] = sha1($values . Symphony::Configuration()->get('secret', 'fingerprint'));
        }
    }

    public function eventPreSaveFilter($context) {
        if (!isset($_SESSION[__SYM_COOKIE_PREFIX_ . 'fingerprint'])) return true;

        $s = & $_SESSION[__SYM_COOKIE_PREFIX_ . 'fingerprint'];
        $fields = unserialize($s['fields']);
        foreach ($fields as $field) {
            $values .= $this->getPostValueFromName($field);
        }

        if ($s['token'] == sha1($values . Symphony::Configuration()->get('secret', 'fingerprint')))
            return true;
        else
            $context['messages'][] = array('fingerprint', false, __('Fingerprint does not match.'));

        unset($_SESSION[__SYM_COOKIE_PREFIX_ . 'fingerprint']);
    }

    public function getPostValueFromName($name) {
        $parts = preg_split('/\[|\]/i', $name, -1, PREG_SPLIT_NO_EMPTY);

        if (isset($parts[3])) {
            return $_POST[$parts[0]][$parts[1]][$parts[2][$parts[3]]];
        } elseif (isset($parts[2])) {
            return $_POST[$parts[0]][$parts[1]][$parts[2]];
        } elseif (isset($parts[1])) {
            return $_POST[$parts[0]][$parts[1]];
        } else {
            return $_POST[$parts[0]];
        }
    }

    public function addCustomPreferenceFieldsets($context) {
        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'settings');
        $fieldset->appendChild(new XMLElement('legend', __('Fingerprint')));

        $div = new XMLElement('div', null);

        // Secret
        $label = new XMLElement('label', __('Secret'));
        $label->appendChild(
            Widget::Input('settings[fingerprint][secret]', Symphony::Configuration()->get('secret', 'fingerprint'))
        );
        $div->appendChild($label);
        $fieldset->appendChild($div);
        $context['wrapper']->appendChild($fieldset);
    }

    public function savePreferences($context) {
        $settings = $context['settings'];

        Symphony::Configuration()->set('secret', $settings['fingerprint']['secret'], 'fingerprint');

        return Symphony::Configuration()->write();
    }


    public function uninstall() {
        // Clean configuration
        Symphony::Configuration()->remove('secret', 'fingerprint');

        return Symphony::Configuration()->write();
    }
}