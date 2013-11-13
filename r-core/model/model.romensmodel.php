<?
# Защита
if (!defined('DIR')) {
    exit();
}
/**
 *  Класс Romens - Базовый класс
 *
 * @author Romens <romantrutnev@gmail.com>
 * @version 1.1
 */

# Необходимые директивы
    define('HTML',TRUE);
    define('NONE',FALSE);

class RomensModel {
    public $registr;
    public $lang; // Фразы фреймворка
    public $app_lang = array();
    public $view;
    public $base;
    public $buffer; // Буффер
    public $layout;
    public $base_list;
    /* Начало класса */
    public function __construct(){
        # Подключаем языковой пакет фреймворка 
        $_LANG = '';
        include_once DIR_CORE.'lang'._DS.strtolower(LANG).'.php';
        $this->lang = $_LANG;
    }
    /* Настройки языка */
    public function app_lang($lang = FALSE,$library = FALSE){
	$lang = ($lang==FALSE)? 'LANG': $lang;
        if (CheckFlag('APP_LANG_FORMAT') && CheckFlag('APP_LANG_METHOD')) {
            if (APP_LANG_FORMAT == 'JSON' && APP_LANG_METHOD == 'JSON_FILE') {
                if($library=FALSE){
                    $path = DIR_APP_LANG.APP_LANG_PREFIX.$lang.'.'.APP_LANG_EXT;
                    $this->app_lang = $this->open_json($path);
                }
                else{
                    if(is_string($library)){
                        $library = explode(',', $library);
                    }
                    foreach ($library as $lib) {
                        $path = DIR_APP_LANG.$lang._DS.APP_LANG_PREFIX.$lib.'.'.APP_LANG_EXT;
                        $app_lang = $this->open_json($path);
                        $this->app_lang = array_merge($this->app_lang,$app_lang);
                    }
            
                }
            }
        }
        return $this->app_lang;
    }
    /* Управление приложением */
    public function start_html_app($meta){
        if(defined('CHARSET')){
                header('Content-Type: text/html; charset=' . strtolower(CHARSET));
            }
        $this->view = new RomensViewHTML($this);
        $this->view->head = array_merge($this->view->head, $meta);
    }
    public function end_html_app(){
        echo $this->buffer;
    }
    /* Работа с базой данных */
    public function connect_base($base = NULL){
        switch (strtolower(BASE_DRIVER)) {
            case 'mysql':
                $this->base = new RomensMYSQL($base);
            break;
            default:
                exit($this->lang['error_base_driver']);
            break;
        }
    }
    public function change_base($base = NULL){
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
    public function render(){
        $buffer = $this->view->render();
        $array = $this->app_lang;
        # Блоки
        preg_match_all(VIEW_BLOCK_TAG_PATTERN, $buffer, $all); // Получаем все доступные в странице ключей
        foreach($all[1] as $value) {
            $block_path = _filter($this->registr['dir_theme'] . VIEW_BLOCK_TAG_FOLDER . _DS . strtolower($value) . '.tpl');
            $block = @file_get_contents($block_path);
            $buffer = str_replace(VIEW_TAG_START . VIEW_BLOCK_TAG_NAME . $value . VIEW_TAG_END , $block, $buffer);
        }
        # Ключи
        preg_match_all(VIEW_TAG_PATTERN, $buffer, $all); // Получаем все доступные в странице ключей
        foreach($all[1] as $value) {
            $buffer = str_replace(VIEW_TAG_START . $value . VIEW_TAG_END, $array[strtolower($value)], $buffer);
        }
        $this->buffer = $buffer;
    }
    public function pattern($name=null){
        return VIEW_TAG_START.strtoupper($name).VIEW_TAG_END;
    }
    public function type_output($type){
        if($type){
            
        }
    }
    public function var_app($var = array()){
        $this->app_lang = array_merge($this->app_lang, $var);
    }
    public function addScript($script, $link = FALSE){
        if ($link == FALSE) {
            $this->view->js[] = $script;
        } else {
            $this->view->js_link[] = $script;
        }
    }
    public function addStyle($style, $link = FALSE){
        if ($link == FALSE) {
            $this->view->css[] = $style;
        } else {
            $this->view->css_link[] = $style;
        }
    }
    public function addComponent($component){
        if(!TEST_MODE){
            return 0;
        }
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
    public function addToHead($string){
        $this->view->head_string.= $string;
    }
    public function setTheme($theme_name){
        $n = DIR_THEMES . $theme_name . _DS;
        if (is_dir($n)) {
            $this->registr['theme_name'] = $theme_name;
            $this->registr['dir_theme'] = $n;
            return TRUE;
        } else {
            return FALSE;
        }
    }
    public function getBlock($name){
        $block_path = _filter($this->registr['dir_theme'] . 'block' . _DS . strtolower($name) . '.tpl');
        return file_get_contents($block_path);
    }
    public function setLayout($layout_name){
        $layout_path = $this->registr['dir_theme'] . $layout_name . '.tpl';
        if (is_file($layout_path) || !empty($layout_path)) {
            $this->registr['layout'] = $layout_path;
            return TRUE;
        } else {
            return FALSE;
        }
    }
	/* Приватные функции */
	private function open_json($path){
            if(is_file($path)){
                $lang_json_data = (string) file_get_contents($path);
                if(strlen($lang_json_data) > 0){
                    $lang_data = json_decode($lang_json_data, TRUE);
                    if(is_array($lang_data)){
                        return $lang_data;
                    }
                }
            }
        }
	
    /* Пасхалки */
    public function __invoke($var){
        if (is_int($var)) {
            return 'Через ' . $var . ' минут я взорву твой компьютер! :-)';
        }
        if (is_string($var)) {
            return ':-)';
        }
    }
    public function __toString(){
        return 'Привет, я Romens-Engine!';
    }
}