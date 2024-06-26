<?php

/**
 * @class PaidAdvertisementLicense
 */
class PaidAdvertisementLicense extends CommerceLicenseBase  {

  /**
   * Implements EntityBundlePluginProvideFieldsInterface::fields().
   */
  static function fields() {
    $fields = parent::fields();

    $fields['pap_publications_left'] = array(
      'field' => array(
        // Такой тип у Integer поля.
        'type' => 'number_integer',
      ),
      'instance' => array(
        'label' => 'Publications left',
        'settings' => array(
          'min' => -1,
        )
      ),
    );

    $fields['pap_promotions_left'] = array(
      'field' => array(
        'type' => 'number_integer',
      ),
      'instance' => array(
        'label' => 'Publications left',
      ),
    );

    return $fields;
  }

  /**
   * Implements CommerceLicenseInterface::isConfigurable().
   */
  public function isConfigurable() {
    return FALSE;
  }

  /**
   * Implements CommerceLicenseInterface::accessDetails().
   */
  public function accessDetails() {
    $publications_left = $this->wrapper->pap_publications_left->value();
    $promotions_left = $this->wrapper->pap_promotions_left->value();
    return format_string('Доступных публикаций: @publications<br>Доступных закреплений: @promotions', array(
      '@publications' => ($publications_left == -1) ? 'Неограниченно' : $publications_left,
      '@promotions' => $promotions_left,
    ));
  }

  /**
   * Overrides Entity::save().
   */
  public function save() {
    // Получаем значения оставшихся публикаций и закреплений.
    $publications_left = $this->wrapper->pap_publications_left->value();
    $promotions_left = $this->wrapper->pap_promotions_left->value();
    if ($publications_left == 0 && $promotions_left == 0) {
      // Если оба значения упали до 0, значит лицензию нужно деактивировать.
      $this->status = COMMERCE_LICENSE_EXPIRED;
    }

    parent::save();
  }

  /**
   * Implements CommerceLicenseInterface::activate().
   */
  public function activate() {
    if ($this->status == COMMERCE_LICENSE_CREATED) {
      // Записываем в поля нашей лицензии, значения из соответствующих полей
      // товара комерца. Доступ к которому можно получить через
      // $this->wrapper->product.
      $this->wrapper->pap_publications_left = $this->wrapper->product->field_pap_number_of_publications->value();
      $this->wrapper->pap_promotions_left = $this->wrapper->product->field_pap_number_of_promotions->value();
    }

    parent::activate();
  }

  /**
   * Implements CommerceLicenseInterface::renew().
   */
  public function renew($expires) {
    $this->wrapper->pap_publications_left = $this->wrapper->product->field_pap_number_of_publications->value();
    $this->wrapper->pap_promotions_left = $this->wrapper->product->field_pap_number_of_promotions->value();
    parent::renew($expires);
  }

}
