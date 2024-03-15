<?php

namespace Clientexec\Utils;

use Clientexec\Utils\Form as Form;

class CustomFieldHelper
{
    public function getLabel($field)
    {
        $type = '';
        if ($field['type'] == typeCHECK) {
            $type = 'checkbox';
        }
        return Form::label($field['name'], $field['name'], $field['description'], $type);
    }

    public function getMarkup($field)
    {
        $isDisabled = true;
        if ($field['ischangeable'] == 1) {
            $isDisabled = '';
        }
        $controlId = $field['id'];
        if (is_numeric($field['id'])) {
            $controlId = 'CT_' . $field['id'];
        }
        $required = ($field['isrequired'] == 1 ? 'true' : 'false');

        switch ($field['fieldtype']) {
            case 'subdomain':
                foreach ($field['subdomains'] as $subDomain) {
                    $field['dropdownoptions'][] = [$subDomain, '.' . $subDomain];
                }

                $html = [];
                $html[] = '<div class="input-group">';
                $html[] = Form::text(
                    $controlId,
                    $field['value'],
                    [
                        'id' => $controlId,
                        'class' => 'form-control subdomain-input',
                        'data-parsley-required' => $required,
                        'data-parsley-pattern' => '^([-0-9A-Za-z]+)$',
                        'data-parsley-errors-container' => "#subDomainGroup",
                    ]
                );
                $html[] = '<div class="input-group-append subdomain">';
                $html[] = Form::select(
                    'subdomaintld_' . $controlId,
                    $field['dropdownoptions'],
                    '',
                    [
                        'id' => $controlId . '-tld',
                        'class' => [
                            'form-control',
                            'searchSelect2',
                            'subdomain-select'
                        ]
                    ]
                );
                $html[] = '</div>';
                $html[] = '</div>';
                return implode("\n", $html);

                break;

            case TYPEPASSWORD:
                return Form::password(
                    $controlId,
                    '',
                    [
                        'id' => $controlId,
                        'class' => 'form-control',
                        'data-parsley-required' => $required
                    ]
                );
                break;

            case typeADDRESS:
            case typeORGANIZATION:
            case typeCITY:
            case typeZIPCODE:
            case typeFIRSTNAME:
            case typeLASTNAME:
            case typePHONENUMBER:
            case typeTEXTFIELD:
                $attributes = [
                    'id' => $controlId,
                    'class' => 'form-control',
                    'data-parsley-required' => $required,
                ];

                if ($field['isDomain'] == 1) {
                    $attributes['data-parsley-pattern'] = REGEXDOMAIN_PARSLEY;
                }

                if (isset($field['regex']) && $field['regex'] != '') {
                    $attributes['data-parsley-pattern'] = $field['regex'];
                }

                if ($isDisabled == 1) {
                    $attributes['disabled'] = '';
                }
                return Form::text(
                    $controlId,
                    $field['value'],
                    $attributes
                );
                break;

            case typeTEXTAREA:
                $attributes = [
                    'id' => $controlId,
                    'class' => 'form-control',
                    'style' => 'width: 100%; height:100px',
                    'data-parsley-required' => $required
                ];
                if ($isDisabled == 1) {
                    $attributes['disabled'] = '';
                }

                return Form::textarea(
                    $controlId,
                    $field['value'],
                    $attributes
                );
                break;

            case typeEMAIL:
                return Form::email(
                    $controlId,
                    $field['value'],
                    [
                        'id' => $controlId,
                        'class' => 'form-control',
                        'data-parsley-required' => $required
                    ]
                );
                break;

            case TYPE_ALLOW_EMAIL:
            case typeYESNO:
                if (is_string($field['dropdownoptions'])) {
                    $field['dropdownoptions'] = [];
                }
                $field['dropdownoptions'][] = [0, \CE_Lib::$lang->_('No')];
                $field['dropdownoptions'][] = [1, \CE_Lib::$lang->_('Yes')];

                return Form::select(
                    $controlId,
                    $field['dropdownoptions'],
                    $field['value'],
                    [
                        'id' => $controlId,
                        'class' => [
                            'form-control',
                            'normalSelect2'
                        ],
                        'disabled' => ($isDisabled == 1) ? true : false
                    ]
                );
                break;
            case typeLANGUAGE:
            case typePRODUCTSTATUS:
            case typeSTATE:
            case typeCOUNTRY:
            case typeDROPDOWN:
                return Form::select(
                    $controlId,
                    $field['dropdownoptions'],
                    $field['value'],
                    [
                        'id' => $controlId,
                        'class' => [
                            'form-control',
                            'searchSelect2'
                        ],
                        'disabled' => ($isDisabled == 1) ? true : false
                    ]
                );
                break;

            case typeVATNUMBER:
                return str_replace(
                    array(
                        "Validating...",
                        "Invalid VAT Number",
                        "Valid VAT Number",
                        "Unable to validate at the moment.",
                        "Retry"
                    ),
                    array(
                        \CE_Lib::$lang->_("Validating..."),
                        \CE_Lib::$lang->_("Invalid VAT Number"),
                        \CE_Lib::$lang->_("Valid VAT Number"),
                        \CE_Lib::$lang->_("Unable to validate at the moment."),
                        \CE_Lib::$lang->_("Retry")
                    ),
                    Form::vat(
                        $controlId,
                        $field['value'],
                        [
                            'id' => $controlId,
                            'class' => 'form-control',
                            'data-parsley-required' => $required
                        ]
                    )
                );
                break;

            case typeDATE:
                return Form::date(
                    $controlId,
                    $field['value'],
                    [
                        'id' => $controlId,
                        'class' => ['form-control'],
                        'data-parsley-required' => $required
                    ]
                );
                break;

            case typeCHECK:
                return Form::checkbox(
                    $controlId,
                    $field['value'],
                    [
                        'id' => $controlId,
                        'class' => 'form-check-input',
                        'data-parsley-required' => $required
                    ]
                );
                break;

            default:
                \CE_Lib::log(4, 'Unknown Field Type: ');
                \CE_Lib::log(4, $field);
                break;
        }
    }
}
