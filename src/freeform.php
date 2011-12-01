<?php

class Freeform implements IteratorAggregate
{
    protected $fields = array();
    private $data = array();
    public $readonly = false;
    
    static public function Select($attr = null, $rules = null)
    {
        return new SelectInput($attr, $rules);
    }
    
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
    
    public function has_errors()
    {
        foreach ($this->fields as $field) {
            if (!$field->is_valid()) {
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
    
    public function __set($id, FreeformInput $input)
    {
/*
        if (!$field instanceof FreeformField) {
            throw new Exception('Form field "' . $id . '" must be initialized with a Field object');
        }
*/
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
            $input->set_submitted_value($this->data[$id]);
        }
        $this->fields[$id] = $input;
    }
    
}


class FAttributes
{

    public function encode($list)
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
    );
    
    public function error($lang_key, $args = array())
    {
        $str = array_key_exists($lang_key, $this->lang) ? $this->lang[$lang_key] : 'Text resource "' . $lang_key . '" not found';
        if (count($args)) {
            $str = vsprintf($str, $args);
        }
        throw new ValidationError($str);
    }
    
    public function test($value, $rule)
    {
        // Run $rule on $value
    }

    public function required($value)
    {
        if (strlen($this->value) === 0) {
            $this->error('required');
            return false;
        }
        return true;
    }
    
    public function minlength($value, $length)
    {
        if (strlen($value) < $length) {
            $this->error('minlength');
            return false;
        }
        return true;
    }

    public function maxlength($value, $length)
    {
        if (strlen($value) > $length) {
            $this->error('maxlength');
            return false;
        }
        return true;
    }

    public function validate($value, $rules)
    {
        
        foreach ($this->rules as $rule => $arg) {
            $this->test($value, $rule, $arg);
        }
        
        switch ($rule)
        {
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
    
    public function get_jquery_rules()
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



abstract class FBaseInput {
    public $form = null;
    public $value = null;
    protected $elem = null;
    protected $attr = array();
    protected $rules = array();
    
    public abstract function render();

    public function __construct($attr = null, $rules = null)
    {
        $this->set_attr($attr);
        $this->set_rules($rules);
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
    
    public function set_submitted_value($value)
    {
        $this->value = $value;
    }
    
    public function set_attr($attr)
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
    
    public function set_rules($rules)
    {
        if (!is_null($rules)) {
            $this->rules = (is_array($rules) ? $rules : FAttributes::decode($rules)) + $this->rules;
        }
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
        if (!$this->is_valid()) {
            //throw new Exception($message, $code, $previous)
        }
    }

    public function __toString()
    {
        return $this->render();
    }
}

class FInput extends FBaseInput
{
    protected $forced_type;
    
    public function render($user_attr = null)
    {
        if (!is_array($user_attr)) {
            $user_attr = array();
        }
        if ($this->forced_type !== null) {
            $this->type = $this->forced_type;
        }
        $value = array('value' => $this->value);
        return '<input ' . FAttributes::encode(array_merge($this->attr, $value, $user_attr)) . ' />';
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
    
    public function set_submitted_value($value)
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
    protected $forced_type = 'radio';
}

class FCheckbox extends FCheckedInput
{
    protected $forced_type = 'checkbox';
}

class FHidden extends FInput
{
    protected $forced_type = 'hidden';
}

class FPassword extends FInput
{
    protected $forced_type = 'password';
}

class FSubmit extends FInput
{
    protected $forced_type = 'submit';
}

class FNumber extends FInput
{
    public function __construct($attr = null, $rules = null) {
        parent::__construct($attr, $rules);
        $this->forced_type = 'number';
        $this->set_rules('number');
    }
}

class FRange extends FInput
{
    protected $forced_type = 'range';
    public function __construct($attr = null, $rules = null) {
        parent::__construct($attr, $rules);
        $this->forced_type = 'range';
        $this->set_rules('number');
    }
}

class FColor extends FInput
{
    protected $forced_type = 'color';
}

class FFile extends FInput
{
    protected $forced_type = 'file';
}

class FReset extends FInput
{
    protected $forced_type = 'reset';
}

class FButton extends FInput
{
    protected $forced_type = 'button';
}

class FImage extends FInput
{
    protected $forced_type = 'image';
}


/*
 *  datetime
    date
    month
    week
    time
    datetime-local
 */

class FTextarea extends FBaseInput
{
    function render($user_attr = null)
    {
        if (!is_array($user_attr)) {
            $user_attr = array();
        }
        return '<textarea ' . FAttributes::encode(array_merge($this->attr, $user_attr)) . '>' . htmlspecialchars($this->value) . '</textarea>';
    }
}

class FSelect extends FBaseInput
{
    public $attributes = '';
    public $items = array();
    
    function set_items($items)
    {
        $this->items = $items;
        return $this;
    }
    
    function render($user_attr = null)
    {
        if (!is_array($user_attr)) {
            $user_attr = array();
        }
        $html = '<select ' . FAttributes::encode(array_merge($this->attr, $user_attr)) . ">\n";
        
        // Detect key names.
        $first = @$this->items[0];
        $first_keys = @array_keys($first);
        $valuekey = @$first_keys[0] or 0;
        $namekey = @$first_keys[1] or 1;
        
        if ($this->items) {
            foreach ($this->items as $item) {
                $selected = ($item[$valuekey] == $this->value) ? ' selected' : '';
                $html .= '<option value="' . htmlspecialchars($item[$valuekey]) . '"' . $selected . '>' . htmlspecialchars($item[$namekey]) . "</option>\n";
            }
        }
        $html .= '</select>';
        return $html;
    }
}

// vim: set ts=4 sw=4 et
