<?
class Regisrtry {
    protected $_data = array();
    
    public function __set($name, $value) {
        return $this->_data[$name] = $value;
    }
    public function __get($value) {
        return $this->_data[$value];
    }
    public function __isset($name) {
        return isset($this->_data[$name]);
    }
    public function __unset($param) {
        unset($this->_data[$param]);
    }
}