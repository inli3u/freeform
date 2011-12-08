<?php

class Freeform implements IteratorAggregate
{
    public $readonly = false;
    protected $fields = array();
    private $data = array();
    private $validate;
    
    public function __construct($data = array())
    {
        $this->data = $data;
	$this->validate = new FValidate();
        $this->config();
    }
    
    public function getIterator()
    {
        return new ArrayIterator($this->fields);
    }
    
    public function config()
    {
        // Override.
    }

    public function isSubmitted()
    {
	return count($this->data) > 0;
    }
    
    public function listErrors()
    {
        $errors = array();
        $msg = '';
        foreach ($this->fields as $field) {	
            foreach ($field->getRules() as $rule => $arg) {
                if (!$this->validate->test($field->value, $rule, $arg, $msg)) {
                    $errors[] = $msg;
                }
            }
        }
        return $errors;
    }
    
    public function hasErrors()
    {
        foreach ($this->fields as $field) {
            if (!$field->test()) {
                return true;
            }
        }
        return false;
    }
    
    public function __get($key)
    {
        if (!isset($this->fields[$key])) {
            throw new Exception('Form field "' . $key . '" must be initialized before it can be accessed');
        }
        return $this->fields[$key];
    }
    
    public function __set($id, FControl $input)
    {
        $input->form = $this;
        // Auto-set the field name, id, and value if not already set.
        if (!isset($input->name)) {
            $input->name = $id;
        }
        if (!isset($input->id)) {
            $input->id = $id;
        }
        if (isset($this->data[$id])) {
            //$input->value = $this->data[$id];
            $input->setSubmittedValue($this->data[$id]);
        }
        $this->fields[$id] = $input;
    }
    
}


class FAttributes
{

    public static function encode($list)
    {
        $pairs = array();
        foreach ($list as $key => $value) {
            if ($value === false || $value === null) {
                // Skip.
            } elseif ($value === true) {
                $pairs[] = $key . '="' . $key . '"';
            } else {
                $pairs[] = $key . '="' . htmlspecialchars($value) . '"';
            }
        }
        return implode(' ', $pairs);
    }
    
    public static function decode($str)
    {
        $CHARS_WHITESPACE = " \n\r\t";
        $CHARS_QUOTE = '\'"';
        $CHARS_EQUAL = '=';
        $ID_WHITESPACE = 'WHITESPACE';
        $ID_QUOTE = 'QUOTE';
        $ID_EQUAL = 'EQUAL';
        $ID_OTHER = 'OTHER';
        $STATE_KEY = 'KEY';
        $STATE_VALUE = 'VALUE';
        
        $state = $STATE_KEY;
        $char_id = null;
        $opening_quote = null;
        $key = '';
        $value = '';
        $attr = array();
        
        $strlen = strlen($str);
        for ($i = 0; $i <= $strlen; $i++) {
            if ($i === $strlen) {
                // End of $str. Send whitespace through to close any open pairs.
                $char = ' ';
            } else {
                $char = $str[$i];
            }
            
            // Identify char.
            if (false !== strpos($CHARS_WHITESPACE, $char)) {
                $char_id = $ID_WHITESPACE;
            } elseif (false !== strpos($CHARS_QUOTE, $char)) {
                $char_id = $ID_QUOTE;
            } elseif ($CHARS_EQUAL === $char) {
                $char_id = $ID_EQUAL;
            } else {
                $char_id = $ID_OTHER;
            }
            
            // State.
            if ($state === $STATE_KEY) {
                if ($char_id === $ID_OTHER) {
                    $key .= $char;
                } elseif ($char_id === $ID_EQUAL && $key !== '') {
                    $state = $STATE_VALUE;
                } elseif ($char_id === $ID_WHITESPACE) {
                    if ($key === '') {
                        // Allow whitespace before key to separate previous key/value pair.
                        // noop.
                    } else {
                        // Whitespace after name, treat as boolean flag.
                        $attr[$key] = true;
                        $key = '';
                    }
                } else {
                    throw new Exception('Unexpected ' . $char_id . ' in attribute name');
                }
            } elseif ($state === $STATE_VALUE) {
                $closed = false;
                $skip = false;
                
                if ($opening_quote === null) {
                    if ($char_id === $ID_QUOTE) {
                        $opening_quote = $char;
                        $skip = true;
                    } else {
                        $opening_quote = '';
                    }
                }
                
                if (!$skip) {
                    if ($opening_quote === '') {
                        if ($char_id === $ID_WHITESPACE) {
                            $closed = true;
                        } elseif ($char_id === $ID_OTHER) {
                            $value .= $char;
                        } else {
                            throw new Exception('Unexpected ' . $char_id . ' in value');
                        }
                    } else {
                        if ($char_id === $ID_QUOTE && $opening_quote === $char) {
                            $closed = true;
                        } else {
                            $value .= $char;
                        }
                    }
                }
                
                if ($closed) {
                    $attr[$key] = $value;
                    $opening_quote = null;
                    $key = '';
                    $value = '';
                    $state = $STATE_KEY;
                }
            } else {
                throw new Exception('Unknown state.');
            }
        }
        
        return $attr;
    }
}



