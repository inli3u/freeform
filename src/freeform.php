<?php

class Form implements IteratorAggregate
{
    protected $fields = array();
    private $data = array();
    public $readonly = false;
    
    public function __construct($data = array())
    {
        $this->data = $data;
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
    
    public function list_errors()
    {
        $errors = array();
        foreach ($this->fields as $field) {
            try {
                $field->validate();
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        return $errors;
    }
    
    public function is_valid()
    {
        foreach ($this->fields as $field) {
            if (!$field->is_valid()) {
                return false;
            }
        }
        return true;
    }
    
    public function __get($key)
    {
        if (!isset($this->fields[$key])) {
            throw new Exception('Form field "' . $key . '" must be initialized before it can be accessed');
        }
        return $this->fields[$key];
    }
    
    public function __set($id, $field)
    {
        if (!$field instanceof Field) {
            throw new Exception('Form field "' . $key . '" must be initialized with a Field object');
        }
        $field->form = $this;
        // Auto-set the field name, id, and value if not already set.
        if (!isset($field->name)) {
            $field->name = $id;
        }
        if (!isset($field->id)) {
            $field->id = $id;
        }
        if (!isset($field->value) && isset($this->data[$id])) {
            $field->value = $this->data[$id];
        }
        $this->fields[$id] = $field;
    }
    
    public static function parse_attr($str)
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
    
    function get_jquery_rules()
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



class Field
{
    public $form;
    //public $value;
    public $view;
    public $rules = array();
    
    
    public function __construct($view, $view_params = null, $rules = null)
    {
        if (!$view instanceof Input) {
            // Init from class name.
            $view .= 'Input';
            $view = new $view();
        }
        $this->view = $view;
        $this->view->set_attr($view_params);
        $this->set_rules($rules);
    }
    
    public function set_rules($rules)
    {
        $this->rules = !is_array($rules) ? Form::parse_attr($rules) : $rules;
    }
    
    function render()
    {
        if ($this->form->readonly) {
            return $this->value;
        } else {
            return $this->view->render($this->name, $this->value);
        }
    }
    
    public function validate()
    {
        if (!isset($this->value) || $this->value === null) {
            // No value was submitted by a form, nothing to validate.
            return;
        }
        
        foreach ($this->rules as $rule => $arg) {
            switch ($rule) {
                case 'required':
                    // TODO: support required based on callback.
                    if (strlen($this->value) === 0) {
                        throw new ValidationError('This field is required.');
                    }
                    break;
                case 'minlength':
                    if (strlen($this->value) < $arg) {
                        throw new ValidationError(sprintf('A minimum of %s characters are required.', $arg));
                    }
                    break;
                case 'maxlength':
                    if (strlen($this->value) > $arg) {
                        throw new ValidationError(sprintf('A maximum of %s characters are allowed.', $arg));
                    }
                    break;
                case 'min':
                    if ((int)$this->value < (int)$arg) {
                        throw new ValidationError(sprintf('Value must be greater than %s', $arg));
                    }
                    break;
                case 'max':
                    if ((int)$this->value > (int)$arg) {
                        throw new ValidationError(sprintf('Value must be less than %s', $arg));
                    }
                    break;
                case 'email':
                    break;
                case 'url':
                    break;
                case 'date':
                    break;
                case 'callback':
                    // TODO: throw error if callback returns false. In client side validation,
                    // use remote validation of the method.
                    break;
            }
        }
    }
    
    public function is_valid()
    {
        try {
            $this->validate();
            return true;
        } catch (ValidationError $e) {
            return false;
        }
    }
    
    public function get_error()
    {
        try {
            $this->validate();
            return null;
        } catch (ValidationError $e) {
            return $e->getMessage();
        }
    }
    
    public function __isset($key)
    {
        return isset($this->view->{$key});
    }
    
    public function __get($key)
    {
        return @$this->view->{$key};
    }
    
    public function __set($key, $value)
    {
        $this->view->{$key} = $value;
    }
    
    public function __toString()
    {
        return $this->value;
    }
}

class Input {
    protected $elem = null;
    protected $attr = array();
    
    public function __construct($attr = null)
    {
        $this->set_attr($attr);
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
    
    public function set_attr($attr)
    {
        $this->attr = is_null($attr) ? array() : (is_array($attr) ? $attr : Form::parse_attr($attr));
    }
    
    public function get_attr_str()
    {
        $pairs = array();
        foreach ($this->attr as $key => $value) {
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
}

class TextInput extends Input
{
    function render($name, $value)
    {
        $this->type = 'text';
        return '<input ' . $this->get_attr_str() . '/>';
    }
}

class RadioInput extends Input
{
    function render($name, $value)
    {
        $this->type = 'radio';
        //$this->value = $value;
        if ($this->value == $value) {
            $this->checked = true;
        }
        
        return '<input ' . $this->get_attr_str() . '/>';
    }
}

class CheckboxInput extends Input
{
    function render($name, $value)
    {
        $this->type = 'checkbox';
        //$this->value = $value;
        if ($this->value == $value) {
            $this->checked = true;
        }
        
        return '<input ' . $this->get_attr_str() . '/>';
    }
}

class TextareaInput extends Input
{
    function render($name, $value)
    {
        return '<textarea ' . $this->get_attr_str() . '>' . htmlspecialchars($value) . '</textarea>';
    }
}

class SelectInput extends Input
{
    public $attributes = '';
    public $items = array();
    
    public function __construct($items, $attr = null)
    {
        parent::__construct($attr);
        $this->items = $items;
    }
    
    function render($name, $value)
    {
        $html = '<select ' . $this->get_attr_str() . ">\n";
        
        // Detect key names.
        $first = @$this->items[0];
        $first_keys = @array_keys($first);
        $valuekey = @$first_keys[0] or 0;
        $namekey = @$first_keys[1] or 1;
        
        foreach ($this->items as $item) {
            
            $selected = ($item[$valuekey] == $value) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($item[$valuekey]) . '"' . $selected . '>' . htmlspecialchars($item[$namekey]) . "</option>\n";
        }
        $html .= '</select>';
        return $html;
    }
}



class ValidationError extends Exception
{

}