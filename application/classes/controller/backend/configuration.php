<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Configuration extends Controller_Backend_Base
{

    public function action_index()
    {
        $configuration = $this->loadConfig();

        if ($_POST AND $new_config = Arr::get($_POST, 'config', false)) {
            $configuration = array_merge($configuration, $new_config);
            if ($this->saveConfig($configuration)) {
                $this->msg(Helper_Message::get("backend.configuration.config_save"), 'success');
            } else {
                $this->msg(Helper_Message::get("backend.configuration.config_save_error"), 'error');
            }
        }

        ksort($configuration);
        $fields = Yaml::loadFile(APPPATH . 'data/articleconfig.yaml');

        $defaultSorts = $this->getConfigPart('sort');
        $sorts = array();
        foreach ($defaultSorts as $type => $field) {
            $sorts[$type] = $fields[$type]['sort'];
        }

        $this->view->fieldTypes = Kohana::$config->load('project.message_field_types');

        $grouped = array();
        foreach ($configuration as $key => $msg) {
            $group_key = explode('.', $key);
            $group_key = $group_key[0];
            if ($group_key != 'country') {
                $grouped[$group_key][$key] = $msg;
            }
        }

        $selectedCountries = is_array($configuration['country.sort']) ? $configuration['country.sort'] : explode('|', $configuration['country.sort']);

        $q = Doctrine::instance()->createQueryBuilder()->from('Model_Criterion_Option', 'o')->setParameter('id', $selectedCountries);
        $this->view->countries = $q->select('o')->where('o.criterion = 19 AND o.parentOption IS NULL AND o.deleted = 0 AND o.id NOT IN(:id)')->orderBy('o.title', 'asc')->getQuery()->getResult();
        $this->view->selectedCountries = $q->select("o, field(o.id, :id) as HIDDEN field")->where('o.criterion = 19 AND o.deleted = 0 AND o.parentOption IS NULL AND o.id IN(:id)')->orderBy('field')->getQuery()->getResult();
        $this->view->sorts = $sorts;
        $this->view->defaultSorts = $defaultSorts;
        $this->view->config = $grouped;
    }

    private function loadConfig()
    {
        return Helper_Message::loadFile(APPPATH . 'config/base.config');
    }

    private function getConfigPart($code)
    {
        $messages = Helper_Message::loadFile(APPPATH . 'config/base.config');

        $result = array();
        $config = array();

        foreach ($messages as $key => $value) {
            $arrKeys = explode('.', $key);
            $command = '$config["' . join('"]["', $arrKeys) . '"] = $value;';
            eval($command);
            $result = array_merge($result, $config);
        }

        if ($code) {
            if (isset($result[$code])) {
                return $result[$code];
            }
            return false;
        }
        return $result;
    }

    private function saveConfig($data)
    {
        $out = '';

        ksort($data);

        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $val = $this->getChildOptions($val);
                $out .= $key . '=' . implode('|', $val) . PHP_EOL;
            } else {
                $out .= $key . '=' . $val . PHP_EOL;
            }
        }

        return (bool)file_put_contents(APPPATH . 'config/base.config', $out);
    }

    private function getChildOptions($parentOptions)
    {
        foreach ($parentOptions as $pos => $countryId) {
            $q = Doctrine::instance()->createQueryBuilder()->from('Model_Criterion_Option', 'o')->setParameter('id', $countryId);
            $childOptions = $q->select("o")->where('o.deleted = 0 AND o.parentOption = :id')->getQuery()->getResult();
            foreach ($childOptions as $childOpt) {
                $parentOptions = $this->array_insert($parentOptions, $pos + 1, $childOpt->id);
            }
        }
        return $parentOptions;
    }

    function array_insert($array, $pos, $val)
    {
        $array2 = array_splice($array, $pos);
        $array[] = $val;
        $array = array_merge($array, $array2);

        return $array;
    }

    protected function accessAllowed()
    {
        return $this->hasRole("admin");
    }

}