class FValidate
{
    private $lang = array(
        'required' => 'This field is required',
        'minlength' => 'A minimum of %s character(s) is required.',
        'maxlength' => 'A maximum of %s character(s) is allowed.',
        'min' => 'Value must be greater than %s',
        'max' => 'Value must be less than %s',
    );
    
    public function error($langKey, $args = array())
    {
        $str = array_key_exists($langKey, $this->lang) ? $this->lang[$langKey] : 'Text resource "' . $langKey . '" not found';
        if (count($args)) {
            $str = vsprintf($str, $args);
        }
        //throw new Exception($str);
    }
    
    public function test($value, $rule, $arg, &$msg)
    {
        print_r($rules);
        $isValid = true;
        foreach ($rules as $rule => $arg) {
            $isValid = $isValid && $this->{$rule}($value, $arg);
        }
        return $isValid;
    }

    public function required($value)
    {
        if (strlen($value) === 0) {
            $this->error('required');
            return false;
        }
        return true;
    }
    
    public function minlength($value, $length)
    {
        if (strlen($value) < $length) {
            $this->error('minlength', array($length));
            return false;
        }
        return true;
    }

    public function maxlength($value, $length)
    {
        if (strlen($value) > $length) {
            $this->error('maxlength', array($length));
            return false;
        }
        return true;
    }

    public function min($value, $min)
    {
        if ($value < $min) {
            $this->error('min', array($min));
            return false;
        }
        return true;
    }

    public function max($value, $max)
    {
        if ($value > $max) {
            $this->error('max', array($max));
            return false;
        }
        return true;
    }

    public function pattern($value, $pattern)
    {
        if (!preg_match('/' . str_replace('/', '\\', $pattern) . '/', $value)) {
            //$this->error('pattern');
            return false;
        }
        return true;
    }

    public function email($value)
    {
        /*
	<ldh-str> ::= <let-dig-hyp> | <let-dig-hyp> <ldh-str>

	<let-dig-hyp> ::= <let-dig> | "-"

	<let-dig> ::= <letter> | <digit>

	<letter> ::= any one of the 52 alphabetic characters A through Z in
	upper case and a through z in lower case

	<digit> ::= any one of the ten digits 0 through 9
        */ 
        $atext = '[0-9a-zA-Z!#$%&\'*+-\/=?^_`{|}~]+';
        $ldh = '[0-9a-zA-Z-]+';
        $pattern = '/' . $atext . '@' . $ldh . '([.]' . $ldh . ')*/';
        if (!preg_match($pattern, $value)) {
            //$this->error('emial');
            return false;
        }
        return true;
    }

    public function validate($value, $rules)
    {
        foreach ($this->rules as $rule => $arg) {
            $this->test($value, $rule, $arg);
        }
    }
    
    public function getJQueryRules()
    {
        $obj;
        foreach ($this->fields as $id => $field) {
            $obj[$id] = array();
            foreach ($field->rules as $rule => $arg) {
                $obj[$id][$rule] = $arg;
            }
            if (count($obj[$id]) === 0) {
                unset($obj[$id]);
            }
        }
        return json_encode($obj);
    }
}



abstract class FControl {
    public $form = null;
    public $value = null;
    protected $elem = null;
    protected $attr = array();
    protected $rules = array();
    private $error = '';
    
    public abstract function render();

    public function __construct($attr = null, $rules = null)
    {
        $this->setAttr($attr);
        $this->setRules($rules);
        $this->config();
    }
    
    private function config()
    {
    }
    
    public function __isset($key)
    {
        return isset($this->attr[$key]);
    }
    
    public function __get($key)
    {
        return @$this->attr[$key];
    }
    
    public function __set($key, $value)
    {
        $this->attr[$key] = $value;
    }
    
    public function setSubmittedValue($value)
    {
        $this->value = $value;
    }
    
    public function setAttr($attr)
    {
        if (!is_null($attr)) {
            $this->attr = (is_array($attr) ? $attr : FAttributes::decode($attr)) + $this->attr;
        }

        // Value is only an attribute of the <input> tag. It's not universal.
        if (array_key_exists('value', $this->attr)) {
            $this->value = $this->attr['value'];
            unset($this->attr['value']);
        }
    }
    
    public function setRules($rules)
    {
        if (!is_null($rules)) {
            $this->rules = (is_array($rules) ? $rules : FAttributes::decode($rules)) + $this->rules;
        }
    }

    public function getRules()
    {
        return $this->rules;
    }

    public function getError()
    {
        // Validate if not done so yet.
        return $this->error;
    }

    public function setError($error)
    {
        $this->error = $error;
    }
    
    public function test()
    {
        $validate = new FValidate();
        return $validate->test($this->value, $this->rules);
    }

    public function errors()
    {
        
        $v = new FValidate();
        $v->test($this->value, $this->rules);
        return $v->errors();
    }
    
    public function validate()
    {
        if (!$this->test()) {
            //throw new Exception($message, $code, $previous)
        }
    }

    public function __toString()
    {
        return $this->render();
    }
}

class FInput extends FControl
{
    protected $forcedType;
    
