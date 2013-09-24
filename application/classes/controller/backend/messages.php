<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Messages extends Controller_Backend_Base {

    public function action_index() {

        if($this->request->param('id') == 'backend') {
            $groups = $this->getGroups(Kohana::$config->load('project.backend_message_groups'));
            $messages = Kohana::$config->load('project.messages_backend');
            $messages = $this->checkPOSTMessages($messages, basename(Kohana::$config->load('project.messages_backend_file')));
        } else {
            $groups = $this->getGroups(Kohana::$config->load('project.frontend_message_groups'));
            $messages = Kohana::$config->load('project.messages');
            $messages = $this->checkPOSTMessages($messages, basename(Kohana::$config->load('project.messages_file')));
        }

        ksort($messages);

        foreach($groups as &$groupData) {
            foreach($messages as $key => $msg) {
                $group_key = explode('.', $key);

                $group_key = $this->request->param('id') == 'backend' ? $group_key[1] : $group_key[0];
                if(in_array($group_key, $groupData['fields'])) {
                    $groupData['messages'][$group_key][$key] = $msg;
                    unset($messages[$key]);
                }
            }
        }

        // Get all messages that are not in a group
        foreach($groups as &$groupData) {
            foreach($messages as $key => $msg) {
                if(empty($groupData['fields'][0])) {
                    $group_key = explode('.', $key);
                    $group_key = $group_key[0];
                    $groupData['messages'][$group_key][$key] = $msg;
                }
            }
        }

        $this->view->groups = $groups;
        $this->view->messages = $messages;
        $this->view->fieldTypes = Kohana::$config->load('project.message_field_types');
    }

    function action_groups() {
        $saveErrors = array();
        $saveErrors[] = $this->checkPOSTGroupMessages('frontend');
        $saveErrors[] = $this->checkPOSTGroupMessages('backend');
        $saveErrors[] = $this->checkPOSTGroupMessages('mobile');

        $frontendGroupFile = Kohana::$config->load('project.frontend_message_groups');
        $messagesFrontend = Helper_Message::loadFile($frontendGroupFile);

        $backendGroupFile = Kohana::$config->load('project.backend_message_groups');
        $messagesBackend = Helper_Message::loadFile($backendGroupFile);

        $mobileGroupFile = Kohana::$config->load('project.mobile_message_groups');
        $messagesMobile = Helper_Message::loadFile($mobileGroupFile);



        foreach($messagesFrontend as $key => $msg) {
           $groupData['frontend'][$key] = $msg;
        }
        ksort($groupData['frontend'], SORT_NATURAL | SORT_FLAG_CASE);

        foreach($messagesBackend as $key => $msg) {
           $groupData['backend'][$key] = $msg;
        }
        ksort($groupData['backend'], SORT_NATURAL | SORT_FLAG_CASE);

        foreach($messagesMobile as $key => $msg) {
           $groupData['mobile'][$key] = $msg;
        }
        ksort($groupData['mobile'], SORT_NATURAL | SORT_FLAG_CASE);

        if($_POST) {
            if (in_array(0, $saveErrors)) {
                $this->msg(Helper_Message::get("backend.configuration.text_save_error"), 'error');
            } else {
                $this->msg(Helper_Message::get("backend.configuration.text_save"), 'success');
            }
        }

        $this->view->groups = $groupData;
        $this->view->fieldTypes = Kohana::$config->load('project.message_field_types');
    }

    function checkPOSTGroupMessages($type) {
        if ($_POST AND $groups = Arr::get($_POST, 'messages', false)) {
            foreach ($groups[$type] as &$msg) {
                $msg = str_replace("\n", ':nl', $msg);
            }

            $saveTo = basename(Kohana::$config->load('project.' . $type . '_message_groups'));
            if ($this->saveMessages($groups[$type], $saveTo)) {
                return 1;
            }
            return 0;
        }
        return 1;
    }

    private function getGroups($file) {
        $group = array();
        $groups = Helper_Message::loadFile($file);

        foreach($groups as $k => $value) {
            $fields = explode('.', $k);
            if($fields[1] == 'fields') {
                $value = explode(',', $value);
            }
            $group[$fields[0]][$fields[1]] = $value;
            if(!isset($group[$fields[0]]['messages'])) {
                $group[$fields[0]]['messages'] = array();
            }
        }
        return $group;
    }

    function checkPOSTMessages($messages, $saveTo) {

        if ($_POST AND $new_messages = Arr::get($_POST, 'messages', false)) {
            foreach ($new_messages as &$msg) {
                $msg = str_replace("\n", ':nl', $msg);
            }

            $messages = array_merge($messages, $new_messages);
            if ($this->saveMessages($messages, $saveTo)) {
                $this->msg(Helper_Message::get("backend.configuration.text_save"), 'success');
            } else {
                $this->msg(Helper_Message::get("backend.configuration.text_save_error"), 'error');
            }
        }
        return $messages;
    }

    public function action_mobile() {
        $saveTo = basename(Kohana::$config->load('project.mobile_messages_file'));
        $messages = Kohana::$config->load('project.mobile_messages');
        $messages = $this->checkPOSTMessages($messages, $saveTo);

        $groups = $this->getGroups(Kohana::$config->load('project.mobile_message_groups'));

        ksort($messages);

        foreach($groups as &$groupData) {
            foreach($messages as $key => $msg) {
                $group_key = explode('.', $key);
                $group_key = $group_key[0];
                if(in_array($group_key, $groupData['fields'])) {
                    $groupData['messages'][$group_key][$key] = $msg;
                    unset($messages[$key]);
                }
            }
        }

        // Get all messages that are not in a group
        foreach($groups as &$groupData) {
            foreach($messages as $key => $msg) {
                if(empty($groupData['fields'][0])) {
                    $group_key = explode('.', $key);
                    $group_key = $group_key[0];
                    $groupData['messages'][$group_key][$key] = $msg;
                }
            }
        }

        $this->view->groups = $groups;
        $this->view->fieldTypes = Kohana::$config->load('project.message_field_types');

        $this->template = "backend/messages/index";
    }

    private function saveMessages($data, $saveTo) {
        $out = '';

        ksort($data);

        foreach($data as $key => $val) {
            $out .= $key . '=' . trim(strip_tags($val, '<a>'), "\n\t ") . PHP_EOL;
        }
        return (bool) file_put_contents(APPPATH . 'messages/' . $saveTo, $out);
    }

    protected function accessAllowed() {
        return $this->hasRole("admin");
    }

}