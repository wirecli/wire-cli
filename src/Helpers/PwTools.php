<?php namespace Wirecli\Helpers;

/**
 * Class PwTools
 *
 * Contains common methods that could be used in every command
 *
 * @package Wirecli
 * @author Tabea David
 */
class PwTools extends PwConnector {

  /**
   * Get available field types short version
   */
  public static function getAvailableFieldtypesShort() {
    return array(
      'text',
      'textarea',
      'email',
      'datetime',
      'checkbox',
      'file',
      'float',
      'image',
      'integer',
      'page',
      'fieldset',
      'tab'
    );
  }

  /**
   * Method to get field type name
   * Type of field: text|textarea|email|datetime|checkbox|file|float|image|integer|page|url
   *
   * @param string $suppliedType
   * @return string
   */
  public static function getProperFieldtypeName($suppliedType) {
    // if empty, use FieldtypeText as default
    $type = !$suppliedType ? 'FieldtypeText' : '';

    switch ($suppliedType) {
    case 'text':
      $type = 'FieldtypeText';
      break;
    case 'textarea':
      $type = 'FieldtypeTextarea';
      break;
    case 'email':
      $type = 'FieldtypeEmail';
      break;
    case 'datetime':
      $type = 'FieldtypeDatetime';
      break;
    case 'checkbox':
      $type = 'FieldtypeCheckbox';
      break;
    case 'file':
      $type = 'FieldtypeFile';
      break;
    case 'float':
      $type = 'FieldtypeFloat';
      break;
    case 'image':
      $type = 'FieldtypeImage';
      break;
    case 'integer':
      $type = 'FieldtypeInteger';
      break;
    case 'page':
      $type = 'FieldtypePage';
      break;
    case 'url':
      $type = 'FieldtypeUrl';
      break;
    case 'fieldset':
      $type = 'FieldtypeFieldsetOpen';
      break;
    case 'tab':
      $type = 'FieldtypeFieldsetTabOpen';
      break;
    }

    // no predefined fieldtype
    if (!$type) {
      // suppliedType without Fieldtype prefix
      $suppliedType = preg_replace('/^[fF]ieldtype/', '', $suppliedType);
      // combine Fieldtype prefix and type (SnakeCase)
      $type = 'Fieldtype' . ucfirst($suppliedType);
    }

    return $type;
  }
}
