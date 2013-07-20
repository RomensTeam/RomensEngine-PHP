<?
# Защита
if (!defined('DIR')) {
    exit();
}
define('HTML', TRUE);
define('NONE', FALSE);
/**
 *  Класс Romens - Базовый класс
 *
 * @author Romens <romantrutnev@gmail.com>
 * @version 1.1
 */
class RomensModel {
    public $registr;
    public $lang; // Фразы фреймворка
    public $app_lang; // Фразы приложения
    public $view;
    public $base;
    public $buffer; // Буффер
    public $layout;
    public $base_list;
    /* Начало класса */
    public function __construct() {
        /* Подключаем языковой пакет фреймворка */
        $_LANG = '';
        include_once DIR_CORE . 'lang' . _DS . strtolower(LANG) . '.php';
        $this->lang = $_LANG;
        unset($_LANG);
    }
    /* Настройки языка */
    public function app_lang($lang = FALSE) {
        if (defined('APP_LANG_METHOD')) {
            if (defined('APP_LANG_FORMAT') && APP_LANG_FORMAT == 'JSON' && APP_LANG_METHOD == 'JSON_FILE') {
                $av_lang = array('status' => FALSE);
                if ($handle = opendir(DIR_APP_LANG)) {
                    while (false !== ($file = readdir($handle))) {
                        if (preg_match('/\.(?:' . APP_LANG_EXT . ')/', $file)) {
                            $av_lang[] = str_replace('.' . APP_LANG_EXT, '', $file);
                        }
                    }
                    closedir($handle);
                }
                foreach($av_lang as $value) {
                    if ($lang == $value) {
                        $av_lang = array('lang' => $lang, 'status' => TRUE);
                        break;
                    } else {
                        $av_lang['status'] = FALSE;
                    }
                }
                if ($av_lang['status'] == FALSE || empty($lang)) {
                    $lang = LANG;
                }
                $lang_file = file_get_contents(_filter(DIR_APP_LANG . $lang . '.json'));
                if ($lang_file != FALSE) {
                    unset($av_lang);
                    $this->app_lang = json_decode($lang_file, TRUE);
                    return $this->app_lang;
                } else {
                    exit($this->lang['not_app_lang']);
                }
            }
        }
    }
    /* Управление приложением */
    public function start_html_app($meta) {
        $this->view = new RomensViewHTML($this);
        $this->view->head = array_merge($this->view->head, $meta);
    }
    public function end_html_app() {
        echo $this->buffer;
    }
    /* Работа с базой данных */
    public function connect_base($base = NULL) {
        switch (strtolower(BASE_DRIVER)) {
            case 'mysql':
                $this->base = new RomensMYSQL($base);
            break;
            default:
                exit($this->lang['error_base_driver']);
            break;
        }
    }
    public function change_base($base = NULL) {
        if (defined('BASE_BASE')) {
            if (is_numeric($base)) {
                $base = intval($base);
                $n = explode(',', BASE_BASE);
                if ($base == NULL) {
                    if (count($n) == 1) {
                        $base = $n[0];
                        $this->base_list = $n[0];
                    }
                    if (count($n) > 1) {
                        $base = $n[0];
                        $this->base_list = $n;
                    }
                } else {
                    if (count($n) == 1) {
                        $base = $n[0];
                        $this->base_list = $n[0];
                    }
                    if (count($n) > 1) {
                        $base = $base - 1;
                        $base = $n[$base];
                        $this->base_list = $n;
                    }
                }
            }
        }
        return $this->base->change_base($base);
    }
    /* Взаимодействие с View */
    public function render() {
        $buffer = $this->view->render();
        $this->view->generateHead();
        $array = array_merge(
                $this->app_lang, 
                array(
                    'head' => $this->view->head_string,
                    'end_area' => $this->view->end_string 
                        )
                );
        # Блоки
        preg_match_all('/{\[BLOCK_([A-Z0-9_]+)\]\}/', $buffer, $all); // Получаем все доступные в странице ключей
        foreach($all[1] as $value) {
            $block_path = _filter($this->registr['dir_theme'] . 'block' . _DS . strtolower($value) . '.tpl');
            $block = @file_get_contents($block_path);
            $buffer = str_replace('{[BLOCK_' . $value . ']}', $block, $buffer);
        }
        # Ключи
        preg_match_all('/{\[([A-Z0-9_]+)\]\}/', $buffer, $all); // Получаем все доступные в странице ключей
        foreach($all[1] as $value) {
            $buffer = str_replace('{[' . $value . ']}', $array[strtolower($value) ], $buffer);
        }
        $this->buffer = $buffer;
    }
    public function type_output($type) {
        if ($type) {
            if (defined('CHARSET')) {
                header('Content-Type: text/html; charset=' . strtolower(CHARSET));
            }
        }
    }
    public function var_app($var = array()) {
        $this->app_lang = array_merge($this->app_lang, $var);
    }
    public function addScript($script, $link = FALSE) {
        if ($link == FALSE) {
            $this->view->js[] = $script;
        } else {
            $this->view->js_link[] = $script;
        }
    }
    public function addStyle($style, $link = FALSE) {
        if ($link == FALSE) {
            $this->view->css[] = $style;
        } else {
            $this->view->css_link[] = $style;
        }
    }
    public function addComponent($component) {
        $component_path = DIR . 'component' . _DS . strtolower($component) . '.json';
        $component_data = file_get_contents($component_path);
        if ($component_data == FALSE) {
            return FALSE;
        }
        $component_data = json_decode($component_data, TRUE);
        if ($component_data == FALSE) {
            return FALSE;
        }
        $comp_a = array();
        foreach($component_data as $key => $value) {
            switch (strtolower($key)) {
                case 'type':
                    $value = (string)strtolower($value);
                    if ($value == 'js' || $value == 'javascript' || $value == 'text/javascript') {
                        $comp_a['type'] = 'js';
                    } else if ($value == 'css' || $value == 'text/css') {
                        $comp_a['type'] = 'css';
                    } else {
                        return FALSE;
                    }
                    break;
                case 'link':
                    if ($value) {
                        $comp_a['link'] = true;
                    } else {
                        $comp_a['link'] = false;
                    }
                    break;
                case 'flag':
                    if (defined(strtoupper($value))) {
                        $comp_a['flag'] = $value;
                    } else {
                        $comp_a['flag'] = FALSE;
                    }
                    break;
                case 'flag_src':
                    if ($comp_a['flag'] != false) {
                        $src = str_replace('*' . strtolower($comp_a['flag']) . '*', constant($comp_a['flag']), $value);
                        $comp_a['src'] = $src;
                    }
                    break;
                case 'noflag_src':
                    if(!isset($comp_a['flag'])){
                        $comp_a['flag'] = FALSE;
                    }
                    if ($comp_a['flag'] == FALSE && $comp_a['link']==true) {
                        $comp_a['src'] = $value;
                    }
                    break;
                case 'data':
                    if ($comp_a['link'] == false) {
                        $comp_a['data'] = $value;
                    }
                    break;
                }
        }
        if ($comp_a['type'] == 'css') {
            if ($comp_a['link']) {
                $this->addStyle($comp_a['src'], TRUE);
            } else {
                $this->addStyle($comp_a['data']);
            }
        }
        if ($comp_a['type'] == 'js') {
            if ($comp_a['link']) {
                $this->addScript($comp_a['src'], TRUE);
                #$this->view->js_link_end[] = $comp_a['src'];
            } else {
                $this->addScript($comp_a['data'], FALSE);
                #$this->view->js_end[] = $comp_a['data'];
            }
        }
    }
    public function addToHead($string) {
        $this->head_string.= $string;
    }
    public function setTheme($theme_name) {
        $n = DIR_THEMES . $theme_name . _DS;
        if (is_dir($n)) {
            $this->registr['theme_name'] = $theme_name;
            $this->registr['dir_theme'] = $n;
            return TRUE;
        } else {
            return FALSE;
        }
    }
    public function setLayout($layout_name) {
        $layout_path = $this->registr['dir_theme'] . $layout_name . '.tpl';
        if (is_file($layout_path) || !empty($layout_path)) {
            $this->registr['layout'] = $layout_path;
            return TRUE;
        } else {
            return FALSE;
        }
    }
    /* Пасхалки */
    public function __invoke($var) {
        if (is_int($var)) {
            return 'Через ' . $var . ' минут я взорву твой компьютер! :-)';
        }
        if (is_string($var)) {
            return ':-)';
        }
    }
    public function __toString() {
        return 'Привет, я Romens-Engine!';
    }
}
if (defined('LOAD_ROMENS') && LOAD_ROMENS == TRUE) {
    $romens = new RomensModel();
}