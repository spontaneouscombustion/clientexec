<?php

namespace Clientexec\Utils;

class Form
{
    public static function open($url, $method, $options = [])
    {
        return '<form action="' . $url . '" method="' . $method .  '"' . self::attributes($options) . '>';
    }

    public static function close()
    {
        return '</form>';
    }

    public static function label($name, $value, $desc = '', $type = 'input')
    {
        if ($type == 'checkbox') {
            return '<label class="form-check-label" for="' . $name . '">' . $value . '</label>';
        }
        if ($desc != '') {
            return '<label data-toggle="tooltip" data-html="true" data-placement="top" class="tool-tip" data-title="' .  $desc . '" for="' . $name . '">' . $value . '</label>';
        } else {
            return '<label for="' . $name . '">' . $value . '</label>';
        }
    }

    public static function input($type, $name, $value = '', $options = [])
    {
        return '<input type="' . trim($type) . '" name="' . trim($name) . '" value="' . $value . '"' . self::attributes($options) . '>';
    }

    public static function text($name, $value = '', $options = [])
    {
        return self::input('text', $name, $value, $options);
    }

    public static function number($name, $value = '', $options = [])
    {
        return self::input('number', $name, $value, $options);
    }

    public static function radio($name, $value = '', $options = [])
    {
        return self::input('radio', $name, $value, $options);
    }

    public static function password($name, $value = '', $options = [])
    {
        return self::input('password', $name, $value, $options);
    }

    public static function email($name, $value = '', $options = [])
    {
        return self::input('email', $name, $value, $options);
    }

    public static function hidden($name, $value = '', $options = [])
    {
        return self::input('hidden', $name, $value, $options);
    }

    public static function submit($value = '', $options = [])
    {
        return self::input('submit', null, $value, $options);
    }


    public static function textarea($name, $value = '', $options = [])
    {
        return '<textarea name="' . $name . '"' . self::attributes($options) . '>' . $value . '</textarea>';
    }

    public static function select($name, $selectOptions, $selected, $options = [])
    {
        $options = array_merge(['name' => $name], $options);

        if (count($selectOptions) == 1) {
            $options = array_merge(['disabled' => 'disabled'], $options);
        }
        $opts = '';
        foreach ($selectOptions as $value) {
            $optionAttributes = [
                'value' => $value[0],
            ];
            if ($selected == trim($value[0])) {
                $optionAttributes['selected'] = 'selected';
            }
            if (isset($value[2])) {
                $optionAttributes = array_merge($optionAttributes, $value[2]);
            }
            $opts .= '<option ' . self::attributes($optionAttributes) . '>' . $value[1] . '</option>';
        }
        return '<select ' . self::attributes($options) . '>' . $opts . '</select>';
    }

    public static function vat($name, $value = '', $options = [])
    {
        return '<div class="prefix">
        <div class="prefix-text" id="vat_country"></div>
        <input type="text" name="' . $name . '" value="' . $value . '"' . self::attributes($options) . '>
        </div>
        <span id="VAT' . $name . '">
        <div id="vat_validating" style="display:none">Validating...</div>
        <div id="vat_valid" style="display:none">Valid VAT Number</div>
        <div id="vat_invalid" style="display:none">Invalid VAT Number.
            <a href="javascript:validate_vat();"><font color=blue>Retry</font></a>
        </div>
        <div id="vat_error" style="display:none">Unable to validate at the moment.&nbsp;<a href="javascript:validate_vat();"><font color=blue>Retry</font></a></div>
        </span>';
    }

    public static function date($name, $value = '', $options = [])
    {
        $additionalClasses = ['datePicker'];
        $options['class'] = array_merge($options['class'], $additionalClasses);

        return '<div class="input-group">
        <input type="' . $type . '" name="' . $name . '" value="' . $value . '"' . self::attributes($options) . '>
            <div class="input-group-append">
                <span class="input-group-text">
                    <i class="fas fa-calendar-alt"></i>
                </span>
            </div>
        </div>
        <script>
        $(".datePicker").datepicker({
            format: clientexec.dateformat,
            autoclose: true
        });
        </script>';
    }

    public static function checkbox($name, $value = '', $options = [])
    {
        return self::input('checkbox', $name, $value, $options);
    }

    private static function attributes($attributes)
    {
        $html = [];

        foreach ((array)$attributes as $key => $value) {
            $element = self::element($key, $value);

            if (!is_null($element)) {
                $html[] = $element;
            }
        }

        return count($html) > 0 ? ' ' . implode(' ', $html) : '';
    }

    private static function element($key, $value)
    {
        if (is_numeric($key)) {
            return $value;
        }

        if (is_bool($value) && $key !== 'value') {
            return $value ? $key : '';
        }

        if (is_array($value) && $key === 'class') {
            return 'class="' . implode(' ', $value) . '"';
        }

        if (!is_null($value)) {
            return $key . '="' . htmlentities($value, false) . '"';
        }
    }
}