    public function render($userAttr = null)
    {
        if (!is_array($userAttr)) {
            $userAttr = array();
        }
        if ($this->forcedType !== null) {
            $this->type = $this->forcedType;
        }
        $value = array('value' => $this->value);
        return '<input ' . FAttributes::encode(array_merge($this->attr, $value, $userAttr)) . ' />';
    }
}

abstract class FCheckedInput extends FInput
{
    public function __construct($attr = null, $rules = null)
    {
        // Default value for checkboxes and radios.
        $this->value = 1;
        parent::__construct($attr, $rules);
    }
    
    public function setSubmittedValue($value)
    {
        // Checkboxes and radios are different, the submitted value should not overwrite the value attr.
        // Instead it will set the checked attr.
        $this->checked = ($value == $this->value);
    }
}

class FText extends FInput
{
    public function __construct($attr = null, $rules = null)
    {
        // Allow type to be overriden to support new text validation types.
        $this->type = 'text';
        parent::__construct($attr, $rules);
    }
}

class FRadio extends FCheckedInput
{
    protected $forcedType = 'radio';
}

class FCheckbox extends FCheckedInput
{
    protected $forcedType = 'checkbox';
}

class FHidden extends FInput
{
    protected $forcedType = 'hidden';
}

class FPassword extends FInput
{
    protected $forcedType = 'password';
}

class FSubmit extends FInput
{
    protected $forcedType = 'submit';
}

class FEmail extends FInput
{
    protected $forcedType = 'email';
}

class FTel extends FInput
{
    protected $forcedType = 'tel';
}

class FUrl extends FInput
{
    protected $forcedType = 'url';
}

class FSearch extends FInput
{
    protected $forcedType = 'search';
}

class FNumber extends FInput
{
    public function __construct($attr = null, $rules = null) {
        parent::__construct($attr, $rules);
        $this->forcedType = 'number';
        $this->setRules('number');
    }
}

class FRange extends FInput
{
    protected $forcedType = 'range';
    public function __construct($attr = null, $rules = null) {
        parent::__construct($attr, $rules);
        $this->forcedType = 'range';
        $this->setRules('number');
    }
}

class FColor extends FInput
{
    protected $forcedType = 'color';
}

class FFile extends FInput
{
    protected $forcedType = 'file';
}

class FReset extends FInput
{
    protected $forcedType = 'reset';
}

class FButton extends FInput
{
    protected $forcedType = 'button';
}

class FImage extends FInput
{
    protected $forcedType = 'image';
}


/*
 *  datetime
    date
    month
    week
    time
    datetime-local
 */

class FTextarea extends FControl
{
    function render($userAttr = null)
    {
        if (!is_array($userAttr)) {
            $userAttr = array();
        }
        return '<textarea ' . FAttributes::encode(array_merge($this->attr, $userAttr)) . '>' . htmlspecialchars($this->value) . '</textarea>';
    }
}

class FOption
{
    public $value;
    public $text;

    public function __construct($text = null, $value = null)
    {
        $this->text = $text;
        $this->value = $value;
    }
}

class FSelect extends FControl
{
    private $options = array();

    public function __get($key)
    {
        switch ($key) {
        case 'length':
            return count($this->options);
        case 'selectedIndex':
            for ($i = 0; $i < count($this->options); $i++) {
                if ($this->options[$i]->value == $this->value) {
                    return $i;
                }
            }
            return -1;
        default:
            return parent::__get($key);
        }
    }

    public function __set($key, $value)
    {
        switch ($key) {
        case 'length':
            if ($value < count($this->options)) {
                $this->options = array_slice($this->options, 0, $value);
            }
            break;
        case 'selectedIndex':
            $this->value = @$this->options[$value]->value;
            break;
        default:
            parent::__set($key, $value);
            break;
        }
    }
    
    public function add(FOption $option, $beforeIndex = null)
    {
        if ($beforeIndex === null) {
            $this->options[] = $option;
        } else {
            array_splice($this->options, $beforeIndex, 0, $option);
        }
    }

    public function remove($index)
    {
        array_splice($this->options, $index, 1);
    }

    public function clear()
    {
        $this->options = array();
    }

    public function listOptions()
    {
        return $this->options;
    }

    public function option($index, $newOption = null)
    {
        if ($newOption === null) {
            return @$this->options[$index];
        } else {
            $this->options[$index] = $newOption;
        }
    }

    function fill($array, $textKey, $valueKey)
    {
        // Detect key names.
        $first = @$this->options[0];
        $firstKeys = @array_keys($first);
        $valuekey = @$firstKeys[0] or 0;
        $namekey = @$firstKeys[1] or 1;
    }
    
    function render($userAttr = null)
    {
        if (!is_array($userAttr)) {
            $userAttr = array();
        }
        $html = '<select ' . FAttributes::encode(array_merge($this->attr, $userAttr)) . ">\n";
        foreach ($this->options as $i => $option) {
            $selected = ($option->value == $this->value) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($option->value) . '"' . $selected . '>' . htmlspecialchars($option->value) . "</option>\n";
        }
        $html .= '</select>';
        return $html;
    }
}

// vim: set ts=4 sw=4 et
